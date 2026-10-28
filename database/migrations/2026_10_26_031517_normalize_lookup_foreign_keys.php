<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('role_id')->nullable()->index();
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->foreignId('service_status_id')->nullable()->index();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('order_status_id')->nullable()->index();
        });

        Schema::table('order_files', function (Blueprint $table): void {
            $table->foreignId('order_file_kind_id')->nullable()->index();
        });

        Schema::table('order_status_histories', function (Blueprint $table): void {
            $table->foreignId('from_order_status_id')->nullable()->index();
            $table->foreignId('to_order_status_id')->nullable()->index();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignId('payment_status_id')->nullable()->index();
        });

        $this->backfill('users', 'role', 'role_id', 'roles');
        $this->backfill('services', 'status', 'service_status_id', 'service_statuses');
        $this->backfill('orders', 'status', 'order_status_id', 'order_statuses');
        $this->backfill('order_files', 'kind', 'order_file_kind_id', 'order_file_kinds');
        $this->backfill('order_status_histories', 'from_status', 'from_order_status_id', 'order_statuses');
        $this->backfill('order_status_histories', 'to_status', 'to_order_status_id', 'order_statuses');
        $this->backfill('payments', 'status', 'payment_status_id', 'payment_statuses');

        $this->assertBackfilled('users', 'role_id');
        $this->assertBackfilled('services', 'service_status_id');
        $this->assertBackfilled('orders', 'order_status_id');
        $this->assertBackfilled('order_files', 'order_file_kind_id');
        $this->assertBackfilled('order_status_histories', 'to_order_status_id');
        $this->assertBackfilled('payments', 'payment_status_id');

        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id')->change();
            $table->foreign('role_id')->references('id')->on('roles')->restrictOnDelete();
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->unsignedBigInteger('service_status_id')->change();
            $table->foreign('service_status_id')->references('id')->on('service_statuses')->restrictOnDelete();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('order_status_id')->change();
            $table->foreign('order_status_id')->references('id')->on('order_statuses')->restrictOnDelete();
        });

        Schema::table('order_files', function (Blueprint $table): void {
            $table->unsignedBigInteger('order_file_kind_id')->change();
            $table->foreign('order_file_kind_id')->references('id')->on('order_file_kinds')->restrictOnDelete();
        });

        Schema::table('order_status_histories', function (Blueprint $table): void {
            $table->unsignedBigInteger('to_order_status_id')->change();
            $table->foreign('from_order_status_id')->references('id')->on('order_statuses')->restrictOnDelete();
            $table->foreign('to_order_status_id')->references('id')->on('order_statuses')->restrictOnDelete();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->unsignedBigInteger('payment_status_id')->change();
            $table->foreign('payment_status_id')->references('id')->on('payment_statuses')->restrictOnDelete();
        });

        Schema::table('users', fn (Blueprint $table) => $table->dropIndex('users_role_index'));
        Schema::table('services', fn (Blueprint $table) => $table->dropIndex('services_status_index'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropIndex('orders_status_index'));
        Schema::table('order_files', fn (Blueprint $table) => $table->dropIndex('order_files_kind_index'));
        Schema::table('payments', fn (Blueprint $table) => $table->dropIndex('payments_status_index'));

        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn('status'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('status'));
        Schema::table('order_files', fn (Blueprint $table) => $table->dropColumn('kind'));
        Schema::table('order_status_histories', fn (Blueprint $table) => $table->dropColumn(['from_status', 'to_status']));
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn('status'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('role')->nullable());
        Schema::table('services', fn (Blueprint $table) => $table->string('status')->nullable());
        Schema::table('orders', fn (Blueprint $table) => $table->string('status')->nullable());
        Schema::table('order_files', fn (Blueprint $table) => $table->string('kind')->nullable());
        Schema::table('order_status_histories', function (Blueprint $table): void {
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
        });
        Schema::table('payments', fn (Blueprint $table) => $table->string('status')->nullable());

        $this->restore('users', 'role_id', 'role', 'roles');
        $this->restore('services', 'service_status_id', 'status', 'service_statuses');
        $this->restore('orders', 'order_status_id', 'status', 'order_statuses');
        $this->restore('order_files', 'order_file_kind_id', 'kind', 'order_file_kinds');
        $this->restore('order_status_histories', 'from_order_status_id', 'from_status', 'order_statuses');
        $this->restore('order_status_histories', 'to_order_status_id', 'to_status', 'order_statuses');
        $this->restore('payments', 'payment_status_id', 'status', 'payment_statuses');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['role_id']);
            $table->dropIndex('users_role_id_index');
            $table->dropColumn('role_id');
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->dropForeign(['service_status_id']);
            $table->dropIndex('services_service_status_id_index');
            $table->dropColumn('service_status_id');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['order_status_id']);
            $table->dropIndex('orders_order_status_id_index');
            $table->dropColumn('order_status_id');
        });

        Schema::table('order_files', function (Blueprint $table): void {
            $table->dropForeign(['order_file_kind_id']);
            $table->dropIndex('order_files_order_file_kind_id_index');
            $table->dropColumn('order_file_kind_id');
        });

        Schema::table('order_status_histories', function (Blueprint $table): void {
            $table->dropForeign(['from_order_status_id']);
            $table->dropForeign(['to_order_status_id']);
            $table->dropIndex('order_status_histories_from_order_status_id_index');
            $table->dropIndex('order_status_histories_to_order_status_id_index');
            $table->dropColumn(['from_order_status_id', 'to_order_status_id']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['payment_status_id']);
            $table->dropIndex('payments_payment_status_id_index');
            $table->dropColumn('payment_status_id');
        });

        Schema::table('users', fn (Blueprint $table) => $table->index('role'));
        Schema::table('services', fn (Blueprint $table) => $table->index('status'));
        Schema::table('orders', fn (Blueprint $table) => $table->index('status'));
        Schema::table('order_files', fn (Blueprint $table) => $table->index('kind'));
        Schema::table('payments', fn (Blueprint $table) => $table->index('status'));
    }

    /**
     * Copy a legacy code column into its foreign key column.
     */
    private function backfill(string $table, string $legacyColumn, string $foreignColumn, string $lookupTable): void
    {
        $ids = DB::table($lookupTable)->pluck('id', 'code')->all();

        $unknown = DB::table($table)
            ->select($legacyColumn)
            ->distinct()
            ->pluck($legacyColumn)
            ->reject(fn (mixed $code): bool => $code === null || array_key_exists((string) $code, $ids))
            ->values();

        if ($unknown->isNotEmpty()) {
            throw new RuntimeException(sprintf(
                'Nilai %s.%s tidak dikenal: %s. Tambahkan ke %s sebelum menjalankan migrasi.',
                $table,
                $legacyColumn,
                $unknown->implode(', '),
                $lookupTable,
            ));
        }

        DB::table($table)
            ->select(['id', $legacyColumn])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, $legacyColumn, $foreignColumn, $ids): void {
                foreach ($rows as $row) {
                    $code = $row->{$legacyColumn};

                    if ($code === null) {
                        continue;
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([$foreignColumn => $ids[(string) $code]]);
                }
            });
    }

    /**
     * Copy a foreign key column back into its legacy code column.
     */
    private function restore(string $table, string $foreignColumn, string $legacyColumn, string $lookupTable): void
    {
        $codes = DB::table($lookupTable)->pluck('code', 'id')->all();

        DB::table($table)
            ->select(['id', $foreignColumn])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, $foreignColumn, $legacyColumn, $codes): void {
                foreach ($rows as $row) {
                    $foreignId = $row->{$foreignColumn};

                    if ($foreignId === null) {
                        continue;
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([$legacyColumn => $codes[$foreignId] ?? null]);
                }
            });
    }

    /**
     * Refuse to proceed when a non-null legacy value has no matching lookup row.
     */
    private function assertBackfilled(string $table, string $foreignColumn): void
    {
        if (DB::table($table)->whereNull($foreignColumn)->exists()) {
            throw new RuntimeException(sprintf(
                'Kolom %s.%s masih memiliki nilai kosong setelah backfill.',
                $table,
                $foreignColumn,
            ));
        }
    }
};
