<?php

namespace App\Http\Controllers;

use App\Models\MetaDatas;
use Illuminate\Http\Request;

class MetaDataController extends Controller
{
    public function getData()
    {
        return MetaDatas::get()->pluck('meta_data');
    }
}
