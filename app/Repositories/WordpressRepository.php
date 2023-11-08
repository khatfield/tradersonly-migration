<?php

namespace App\Repositories;

use App\Models\TOSubscription;
use App\Models\WordpressProduct;
use Carbon\Carbon;
use Codexshaper\WooCommerce\Facades\Attribute;
use Codexshaper\WooCommerce\Facades\BulkCustomer;
use Codexshaper\WooCommerce\Facades\BulkOrder;
use Codexshaper\WooCommerce\Facades\BulkProductVariation;
use Codexshaper\WooCommerce\Facades\BulkSubscription;
use Codexshaper\WooCommerce\Facades\Order;
use Codexshaper\WooCommerce\Facades\Product;
use Codexshaper\WooCommerce\Facades\Term;
use Codexshaper\WooCommerce\Facades\Variation;
use Codexshaper\WooCommerce\Models\Subscription;
use Illuminate\Support\Collection;

class WordpressRepository
{
    const WP_SUB_ACTIVE               = "active";
    const WP_SUB_PENDING_CANCELLATION = "pending-cancel";
    const WP_SUB_FAILED               = "failed";
    const WP_SUB_EXPIRED              = "expired";
    const WP_SUB_CANCELLED            = "cancelled";

    public function findOrCreateCustomers($data)
    {
        $response = $this->findOrCreateCustomersFromSubscription($data);
        foreach ($response as $customer) {
            $data[$customer->email]["customer"] = $customer;
        }

        return $data;
    }

    protected function findOrCreateCustomersFromSubscription($data)
    {
        $customers = [];
        foreach ($data as $email => $d) {
            $customer = $d["customer"];
            [$firstName, $lastName] = $this->nameStringSplit($customer["sfRecord"]->Name);
            $customers[] = [
                'email'      => $email,
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'username'   => $email,
                'virtual'    => true,
                'billing'    => [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'address_1'  => $customer["sfRecord"]->BillingAddress->street ?? '',
                    'city'       => $customer["sfRecord"]->BillingAddress->city ?? '',
                    'state'      => $customer["sfRecord"]->BillingAddress->state ?? '',
                    'postcode'   => $customer["sfRecord"]->BillingAddress->postalCode ?? '',
                    'country'    => $customer["sfRecord"]->BillingAddress->country ?? '',
                    'email'      => $email,
                    'phone'      => $customer["sfRecord"]->Phone ?? '',
                ],
                'shipping'   => [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'address_1'  => $customer["sfRecord"]->PersonMailingAddress->street ?? '',
                    'city'       => $customer["sfRecord"]->PersonMailingAddress->city ?? '',
                    'state'      => $customer["sfRecord"]->PersonMailingAddress->state ?? '',
                    'postcode'   => $customer["sfRecord"]->PersonMailingAddress->postalCode ?? '',
                    'country'    => $customer["sfRecord"]->PersonMailingAddress->country ?? '',
                ],
                'meta_data'  => [
                    ['key' => 'gpid', 'value' => $customer["sfRecord"]->GP_CustID__c],
                    ['key' => 'crm_id', 'value' => $customer["sfRecord"]->Id],
                    ['key' => 'auth_code', 'value' => $customer["sfRecord"]->Id],
                    ['key' => 'wc_authorize_net_cim_customer_profile_id', 'value' => $customer["sfRecord"]->Id],
                    ['key' => 'paying_customer', 'value' => $customer["sfRecord"]->Id],
                    ['key' => '_to_legacy_id', 'value' => $customer['subscription']->user->id],
                ],
            ];
        }

        return $this->formatBulkResponse(BulkCustomer::create(["customers" => $customers]));
    }

    protected function formatBulkResponse($bulkApiResponse)
    {
        if (empty($bulkApiResponse) || !$bulkApiResponse["success"]) {
            throw new \Exception("Empty or error response from WP");
        }

        return $bulkApiResponse["data"];
    }

    public function getProduct($product_id = null)
    {
        if (is_null($product_id)) {
            $product    = WordpressProduct::first();
            $product_id = !empty($product) ? $product->product_id : null;
        }

        return !empty($product_id) ? Product::find($product_id) : null;
    }

    public function getAllVariations($product_id)
    {
        $per_page   = 100;
        $page       = 1;
        $variations = collect();
        $done       = false;
        while (!$done) {
            $result     = Variation::paginate($product_id, $per_page, $page);
            $variations = $variations->merge($result->get('data'));
            $meta       = $result->get('meta');
            if ($meta['current_page'] >= $meta['total_pages']) {
                $done = true;
            } else {
                $page = $meta['next_page'];
            }
        }

        if($variations->isNotEmpty()) {
            $variations = $variations->mapWithKeys(function($variation)
            {
                $option              = strtolower(str_replace(' ', '_', $variation->attributes[0]->option));
                $v                   = new \StdClass;
                $v->id               = $variation->id;
                $v->regular_price    = $variation->regular_price;
                $v->attribute_option = $option;

                return [$v->attribute_option . '-' . $v->regular_price => $v];
            });
        }

        return $variations;
    }

    public function getAllTerms($attribute_id)
    {
        $page     = 1;
        $per_page = 100;

        $done  = false;
        $terms = collect();
        while (!$done) {
            $result = Term::paginate($attribute_id, $per_page, $page);
            $terms  = $terms->merge($result->get('data'));
            $meta   = $result->get('meta');
            if ($meta['current_page'] >= $meta['total_pages']) {
                $done = true;
            } else {
                $page = $meta['next_page'];
            }
        }

        return $terms;
    }

    public function createOrders($data)
    {
        $response  = $this->createOrdersFromSubscriptions($data);
        $idToEmail = [];
        foreach ($data as $email => $record) {
            $idToEmail[$record["customer"]->id] = $email;
        }
        foreach ($response as $order) {
            $data[$idToEmail[$order->customer_id]]["order"] = $order;
        }

        return $data;
    }

    protected function createOrdersFromSubscriptions($data)
    {
        $orders = [];
        foreach ($data as $email => $record) {

            $order = $record["order"];

            [$firstName, $lastName] = $this->nameStringSplit($order["sfRecord"]->Name);
            if ($order['subscription']->invoice->amount == 0) {
                $paymentMethod      = "comp";
                $paymentMethodTitle = "Comp";
            } elseif ($order["subscription"]->invoice->payment->method == "authorize") {
                $paymentMethod      = "authorize_net_cim_credit_card";
                $paymentMethodTitle = "Credit Card";
            } else {
                $paymentMethod      = "bacs";
                $paymentMethodTitle = "ACH/Wire/Check";
            }

            $paid      = false;
            $paid_date = null;
            if (!empty($order["subscription"]->invoice->paid)) {
                $paid      = true;
                $paid_date = $order["subscription"]->invoice->paid;
            }

            if (!empty($order['subscription']->invoice->payment) && !empty($order["subscription"]->invoice->payment->refund)) {
                $status = "bulk-refunded";
            } elseif (!empty($order["subscription"]->canceled)) {
                $status = "cancelled";
            } else {
                $status = "completed";
            }

            $orders[] = [
                'payment_method'        => $paymentMethod,
                'payment_method_title'  => $paymentMethodTitle,
                'set_paid'              => $paid,
                'status'                => $status,
                'order_date'            => Carbon::parse($order["subscription"]->invoice->paid)->unix(),
                'order_total'           => $order["subscription"]->invoice->amount,
                'customer_id'           => $record["customer"]->id,
                //
                "_wpo_order_creator"    => $order["subscription"]->invoice->orderCreator->email ?? "",
                "_subscription_renewal" => !empty($order["subscription"]->autoRenew->success),
                '_legacy_inv_id'        => $order['subscription']->invoice->id,
                //
                'billing'               => [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'address_1'  => $order["sfRecord"]->BillingAddress->street ?? '',
                    'city'       => $order["sfRecord"]->BillingAddress->city ?? '',
                    'state'      => $order["sfRecord"]->BillingAddress->state ?? '',
                    'postcode'   => $order["sfRecord"]->BillingAddress->postalCode ?? '',
                    'country'    => $order["sfRecord"]->BillingAddress->country ?? '',
                    'email'      => $email,
                    'phone'      => $order["sfRecord"]->Phone ?? '',
                ],
                'shipping'              => [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'address_1'  => $order["sfRecord"]->PersonMailingAddress->street ?? '',
                    'city'       => $order["sfRecord"]->PersonMailingAddress->city ?? '',
                    'state'      => $order["sfRecord"]->PersonMailingAddress->state ?? '',
                    'postcode'   => $order["sfRecord"]->PersonMailingAddress->postalCode ?? '',
                    'country'    => $order["sfRecord"]->PersonMailingAddress->country ?? '',
                ],
                'line_items'            => [
                    [
                        'product_id'   => $order["product"]['id'],
                        'variation_id' => $record["variation"]->id,
                        'quantity'     => 1,
                    ],
                ],
            ];
        }

        return $this->formatBulkResponse(BulkOrder::create(["orders" => $orders]));

    }

    public function createSubscriptions($data)
    {
        $subscriptions = [];
        foreach ($data as $email => $record) {

            $subscription = $record["subscription"];

            [$firstName, $lastName] = $this->nameStringSplit($subscription["sfRecord"]->Name);

            [$paymentMethod, $paymentMethodTitle, $paymentDetails] = $this->getPaymentDetails(
                $subscription["subscription"]->invoice->payment
            );

            // make sure start date & expire date are set
            if (empty($subscription["subscription"]->start_date) &&
                !empty($subscription["subscription"]->created) &&
                !empty($subscription["subscription"]->initial_term)) {
                $newStartDate                              = Carbon::parse($subscription["subscription"]->created);
                $subscription["subscription"]->start_date  = $newStartDate->format("Y-m-d");
                $subscription["subscription"]->expire_date = $newStartDate->addMonths(
                    $subscription["subscription"]->initial_term
                )->format("Y-m-d");
            }

            $status = $this->getSubscriptionStatus($subscription["subscription"]);

            /**
             * WC does not support subscription renews in the future with a different term or price,
             * To account for this, the subscription may be active with the renewal term and pricing
             */
            $billingInterval    = $subscription["subscription"]->initial_term;
            $orderTotal         = $subscription["subscription"]->invoice->amount;
            $productVariationId = $record["variation"]->id;
            if (Carbon::parse($subscription["subscription"]->expire_date)->isFuture() &&
                !empty($subscription["subscription"]->auto_renew) &&
                !empty($subscription["subscription"]->renewal_plan)) {
                $billingInterval    = $subscription["subscription"]->renewalRatePlan->term;
                $orderTotal         = $subscription["subscription"]->renewalRatePlan->recurring;
                $productVariationId = $record["subscriptionVariation"]->id;
            }

            $subscriptions[] = [
                'parent_id'            => $record["order"]->id,
                'customer_id'          => $record["customer"]->id,
                'status'               => $status,
                'billing_period'       => 'month',
                'billing_interval'     => $billingInterval,
                'start_date'           => Carbon::parse($subscription["subscription"]->start_date)->format("Y-m-d h:i:s"),
                'next_payment_date'    => Carbon::parse($subscription["subscription"]->expire_date)->format("Y-m-d h:i:s"),
                'number'               => "TO-" . $subscription["subscription"]->id,
                'payment_method'       => $paymentMethod,
                'payment_method_title' => $paymentMethodTitle,
                'order_total'          => $orderTotal,
                'payment_details'      => $paymentDetails,

                'billing'    => [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'address_1'  => $subscription["sfRecord"]->BillingAddress->street ?? '',
                    'city'       => $subscription["sfRecord"]->BillingAddress->city ?? '',
                    'state'      => $subscription["sfRecord"]->BillingAddress->state ?? '',
                    'postcode'   => $subscription["sfRecord"]->BillingAddress->postalCode ?? '',
                    'country'    => $subscription["sfRecord"]->BillingAddress->country ?? '',
                    'email'      => $email,
                    'phone'      => $subscription["sfRecord"]->Phone ?? '',
                ],
                'shipping'   => [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'address_1'  => $subscription["sfRecord"]->PersonMailingAddress->street ?? '',
                    'city'       => $subscription["sfRecord"]->PersonMailingAddress->city ?? '',
                    'state'      => $subscription["sfRecord"]->PersonMailingAddress->state ?? '',
                    'postcode'   => $subscription["sfRecord"]->PersonMailingAddress->postalCode ?? '',
                    'country'    => $subscription["sfRecord"]->PersonMailingAddress->country ?? '',
                ],
                'line_items' => [
                    [
                        'product_id'   => $subscription["product"]['id'],
                        'variation_id' => $productVariationId,
                        'quantity'     => 1,
                    ],
                ],
                'extra_meta'  => [
                    [
                        'legacy_sub_id' => $subscription["subscription"]->id,
                    ],
                ],
            ];
        }

        return $this->formatBulkResponse(BulkSubscription::create(["subscriptions" => $subscriptions]));
    }

    protected function getPaymentDetails($payment)
    {

        if (!is_null($payment)) {
            $paymentMethod      = "bacs";
            $paymentMethodTitle = "Direct bank transfer";
            $paymentDetails     = [];
            if ($payment->method == "authorize") {
                $paymentMethod      = "authorize_net_cim_credit_card";
                $paymentMethodTitle = "Credit Card";
                $paymentDetails     = [
                    "post_meta" => [
                        '_wc_authorize_net_cim_credit_card_customer_id'   => $payment->profile->pay_profile_id ?? null,
                        '_wc_authorize_net_cim_credit_card_payment_token' => $payment->profile->auth_id ?? null,
                    ],
                ];
            }
        } else {
            $paymentMethod      = "comp";
            $paymentMethodTitle = "Comp";
            $paymentDetails     = [];
        }

        return [$paymentMethod, $paymentMethodTitle, $paymentDetails];
    }

    /**
     * @param TOSubscription $subscription
     *
     * @return string
     */
    protected function getSubscriptionStatus($subscription)
    {
        $status = self::WP_SUB_ACTIVE;

        if (!Carbon::parse($subscription->expire_date)->isFuture()) {
            $status = self::WP_SUB_EXPIRED;
        } elseif (!empty($subscription->canceled)) {
            $status = self::WP_SUB_CANCELLED;
        } elseif (empty($subscription->auto_renew)) {
            $status = self::WP_SUB_PENDING_CANCELLATION;
        }

        return $status;
    }

    protected function nameStringSplit($name)
    {
        $names = explode(" ", $name);

        return [
            array_shift($names),
            implode(" ", $names),
        ];
    }

    /******** Unused Methods? *********/
//
//    public function setProductVariations($data, $wp_variations)
//    {
//        foreach ($data as &$d) {
//            // Order Variation
//            $d["variation"] = $wp_variations->get(
//                $d["variation"]["term"] . "_month" . '-' . $d["variation"]["price"]
//            );
//
//            /**
//             * Subscription Variation
//             *
//             * WC does not support subscription renews in the future with a different term or price,
//             * To account for this, we need to make sure we have a variation for the sub. we will set.
//             */
//            $subscription = $d["subscription"]["subscription"];
//            if (Carbon::parse($subscription["expire_date"])->isFuture() &&
//                !empty($subscription["auto_renew"]) &&
//                !empty($subscription["renewal_plan"])) {
//                $d["subscriptionVariation"] = $wp_variations->get(
//                    $subscription->renewalRatePlan->term . "_month" . '-' .
//                    intval($subscription->renewalRatePlan->recurring)
//                );
//            }
//        }
//
//        return [$data, $wp_variations];
//    }
//
//    public function createSubscription($subscription, $wpCustomer, $sfRecord, $wpProduct, $wpVariation, $wpOrder)
//    {
//        [$firstName, $lastName] = $this->nameStringSplit($sfRecord->Name);
//
//        $paymentData = json_decode($subscription->invoice->payment->auth_data, true);
//
//        $paymentMethod  = "bacs";
//        $paymentDetails = [];
//        if ($subscription->invoice->payment->method == "authorize") {
//            $paymentMethod  = "authorize_net_cim_credit_card";
//            $paymentDetails = [
//                "post_meta" => [
//                    '_wc_authorize_net_cim_credit_card_customer_id'   =>
//                        $subscription->invoice->payment->profile->pay_profile_id ?? null,
//                    '_wc_authorize_net_cim_credit_card_payment_token' =>
//                        $subscription->invoice->payment->profile->auth_id ?? null,
//                ],
//            ];
//        }
//
//        $status = "active";
//        if (!empty($paymentData["approved"]) && $paymentData["approved"] != true) {
//            $status = "on-hold";
//        }
//        if (!Carbon::parse($subscription->expire_time)->isFuture()) {
//            $status = "expired";
//        }
//
//
//        return Subscription::create([
//            'parent_id'         => $wpOrder["id"],
//            'customer_id'       => $wpCustomer["id"],
//            'status'            => $status,
//            'billing_period'    => 'month',
//            'billing_interval'  => $subscription->renewal_term,
//            'start_date'        => Carbon::parse($subscription->start_date)->format("Y-m-d h:i:s"),
//            'next_payment_date' => Carbon::parse($subscription->expire_time)->format("Y-m-d h:i:s"),
//            'number'            => "TO-" . $subscription->id,
//            'payment_method'    => $paymentMethod,
//            'payment_details'   => $paymentDetails,
//            'billing'           => [
//                'first_name' => $firstName,
//                'last_name'  => $lastName,
//                'address_1'  => $sfRecord->BillingAddress->street ?? '',
//                'city'       => $sfRecord->BillingAddress->city ?? '',
//                'state'      => $sfRecord->BillingAddress->state ?? '',
//                'postcode'   => $sfRecord->BillingAddress->postalCode ?? '',
//                'country'    => $sfRecord->BillingAddress->country ?? '',
//                'email'      => $subscription->user->email,
//                'phone'      => $sfRecord->Phone ?? '',
//            ],
//            'shipping'          => [
//                'first_name' => $firstName,
//                'last_name'  => $lastName,
//                'address_1'  => $sfRecord->PersonMailingAddress->street ?? '',
//                'city'       => $sfRecord->PersonMailingAddress->city ?? '',
//                'state'      => $sfRecord->PersonMailingAddress->state ?? '',
//                'postcode'   => $sfRecord->PersonMailingAddress->postalCode ?? '',
//                'country'    => $sfRecord->PersonMailingAddress->country ?? '',
//            ],
//            'line_items'        => [
//                [
//                    'product_id'   => $wpProduct['id'],
//                    'variation_id' => $wpVariation->id,
//                    'quantity'     => 1,
//                ],
//            ],
//            'meta_data'         => [
//                [
//                    'key'   => '_migrated_from_tradersonly',
//                    'value' => '1',
//                ],
//            ],
//        ]);
//    }
//
//    public function firstOrCreateProduct($bar = null): ?Collection
//    {
//        $customer = $this->getProduct();
//        if (empty($customer)) {
//            return $this->createProduct($bar);
//        }
//
//        return $customer;
//    }
//
//    protected function createProduct($bar = null)
//    {
//        $options = [];
//        foreach (range(0, 120) as $option) {
//            $options[] = [
//                'name' => $option . " Month",
//                'slug' => $option . "_month",
//            ];
//        }
//
//        $attribute = Attribute::create([
//            'name' => 'Subscription Term',
//            'slug' => 'subscription_term',
//            'type' => 'select',
//        ]);
//
//        $attributeTerms = collect($options)->map(function($option) use ($attribute, &$bar)
//        {
//            $term = Term::create($attribute["id"], [
//                'name' => $option["name"],
//                'slug' => $option["slug"],
//            ]);
//
//            if ($bar) {
//                $bar->advance();
//            }
//
//            // give the wp api a break...
//            usleep(200000);
//
//            return $term;
//        });
//
//        $product = Product::create([
//            'name'          => 'Traders Only Data Subscription',
//            'slug'          => 'traders-only-data',
//            'type'          => 'variable',
//            'virtual'       => true,
//            'regular_price' => '0.00',
//            'attributes'    => [
//                [
//                    'id'        => $attribute["id"],
//                    'position'  => 0,
//                    'visible'   => true,
//                    'variation' => true,
//                    'options'   => $attributeTerms->pluck("name"),
//                ],
//            ],
//        ]);
//
//        if ($bar) {
//            $bar->advance();
//        }
//
//        WordpressProduct::insert([
//            "product_id"   => $product["id"],
//            "attribute_id" => $attribute["id"],
//        ]);
//
//        return $product;
//    }
//
//    public function createOrder($subscription, $sfRecord, $wpProduct, $wpVariation, $wpCustomer)
//    {
//        # Get a first & last out of name string
//        [$firstName, $lastName] = $this->nameStringSplit($sfRecord->Name);
//
//        if ($subscription->invoice->payment->method == "authorize") {
//            $paymentMethod      = "authorize_net_cim_credit_card";
//            $paymentMethodTitle = "Credit Card";
//        } else {
//            $paymentMethod      = "bacs";
//            $paymentMethodTitle = "ACH/Wire/Check";
//        }
//
//        $status = "completed";
//        $paid   = true;
//        if (!empty($paymentData["approved"]) && $paymentData["approved"] != true) {
//            $status = "failed";
//            $paid   = false;
//        }
//
//        return Order::create([
//            'payment_method'       => $paymentMethod,
//            'payment_method_title' => $paymentMethodTitle,
//            'set_paid'             => $paid,
//            'status'               => $status,
//            'customer_id'          => $wpCustomer["id"],
//            'billing'              => [
//                'first_name' => $firstName,
//                'last_name'  => $lastName,
//                'address_1'  => $sfRecord->BillingAddress->street ?? '',
//                'city'       => $sfRecord->BillingAddress->city ?? '',
//                'state'      => $sfRecord->BillingAddress->state ?? '',
//                'postcode'   => $sfRecord->BillingAddress->postalCode ?? '',
//                'country'    => $sfRecord->BillingAddress->country ?? '',
//                'email'      => $subscription->user->email,
//                'phone'      => $sfRecord->Phone ?? '',
//            ],
//            'shipping'             => [
//                'first_name' => $firstName,
//                'last_name'  => $lastName,
//                'address_1'  => $sfRecord->PersonMailingAddress->street ?? '',
//                'city'       => $sfRecord->PersonMailingAddress->city ?? '',
//                'state'      => $sfRecord->PersonMailingAddress->state ?? '',
//                'postcode'   => $sfRecord->PersonMailingAddress->postalCode ?? '',
//                'country'    => $sfRecord->PersonMailingAddress->country ?? '',
//            ],
//            'line_items'           => [
//                [
//                    'product_id'   => $wpProduct['id'],
//                    'variation_id' => $wpVariation->id,
//                    'quantity'     => 1,
//                ],
//            ],
//        ]);
//    }
//
//    protected function findOrCreateProductVariationsFromSubscriptions($data, $wpVariations)
//    {
//        $variations = [];
//        foreach ($data as $record) {
//
//            // Order Variation
//            $variation = $record["variation"];
//            $option    = $variation["term"] . "_month";
//            if (!$wpVariations->has($option . '-' . $variation["price"])) {
//                $variations[] = [
//                    "product_id"                    => $variation["product_id"],
//                    "_regular_price"                => $variation["price"],
//                    "_subscription_period"          => "month",
//                    "_subscription_period_interval" => $variation["term"],
//                    'attribute_option'              => $option,
//                ];
//            }
//
//            /**
//             * Subscription Variation
//             *
//             * WC does not support subscription renews in the future with a different term or price,
//             * To account for this, we need to make sure we have a variation for the sub. we will set.
//             */
//            $subscription = $record["subscription"]["subscription"];
//            if (Carbon::parse($subscription["expire_date"])->isFuture() &&
//                !empty($subscription["auto_renew"]) &&
//                !empty($subscription["renewal_plan"])) {
//                $price  = $subscription->renewalRatePlan->recurring;
//                $option = $subscription->renewalRatePlan->term . "_month";
//                if (!$wpVariations->has($option . '-' . $price)) {
//                    $variations[] = [
//                        "product_id"                    => $variation["product_id"],
//                        "_regular_price"                => $price,
//                        "_subscription_period"          => "month",
//                        "_subscription_period_interval" => $subscription->renewalRatePlan->term,
//                        'attribute_option'              => $option,
//                    ];
//                }
//            }
//        }
//
//        if (!empty($variations)) {
//
//            $results = self::formatBulkResponse(BulkProductVariation::create(["product-variations" => $variations]));
//            foreach ($results as $result) {
//
//                # getAlLVariations returns objects & this returns an array by default, so conversion is needed.
//                $return = new \StdClass;
//                foreach ($result as $k => $v) {
//                    $return->{$k} = $v;
//                }
//
//                $wpVariations->put(
//                    $return->attribute_option . '-' . $return->regular_price, // i.e., "12_month-120.00"
//                    $return
//                );
//            }
//        }
//
//        return $wpVariations;
//    }

}
