<?php

use App\Http\Controllers\Api\Membership\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('membership')->name('membership.')->group(function () {

    Route::get('transaction/datalist', [TransactionController::class, 'datalist'])->name('transaction.datalist');
    Route::apiResource('transaction', TransactionController::class);
});
