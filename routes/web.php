<?php

use App\Http\Controllers\Facebook\PageController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\WebhooksController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('page/{pageId}/posts-detail', [PageController::class, 'postsDetail']);

Route::get('page/handle-air-table', [PageController::class, 'handleAirTable']);
Route::get('page/get-leads', [PageController::class, 'getLeads']);

Route::group(['prefix' => 'webhooks'], function () {
    Route::post('change-table', [WebhooksController::class, 'changeTable']);

    Route::group(['prefix' => 'facebook'], function () {
        Route::get('leads', [WebhooksController::class, 'leadsCallBack']);
        Route::post('leads', [WebhooksController::class, 'handleLeads']);

        Route::get('page', [WebhooksController::class, 'pageCallBack']);
        Route::post('page', [WebhooksController::class, 'handlePage']);
    });
});
