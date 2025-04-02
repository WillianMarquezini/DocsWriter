<?php
class PdfToPhpController
{
    public function index()
    {
        // Lógica do controller aqui
        $pageTitle = "PDF to PHP - Docs Writer";

        // Inclui a view correspondente
        require_once __DIR__ . '/../views/pdf-to-php.php';
    }

    public function generate()
    {
        // Método para gerar PDF
        // Implemente sua lógica aqui
    }

    
}
