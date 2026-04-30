<?php

use App\Livewire\Departments\Index;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/departments', Index::class)->name('departments.index');
});
