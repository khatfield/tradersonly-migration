<?php

namespace App\Console\Commands;

use App\Models\TOSubscription;
use App\Repositories\WordpressRepository;
use Codexshaper\WooCommerce\Facades\Term;
use Codexshaper\WooCommerce\Facades\Variation;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CreateLegacyVariations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'variations:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Variations based on legacy TO Subscriptions';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(WordpressRepository $wordpress_repository)
    {
        $product_id   = 727783;
        $attribute_id = 5;

        $to_subscriptions =
            TOSubscription::with(['invoice'])
                          ->where('user_id', '!=', 0)
                          ->whereNotNull('start_date')
                          ->whereNotNull('expire_date')
                          ->whereHas('invoice', function(Builder $query)
                          {
                              $query->whereNotNull('paid');
                          });

        $sub_count  = $to_subscriptions->count();
        $variations = collect();

        $this->info('Processing Legacy Subscriptions');
        $bar = $this->output->createProgressBar($sub_count);
        $bar->setFormat('very_verbose');

        $to_subscriptions->chunk(1000, function($subs) use ($bar, &$variations)
        {
            /** @var TOSubscription[]|Collection $subs */
            foreach ($subs as $sub) {
                $price = intval($sub->invoice->amount);
                $term  = $sub->initial_term . '_month';
                $key   = $term . '-' . $price;
                if (!$variations->has($key)) {
                    $variation = [
                        'term'  => $sub->initial_term,
                        'price' => $price,
                        'name'  => $sub->initial_term . ' Month',
                        'slug'  => $sub->initial_term . '_month',
                    ];
                    $variations->put($key, collect($variation));
                }
                $bar->advance();
            }
        });
        $bar->finish();
        $this->output->newLine(2);

        $terms              = $wordpress_repository->getAllTerms($attribute_id)->pluck('name', 'slug');
        $current_variations = $wordpress_repository->getAllVariations($product_id);

        $stats = [
            'term-created'      => 0,
            'variation-created' => 0,
        ];

        $this->info('Building Variation Data');
        $bar = $this->output->createProgressBar($variations->count());
        $variation_data = [];
        foreach ($variations as $key => $variation) {
            if (!$terms->has($variation->get('slug'))) {
                try {
                    Term::create($attribute_id, ['name' => $variation->get('name'), 'slug' => $variation->get('slug')]);
                    $terms->put($variation->get('slug'), $variation->get('name'));
                } catch(\Exception $e) {
                    $this->error('Error Creating Term: ' . $variation->get('slug'));
                    $this->warn($e->getMessage());
                }

                $stats['term-created']++;
            }

            if (!$current_variations->has($key)) {
                $variation_data[] = [
                    'description'   => $variation->get('term') . ' Month Data Subscription',
                    'regular_price' => $variation->get('price'),
                    'attributes'    => [
                        [
                            'id'     => $attribute_id,
                            'option' => $variation->get('slug'),
                        ],
                    ],
                    'virtual'       => true,
                    'meta_data'     => [
                        [
                            'key'   => '_subscription_period',
                            'value' => 'month',
                        ],
                        [
                            'key'   => '_subscription_period_interval',
                            'value' => $variation->get('term'),
                        ],
                    ],
                ];
                $stats['variation-created']++;
            }

            $bar->advance();
        }
        $bar->finish();
        $this->output->newLine(2);

        if(!empty($variation_data)) {
            $this->info('Creating WP Variations');
            $variation_data = collect($variation_data)->chunk(10);
            $bar = $this->output->createProgressBar($variation_data->count());
            $bar->setFormat('very_verbose');
            $bar->start();
            foreach($variation_data as $chunk) {
                $request = ['create' => $chunk->toArray()];
                Variation::batch($product_id, $request);
                $bar->advance();
            }
            $bar->finish();
            $this->output->newLine(2);
        }

        $this->table(array_keys($stats), [$stats]);

        return 0;
    }
}
