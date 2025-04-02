<?php

namespace App\Services;

use TCPDF;

class PdfService
{
    protected $pdfStoragePath;

    public function __construct()
    {
        $this->pdfStoragePath = __DIR__ . '/../../storage/pdfs/';
        $this->ensureDirectoryExists();
    }

    public function generateFromText(string $content, string $filename): string
    {

        // Sanitize input
        $content = strip_tags($content, '<h1><h2><h3><p><b><i><u><br><hr>');
        $filename = substr($filename, 0, 50);

        $hash = md5($content);
        $cacheFile = $this->pdfStoragePath . 'cache/' . $hash . '.pdf';

        if (file_exists($cacheFile)) {
            return $cacheFile;
        }
        
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->writeHTML($content);

        $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);
        $fullPath = $this->pdfStoragePath . $safeFilename . '_' . time() . '.pdf';

        $pdf->Output($fullPath, 'F');

        return $fullPath;
    }

    private function ensureDirectoryExists(): void
    {
        if (!file_exists($this->pdfStoragePath)) {
            mkdir($this->pdfStoragePath, 0755, true);
        }
    }
}
