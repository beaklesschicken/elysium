<?php

use App\Http\Controllers\CredentialDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SwitchOrganizationController;
use App\Livewire\Credentials\Index as CredentialIndex;
use App\Livewire\Credentials\Types as CredentialTypes;
use App\Livewire\Employees\EmployeeForm;
use App\Livewire\Employees\Import as ImportEmployees;
use App\Livewire\Employees\Index as EmployeeIndex;
use App\Livewire\Employees\Show as ShowEmployee;
use App\Livewire\Invitations\AcceptInvitation;
use App\Livewire\Organizations\CreateOrganization;
use App\Livewire\Organizations\EditOrganization;
use App\Livewire\Organizations\Members;
use App\Livewire\Organizations\Permits;
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
        Route::view('organizations/none', 'organizations.none')->name('organizations.none');
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
            Route::get('employees/{employee}', ShowEmployee::class)->name('employees.show');
            Route::get('employees/{employee}/edit', EmployeeForm::class)->name('employees.edit');

            Route::get('credentials', CredentialIndex::class)->name('credentials.index');
            Route::get('credentials/types', CredentialTypes::class)->name('credentials.types');
            Route::get('credentials/{credential}/documents/{document}', CredentialDocumentController::class)->name('credentials.documents.show');

            Route::get('organization', EditOrganization::class)->name('organization.edit');
            Route::get('organization/members', Members::class)->name('organization.members');
            Route::get('organization/permits', Permits::class)->name('organization.permits');
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
