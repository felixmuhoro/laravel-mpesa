<?php

use FelixMuhoro\Mpesa\Http\Controllers\CallbackController;
use Illuminate\Support\Facades\Route;

$prefix = config('mpesa.callback.route_prefix', 'mpesa');

Route::prefix($prefix)->middleware(['mpesa.callback'])->group(function () {
    Route::post('callback/stk',      [CallbackController::class, 'stk'])->name('mpesa.callback.stk');
    Route::post('callback/c2b/validate', [CallbackController::class, 'c2bValidate'])->name('mpesa.callback.c2b.validate');
    Route::post('callback/c2b/confirm',  [CallbackController::class, 'c2bConfirm'])->name('mpesa.callback.c2b.confirm');
    Route::post('callback/b2c/result',   [CallbackController::class, 'b2cResult'])->name('mpesa.callback.b2c.result');
    Route::post('callback/b2c/timeout',  [CallbackController::class, 'b2cTimeout'])->name('mpesa.callback.b2c.timeout');
});
