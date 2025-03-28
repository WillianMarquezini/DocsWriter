<?php


$type = $_GET['type'] ?? false;

if (!$type) {
    echo "Error";
    exit;
}

switch ($type) {
    case 'doc':
        include_once('DocGenerator.php');
        break;
    case 'pdf':
        include_once('PdfGenerator.php');
    default:
        echo "indefinido";
        break;
}
