<?php

namespace Database\Factories;

use App\Models\Lookup\OrderFileKind as OrderFileKindLookup;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * @extends Factory<OrderFile>
 */
class OrderFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word().'.pdf';

        return [
            'order_id' => Order::factory(),
            'file_name' => $name,
            'file_path' => "orders/requirements/{$name}",
            'file_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(10_000, 500_000),
            'order_file_kind_id' => $this->kindId(OrderFile::KIND_REQUIREMENT),
            'uploaded_by' => User::factory(),
        ];
    }

    /**
     * A file the customer attached as a requirement.
     */
    public function requirement(): static
    {
        return $this->state(fn (): array => [
            'order_file_kind_id' => $this->kindId(OrderFile::KIND_REQUIREMENT),
        ]);
    }

    /**
     * A deliverable produced by the admin/worker.
     */
    public function result(): static
    {
        return $this->state(fn (): array => [
            'order_file_kind_id' => $this->kindId(OrderFile::KIND_RESULT),
            'file_name' => 'hasil-'.$this->faker->word().'.pdf',
        ]);
    }

    /**
     * Attach a real temporary file to the private disk.
     */
    public function stored(): static
    {
        return $this->afterMaking(function (OrderFile $file): void {
            $file->file_path = UploadedFile::fake()->create($file->file_name, 64, 'application/pdf')
                ->store("orders/{$file->order_id}/files", 'local');
        });
    }

    /**
     * Resolve the lookup id for a file kind.
     */
    private function kindId(string $kind): int
    {
        return OrderFileKindLookup::ensureIdFor(
            $kind,
            $kind === OrderFile::KIND_REQUIREMENT ? 'Persyaratan' : 'Hasil',
        );
    }
}
