<?php

require_once("clsGen/clsMsDocGenerator.php");
$doc = new clsMsDocGenerator();
$textHeader = array(
    'text-align' => 'left',
    'font-weight' => 'normal',
    'font-size' => '30pt',
    'font-style' => 'italic',
    'font-family' => 'serif',
    'color' => '#7B7B7B'
);

$secondTextHeader = array(
    'text-align' => 'right',
    'font-weight' => 'lighter',
    'font-size' => '18pt',
    'font-style' => 'normal',
    'font-family' => 'serif',
    'color' => 'rgb(169, 169, 169)'
);
$lineStyle = array(
    'border-bottom' => '1px solid #000000',
);

$doc->addParagraph("Guimar&atilde;es & Borges", $textHeader);
$doc->addParagraph("Advogados Associados", $secondTextHeader);
$doc->addParagraph("", $lineStyle);

$doc->output("docGen.doc");
