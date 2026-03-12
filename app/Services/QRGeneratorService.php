<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRGeneratorService
{
    /**
     * Generate a QR code for a given URL.
     * Returns the SVG string for the QR code.
     */
    public function generateForUrl(string $url): string
    {
        return QrCode::size(300)->generate($url);
    }
}
