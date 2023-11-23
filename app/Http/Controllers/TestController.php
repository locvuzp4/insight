<?php

namespace App\Http\Controllers;

use Intervention\Image\ImageManagerStatic as Image;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestController extends Controller
{
    public function test()
    {
        return Storage::download('public/pages/page_0.jpg');
        $imagePath = storage_path('app/public/pages/page_0.jpg');

        // Tải ảnh sử dụng Intervention Image
        $image = Image::make($imagePath);

        // Lấy thông tin về kích thước ảnh
        $width = $image->width();
        $height = $image->height();
        // dd($width, $height);

        // Duyệt qua từng pixel để xác định kích thước thực của ảnh
        $left = 0;
        $top = 0;
        $right = $width;
        $bottom = $height;
        $ratio = $height / $width;

        $arr = [];
        // Xác định vị trí bắt đầu cắt từ trái sang phải
        for ($x = 100; $x < 200; $x++) {
            $data = $image->pickColor($x, round($x * $ratio), 'hex');
            if ($data != '#ffffff') {
                return [$x, round($x * $ratio)];
            }
        }
        return $arr;
        dd($left);

        // Xác định vị trí bắt đầu cắt từ trên xuống
        for ($y = 0; $y < $height; $y++) {
            if ($image->pickColor(0, $y, 'hex') != '#000000') {
                $top = $y;
                break;
            }
        }

        // Xác định vị trí kết thúc cắt từ phải sang trái
        for ($x = $width - 1; $x >= 0; $x--) {
            if ($image->pickColor($x, $height - 1, 'hex') != '#000000') {
                $right = $x;
                break;
            }
        }

        // Xác định vị trí kết thúc cắt từ dưới lên
        for ($y = $height - 1; $y >= 0; $y--) {
            if ($image->pickColor($width - 1, $y, 'hex') != '#000000') {
                $bottom = $y;
                break;
            }
        }
        dd($top, $bottom, $left, $right);
        // Cắt ảnh sử dụng các giá trị vị trí xác định được
        $image->crop($right - $left, $bottom - $top, $left, $top);

        // Lưu ảnh đã cắt
        // $image->store('public/test/new_image.jpg');
        Storage::put('public/test/file.jpg', $image);

        return 1;
    }
}
