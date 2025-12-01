<?php

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

Route::get('/public-file/{folder}/{filename}', function ($folder, $filename) {
    $path = storage_path("app/public/$folder/$filename");

    if (!file_exists($path)) {
        return response()->json(['error' => "File not found: $path"], 404);
    }

    return response()->file($path, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type',
    ]);
})->where('filename', '.*');

Route::get('/public-file/visualisasi_tte/{filename}', function ($filename) {
    $path = storage_path("app/public/visualisasi_tte/$filename");

    if (!file_exists($path)) {
        return response()->json(['error' => "File not found: $path"], 404);
    }

    return response()->file($path, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type',
    ]);
})->where('filename', '.*');

Route::get('/public-file/profile/{filename}', function ($filename) {
    $path = storage_path("app/public/profile/$filename");

    if (!file_exists($path)) {
        return response()->json(['error' => "File not found: $path"], 404);
    }

    return response()->file($path, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type',
    ]);
})->where('filename', '.*');