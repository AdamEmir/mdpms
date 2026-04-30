<?php

use App\Livewire\Employees\Index;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/employees', Index::class)->name('employees.index');
});
