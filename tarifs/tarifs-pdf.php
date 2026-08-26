```php
<?php
/**
 * Test génération PDF public Electrojul
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


/*
 * ==========================================================
 * ACCÈS PUBLIC
 * ==========================================================
 */

define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);
define('NOREQUIREUSER', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);


/*
 * ==========================================================
 * CHARGEMENT DOLIBARR
 * ==========================================================
 */

require_once dirname(__DIR__, 2).'/../main.inc.php';


/*
 * ==========================================================
 * CHARGEMENT PDF DOLIBARR
 * ==========================================================
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/*
 * ==========================================================
 * CRÉATION DU PDF AVEC LA MÉTHODE DOLIBARR
 * ==========================================================
 */

$pdf = pdf_getInstance('A4');


if (!$pdf) {
    http_response_code(500);
    die('Impossible de créer l\'instance PDF Dolibarr.');
}


/*
 * Configuration
 */

$pdf->SetTitle('Tarifs Electrojul');
$pdf->SetAuthor('Electrojul');

$pdf->SetMargins(15, 15, 15);

$pdf->setAutoPageBreak(true, 20);

if (class_exists('TCPDF')) {
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
}


/*
 * Nouvelle page
 */

$pdf->AddPage();


/*
 * Contenu de test
 */

$pdf->SetFont(
    pdf_getPDFFont($langs),
    '',
    16
);

$pdf->SetTextColor(0, 0, 60);

$pdf->SetXY(15, 20);

$pdf->Cell(
    180,
    10,
    'TARIFS ELECTROJUL',
    0,
    1,
    'C'
);


$pdf->SetFont(
    pdf_getPDFFont($langs),
    '',
    10
);

$pdf->SetTextColor(40, 40, 40);

$pdf->SetXY(15, 40);

$pdf->MultiCell(
    180,
    6,
    'Test de génération PDF publique Dolibarr.',
    0,
    'L'
);


/*
 * ==========================================================
 * SORTIE
 * ==========================================================
 */

$pdf->Output(
    'tarifs-electrojul.pdf',
    'I'
);

exit;
```
