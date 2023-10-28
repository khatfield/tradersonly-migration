<?php

namespace App\Console\Commands;

use App\Models\MigrationDelta;
use Illuminate\Console\Command;

class ShowCurrentDelta extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delta:show';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the current migration delta';

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
    public function handle()
    {
        $delta = MigrationDelta::getDeltaId();
        $this->info('The last legacy subscription id that was migrated: ' . $delta);
        return 0;
    }
}
