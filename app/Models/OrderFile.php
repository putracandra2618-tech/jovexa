<?php

namespace App\Models;

use App\Models\Lookup\OrderFileKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['order_id', 'file_name', 'file_path', 'file_type', 'file_size', 'order_file_kind_id', 'uploaded_by'])]
class OrderFile extends Model
{
    /** @use HasFactory<OrderFileFactory> */
    use HasFactory;

    public const KIND_REQUIREMENT = 'requirement';

    public const KIND_RESULT = 'result';

    /**
     * Extensions accepted for both requirement and result uploads.
     *
     * @var list<string>
     */
    public const ALLOWED_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'zip', 'rar', '7z', 'jpg', 'jpeg', 'png', 'webp',
    ];

    /**
     * The lookup row backing the file kind.
     *
     * @return BelongsTo<OrderFileKind, $this>
     */
    public function kindRecord(): BelongsTo
    {
        return $this->belongsTo(OrderFileKind::class, 'order_file_kind_id');
    }

    /**
     * Expose the kind as its legacy code so existing callers keep working.
     */
    protected function kind(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->kindRecord?->code);
    }

    /**
     * The order the file belongs to.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The account that uploaded the file.
     *
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Determine whether the file is a customer requirement.
     */
    public function isRequirement(): bool
    {
        return $this->kind === self::KIND_REQUIREMENT;
    }

    /**
     * Determine whether the file is a work result.
     */
    public function isResult(): bool
    {
        return $this->kind === self::KIND_RESULT;
    }

    /**
     * The human readable file size.
     */
    public function readableSize(): string
    {
        $bytes = $this->file_size ?? 0;

        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024) {
                return $bytes.' '.$unit;
            }

            $bytes /= 1024;
        }

        return round($bytes, 1).' TB';
    }

    /**
     * The icon matching the file extension.
     */
    public function icon(): string
    {
        $extension = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'bi-file-earmark-pdf',
            'doc', 'docx' => 'bi-file-earmark-word',
            'xls', 'xlsx', 'csv' => 'bi-file-earmark-excel',
            'ppt', 'pptx' => 'bi-file-earmark-slides',
            'zip', 'rar', '7z' => 'bi-file-earmark-zip',
            'png', 'jpg', 'jpeg', 'gif', 'webp' => 'bi-file-earmark-image',
            default => 'bi-file-earmark',
        };
    }

    /**
     * Determine whether the stored file is still present.
     */
    public function exists(): bool
    {
        return Storage::disk('local')->exists($this->file_path);
    }
}
