<?php
/* Copyright (C) 2026 - Générateur autonome de Fiche Tarifaire */

// 1. Chargement de l'environnement Dolibarr
// Ajustez le chemin de "main.inc.php" selon l'emplacement exact de votre fichier
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');

$res = 0;
if (file_exists('../main.inc.php')) $res = include_once '../main.inc.php';
elseif (file_exists('../../main.inc.php')) $res = include_once '../../main.inc.php';
elseif (file_exists('../../../main.inc.php')) $res = include_once '../../../main.inc.php';

if (!$res) {
    die("Erreur: Impossible de trouver le fichier main.inc.php de Dolibarr.");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

global $conf, $langs, $mysoc, $db, $user;

// Vérification des droits d'accès
if (empty($user->id)) {
    accessforbidden();
}

$outputlangs = $langs;
$outputlangs->loadLangs(array("main", "bills", "products"));

// 2. Configuration des dimensions (Format A4)
$page_largeur = 210;
$page_hauteur = 297;
$marge_gauche = 10;
$marge_droite = 10;
$marge_haute = 10;
$marge_basse = 10;
$corner_radius = 2;
$default_font_size = pdf_getPDFFontSize($outputlangs);

// 3. Création du PDF TCPDF
$pdf = pdf_getInstance($page_largeur, $page_hauteur);
$pdf->SetAutoPageBreak(true, $marge_basse);
$pdf->SetMargins($marge_gauche, $marge_haute, $marge_droite);
$pdf->AddPage();

// ------------------------------------------------------------------
// EN-TÊTE DE PAGE
// ------------------------------------------------------------------
pdf_pagehead($pdf, $outputlangs, $page_hauteur);

$posy = $marge_haute;
$w = 100;

// Logo de la société
if (!getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO') && !empty($mysoc->logo)) {
    $logodir = $conf->mycompany->dir_output;
    if (!empty($mysoc->entity) && !empty($conf->mycompany->multidir_output[$mysoc->entity])) {
        $logodir = $conf->mycompany->multidir_output[$mysoc->entity];
    }
    $logo = $logodir.'/logos/'.$mysoc->logo;
    if (is_readable($logo)) {
        $height = pdf_getHeightForLogo($logo);
        $pdf->Image($logo, $marge_gauche, $posy, 0, $height);
    }
}

// Titre du document (En haut à droite)
$posx_title = $page_largeur - $marge_droite - $w;
$pdf->SetFont('', 'B', $default_font_size + 3);
$pdf->SetXY($posx_title, $posy);
$pdf->SetTextColor(0, 0, 60);
$pdf->MultiCell($w, 4, "FICHE TARIFAIRE", '', 'R');

$pdf->SetFont('', '', $default_font_size - 2);
$posy_date = $pdf->getY() + 2;
$pdf->SetXY($posx_title, $posy_date);
$pdf->MultiCell($w, 3, $outputlangs->transnoentities("Date")." : ".dol_print_date(time(), 'day', false, $outputlangs, true), '', 'R');

// Pavé Coordonnées Émetteur (Cadre gris arrondi)
$carac_emetteur = pdf_build_address($outputlangs, $mysoc, null, '', 0, 'source');

$posy_emetteur = 42;
$hautcadre = 40;
$widthrecbox = 92;

// Libellé "Émetteur"
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('', '', $default_font_size - 2);
$pdf->SetXY($marge_gauche, $posy_emetteur - 5);
$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillFrom"), 0, 'L');

// Cadre gris d'arrière-plan
$pdf->SetFillColor(230, 230, 230);
$pdf->RoundedRect($marge_gauche, $posy_emetteur, $widthrecbox, $hautcadre, $corner_radius, '1234', 'F');

// Nom de l'entreprise
$pdf->SetTextColor(0, 0, 60);
$pdf->SetXY($marge_gauche + 2, $posy_emetteur + 3);
$pdf->SetFont('', 'B', $default_font_size);
$pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($mysoc->name), 0, 'L');

// Détails adresse émetteur
$pdf->SetXY($marge_gauche + 2, $pdf->getY());
$pdf->SetFont('', '', $default_font_size - 1);
$pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, 'L');

// ------------------------------------------------------------------
// TABLEAU DES TARIFS
// ------------------------------------------------------------------
$tab_top = 90;
$tab_width = $page_largeur - $marge_gauche - $marge_droite;

$pdf->SetXY($marge_gauche, $tab_top);
$pdf->SetFont('', 'B', $default_font_size - 1);
$pdf->SetTextColor(0, 0, 60);
$pdf->SetFillColor(220, 220, 220);

// En-têtes du tableau
$pdf->Cell(30, 7, $outputlangs->transnoentities("Ref"), 1, 0, 'L', true);
$pdf->Cell(100, 7, $outputlangs->transnoentities("Label"), 1, 0, 'L', true);
$pdf->Cell(30, 7, $outputlangs->transnoentities("PriceUHT"), 1, 0, 'R', true);
$pdf->Cell(30, 7, $outputlangs->transnoentities("VAT"), 1, 1, 'R', true);

// Requête SQL de récupération des produits
$pdf->SetFont('', '', $default_font_size - 1);
$pdf->SetTextColor(0, 0, 0);

$sql = "SELECT ref, label, price, tva_tx FROM ".MAIN_DB_PREFIX."product WHERE tosell = 1 ORDER BY ref ASC";
$resql = $db->query($sql);

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        // Saut de page automatique si besoin
        if ($pdf->getY() > ($page_hauteur - $marge_basse - 30)) {
            $pdf->AddPage();
            $pdf->SetXY($marge_gauche, $marge_haute + 10);
            
            $pdf->SetFont('', 'B', $default_font_size - 1);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->Cell(30, 7, $outputlangs->transnoentities("Ref"), 1, 0, 'L', true);
            $pdf->Cell(100, 7, $outputlangs->transnoentities("Label"), 1, 0, 'L', true);
            $pdf->Cell(30, 7, $outputlangs->transnoentities("PriceUHT"), 1, 0, 'R', true);
            $pdf->Cell(30, 7, $outputlangs->transnoentities("VAT"), 1, 1, 'R', true);
            $pdf->SetFont('', '', $default_font_size - 1);
        }

        $pdf->SetX($marge_gauche);
        $pdf->Cell(30, 6, $outputlangs->convToOutputCharset($obj->ref), 1, 0, 'L');
        $pdf->Cell(100, 6, $outputlangs->convToOutputCharset(dol_trunc($obj->label, 60)), 1, 0, 'L');
        $pdf->Cell(30, 6, price($obj->price), 1, 0, 'R');
        $pdf->Cell(30, 6, vatrate($obj->tva_tx, true), 1, 1, 'R');
    }
    $db->free($resql);
}

// ------------------------------------------------------------------
// NOTE DE BAS DE PAGE
// ------------------------------------------------------------------
$posy_note = $pdf->getY() + 8;

if ($posy_note > ($page_hauteur - $marge_basse - 25)) {
    $pdf->AddPage();
    $posy_note = $marge_haute + 10;
}

$pdf->SetXY($marge_gauche, $posy_note);
$pdf->SetFont('', 'I', $default_font_size - 2);
$pdf->SetTextColor(80, 80, 80);

$noteText = "Note : Les tarifs indiqués sur cette fiche sont donnés à titre indicatif et sont susceptibles d'être modifiés sans préavis. Nos prix s'entendent hors taxes sauf mention contraire.";
$pdf->MultiCell($tab_width, 4, $outputlangs->convToOutputCharset($noteText), 0, 'L');

// ------------------------------------------------------------------
// PIED DE PAGE ET SORTIE PDF
// ------------------------------------------------------------------
pdf_pagefoot($pdf, $outputlangs, 'FICHE_TARIF_FREE_TEXT', $mysoc, $marge_basse, $marge_gauche, $page_hauteur, null, 0, 0, $page_largeur);

// Affichage direct du PDF dans le navigateur
$pdf->Output('Fiche_Tarifaire_'.date('Y-m-d').'.pdf', 'I');
