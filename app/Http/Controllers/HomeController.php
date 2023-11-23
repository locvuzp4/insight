<?php

namespace App\Http\Controllers;

use App\Events\HandlePercentPdfToPusher;
use App\Http\Services\ExportExcel;
use App\Jobs\PdfToImages;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class HomeController extends Controller
{
    public function uploadFile(Request $request)
    {
        // return [
        //     'image_path' => config('app.url') . Storage::url('public/pages/page_0.jpg'),
        //     'page_number' => 5
        // ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('public');
            // $fullPath = config('app.url'). Storage::url($path);
            PdfToImages::dispatch(storage_path('app/' . $path));
        }
        return true;
    }

    public function caculatePoint(Request $request)
    {
        $pageNumber = $request->page_number;
        $coordinates = $request->coordinates;

        $header = ['Page'];
        $body = [];

        $countValue = [];
        $points = [];
        $titles = [];

        $n = 0;
        $h = 1;
        foreach ($coordinates as $item) {
            if ($item[0] == 1) {
                $header[] = $h . '. ' . $item[2];
                $h++;
                if ($n) {
                    $countValue[] = $n;
                    $n = 0;
                }
            } else {
                $points[] = [$item[3], $item[4]];
                $titles[] = $item[2];
                $n++;
            }
        }
        // dd($points);
        $countValue[] = $n;

        for ($n = 0; $n < $pageNumber; $n++) {
            $imagePath = storage_path('app/public/pages/page_' . $n . '.jpg');
            $image = Image::make($imagePath);
            $image->greyscale();

            $value = [];
            foreach ($points as $point) {
                try {
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
                } catch (\Exception $e) {
                    $value[] = 0;
                }
            }

            $maxValue = max($value);
            $result = [];
            $vitriDefault = -1;
            foreach ($value as $index => $item) {
                if ($item < $maxValue - 20 && $item != 0) {
                    $vitri = $this->layViTri($countValue, $index);
                    if ($vitriDefault == $vitri || $vitriDefault == $vitri - 1) {
                        $vitriDefault = $vitri;
                    } else {
                        for ($i = $vitriDefault + 1; $i < $vitri; $i++) {
                            $result[$i] = '';
                        }
                    }
                    if (isset($result[$vitri])) {
                        $result[$vitri] = $result[$vitri] . ', ' . $titles[$index];
                        // $value = str_replace('\n', nl2br("\n", true), $value);
                        // $result[$vitri] = str_replace('<br />', '', $value);
                    } else {
                        $result[$vitri] = $titles[$index];
                    }
                }
            }
            array_unshift($result, $n + 1);
            $body[] = $result;
        }
        // dd($body);

        return (new ExportExcel($header, $body))->export('Report.xlsx');
        // return $body;
    }

    private function layViTri($countValue, $index)
    {
        $n = 0;
        for ($i = 0; $i < count($countValue); $i++) {
            $n += $countValue[$i];
            if ($n > $index) {
                return $i;
            }
        }
    }
}
