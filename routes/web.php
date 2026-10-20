<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/payments.php';

Route::get('/', fn () => view('welcome'))->name('home');
