<?php
/**
 * Fiche tarifaire publique ElectroJul
 *
 * Fichier : htdocs/custom/qualirepar/tarifs-pdf.php
 * Accès direct sans connexion Dolibarr.
 */

define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);
define('NOREQUIREUSER', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);
define('NOREQUIREAJAX', 1);

require_once dirname(__DIR__, 2).'/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/qualirepar/class/tarifs.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

global $conf, $langs, $mysoc, $db;

$langs->loadLangs(array('main', 'companies', 'propal', 'bills'));
$outputlangs = clone $langs;
$outputlangs->setDefaultLang('fr_FR');

$mysoc->fetch(1);

$tarifsObj = new QualiReparTarifs($db);
$tarifs = $tarifsObj->getTarifs();
$dateMiseAJour = $tarifsObj->getDateMiseAJour();

// Même mécanisme que les modèles PDF Dolibarr : le format est fourni à pdf_getInstance().
$format = pdf_getFormat($outputlangs);
$page_largeur = $format['width'];
$page_hauteur = $format['height'];
$pdf = pdf_getInstance($format);

if (!$pdf) {
    die('Impossible de créer le moteur PDF Dolibarr.');
}

$pdf->SetTitle('Tarifs - '.($mysoc->name ?: 'ElectroJul'));
$pdf->SetAuthor($mysoc->name ?: 'ElectroJul');
$pdf->SetSubject('Tarifs');
$pdf->SetCreator('Dolibarr');
$pdf->setAutoPageBreak(true, 0);

if (class_exists('TCPDF')) {
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
}

$pdf->AddPage();

// En-tête Dolibarr : logo et société.
pdf_pagehead($pdf, $outputlangs, $page_hauteur);

$font = pdf_getPDFFont($outputlangs);
$default_font_size = pdf_getPDFFontSize($outputlangs);

$pdf->SetTextColor(0, 0, 60);
$pdf->SetFont($font, 'B', $default_font_size + 6);
$pdf->SetXY(15, 48);
$pdf->Cell($page_largeur - 30, 9, "TARIFS", 0, 1, "C");

$pdf->SetFont($font, "", $default_font_size);
$pdf->SetTextColor(80, 80, 80);
$pdf->SetXY(15, 58);
$pdf->Cell($page_largeur - 30, 5, "Tarifs publics ElectroJul", 0, 1, "C");

if (!empty($dateMiseAJour)) {
    $pdf->SetXY(15, 63);
    $pdf->Cell($page_largeur - 30, 5, "Dernière mise à jour : ".$dateMiseAJour, 0, 1, "C");
}

$left = 20;
$right = 20;
$tableWidth = $page_largeur - $left - $right;
$nameWidth = $tableWidth - 45;
$priceWidth = 45;
$y = 75;

$pdf->SetFillColor(224, 224, 224);
$pdf->SetTextColor(0, 0, 60);
$pdf->SetFont($font, "B", $default_font_size);
$pdf->SetXY($left, $y);
$pdf->Cell($nameWidth, 8, "Prestation", 1, 0, "L", true);
$pdf->Cell($priceWidth, 8, "Tarif TTC", 1, 1, "R", true);

$pdf->SetFont($font, "", $default_font_size);
$pdf->SetTextColor(0, 0, 0);

foreach ($tarifs as $tarif) {
    $label = (string) ($tarif['label'] ?? '');
    $priceTtc = (float) ($tarif['price_ttc'] ?? 0);

    $pdf->SetX($left);
    $pdf->Cell($nameWidth, 8, $label, 1, 0, "L");
    $pdf->Cell($priceWidth, 8, price($priceTtc, 2, $outputlangs), 1, 1, "R");
}

$y = $pdf->GetY() + 8;
$pdf->SetFont($font, "", $default_font_size - 1);
$pdf->SetTextColor(60, 60, 60);
$pdf->SetXY($left, $y);
$pdf->MultiCell(
    $tableWidth,
    5,
    "Le forfait est dû lors d'une réparation réussie.\nLes pièces sont garanties 3 mois dans le cadre d'une utilisation normale.",
    0,
    "L"
);

// Pied de page natif Dolibarr.
pdf_pagefoot(
    $pdf,
    $outputlangs,
    "PROPOSAL_FREE_TEXT",
    $mysoc,
    10,
    10,
    $page_hauteur,
    null,
    0,
    0,
    $page_largeur,
    ""
);

$pdf->Output("tarifs-electrojul.pdf", "I");
exit;
