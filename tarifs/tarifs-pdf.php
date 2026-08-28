<?php
/**
 * Fiche tarifaire PDF publique - ElectroJul
 * Fichier : htdocs/custom/qualirepar/tarifs-pdf.php
 */

date_default_timezone_set('Europe/Paris');

/*
 * Ce fichier est volontairement appelable directement par URL.
 * Il ne contient donc PAS le classique :
 * die('This file must be called from Dolibarr');
 */
define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);
define('NOREQUIREUSER', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);

/*
 * Chargement standard recommandé par Dolibarr pour un fichier de module.
 * Cette méthode fonctionne que le module soit dans /custom/qualirepar
 * ou directement dans /htdocs/qualirepar.
 */
$res = 0;
if (!$res && !empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) {
    $res = @include $_SERVER['CONTEXT_DOCUMENT_ROOT'].'/main.inc.php';
}

$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] === $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, $i + 1).'/main.inc.php')) {
    $res = @include substr($tmp, 0, $i + 1).'/main.inc.php';
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, $i + 1)).'/main.inc.php')) {
    $res = @include dirname(substr($tmp, 0, $i + 1)).'/main.inc.php';
}
if (!$res && file_exists(__DIR__.'/../../main.inc.php')) {
    $res = @include __DIR__.'/../../main.inc.php';
}
if (!$res && file_exists(__DIR__.'/../../../main.inc.php')) {
    $res = @include __DIR__.'/../../../main.inc.php';
}
if (!$res) {
    http_response_code(500);
    die('Impossible de charger Dolibarr.');
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/qualirepar/class/tarifs.class.php';

/* Langue PDF */
$outputlangs = new Translate('', $conf);
$outputlangs->setDefaultLang('fr_FR');
$outputlangs->loadLangs(array('main', 'companies', 'bills', 'propal'));

/* Société émettrice */
$mysoc = new Societe($db);
$mysoc->fetch($conf->entity);

/* Tarifs du module */
$tarifsObj = new QualiReparTarifs($db);
$tarifs = $tarifsObj->getTarifs();
$dateMiseAJour = $tarifsObj->getDateMiseAJour();

usort($tarifs, static function ($a, $b) {
    return ((int) ($a['ordre'] ?? 0)) <=> ((int) ($b['ordre'] ?? 0));
});

/* PDF A4 portrait */
$pdf = pdf_getInstance('A4', 'P', 'mm', true, 'UTF-8', false);
$default_font_size = pdf_getPDFFontSize($outputlangs);

$pdf->SetCreator('Dolibarr');
$pdf->SetAuthor($mysoc->name);
$pdf->SetTitle('Tarifs - '.$mysoc->name);
$pdf->SetSubject('Fiche tarifaire');
$pdf->SetKeywords('tarifs, ElectroJul, QualiRépar');
$pdf->SetMargins(15, 48, 15);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);
$pdf->SetAutoPageBreak(true, 28);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AliasNbPages();

$pdf->AddPage();

/* =========================
 * EN-TÊTE façon PDF Dolibarr
 * ========================= */
pdf_pagehead($pdf, $outputlangs, 297);

$pdf->SetTextColor(0, 0, 60);
$pdf->SetFont('', 'B', $default_font_size + 3);

$w = 100;
$posy = 12;
$posx = 210 - 15 - $w;

/* Logo */
if (!getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO') && !empty($mysoc->logo)) {
    $logodir = $conf->mycompany->dir_output;
    if (!empty($conf->mycompany->multidir_output[$conf->entity])) {
        $logodir = $conf->mycompany->multidir_output[$conf->entity];
    }

    if (!getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO') && !empty($mysoc->logo_small)) {
        $logo = $logodir.'/logos/thumbs/'.$mysoc->logo_small;
    } else {
        $logo = $logodir.'/logos/'.$mysoc->logo;
    }

    if (is_readable($logo)) {
        $height = pdf_getHeightForLogo($logo);
        $pdf->Image($logo, 15, $posy, 0, min($height, 28));
    }
}

/* Titre */
$pdf->SetXY($posx, $posy);
$pdf->MultiCell($w, 5, 'TARIFS', 0, 'R');

$pdf->SetFont('', '', $default_font_size - 1);
$pdf->SetXY($posx, $posy + 8);

$coordonnees = $mysoc->name;
if (!empty($mysoc->address)) {
    $coordonnees .= "\n".$mysoc->address;
}
if (!empty($mysoc->zip) || !empty($mysoc->town)) {
    $coordonnees .= "\n".trim($mysoc->zip.' '.$mysoc->town);
}
if (!empty($mysoc->phone)) {
    $coordonnees .= "\nTél. : ".$mysoc->phone;
}
if (!empty($mysoc->email)) {
    $coordonnees .= "\n".$mysoc->email;
}

$pdf->MultiCell($w, 4, $coordonnees, 0, 'R');

$pdf->SetDrawColor(128, 128, 128);
$pdf->Line(15, 43, 195, 43);

/* =========================
 * TITRE
 * ========================= */
$pdf->SetTextColor(0, 0, 60);
$pdf->SetFont('', 'B', $default_font_size + 2);
$pdf->SetXY(15, 52);
$pdf->MultiCell(180, 6, 'NOS TARIFS', 0, 'L');

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('', '', $default_font_size - 1);
$pdf->SetXY(15, 60);

$texteDate = 'Tarifs en vigueur';
if (!empty($dateMiseAJour)) {
    $texteDate .= ' - Mise à jour : '.$dateMiseAJour;
}
$pdf->MultiCell(180, 5, $texteDate, 0, 'L');

/* =========================
 * TABLEAU DES TARIFS
 * ========================= */
$y = 70;
$left = 15;
$labelWidth = 130;
$priceWidth = 50;
$rowHeight = 9;

$pdf->SetXY($left, $y);
$pdf->SetFillColor(224, 224, 224);
$pdf->SetTextColor(0, 0, 60);
$pdf->SetFont('', 'B', $default_font_size);
$pdf->Cell($labelWidth, $rowHeight, 'Prestation', 1, 0, 'L', true);
$pdf->Cell($priceWidth, $rowHeight, 'Tarif TTC', 1, 1, 'R', true);

$pdf->SetFont('', '', $default_font_size);
$pdf->SetTextColor(0, 0, 0);

foreach ($tarifs as $tarif) {
    $label = (string) ($tarif['label'] ?? '');
    $prix = (float) ($tarif['price_ttc'] ?? 0);

    $pdf->SetFillColor(255, 255, 255);
    $pdf->Cell($labelWidth, $rowHeight, $label, 1, 0, 'L', true);
    $pdf->Cell($priceWidth, $rowHeight, price($prix, 0, $outputlangs).' TTC', 1, 1, 'R', true);
}

/* =========================
 * INFORMATIONS
 * ========================= */
$y = $pdf->GetY() + 8;
$pdf->SetTextColor(0, 0, 60);
$pdf->SetFont('', 'B', $default_font_size);
$pdf->SetXY(15, $y);
$pdf->MultiCell(180, 5, 'Informations', 0, 'L');

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('', '', $default_font_size - 1);
$y = $pdf->GetY() + 2;

$mentions = array(
    'Le forfait est dû lors d’une réparation réussie.',
    'Les pièces sont garanties 3 mois dans le cadre d’une utilisation normale.',
    'Les tarifs affichés sont TTC.'
);

foreach ($mentions as $mention) {
    $pdf->SetXY(18, $y);
    $pdf->MultiCell(177, 5, '• '.$mention, 0, 'L');
    $y = $pdf->GetY();
}

/* =========================
 * PIED DE PAGE DOlIBARR
 * ========================= */
$showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', 0);

pdf_pagefoot(
    $pdf,
    $outputlangs,
    'PROPOSAL_FREE_TEXT',
    $mysoc,
    15,
    15,
    297,
    null,
    $showdetails,
    0,
    210,
    ''
);

/* Sortie directe dans le navigateur */
$nomFichier = 'tarifs';
if (!empty($mysoc->name)) {
    $nomFichier .= '-'.dol_sanitizeFileName($mysoc->name);
}
$nomFichier .= '.pdf';

$pdf->Output($nomFichier, 'I');
exit;
