<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\adminController;

Route::get('/', function () {
    return view('welcome');
});
// direct route ...
// Route::get('/dashboard',function(){
//     return view('dashboard');
// });
// Indirect Route ..
Route::get('/dashboard',[adminController::class,'dashboard'])->name('dashboard');
Route::get('/test',[adminController::class,'test'])->name('test');
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
