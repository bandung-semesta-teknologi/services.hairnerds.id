<?php

use App\Http\Controllers\Api\Booking\StoreController;
use App\Http\Controllers\Api\Booking\BarberController;
use App\Http\Controllers\Api\Booking\ServiceController;
use App\Http\Controllers\Api\Booking\ServiceCategoryController;
use App\Http\Controllers\Api\Booking\CatalogCategoryController;
use App\Http\Controllers\Api\Booking\ServiceBarberController;
use Illuminate\Support\Facades\Route;

Route::prefix('booking')->middleware('auth:sanctum')->name('booking.')->group(function () {

        Route::apiResource('stores', StoreController::class);
        
        Route::apiResource('barbers', BarberController::class);

        Route::apiResource('services', ServiceController::class);

        Route::apiResource('service-categories', ServiceCategoryController::class);

        Route::apiResource('catalog-categories', CatalogCategoryController::class);

        Route::apiResource('service-barbers', ServiceBarberController::class)->only(['index', 'store', 'destroy']);
    });
