<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\OrderController;

Route::get('/', function () {
    return view('index');
});

Route::get('/license/index', [LicenseController::class, 'index'])->name('license.index');
Route::post('/license/generate', [LicenseController::class, 'generate']);
Route::post('/license/verify', [LicenseController::class, 'verify']);
Route::get('/license/list', [LicenseController::class, 'list']);

Route::get('/company/index', [CompanyController::class, 'index'])->name('company.index');
Route::post('/company/store', [CompanyController::class, 'store']);
Route::get('/company/list', [CompanyController::class, 'list']);

Route::get('/order/index', [OrderController::class, 'index'])->name('order.index');
