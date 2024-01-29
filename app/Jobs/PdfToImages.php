<?php

namespace App\Jobs;

use App\Events\HandlePercentPdfToPusher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\PdfToImage\Pdf;

class PdfToImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pdfPath;

    /**
     * Create a new job instance.
     */
    public function __construct($pdfPath)
    {
        $this->pdfPath = $pdfPath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // HandlePercentPdfToPusher::dispatch([
        //     'type' => 2,
        //     'image_path' => config('app.url') . Storage::url('public/pages/page_0.jpg'),
        //     'page_number' => 600
        // ]);
        // return;
        try {
            $pdf = new Pdf($this->pdfPath);
            $pageCount = $pdf->getNumberOfPages();

            $imagePaths = [];

            if (!Storage::exists('public/pages')) {
                Storage::makeDirectory('public/pages');
            }

            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $imagePath = storage_path('app/public/pages/page_' . $pageNumber - 1 . '.jpg');
                $pdf->setPage($pageNumber)
                    ->saveImage($imagePath);

                $imagePaths[] = $imagePath;

                HandlePercentPdfToPusher::dispatch([
                    'type' => 1,
                    'percent' => round($pageNumber * 100 / $pageCount)
                ]);
                Log::info(round($pageNumber * 100 / $pageCount));
            }

            // Storage::delete($path);
        } catch (\Throwable $th) {
            // Storage::delete($path);
            HandlePercentPdfToPusher::dispatch([
                'type' => 3
            ]);
        }

        HandlePercentPdfToPusher::dispatch([
            'type' => 2,
            'image_path' => config('app.url') . Storage::url('public/pages/page_0.jpg'),
            'page_number' => $pageCount
        ]);
        Log::info('count: '.$pageCount);
    }
}
