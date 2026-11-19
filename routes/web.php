<?php

use App\Http\Controllers\ResponseController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('/verification-pending', 'auth.pending')
        ->name('verification.pending');
});

Route::middleware(['auth', 'active.student'])->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('surveys.index'))
        ->name('dashboard');

    Route::get('/surveys', [SurveyController::class, 'index'])
        ->name('surveys.index');
    Route::get('/surveys/{survey}/edit', [SurveyController::class, 'edit'])
        ->name('surveys.edit');
    Route::get('/s/{survey}', [SurveyController::class, 'show'])
        ->name('surveys.show');
    Route::post('/surveys/{survey}/responses', [ResponseController::class, 'store'])
        ->name('responses.store');
    Route::get('/inbox/my-surveys', [SurveyController::class, 'mySurveys'])
        ->name('inbox.my-surveys');
    Route::patch('/surveys/{survey}', [SurveyController::class, 'update'])
        ->name('surveys.update');
    Route::patch('/surveys/{survey}/toggle-close', [SurveyController::class, 'toggleClose'])
        ->name('surveys.toggle-close');
    Route::get('/surveys/{survey}/results', [ResponseController::class, 'results'])
        ->name('surveys.results');
    Route::get('/surveys/{survey}/export-csv', [ResponseController::class, 'exportCsv'])
        ->name('surveys.export-csv');
    Route::get('/inbox/wallet', [WalletController::class, 'index'])
        ->name('inbox.wallet');
    Route::get('/settings', [SettingsController::class, 'edit'])
        ->name('settings.edit');
    Route::post('/settings/send-reset-link', [SettingsController::class, 'sendResetLink'])
        ->name('settings.send-reset-link');
});

Route::middleware(['auth', 'active.student:create'])->group(function () {
    Route::get('/surveys/create', [SurveyController::class, 'create'])
        ->name('surveys.create');
    Route::post('/surveys', [SurveyController::class, 'store'])
        ->name('surveys.store');
    Route::post('/inbox/wallet/redeem', [WalletController::class, 'redeem'])
        ->name('wallet.redeem');
});

require __DIR__.'/auth.php';
