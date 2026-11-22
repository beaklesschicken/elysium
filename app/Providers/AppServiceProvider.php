<?php

namespace App\Providers;

use App\Http\Middleware\EnsureCurrentOrganization;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'employee' => Employee::class,
            'unit' => Unit::class,
            'organization' => Organization::class,
            'user' => User::class,
        ]);

        Livewire::addPersistentMiddleware([
            EnsureCurrentOrganization::class,
        ]);
    }
}
