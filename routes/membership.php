<?php

use App\Http\Controllers\Api\Membership\MemberController;
use App\Http\Controllers\Api\Membership\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('membership')->middleware('membership.auth')->name('membership.')->group(function () {

    Route::get('transaction/datalist', [TransactionController::class, 'datalist'])->name('transaction.datalist');
    Route::get('transaction/latest', [TransactionController::class, 'latestTransaction'])->name('transaction.latest');
    Route::apiResource('transaction', TransactionController::class);

    Route::get('member/datalist', [MemberController::class, 'datalist'])->name('member.datalist');
    Route::get('member/show-user/{user}', [MemberController::class, 'showUser'])->name('member.showUser');
    Route::post('member/unbind/{serial_number}', [MemberController::class, 'unbind'])->name('member.unbind');
    Route::apiResource('member', MemberController::class);
});
