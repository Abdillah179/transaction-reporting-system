<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest.custom')->group(function () {

    Route::get('/', [LoginController::class, 'login'])->name('login');
    Route::post('/postlogin', [LoginController::class, 'PostLogin'])->middleware('throttle:10,3');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/Dashboard', [DashboardController::class, 'Dashboard']);
    Route::get('/Category', [CategoryController::class, 'Category']);
    Route::get('/Category/create', [CategoryController::class, 'CategoryCreate']);
    Route::post('/postcretecategory', [CategoryController::class, 'PostCategoryCreate']);
    Route::get('/Category/edit/{data:uuid}', [CategoryController::class, 'CategoryEdit']);
    Route::patch('/postupdatecategory/{data:uuid}', [CategoryController::class, 'PostUpdateCategory']);
    Route::delete('/Category/delete/{data:uuid}', [CategoryController::class, 'CategoryDelete']);

    Route::get('/Status', [StatusController::class, 'StatusIndex']);
    Route::get('/Status/create', [StatusController::class, 'StatusCreate']);
    Route::post('/postcreatestatus', [StatusController::class, 'PostCreateStatus']);
    Route::get('/Status/edit/{data:uuid}', [StatusController::class, 'StatusEdit']);
    Route::patch('/postupdatestatus/{data:uuid}', [StatusController::class, 'PostUpdateStatus']);
    Route::delete('/Status/delete/{data:uuid}', [StatusController::class, 'StatusDelete']);

    Route::get('/Import', [ImportController::class, 'ImportIndex']);
    Route::get('/Import/create', [ImportController::class, 'ImportCreate']);
    Route::post('/postimportchunk', [ImportController::class, 'PostImportChunk'])->middleware('throttle:import-chunk');
    Route::post('/postimportcomplete', [ImportController::class, 'PostImportComplete'])->middleware('throttle:import-complete');
    Route::get('/Import/detail/{data}', [ImportController::class, 'ImportDetail']);
    Route::get('/Import/progress/{data}', [ImportController::class, 'ImportProgress']);

    Route::get('/Transaction', [TransactionController::class, 'TransactionIndex']);
    Route::get('/Transaction/export/csv', [TransactionController::class, 'TransactionExportCsv'])->middleware('throttle:transaction-export');
    Route::get('/Transaction/export/excel', [TransactionController::class, 'TransactionExportExcel'])->middleware('throttle:transaction-export');

    Route::post('/logout', [LoginController::class, 'Logout']);
});
