<?php

$r->addRoute('GET', '/pdf-builder', [PdfBuilderController::class, 'ShowForm']);
$r->addRoute('GET', '/generate-pdf', [PdfBuilderController::class, 'generatePdf']);

