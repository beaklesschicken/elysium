<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SwitchOrganizationController;
use App\Livewire\Employees\EmployeeForm;
use App\Livewire\Employees\Import as ImportEmployees;
use App\Livewire\Employees\Index as EmployeeIndex;
use App\Livewire\Invitations\AcceptInvitation;
use App\Livewire\Organizations\CreateOrganization;
use App\Livewire\Organizations\EditOrganization;
use App\Livewire\Organizations\Members;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Units\Index as UnitIndex;
use App\Livewire\Units\Show as ShowUnit;
use App\Livewire\Units\UnitForm;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::middleware('portal')->group(function () {
    Route::get('invitations/{token}', AcceptInvitation::class)
        ->middleware('throttle:20,1')
        ->name('invitations.accept');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('organizations/create', CreateOrganization::class)->name('organizations.create');
        Route::post('organizations/{organization}/switch', SwitchOrganizationController::class)->name('organizations.switch');

        Route::middleware('organization')->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

            Route::get('units', UnitIndex::class)->name('units.index');
            Route::get('units/create', UnitForm::class)->name('units.create');
            Route::get('units/{unit}', ShowUnit::class)->name('units.show');
            Route::get('units/{unit}/edit', UnitForm::class)->name('units.edit');

            Route::get('employees', EmployeeIndex::class)->name('employees.index');
            Route::get('employees/create', EmployeeForm::class)->name('employees.create');
            Route::get('employees/import', ImportEmployees::class)->name('employees.import');
            Route::get('employees/{employee}/edit', EmployeeForm::class)->name('employees.edit');

            Route::get('organization', EditOrganization::class)->name('organization.edit');
            Route::get('organization/members', Members::class)->name('organization.members');
        });
    });

    Route::middleware(['auth'])->group(function () {
        Route::redirect('settings', 'settings/profile');

        Route::get('settings/profile', Profile::class)->name('settings.profile');
        Route::get('settings/password', Password::class)->name('settings.password');
        Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
    });

    require __DIR__.'/auth.php';
});
