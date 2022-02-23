# TradersOnly Migration Script

This script migrates tradersonly data from an old system to woocommerce. 
Contains a sqlite database that stores a delta id so that migration can pick up where it left off if needed.

### TODO: Package updates

This script relies on modified repositories!
- khatfield/soap-client
  - this package was modified to support laravel 8. I will provide a PR but the composer.json will need to be 
  updated once it is accepted.
- codexshaper/laravel-woocommerce
  - I added new endpoints to WP via a bulk import plugin, so these needed to be incorporated. 
  Please see the fork on my github which will include the modifications.

### Running the migration
- `php artisan subscriptions:migrate`

As of the time of upload, the sqlite db is current, however, 
if you need to rerun the import fresh, simply truncate the delta table or create a new database file.
