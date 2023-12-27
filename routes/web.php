<?php

use App\Http\Controllers\Facebook\PageController;
use App\Http\Controllers\TestController;
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
