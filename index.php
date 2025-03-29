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
        break;
    case 'pdf2':
        include_once('PDF2.php');
        break;
    default:
        echo "indefinido";

}
