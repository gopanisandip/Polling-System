<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PollController as AdminPollController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;




Route::get('/', [PollController::class, 'index'])->name('home');


Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/poll/{slug}', [PollController::class, 'show'])->name('polls.show');
Route::post('/poll/{poll}/vote', [PollController::class, 'vote'])->name('polls.vote');


Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::get('/polls', [AdminPollController::class, 'index'])->name('polls.index');
    Route::get('/polls/create', [AdminPollController::class, 'create'])->name('polls.create');
    Route::post('/polls', [AdminPollController::class, 'store'])->name('polls.store');
    Route::get('/polls/{poll}', [AdminPollController::class, 'show'])->name('polls.show');
    Route::get('/polls/{poll}/edit', [AdminPollController::class, 'edit'])->name('polls.edit');
    Route::put('/polls/{poll}', [AdminPollController::class, 'update'])->name('polls.update');
    Route::delete('/polls/{poll}', [AdminPollController::class, 'destroy'])->name('polls.destroy');
    Route::get('/polls/{poll}/results', [AdminPollController::class, 'results'])->name('polls.results');
});