<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    /**
     * PNG siap cetak (pakai GD, tanpa Imagick) dengan ikon Memoraa di tengah.
     * Error correction "High" agar QR tetap terbaca walau tengahnya tertutup ikon (ikon berlatar putih, bukan transparan).
     */
    public function png(string $url, string $label = '', int $size = 800): string
    {
        $logo = public_path('images/brand/icon-qr.png');

        $builder = new Builder(
            writer: new PngWriter,
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 32,
            foregroundColor: new Color(74, 53, 39),
            backgroundColor: new Color(255, 255, 255),
            labelText: $label,
            labelTextColor: new Color(74, 53, 39),
            logoPath: is_file($logo) ? $logo : '',
            logoResizeToWidth: (int) round($size * 0.22),
            logoPunchoutBackground: true,
        );

        return $builder->build()->getString();
    }
}
