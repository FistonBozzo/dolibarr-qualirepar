```php
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);
define('NOREQUIREUSER', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);

echo "ETAPE 1<br>";

require_once dirname(__DIR__, 2)."/../main.inc.php";

echo "ETAPE 2<br>";

require_once DOL_DOCUMENT_ROOT."/core/lib/pdf.lib.php";

echo "ETAPE 3<br>";

if (!function_exists("pdf_getInstance")) {
    die("ERREUR : pdf_getInstance() inexistante");
}

echo "ETAPE 4<br>";

$pdf = pdf_getInstance("A4");

echo "ETAPE 5<br>";

if (!$pdf) {
    die("ERREUR : pdf_getInstance() retourne false");
}

echo "ETAPE 6<br>";

$pdf->AddPage();

echo "ETAPE 7<br>";

$pdf->SetFont(
    pdf_getPDFFont($langs),
    "",
    16
);

echo "ETAPE 8<br>";

$pdf->Cell(
    180,
    10,
    "TEST PDF ELECTROJUL",
    0,
    1,
    "C"
);

echo "ETAPE 9<br>";

$pdf->Output(
    "test-electrojul.pdf",
    "I"
);

exit;
