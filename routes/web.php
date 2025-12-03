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

Route::get('/public-file/{filename}', function ($filename) {
    $path = storage_path("app/public/$filename");

    if (!file_exists($path)) {
        return response()->json(['error' => 'File not found', 'path' => $path], 404);
    }

    return response()->file($path);
})->where('filename', '.*');

Route::get('/public-file/{folder?}/{filename}', function ($folder = null, $filename) {
    // Tentukan path dasar
    $base = storage_path('app/public');

    // Jika folder ada → storage/app/public/folder/filename
    // Jika tidak ada → storage/app/public/filename
    $path = $folder
        ? "$base/$folder/$filename"
        : "$base/$filename";

    if (!file_exists($path)) {
        return response()->json([
            'error' => "File not found",
            'path' => $path
        ], 404);
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