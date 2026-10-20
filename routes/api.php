<?php

use Illuminate\Support\Facades\Route;

// Versioned REST API — see routes/api_v1.php
Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(base_path('routes/api_v1.php'));
