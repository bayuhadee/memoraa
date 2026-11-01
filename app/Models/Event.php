<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;

/**
 * Event juga berperan sebagai "user" untuk guard host
 * (login: event_code + password event).
 */
class Event extends Model implements AuthenticatableContract
{
    use Authenticatable, HasFactory, SoftDeletes;

    protected $fillable = [
        'event_code',
        'bride_groom_names',
        'event_date',
        'address',
        'payment_status',
        'is_active',
    ];

    protected $hidden = ['password_plain', 'password_hash'];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'password_plain' => 'encrypted',
            'payment_status' => PaymentStatus::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Event $event) {
            $event->event_code = strtoupper(trim($event->event_code));
        });

        // Hapus semua foto (beserta file) saat event dihapus.
        static::deleting(function (Event $event) {
            $event->photos()->each(fn (Photo $photo) => $photo->delete());
            $event->zipExports()->each(fn (ZipExport $zip) => $zip->delete());
        });

        // Bebaskan event_code agar bisa dipakai lagi oleh event baru.
        static::softDeleted(function (Event $event) {
            $event->event_code = substr($event->event_code, 0, 40).'~DEL'.$event->id;
            $event->saveQuietly();
        });
    }

    /** Password host dicek terhadap kolom password_hash. */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /** Host tidak memakai fitur "remember me". */
    public function getRememberTokenName(): string
    {
        return '';
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function zipExports(): HasMany
    {
        return $this->hasMany(ZipExport::class);
    }

    /** Set password 4 karakter: hash untuk login, terenkripsi untuk ditampilkan ke owner. */
    public function setEventPassword(string $plain): void
    {
        $this->password_plain = $plain;
        $this->password_hash = Hash::make($plain);
    }

    /** Batas akhir upload: 23:59:59 pada tanggal acara (zona waktu config). */
    public function uploadDeadline(): CarbonImmutable
    {
        $tz = config('galeri.timezone');

        return CarbonImmutable::parse($this->event_date->format('Y-m-d'), $tz)->endOfDay();
    }

    public function isUploadOpen(): bool
    {
        return $this->is_active && now(config('galeri.timezone'))->lessThanOrEqualTo($this->uploadDeadline());
    }

    public function eventDateLabel(): string
    {
        return $this->event_date->locale('id')->translatedFormat('l, j F Y');
    }

    public function galleryUrl(): string
    {
        return route('guest.gallery', $this);
    }

    public function getRouteKeyName(): string
    {
        return 'event_code';
    }

    /** Owner memakai ID numerik, tamu memakai event_code (tidak peka huruf besar/kecil). */
    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();

        if ($field === 'event_code') {
            $value = strtoupper((string) $value);
        }

        return $this->where($field, $value)->first();
    }

    /** Teks siap kirim via WhatsApp ke host. */
    public function hostInfoText(): string
    {
        return implode("\n", [
            'Halo, berikut info buku tamu digital untuk acara '.$this->bride_groom_names.':',
            '',
            'Link untuk tamu: '.$this->galleryUrl(),
            'Login host: '.route('host.login'),
            'ID Event: '.$this->event_code,
            'Password: '.$this->password_plain,
            '',
            'Batas upload tamu: '.$this->eventDateLabel().' pukul 23:59.',
        ]);
    }
}
