<?php

namespace App\Models;

use App\Services\PhotoStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ZipExport extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (ZipExport $zip) {
            $zip->uuid ??= (string) Str::uuid();
        });

        static::deleted(function (ZipExport $zip) {
            app(PhotoStorage::class)->delete([$zip->path]);
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
}
