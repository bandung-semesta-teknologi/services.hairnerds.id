<?php

use App\Http\Controllers\Api\Membership\MemberController;
use App\Http\Controllers\Api\Membership\PrizeController;
use App\Http\Controllers\Api\Membership\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('membership')->middleware('membership.auth')->name('membership.')->group(function () {

    Route::get('transaction/datalist', [TransactionController::class, 'datalist'])->name('transaction.datalist');
    Route::get('transaction/datalist/member/{member_id}', [TransactionController::class, 'datalistMember'])->name('transaction.datalistMember');
    Route::get('transaction/latest', [TransactionController::class, 'latestTransaction'])->name('transaction.latest');
    Route::get('transaction/count', [TransactionController::class, 'countTransaction'])->name('transaction.count');
    Route::apiResource('transaction', TransactionController::class);

    Route::get('member/datalist', [MemberController::class, 'datalist'])->name('member.datalist');
    Route::get('member/show-user/{user}', [MemberController::class, 'showUser'])->name('member.showUser');
    Route::post('member/unbind/{serial_number}', [MemberController::class, 'unbind'])->name('member.unbind');
    Route::apiResource('member', MemberController::class);

    Route::get('prizes', [PrizeController::class, 'index'])->name('prizes.index');
    Route::post('prizes', [PrizeController::class, 'store'])->name('prizes.store');
    Route::get('prizes/{prize:slug}', [PrizeController::class, 'show'])->name('prizes.show');
    Route::post('prizes/{prize:slug}', [PrizeController::class, 'update'])->name('prizes.update');
    Route::delete('prizes/{prize:slug}', [PrizeController::class, 'destroy'])->name('prizes.destroy');
});
