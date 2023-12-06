<?php

namespace App\Console\Commands;

use App\Jobs\GetPostDetail;
use Illuminate\Console\Command;

class AnalysisPagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pages:analysis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'analysis for page';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pagesData = [
            [
                'token' => 'EAAFfK6VooAcBOZBcblNfR98TSzEcPrGKvFsyrVwE5SwLLi6H6Qhy4tT1dblGLPQadaNQOrAafSg0r5Eo4SGTomHF663F3uQGZBhUkSZBZAZCnpanlx1KXf7xQHm4zhEwWZCO6kY815K1aSZC8bOktSwfZBbKtB4RSsF10ZCWtWXD44M6rX7idAS2ZAcMHHxV0vDccZD',
                'spread_sheet_id' => '13PVDQEY1jIAQQuzxzx1VsYH4IpV_xeo7s0C-8YOAWC4'
            ]
        ];

        foreach ($pagesData as $page) {
            GetPostDetail::dispatch($page['token'], $page['spread_sheet_id']);
        }
    }
}
