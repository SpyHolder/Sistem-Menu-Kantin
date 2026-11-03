<?php

use App\Http\Controllers\MenuController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/menu');

Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
Route::post('/menu', [MenuController::class, 'store'])->name('menu.store');
Route::get('/menu/{id}/edit', [MenuController::class, 'edit'])->whereNumber('id')->name('menu.edit');
Route::put('/menu/{id}', [MenuController::class, 'update'])->whereNumber('id')->name('menu.update');
Route::delete('/menu/{id}', [MenuController::class, 'destroy'])->whereNumber('id')->name('menu.destroy');
