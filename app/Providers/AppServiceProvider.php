<?php

namespace App\Providers;

use App\Models\FleetAlert;
use App\Models\FuelLog;
use App\Models\Maintenance;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\VehicleSale;
use App\Policies\FleetAlertPolicy;
use App\Policies\FuelLogPolicy;
use App\Policies\MaintenancePolicy;
use App\Policies\VehicleDocumentPolicy;
use App\Policies\VehiclePolicy;
use App\Policies\VehicleSalePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        Vite::prefetch(concurrency: 3);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(VehicleDocument::class, VehicleDocumentPolicy::class);
        Gate::policy(FuelLog::class, FuelLogPolicy::class);
        Gate::policy(Maintenance::class, MaintenancePolicy::class);
        Gate::policy(FleetAlert::class, FleetAlertPolicy::class);
        Gate::policy(VehicleSale::class, VehicleSalePolicy::class);
    }
}
