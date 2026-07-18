<?php

namespace App\Services;

use App\Models\FleetAlert;
use App\Models\Vehicle;
use App\Notifications\FleetAlertNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class AlertEngineService
{
    public function syncAll(int $warningDays = 30): int
    {
        $created = 0;

        Vehicle::query()->each(function (Vehicle $vehicle) use (&$created, $warningDays) {
            $created += $this->syncVehicle($vehicle, $warningDays);
        });

        return $created;
    }

    public function syncVehicle(Vehicle $vehicle, int $warningDays = 30): int
    {
        $created = 0;
        $horizon = Carbon::today()->addDays($warningDays);

        $checks = [
            [
                'type' => 'technical_control',
                'date' => $vehicle->technical_control_deadline,
                'title' => 'Contrôle technique à renouveler',
                'message' => "Le CT du véhicule {$vehicle->license_plate} expire bientôt.",
            ],
            [
                'type' => 'insurance_renewal',
                'date' => $vehicle->insurance_expiry,
                'title' => 'Assurance à renouveler',
                'message' => "L'assurance du véhicule {$vehicle->license_plate} expire bientôt.",
            ],
            [
                'type' => 'maintenance_due',
                'date' => $vehicle->next_maintenance_date,
                'title' => 'Entretien programmé',
                'message' => "Un entretien est prévu pour {$vehicle->license_plate}.",
            ],
        ];

        foreach ($checks as $check) {
            if (empty($check['date'])) {
                continue;
            }

            $due = Carbon::parse($check['date']);
            if ($due->gt($horizon)) {
                continue;
            }

            $severity = $due->isPast() ? 'critical' : ($due->lte(Carbon::today()->addDays(7)) ? 'warning' : 'info');
            $fingerprint = hash('sha256', implode('|', [
                $vehicle->id,
                $check['type'],
                $check['title'],
                $due->toDateString(),
            ]));

            $alert = FleetAlert::firstOrCreate(
                ['fingerprint' => $fingerprint],
                [
                    'vehicle_id' => $vehicle->id,
                    'type' => $check['type'],
                    'severity' => $severity,
                    'title' => $check['title'],
                    'message' => $check['message'],
                    'due_date' => $due,
                ]
            );

            if (! $alert->wasRecentlyCreated) {
                continue;
            }

            $this->notifyManagers($alert);
            $alert->update(['notified_at' => now()]);
            $created++;
        }

        if ($vehicle->next_maintenance_mileage
            && $vehicle->last_certified_mileage
            && $vehicle->last_certified_mileage >= $vehicle->next_maintenance_mileage
        ) {
            $fingerprint = hash('sha256', implode('|', [
                $vehicle->id,
                'maintenance_due',
                'mileage',
                $vehicle->next_maintenance_mileage,
            ]));
            $alert = FleetAlert::firstOrCreate(
                ['fingerprint' => $fingerprint],
                [
                    'vehicle_id' => $vehicle->id,
                    'type' => 'maintenance_due',
                    'severity' => 'warning',
                    'title' => 'Seuil kilométrique d\'entretien atteint',
                    'message' => "Le véhicule {$vehicle->license_plate} a atteint le seuil de {$vehicle->next_maintenance_mileage} km.",
                    'due_date' => Carbon::today(),
                ]
            );
            if ($alert->wasRecentlyCreated) {
                $this->notifyManagers($alert);
                $alert->update(['notified_at' => now()]);
                $created++;
            }
        }

        return $created;
    }

    protected function notifyManagers(FleetAlert $alert): void
    {
        $managers = User::query()
            ->whereIn('role', ['super_admin', 'gestionnaire_parc'])
            ->where('is_active', true)
            ->where('notification_email', true)
            ->get();

        if ($managers->isNotEmpty()) {
            Notification::send($managers, new FleetAlertNotification($alert));
        }
    }
}
