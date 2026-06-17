<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\adminController;

Route::get('/', function () {
    return view('auth.login');
});
// Indirect Route ..
Route::get('/test',[adminController::class,'test'])->name('test');
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
//livewire routes ..
// dashboard ..
Route::livewire('/dashboard','pages::dashboard')->name('dashboard');
// categories
Route::livewire('/categories','pages::categories.categories')->name('categories');
// brands ..
Route::livewire('/brands','pages::brand.create')->name('brand');
// products ..
Route::prefix('products')->name('products.')->group(function(){
    Route::livewire('/create','pages::products.create')->name('create');
    Route::livewire('/','pages::products.all')->name('all');
    Route::livewire('/show/{id}','pages::products.show')->name('show');
    Route::livewire('/product/{id}','pages::products.edit')->name('edit');
});
// reports ..
Route::prefix('reports')->name('reports.')->group(function(){
    Route::livewire('/sales','pages::reports.sales')->name('sales');
    Route::livewire('/stock','pages::reports.stock')->name('stock');
    Route::livewire('/supplier','pages::reports.supplier')->name('supplier');
});
// sales ..
Route::prefix('sales')->name('sales.')->group(function(){
    Route::livewire('/create','pages::sales.create')->name('create');
    Route::livewire('/history','pages::sales.history')->name('history');
    Route::livewire('/invoice','pages::sales.invoices')->name('invoice');
    Route::livewire('/sales','pages::sales.sales')->name('sales');
});
// Stock ..
Route::prefix('stock')->name('stock.')->group(function(){
    Route::livewire('/history','pages::stock.history')->name('history');
    Route::livewire('/stock-details','pages::stock.stock')->name('details');
});
// Supplies ..
Route::prefix('suppliers')->name('supplier.')->group(function(){
    Route::livewire('/all','pages::suppliers.all')->name('all');
    Route::livewire('/create','pages::suppliers.create')->name('create');
});

