<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TeamController;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/contact', function() {
    return view('contact');
});

Route::get('dashboard', [DashboardController::class,'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::controller(TeamController::class)->group(function() {
    Route::get('/teams/new','create')->name('admin-team-create');
    Route::get('/teams/{team_id?}','index')->name('admin-teams');

})->middleware(['auth', 'verified']);

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

require __DIR__.'/auth.php';
