<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\adminController;

Route::get('/', function () {
    return view('welcome');
});
// Indirect Route ..
Route::get('/test',[adminController::class,'test'])->name('test');
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
//livewire routes ..
Route::livewire('/dashboard','pages::dashboard');
