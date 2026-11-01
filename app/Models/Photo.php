<?php

namespace App\Models;

use App\Services\PhotoStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Photo extends Model
{
    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    protected $fillable = ['caption'];

    protected $hidden = ['id', 'event_id', 'guest_id', 'path', 'thumb_path', 'pending_path'];

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'file_size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Photo $photo) {
            $photo->uuid ??= (string) Str::uuid();
        });

        // Model event: file di storage selalu ikut terhapus.
        static::deleted(function (Photo $photo) {
            app(PhotoStorage::class)->delete([$photo->path, $photo->thumb_path, $photo->pending_path]);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /** Foto yang sudah punya versi hasil resize (tampil di galeri). */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNotNull('path')->whereNotNull('thumb_path');
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function hasImage(): bool
    {
        return $this->path !== null;
    }

    public function thumbWidth(): int
    {
        return (int) config('galeri.photos.thumb_width');
    }

    public function thumbHeight(): int
    {
        if (! $this->width || ! $this->height) {
            return $this->thumbWidth();
        }

        return (int) round($this->thumbWidth() * $this->height / $this->width);
    }

    /** Nama file untuk download, mis. "budi-santoso_1.jpg". */
    public function downloadName(int $number = 1): string
    {
        $name = Str::slug($this->guest?->name ?? 'tamu') ?: 'tamu';

        return $name.'_'.$number.'.jpg';
    }
}
