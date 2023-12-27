<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhooksController extends Controller
{
    public function pageCallBack(Request $request)
    {
        if ($request->get('hub_verify_token', '') === 'cookplay') {
            Log::info('webhook call back');
            return $request->get('hub_challenge', '');
        }
        return false;
    }

    public function page(Request $request)
    {
        Log::info('test webhook', $request->all());
    }
}
