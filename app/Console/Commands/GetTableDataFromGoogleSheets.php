<?php

namespace App\Console\Commands;

use App\Http\Services\GoogleSheetsService;
use App\Models\MetaDatas;
use Illuminate\Console\Command;

class GetTableDataFromGoogleSheets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'table-data:get';

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
        $sheetService = new GoogleSheetsService();
        $sheets = $sheetService->setupSheetsConnection();

        $spreadsheetId = '1VemYScOC-69rvR2sAAyH0f_Ojj0Rh4vVnCLgmnpSHag';
        $existingSheetName = '1.2024';

        // Thực hiện truy vấn để đọc dữ liệu từ ô A1
        $response = $sheets->spreadsheets_values->get($spreadsheetId, $existingSheetName.'!A:J');
        $values = $response->getValues();

        MetaDatas::truncate();
        if (empty($values)) {
            $this->info('No data found');
        } else {
            foreach ($values as $row) {
                if (empty($row[0])) {
                    break;
                }
                MetaDatas::create(['meta_data' => $row]);
            }
        }
    }
}
