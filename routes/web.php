<?php

use App\Http\Controllers\Web\CliAuthBrowserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('cli-auth')->middleware('throttle:cli-auth-verify')->group(function () {
    Route::get('/verify', [CliAuthBrowserController::class, 'verify'])->name('cli-auth.verify');
    Route::get('/authorize/{deviceCode}', [CliAuthBrowserController::class, 'authorizeRequest'])->name('cli-auth.authorize');
    Route::get('/requests/{loginRequest}', [CliAuthBrowserController::class, 'show'])->name('cli-auth.request.show');
    Route::post('/requests/{loginRequest}/approve', [CliAuthBrowserController::class, 'approve'])->name('cli-auth.request.approve');
    Route::get('/google/{loginRequest}/redirect', [CliAuthBrowserController::class, 'redirectToGoogle'])->name('cli-auth.google.redirect');
    Route::get('/google/callback', [CliAuthBrowserController::class, 'googleCallback'])->name('cli-auth.google.callback');
});
