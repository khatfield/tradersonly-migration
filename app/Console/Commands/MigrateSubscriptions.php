<?php

namespace App\Console\Commands;

use App\Models\LegacyMap;
use App\Models\MigrationDelta;
use App\Models\TOSubscription;
use App\Repositories\SalesforceRepository;
use App\Repositories\WordpressRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class MigrateSubscriptions extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:migrate {--d|delta= : Override the starting delta id}
                                                  {--t|terms : Update products and terms}
                                                  {--m|missing : Only process missing active subscriptions}
                                                  {--f|file= : Process from file of invoice numbers}';


    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Migrate subscription data from tradersonly to woocommerce/wp';

    public function __construct(WordpressRepository $wordpress)
    {
        $this->wordpress = $wordpress;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(SalesforceRepository $salesforceRepository)
    {
        /**
         * Maximum SOQL query length: 10,000 characters.
         * Accounting for 200 characters in query + 15 characters per sf id,
         * we can safely chunk in sets of 650.
         */
        $chunk_size   = 400;
        $base_product = 727783;
        $time         = now();
        $missing_only = $this->option('missing');
        $terms        = $this->option('terms');
        $delta_id     = $this->option('delta');
        $file         = $this->option('file');
        $cutoff       = Carbon::parse('2023-10-15 00:00:00');

        $invoice_numbers = [];
        if(!empty($file)) {
            $filename = storage_path('app/public/' . $file);
            if(file_exists($filename)) {
                $invoice_numbers = array_unique(explode("\n", trim(file_get_contents($filename))));
            }
        }

        if (is_null($delta_id)) {
            if ($missing_only) {
                $delta_id = 0;
            } else {
                $delta_id = MigrationDelta::getDeltaId();
            }
        }

        if ($terms) {
            $this->warn('Checking Terms and Variations');
            $this->call('variations:create');
        }

        $this->info("Loading Existing Products and Variations");
        $wp_product    = $this->wordpress->getProduct($base_product);
        $wp_variations = $this->wordpress->getAllVariations($base_product);
        $legacy_map    = LegacyMap::whereNotNull('legacy_sub')->get()->keyBy('legacy_sub');

        # get the subscriptions to migrate
        $to_subscriptions =
            TOSubscription::with([
                "user",
                "renewalRatePlan",
                "invoice",
                "autoRenew",
                "invoice.orderCreator",
                "invoice.payment",
                "invoice.payment.profile",
                "invoice.payment.refund",
            ]);

        if(!empty($invoice_numbers)) {
            $to_subscriptions->whereHas('invoice', function(Builder $query) use ($invoice_numbers)
            {
                $query->whereIn('invoice_number', $invoice_numbers);
            });
        } else {
            $to_subscriptions->where("id", ">", $delta_id)
                             ->where('user_id', '!=', 0)
                             ->whereNull('deleted')
                             ->whereHas('user')
                             ->whereNotNull('start_date')
                             ->whereNotNull('expire_date')
                             ->whereHas('invoice', function(Builder $query)
                             {
                                 $query->whereNotNull('paid');
                             })
                             ->orderBy("id", "ASC");
        }

        $missed = collect();
        if ($missing_only) {
            $to_query     = clone $to_subscriptions;
            $migrated_ids = $legacy_map->keys();
            $legacy_ids   = $to_query->select('id')
                                     ->where('expire_date', '>=', $cutoff)
                                     ->get()
                                     ->pluck('id');
            $missed       = $legacy_ids->diff($migrated_ids);
        }

        $count = $to_subscriptions->count();

        # Notify user & begin progress bar
        $this->info(sprintf(
            "Processing %s record(s), starting with subscription #%s",
            number_format($count), $delta_id == 0 ? 1 : $delta_id
        ));
        $this->newLine();
        $bar = $this->output->createProgressBar($count);
        $bar->setFormat('very_verbose');
        $bar->start();

        $stat_ids = [
            'migrated' => [],
            'skipped'  => [],
            'no_sf'    => [],
            'not_paid' => [],
        ];

        $to_subscriptions->chunk($chunk_size,
            function($chunk) use ($salesforceRepository, $wp_product, &$wp_variations, &$delta, &$bar, $legacy_map, &$stat_ids, $missing_only, $missed)
            {
                $sf_data = $salesforceRepository->getMigrationData(
                    $chunk->pluck("user.sf_id")->unique()
                );

                $chunk->each(function($subscription) use ($sf_data, $wp_product, &$wp_variations, &$delta, &$bar, &$data, $legacy_map, &$stat_ids, $missing_only, $missed)
                {
                    /** @var TOSubscription $subscription */
                    $delta = $subscription->id;
                    $email = $subscription->user->email;

                    if ($legacy_map->has($subscription->id) || ($missing_only && $missed->search($subscription->id) === false)) {
                        //we've already migrated this one ...
                        $stat_ids['skipped'][] = $subscription->id;

                        return;
                    }

                    $sf_id     = $subscription->user->sf_id ?? null;
                    $sf_record = null;
                    if (!empty($sf_id) && $sf_data->has($sf_id)) {
                        $sf_record = $sf_data->get($sf_id);
                    }

                    if (empty($sf_record)) {
                        //it's possible that the TO database has the shortened form of the account id ... try something different
                        if (!empty($sf_id)) {
                            foreach ($sf_data as $acct_id => $record) {
                                if (str_contains($acct_id, $sf_id)) {
                                    $sf_record = $record;
                                    break;
                                }
                            }
                        }

                        if (empty($sf_record)) {
                            $stat_ids['no_sf'][] = $subscription->id;
                            Log::info('No SF Data for Sub ID: ' . $subscription->id);

                            return;
                        }
                    }

                    $data[$email]["customer"] = [
                        "subscription" => $subscription,
                        "sfRecord"     => $sf_record,
                    ];

                    //get the variation
                    $key = $subscription->initial_term . '_month-' . intval($subscription->invoice->amount);
                    if ($wp_variations->has($key)) {
                        $data[$email]["variation"] = $wp_variations->get($key);
                    }
                    /**
                     * Pretty sure this is causing me some issues ...
                     * if (Carbon::parse($subscription->expire_date)->isFuture() &&
                     * $subscription->auto_renew &&
                     * !empty($subscription->renewal_plan)) {
                     * $key = $subscription->renewalRatePlan->term . '_month-' . intval($subscription->renewalRatePlan->recurring);
                     * if ($wp_variations->has($key)) {
                     * $data[$email]["subscriptionVariation"] = $wp_variations->get($key);
                     * }
                     * }
                     **/

                    $data[$email]["order"] = [
                        "subscription" => $subscription,
                        "sfRecord"     => $sf_record,
                        "product"      => $wp_product,
                    ];

                    $data[$email]["subscription"] = [
                        "subscription" => $subscription,
                        "sfRecord"     => $sf_record,
                        "product"      => $wp_product,
                    ];

                    $stat_ids['migrated'][] = $subscription->id;
                });

                if (!empty($data)) {
                    $data = $this->wordpress->findOrCreateCustomers($data);

                    $data = $this->wordpress->createOrders($data);

                    $this->wordpress->createSubscriptions($data);
                }

                if (!$missing_only) {
                    MigrationDelta::setDeltaId($delta);
                }

                $bar->advance($chunk->count());
            });

        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf(
            'Migration has completed in ~%s minutes after finishing subscription w/TO id #%s!',
            now()->diffInMinutes($time),
            MigrationDelta::getDeltaId()
        ));
        $this->output->newLine(2);

        $stats = [];
        foreach ($stat_ids as $key => $ids) {
            $stats[$key] = count($ids);
            if (!empty($ids)) {
                $filepath = storage_path('app/public/' . $key . '.csv');
                file_put_contents($filepath, implode("\n", $ids));
            }
        }

        $this->table(array_keys($stats), [$stats]);

        return 0;
    }
}

