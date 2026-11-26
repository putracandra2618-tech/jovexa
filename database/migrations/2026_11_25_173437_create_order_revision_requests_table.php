<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_revision_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('revision_number');
            $table->text('note');
            $table->timestamps();

            $table->unique(['order_id', 'revision_number']);
            $table->index(['order_id', 'created_at']);
        });

        $this->backfillFromHistory();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_revision_requests');
    }

    /**
     * Move existing revision notes out of the generic audit trail.
     */
    private function backfillFromHistory(): void
    {
        $revisionStatusId = DB::table('order_statuses')->where('code', 'revision')->value('id');

        if ($revisionStatusId === null) {
            return;
        }

        $counts = [];

        DB::table('order_status_histories')
            ->where('to_order_status_id', $revisionStatusId)
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$counts): void {
                foreach ($rows as $row) {
                    $number = ($counts[$row->order_id] ?? 0) + 1;
                    $note = (string) ($row->note ?? '');
                    $prefix = 'Revisi diminta oleh customer: ';

                    if (str_starts_with($note, $prefix)) {
                        $note = Str::after($note, $prefix);
                    }

                    DB::table('order_revision_requests')->insert([
                        'order_id' => $row->order_id,
                        'requested_by' => $row->changed_by,
                        'revision_number' => $number,
                        'note' => $note,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->created_at,
                    ]);

                    $counts[$row->order_id] = $number;
                }
            });
    }
};
