<?php

    namespace App\Console\Commands;

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
        protected $signature = 'subscriptions:migrate';

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
            MigrationDelta::truncate();
            $deltaId = MigrationDelta::getDeltaId();

            $time = now();

            /**
             * Maximum SOQL query length: 10,000 characters.
             * Accounting for 200 characters in query + 15 characters per sf id,
             * we can safely chunk in sets of 650.
             */
            $chunk = 500;

            $this->info("Configuring subscription product...");
            $bar = $this->output->createProgressBar(122);
            $bar->start();
            $wpProduct = $this->wordpress->firstOrCreateProduct($bar);
            $bar->finish();
            $this->newLine();

            $this->info("Load any existing subscription product variations...");
            $wpVariations = $this->wordpress->getAllVariations($wpProduct["id"]);

            # get the subscriptions to migrate
            $toSubscriptions = TOSubscription::with([
                "user", "renewalRatePlan", "invoice", "autoRenew",
                "invoice.orderCreator", "invoice.payment",
                "invoice.payment.profile", "invoice.payment.refund",
            ])->has("invoice.payment.profile")
                ->where("id", ">", $deltaId)
                ->whereNull("deleted")
                ->where(function ($query) {
                    // OK if just start date exists, not OK if both blank.
                    return $query->whereNotNull("start_date")->whereNotNull("expire_date");
                })
                ->orderBy("id", "ASC");

            $count = $toSubscriptions->count();

            # Notify user & begin progress bar
            $this->info(sprintf(
                "Processing %s record(s), starting with subscription #%s",
                number_format($count), $deltaId == 0 ? 1 : $deltaId
            ));
            $this->newLine();
            $bar = $this->output->createProgressBar($count);
            $bar->start();

            $toSubscriptions->chunk($chunk,
                function ($chunk) use ($salesforceRepository, $wpProduct, &$wpVariations, &$delta, &$bar) {

                    $sfData = $salesforceRepository->getMigrationData(
                        $chunk->pluck("user.sf_id")->unique()
                    );

                    $chunk->each(function ($subscription) use ($sfData, $wpProduct, &$wpVariations, &$delta, &$bar, &$data) {

                        if (empty($subscription->user) ||
                            empty($subscription->user->sf_id) ||
                            empty($sfData[$subscription->user->sf_id])) {
                            return; //@TODO: log this?
                        }

                        //effectively set to last id ran in chunk
                        $delta = $subscription->id;

                        $sfRecord = $sfData[$subscription->user->sf_id];

                        $data[$subscription->user->email]["customer"] = [
                            "subscription" => $subscription,
                            "sfRecord" => $sfRecord
                        ];

                        $data[$subscription->user->email]["variation"] = [
                            "term" => $subscription->initial_term,
                            "product_id" => $wpProduct["id"],
                            "price" => $subscription->invoice->amount
                        ];

                        // Generated later, just here so this is less confusing.
                        $data[$subscription->user->email]["subscriptionVariation"] = [];

                        $data[$subscription->user->email]["order"] = [
                            "subscription" => $subscription,
                            "sfRecord" => $sfRecord,
                            "product" => $wpProduct,
                            // $wpVariation,
                            // $wpCustomer
                        ];

                        $data[$subscription->user->email]["subscription"] = [
                            "subscription" => $subscription,
                            // $wpCustomer,
                            "sfRecord" => $sfRecord,
                            "product" => $wpProduct,
                            // $wpVariation,
                            // $wpOrder
                        ];

                    });

                    $data = $this->wordpress->findOrCreateCustomers($data);

                    [$data, $wpVariations] = $this->wordpress->findOrCreateProductVariations($data, $wpVariations);

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

