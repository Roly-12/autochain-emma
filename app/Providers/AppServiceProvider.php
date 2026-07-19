<?php

namespace App\Providers;

use App\Mail\Transport\BrevoApiTransport;
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
use Illuminate\Support\Facades\Mail;
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
        Mail::extend('brevo-api', fn (array $config) => new BrevoApiTransport(
            apiKey: (string) ($config['key'] ?? ''),
            endpoint: (string) ($config['endpoint'] ?? 'https://api.brevo.com/v3/smtp/email'),
            timeout: (int) ($config['timeout'] ?? 15),
        ));

        Vite::prefetch(concurrency: 3);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(VehicleDocument::class, VehicleDocumentPolicy::class);
        Gate::policy(FuelLog::class, FuelLogPolicy::class);
        Gate::policy(Maintenance::class, MaintenancePolicy::class);
        Gate::policy(FleetAlert::class, FleetAlertPolicy::class);
        Gate::policy(VehicleSale::class, VehicleSalePolicy::class);
    }
}
