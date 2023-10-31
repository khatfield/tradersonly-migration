<?php

namespace App\Console\Commands;

use App\Models\LegacyMap;
use App\Models\MigrationDelta;
use App\Models\TOSubscription;
use App\Repositories\SalesforceRepository;
use App\Repositories\WordpressRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class MigrateSubscriptions extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:migrate {--d|delta= : Override the starting delta id}';

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
        $delta_id     = $this->option('delta');
        if (is_null($delta_id)) {
            $delta_id = MigrationDelta::getDeltaId();
        }

        $this->warn('Checking Terms and Variations');
        $this->call('variations:create');

        $this->info("Load any existing subscription product variations...");
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
            ])->where("id", ">", $delta_id)
                          ->where('user_id', '!=', 0)
                          ->whereHas('user')
                          ->whereNotNull('start_date')
                          ->whereNotNull('expire_date')
                          ->whereHas('invoice', function(Builder $query)
                          {
                              $query->whereNotNull('paid');
                          })
                          ->orderBy("id", "ASC");

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
            function($chunk) use ($salesforceRepository, $wp_product, &$wp_variations, &$delta, &$bar, $legacy_map, &$stat_ids)
            {
                $sf_data = $salesforceRepository->getMigrationData(
                    $chunk->pluck("user.sf_id")->unique()
                );

                $chunk->each(function($subscription) use ($sf_data, $wp_product, &$wp_variations, &$delta, &$bar, &$data, $legacy_map, &$stat_ids)
                {
                    /** @var TOSubscription $subscription */
                    $email = $subscription->user->email;

                    if ($legacy_map->has($subscription->id)) {
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
                        $stat_ids['no_sf'][] = $subscription->id;
                        Log::info('No SF Data for Sub ID: ' . $subscription->id);

                        return;
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

                    if (Carbon::parse($subscription->expire_date)->isFuture() &&
                        $subscription->auto_renew &&
                        !empty($subscription->renewal_plan)) {
                        $key = $subscription->renewalRatePlan->term . '_month-' . intval($subscription->renewalRatePlan->recurring);
                        if($wp_variations->has($key)) {
                            $data[$email]["subscriptionVariation"] = $wp_variations->get($key);
                        }
                    }

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

                    //effectively set to last id ran in chunk
                    $delta                  = $subscription->id;
                    $stat_ids['migrated'][] = $subscription->id;
                });

                $data = $this->wordpress->findOrCreateCustomers($data);

                $data = $this->wordpress->createOrders($data);

                $this->wordpress->createSubscriptions($data);

                MigrationDelta::setDeltaId($delta);

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

