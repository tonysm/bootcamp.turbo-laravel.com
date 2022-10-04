<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

Route::get('/{page?}', function (string $page = 'introduction') {
    abort_unless(View::exists($page), 404);

    return view('docs', [
        'page' => $page,
    ]);
});
