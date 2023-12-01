<?php

namespace App\Http\Controllers;

use App\Http\Services\ExportExcel;
use App\Jobs\PdfToImages;
use Illuminate\Http\Request;
use Intervention\Image\ImageManagerStatic as Image;

class HomeController extends Controller
{
    public function uploadFile(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('public/upload');
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

        $countValue = []; // số đáp án mỗi câu
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
        $countValue[] = $n;

        $datas = [];
        $baseX = 0;
        $baseY = 0;

        $changeArr = [];
        for ($n = 0; $n < $pageNumber; $n++) {
            $changeX = 0;
            $changeY = 0;
            $imagePath = storage_path('app/public/pages/page_' . $n . '.jpg');
            $image = Image::make($imagePath);
            $image->greyscale();

            if ($request->type) {
                if ($n == 0) {
                    $width = $image->width();
                    $height = $image->height();
                    $halfW = round($width / 2);
                    $halfH = round($height / 2);
                }

                $arr = [];
                for ($i = 0; $i < $halfH; $i++) {
                    $data = $image->pickColor($halfW, $i);
                    // $arr[] = [$data[0], $dataNext[0], $dataNext2[0]];
                    if ($data[0] < 200) {
                        $dataNext = $image->pickColor($halfW + 1, $i);
                        if ($dataNext[0] < 200) {
                            $dataPre = $image->pickColor($halfW - 1, $i);
                            if ($dataPre[0] < 200) {
                                if ($n == 0) {
                                    $baseY = $i;
                                } else {
                                    $changeY = $i - $baseY;
                                }
                                break;
                            }
                        }
                    }
                }

                for ($i = 0; $i < $halfW; $i++) {
                    $data = $image->pickColor($i, $halfH);
                    if ($data[0] < 200) {
                        $dataNext = $image->pickColor($i, $halfH + 1);
                        if ($dataNext[0] < 200) {
                            $dataPre = $image->pickColor($i, $halfH - 1);
                            if ($dataPre[0] < 200) {
                                if ($n == 0) {
                                    $baseX = $i;
                                } else {
                                    $changeX = $i - $baseX;
                                }
                                break;
                            }
                        }
                    }
                }
                $datas[] = [$changeX, $changeY];
            }

            $changeArr[] = [$changeX, $changeY];
            $value = [];
            foreach ($points as $point) {
                try {
                    $colors = [];
                    for ($x = -10; $x < 11; $x++) {
                        for ($y = -10; $y < 11; $y++) {
                            $pointX = $point[0] + $changeX + $x;
                            $pointY = $point[1] + $changeY + $y;
                            $pixel = $image->pickColor($pointX, $pointY);
                            $colors[] = $pixel[0];
                        }
                    }
                    $value[] = array_sum($colors) / count($colors);
                } catch (\Exception $e) {
                    $value[] = 0;
                }
            }

            // if ($n == 48) {
            //     return $value;
            // }

            $maxValue = max($value);
            $result = [];
            $vitriDefault = -1;
            foreach ($value as $index => $item) {
                if ($item < $maxValue - 30 && $item != 0) {
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
                    } else {
                        $result[$vitri] = $titles[$index];
                    }
                }
            }
            array_unshift($result, $n + 1);
            $body[] = $result;
        }
        // return $changeArr;

        return (new ExportExcel($header, $body))->export('Report.xlsx');
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
