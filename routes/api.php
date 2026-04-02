<?php

use App\Http\Controllers\HotelSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/hotels/search', HotelSearchController::class);
