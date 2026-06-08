<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', ['activeTab' => 'dashboard']);
})->name('dashboard');

Route::get('/upload', function () {
    return view('welcome', ['activeTab' => 'upload']);
})->name('upload');

Route::get('/duplicates', function () {
    return view('welcome', ['activeTab' => 'duplicates']);
})->name('duplicates');

Route::get('/warehouse', function () {
    return view('welcome', ['activeTab' => 'warehouse']);
})->name('warehouse');

Route::get('/sql-assistant', function () {
    return view('welcome', ['activeTab' => 'sql-assistant']);
})->name('sql-assistant');

Route::get('/etl-monitoring', function () {
    return view('welcome', ['activeTab' => 'etl-monitoring']);
})->name('etl-monitoring');

Route::get('/analytics', function () {
    return view('welcome', ['activeTab' => 'analytics']);
})->name('analytics');

Route::prefix('studio')->name('studio.')->group(function () {
    Route::get('/connections', function () {
        return view('welcome', ['activeTab' => 'studio-connections']);
    })->name('connections');

    Route::get('/pipelines', function () {
        return view('welcome', ['activeTab' => 'studio-pipelines']);
    })->name('pipelines');

    Route::get('/runs', function () {
        return view('welcome', ['activeTab' => 'studio-runs']);
    })->name('runs');

    Route::get('/schedules', function () {
        return view('welcome', ['activeTab' => 'studio-schedules']);
    })->name('schedules');

    Route::get('/assistant', function () {
        return view('welcome', ['activeTab' => 'studio-assistant']);
    })->name('assistant');

    Route::get('/monitoring', function () {
        return view('welcome', ['activeTab' => 'studio-monitoring']);
    })->name('monitoring');
});

