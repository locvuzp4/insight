<?php

namespace App\Console\Commands;

use App\Events\HandlePercentPdfToPusher;
use Illuminate\Console\Command;
use Airtable;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        for ($i=0; $i < 10; $i++) { 
            Airtable::table('ba_con_soi')->create([
                'Post ID' => strval($i)
            ]);
            dump($i);
        }
    }
}
