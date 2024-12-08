<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\FacebookController;
use App\Http\Controllers\AccountSelectionController;
use App\Livewire\SelectAccountComponent;

// Ana sayfa (Kullanıcıyı Facebook Login ekranına yönlendirmek veya token almak için)
Route::get('/', function(){return view("index");} )->name("home");



Route::get('/login/facebook', [FacebookController::class, 'redirectToFacebook'])->name('facebook.login');
Route::get('/callback/facebook', [FacebookController::class, 'handleFacebookCallback'])->name('facebook.callback');
//  Route::get('select-account', function(){return view('app.account_selection');});
Route::get('instagram-accounts', function(){return view('app.instagram_accounts');})->name("accounts");

Route::get('/select-account', function () {
    return view('app.account_selection');
})->name('select.account');

