<?php
class DocBuilderController
{
    public function index()
    {
        // Lógica do controller aqui
        $pageTitle = "Doc Builder - Docs Writer";

        // Inclui a view correspondente
        require_once __DIR__ . '/../views/doc-builder.php';
    }

    public function generate()
    {
        // Método para gerar Doc
        // Implemente sua lógica aqui
    }
}
