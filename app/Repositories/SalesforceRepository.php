<?php

    namespace App\Repositories;

    use Illuminate\Support\Collection;
    use Khatfield\LaravelSalesforce\Facades\Salesforce;

    class SalesforceRepository
    {

        /**
         * @param string $query
         *
         * @return \Illuminate\Support\Collection
         */
        public function query(string $query)
        {
            $results = Salesforce::query($query);
            $done = false;
            $return = collect();

            while (!$done) {
                $records = $results->getQueryResult()->getRecords();
                $return = $return->merge($records);
                if (!$results->getQueryResult()->isDone()) {
                    $results = Salesforce::queryMore($results->getQueryResult()->getQueryLocator());
                } else {
                    $done = true;
                }
            };

            return $return;
        }

        public function getMigrationData(Collection $ids)
        {
            $ids = $ids->map(function($id){
                return "'".$id."'";
            })->implode(", ");

            return $this->query("
                SELECT
                       Id, GP_CustID__c, BillingAddress,
                       PersonMailingAddress, Phone, Name
                FROM Account
                WHERE Id IN ({$ids})")->keyBy("Id");
        }
    }
