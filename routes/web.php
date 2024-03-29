<?php

use App\Http\Controllers\EntryController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserController;
use GuzzleHttp\Middleware;
use Illuminate\Support\Facades\Route;

// Welcome
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Static Pages
Route::view('/about', 'about')->name('about');
Route::view('/contacts', 'contacts')->name('contact');

// Auth
Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'index')->name('login');
    Route::post('/login', 'authenticate')->name('login.auth');
    Route::get('/logout', 'logout')->name('login.logout');
});
Route::controller(RegisterController::class)->group(function () {
    Route::get('/register', 'index')->name('register.index');
    Route::post('/register', 'createAccount')->name('register.createAccount');
});

Route::middleware(['auth'])->group(function () {

    // Users
    Route::controller(UserController::class)->group(function () {
        Route::get('/user/{account_name}', 'index')->name('user.index');
        Route::get('/user/{account_name}/edit', 'editIndex')->name('user.edit');
        Route::post('/user/{account_name}/update', 'updateUser')->name('user.update');
        Route::post('/user/{account_name}/follow', 'follow')->name('user.follow');
        Route::delete('/user/{account_name}/unfollow', 'unfollow')->name('user.unfollow');
        Route::get('/user/{account_name}/settings', 'settingsIndex')->name('user.settings');
        Route::delete('/user/{account_name}/delete', 'deleteAccount')->name('user.delete');
    });
    
    // Entries
    Route::controller(EntryController::class)->group(function () {
        Route::get('/home', 'homeIndex')->name('home');
        Route::post('/entry/create', 'createEntry')->name('entry.create');
        Route::delete('/entry/{id}/delete', 'deleteEntry')->name('entry.delete');
        Route::post('/entry/{id}/like', 'likeEntry')->name('entry.like');
        Route::delete('/entry/{id}/dislike', 'dislikeEntry')->name('entry.dislike');
    });

    // Search
    Route::controller(SearchController::class)->group(function () {
        Route::get('/search', 'searchIndex')->name('search.index');
        Route::get('/search/user', 'searchUser')->name('search.searchUser');
    });
});


