<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Baru = 'baru';
    case BelumBayar = 'belum_bayar';
    case Lunas = 'lunas';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::BelumBayar => 'Belum Bayar',
            self::Lunas => 'Lunas',
        };
    }

    /** Kelas Tailwind untuk badge status. */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Baru => 'bg-sky-100 text-sky-800 ring-sky-200',
            self::BelumBayar => 'bg-amber-100 text-amber-800 ring-amber-200',
            self::Lunas => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
