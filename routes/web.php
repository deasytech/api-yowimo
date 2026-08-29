<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', function () {
    return response()->file(resource_path('docs/api-reference.html'), [
        'Content-Type' => 'text/html; charset=UTF-8',
    ]);
});
