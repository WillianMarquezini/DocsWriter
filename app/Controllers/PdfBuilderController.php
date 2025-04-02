<?php

namespace App\Controllers;

use App\Services\PdfService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PdfBuilderController
{
    protected $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function showForm(ServerRequestInterface $request): ResponseInterface
    {
        // Renderiza o formulário
        ob_start();
        include __DIR__ . '/../../app/Views/pdf-builder.php';
        $content = ob_get_clean();

        return new Response(200, [], $content);
    }

    public function generatePdf(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        try {
            $pdfPath = $this->pdfService->generateFromText(
                $data['content'],
                $data['filename'] ?? 'documento'
            );

            return new JsonResponse([
                'success' => true,
                'pdf_url' => '/storage/pdfs/' . basename($pdfPath)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
