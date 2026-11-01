<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'message'];

    protected $hidden = ['device_token', 'ip_address', 'user_agent'];

    protected static function booted(): void
    {
        // Pastikan file foto ikut terhapus (cascade DB tidak memicu event model).
        static::deleting(function (Guest $guest) {
            $guest->photos()->each(fn (Photo $photo) => $photo->delete());
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }
}
