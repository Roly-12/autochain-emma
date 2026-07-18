<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vehicle> */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'blockchain_vehicle_id' => \Illuminate\Support\Str::uuid(),
            'license_plate' => strtoupper(fake()->bothify('??-###-??')),
            'vin' => strtoupper(fake()->bothify('?????????????????')),
            'brand' => fake()->randomElement(['Renault','Peugeot','Citroën','Toyota','Ford']),
            'model' => fake()->word(),
            'year' => fake()->numberBetween(2015, 2024),
            'fuel_type' => fake()->randomElement(['essence','diesel','électrique','hybride']),
            'status' => fake()->randomElement(['available','in_mission','maintenance']),
            'last_certified_mileage' => fake()->numberBetween(5000, 200000),
            'mileage_certified_at' => now(),
        ];
    }
}
