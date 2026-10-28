<?php

namespace Database\Factories;

use App\Enums\ServiceStatus;
use App\Models\Lookup\ServiceStatus as ServiceStatusLookup;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(4, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(50, 2000) * 1000,
            'estimated_duration' => fake()->numberBetween(1, 14),
            'requirements' => fake()->sentences(3, true),
            'service_status_id' => $this->statusId(ServiceStatus::Published),
        ];
    }

    /**
     * Indicate that the service is available in the public catalog.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_status_id' => $this->statusId(ServiceStatus::Published),
        ]);
    }

    /**
     * Indicate that the service is still being prepared.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_status_id' => $this->statusId(ServiceStatus::Draft),
        ]);
    }

    /**
     * Indicate that the service is no longer offered.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_status_id' => $this->statusId(ServiceStatus::Archived),
        ]);
    }

    /**
     * Resolve the lookup id for a service status.
     */
    private function statusId(ServiceStatus $status): int
    {
        return ServiceStatusLookup::ensureIdFor($status->value, $status->label());
    }
}
