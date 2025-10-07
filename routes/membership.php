<?php

use App\Http\Controllers\Api\Membership\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('membership')->name('membership.')->group(function () {

    Route::get('transaction/datalist', [TransactionController::class, 'datalist'])->name('transaction.datalist');
    Route::apiResource('transaction', TransactionController::class);

    Route::get('member/datalist', [MemberController::class, 'datalist'])->name('member.datalist');
    Route::apiResource('member', MemberController::class);
});
