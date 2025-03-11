<?php
require_once("fpdf/fpdf.php");
require_once("helper/StringFunctions.php");
// ini_set('display_startup_errors', 1);
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Times', 'I', 30);
        $this->SetTextColor(123, 123, 123); // #7B7B7B em RGB
        $this->Cell(0, 15, StringFunctions::utf8Conv("Guimarães & Borges"), 0, 1, 'L');
        $this->SetFont('Times', '', 18);
        $this->SetTextColor(169, 169, 169); // rgb(169, 169, 169)
        $this->Cell(0, 10, ("Advogados Associados"), 0, 1, 'R');

        $this->SetLineWidth(0.1);
        $this->Line(10, $this->GetY(), $this->GetPageWidth() - 10, $this->GetY());
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->Output("PdfGen.pdf", "I");
