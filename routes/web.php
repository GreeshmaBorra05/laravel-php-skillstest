<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/', [ProductController::class, 'index'])->name('products.index');

Route::get('/products', [ProductController::class, 'list'])->name('products.list');
Route::post('/products', [ProductController::class, 'store'])->name('products.store');
Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');


Route::get('/export/xml', [ProductController::class, 'exportXml'])->name('products.exportXml');
