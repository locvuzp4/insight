<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use Spatie\PdfToImage\Pdf;

class HomeController extends Controller
{
    public function uploadFile(Request $request)
    {

        return config('app.url') . Storage::url('public/pages/page_0.jpg');
        // return storage_path('app/public/whpU1t0LSc3M3dqXgpnZ1980qHU8HJ850jtUSHSp.pdf');
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('public');
            // return $path;
            // $fullPath = config('app.url'). Storage::url($path);
            // return config('app.url') . $fullPath;
            $this->pdfToImages(storage_path('app/' . $path));
            return config('app.url') . Storage::url('public/pages/page_0.jpg');
        }
    }

    public function pdfToImages($pdfPath)
    {
        $pdf = new Pdf($pdfPath);
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
        }

        return $imagePaths[0];
    }

    public function caculatePoint(Request $request)
    {
        $arr = [];
        for ($n = 0; $n < 5; $n++) {
            $imagePath = storage_path('app/public/pages/page_' . $n . '.jpg');
            $image = Image::make($imagePath);
            $image->greyscale();

            $value = [];
            foreach ($request->coordinates as $point) {
                $colors = [];
                for ($x = -10; $x < 11; $x++) {
                    for ($y = -10; $y < 11; $y++) {
                        $pointX = $point[0] + $x;
                        $pointY = $point[1] + $y;
                        $pixel = $image->pickColor($pointX, $pointY);
                        $colors[] = $pixel[0];
                    }
                }
                $value[] = array_sum($colors) / count($colors);
            }

            // dd($value);

            $maxValue = max($value);
            $result = [];
            foreach ($value as $index => $item) {
                if ($item < $maxValue - 30) {
                    $result[] = $index + 1;
                }
            }
            // $minValue = min($value);
            // $index = array_search($minValue, $value);

            $arr[] = $result;
        }
        return $arr;
    }
}
