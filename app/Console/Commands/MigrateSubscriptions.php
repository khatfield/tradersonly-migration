<?php

namespace App\Console\Commands;

use App\Models\LegacyMap;
use App\Models\MigrationDelta;
use App\Models\TOSubscription;
use App\Repositories\SalesforceRepository;
use App\Repositories\WordpressRepository;
use Illuminate\Console\Command;

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
        $chunk_size   = 500;
        $base_product = 727783;
        $time         = now();
        $delta_id     = $this->option('delta');
        if (empty($delta_id)) {
            $delta_id = MigrationDelta::getDeltaId();
        }

        $wp_product = $this->wordpress->getProduct($base_product);
        $this->info("Load any existing subscription product variations...");
        $wp_variations = $this->wordpress->getAllVariations($wp_product["id"]);

        $legacy_map = LegacyMap::whereNotNull('legacy_sub')->get()->keyBy('legacy_sub');

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
                          ->whereNull("deleted")
                          ->where(function($query)
                          {
                              // OK if just start date exists, not OK if both blank.
                              return $query->whereNotNull("start_date")->whereNotNull("expire_date");
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

        $to_subscriptions->chunk($chunk_size,
            function($chunk) use ($salesforceRepository, $wp_product, &$wp_variations, &$delta, &$bar, $legacy_map)
            {
                $sf_data = $salesforceRepository->getMigrationData(
                    $chunk->pluck("user.sf_id")->unique()
                );

                $chunk->each(function($subscription) use ($sf_data, $wp_product, &$wp_variations, &$delta, &$bar, &$data, $legacy_map)
                {
                    /** @var TOSubscription $subscription */

                    if ($legacy_map->has($subscription->id)) {
                        //we've already migrated this one ...
                        $bar->advance();
                        return;
                    }

                    if (empty($subscription->user) ||
                        empty($subscription->user->sf_id) ||
                        empty($sf_data[$subscription->user->sf_id])) {
                        $bar->advance();
                        return;
                    }

                    //effectively set to last id ran in chunk
                    $delta = $subscription->id;

                    $sfRecord = $sf_data[$subscription->user->sf_id];

                    $data[$subscription->user->email]["customer"] = [
                        "subscription" => $subscription,
                        "sfRecord"     => $sfRecord,
                    ];

                    $data[$subscription->user->email]["variation"] = [
                        "term"       => $subscription->initial_term,
                        "product_id" => $wp_product["id"],
                        "price"      => $subscription->invoice->amount,
                    ];

                    // Generated later, just here so this is less confusing.
                    $data[$subscription->user->email]["subscriptionVariation"] = [];

                    $data[$subscription->user->email]["order"] = [
                        "subscription" => $subscription,
                        "sfRecord"     => $sfRecord,
                        "product"      => $wp_product,
                        // $wpVariation,
                        // $wpCustomer
                    ];

                    $data[$subscription->user->email]["subscription"] = [
                        "subscription" => $subscription,
                        // $wpCustomer,
                        "sfRecord"     => $sfRecord,
                        "product"      => $wp_product,
                        // $wpVariation,
                        // $wpOrder
                    ];
                    $bar->advance();
                });

                $data = $this->wordpress->findOrCreateCustomers($data);

                [$data, $wp_variations] = $this->wordpress->findOrCreateProductVariations($data, $wp_variations);

                $data = $this->wordpress->createOrders($data);

                $this->wordpress->createSubscriptions($data);

                MigrationDelta::setDeltaId($delta);

                $bar->advance(count(array_keys($data)));
            });

        $bar->finish();
        $this->newLine();

        $this->info(sprintf(
            'Migration has completed in ~%s minutes after finishing subscription w/TO id #%s!',
            now()->diffInMinutes($time),
            MigrationDelta::getDeltaId()
        ));

        return 0;
    }
}

