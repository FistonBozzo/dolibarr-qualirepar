<?php
/* Copyright (C) 2026 - Fiche Tarifaire Publique Electrojul */

// 1. DÉCLARATIONS POUR ACCÈS PUBLIC (Sans authentification requis)
define('NOLOGIN', '1'); 
define('NOCSRFCHECK', '1');
define('NOTOKENRENEWAL', '1');

// 2. Chargement de l'environnement Dolibarr
$res = 0;
if (file_exists('../main.inc.php')) $res = include_once '../main.inc.php';
elseif (file_exists('../../main.inc.php')) $res = include_once '../../main.inc.php';
elseif (file_exists('../../../main.inc.php')) $res = include_once '../../../main.inc.php';

if (!$res) {
    die("Erreur: Impossible de trouver le fichier main.inc.php de Dolibarr.");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

global $conf, $langs, $mysoc, $db;

$outputlangs = $langs;
$outputlangs->loadLangs(array("main", "bills", "products"));

$page_largeur = 210;
$page_hauteur = 297;
$marge_gauche = 10;
$marge_droite = 10;
$marge_haute = 10;
$marge_basse = 10;
$corner_radius = 2;

// Initialisation du PDF au format A4
$pdf = pdf_getInstance('A4');
$default_font_size = pdf_getPDFFontSize($outputlangs);

$pdf->SetAutoPageBreak(true, $marge_basse + 22);
$pdf->SetMargins($marge_gauche, $marge_haute, $marge_droite);
$pdf->AddPage();

// ------------------------------------------------------------------
// EN-TÊTE DE PAGE (Sans ligne supérieure)
// ------------------------------------------------------------------
pdf_pagehead($pdf, $outputlangs, $page_hauteur);

$posy = $marge_haute;
$w = 100;

// Logo de l'entreprise
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

// Titre du document (Haut Droite)
$posx_title = $page_largeur - $marge_droite - $w;
$pdf->SetFont('', 'B', $default_font_size + 3);
$pdf->SetXY($posx_title, $posy);
$pdf->SetTextColor(0, 0, 60);
$pdf->MultiCell($w, 4, "FICHE TARIFAIRE", '', 'R');

$pdf->SetFont('', '', $default_font_size - 2);
$posy_date = $pdf->getY() + 2;
$pdf->SetXY($posx_title, $posy_date);
$pdf->MultiCell($w, 3, $outputlangs->transnoentities("Date")." : ".dol_print_date(time(), 'day', false, $outputlangs, true), '', 'R');

// Pavé Émetteur (Cadre gris arrondi identique aux devis)
$carac_emetteur = pdf_build_address($outputlangs, $mysoc, null, '', 0, 'source');

$posy_emetteur = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 40 : 42;
$hautcadre = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 38 : 40;
$widthrecbox = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 92 : 82;

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('', '', $default_font_size - 2);
$pdf->SetXY($marge_gauche, $posy_emetteur - 5);
$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillFrom"), 0, 'L');

// Rectangle gris d'arrière-plan
$pdf->SetFillColor(230, 230, 230);
$pdf->RoundedRect($marge_gauche, $posy_emetteur, $widthrecbox, $hautcadre, $corner_radius, '1234', 'F');

// Coordonnées émetteur
$pdf->SetTextColor(0, 0, 60);
$pdf->SetXY($marge_gauche + 2, $posy_emetteur + 3);
$pdf->SetFont('', 'B', $default_font_size);
$pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($mysoc->name), 0, 'L');

$pdf->SetXY($marge_gauche + 2, $pdf->getY());
$pdf->SetFont('', '', $default_font_size - 1);
$pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, 'L');

// ------------------------------------------------------------------
// TABLEAU DES TARIFS
// ------------------------------------------------------------------
$tab_top = 95;
$pdf->SetXY($marge_gauche, $tab_top);
$pdf->SetFont('', 'B', $default_font_size - 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFillColor(235, 235, 240);

// Structure des en-têtes
$pdf->Cell(30, 7, $outputlangs->transnoentities("Ref"), 1, 0, 'C', true);
$pdf->Cell(100, 7, $outputlangs->transnoentities("Label"), 1, 0, 'C', true);
$pdf->Cell(30, 7, $outputlangs->transnoentities("PriceUHT"), 1, 0, 'C', true);
$pdf->Cell(30, 7, $outputlangs->transnoentities("VAT"), 1, 1, 'C', true);

$pdf->SetFont('', '', $default_font_size - 1);

// Extraction des produits/prestations en vente
$sql = "SELECT ref, label, price, tva_tx FROM ".MAIN_DB_PREFIX."product WHERE tosell = 1 ORDER BY ref ASC";
$resql = $db->query($sql);

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        if ($pdf->getY() > ($page_hauteur - $marge_basse - 40)) {
            $pdf->AddPage();
            $pdf->SetXY($marge_gauche, $marge_haute + 10);
            
            $pdf->SetFont('', 'B', $default_font_size - 1);
            $pdf->Cell(30, 7, $outputlangs->transnoentities("Ref"), 1, 0, 'C', true);
            $pdf->Cell(100, 7, $outputlangs->transnoentities("Label"), 1, 0, 'C', true);
            $pdf->Cell(30, 7, $outputlangs->transnoentities("PriceUHT"), 1, 0, 'C', true);
            $pdf->Cell(30, 7, $outputlangs->transnoentities("VAT"), 1, 1, 'C', true);
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
// MENTIONS ET CONDITIONS SOUS LE TABLEAU
// ------------------------------------------------------------------
$posy_note = $pdf->getY() + 6;
$tab_width = $page_largeur - $marge_gauche - $marge_droite;

if ($posy_note > ($page_hauteur - $marge_basse - 35)) {
    $pdf->AddPage();
    $posy_note = $marge_haute + 10;
}

$pdf->SetXY($marge_gauche, $posy_note);
$pdf->SetFont('', '', $default_font_size - 2);
$pdf->SetTextColor(0, 0, 0);

$noteText = "Le forfait total est dû lors d'une réparation réussie. *Le déplacement reste dû dans tous les cas\n";
$noteText .= "Les pièces sont garanties 3 mois dans me cadre d'une utilisation normale.\n";
$noteText .= "Dernière mise à jour : 25/08/2026 16:09";

$pdf->MultiCell($tab_width, 4, $outputlangs->convToOutputCharset($noteText), 0, 'L');

// ------------------------------------------------------------------
// PIED DE PAGE AVEC LIEN VERS LE NOUVEAU SITE
// ------------------------------------------------------------------
$tab_width = $page_largeur - $marge_gauche - $marge_droite;
$url_tarifs = "https://electrojul69.gitlab.io/silex_Electrojul/#tarifs";

// 1. Ligne de paiement
$pdf->SetXY($marge_gauche, $page_hauteur - $marge_basse - 16);
$pdf->SetFont('', '', $default_font_size - 3);
$pdf->SetTextColor(80, 80, 80);
$pdf->MultiCell($tab_width, 3, $outputlangs->convToOutputCharset("Paiement par espèces, chèque, carte bancaire ou virement"), 0, 'C');

// 2. Ligne avec lien web cliquable
$pdf->SetXY($marge_gauche, $pdf->getY());
$pdf->SetFont('', '', $default_font_size - 3);
$pdf->WriteHTMLCell($tab_width, 3, $marge_gauche, $pdf->getY(), 'Tarifs des prestations : <a href="'.$url_tarifs.'" style="color:#0000FF; text-decoration:underline;">Consulter mes tarifs à jour sur mon site internet</a>', 0, 1, false, true, 'C');

// 3. Ligne SIRET / NAF
$pdf->SetXY($marge_gauche, $pdf->getY());
$pdf->MultiCell($tab_width, 3, $outputlangs->convToOutputCharset("SIRET: 808 240 048 00047   -   NAF-APE: 95.22Z"), 0, 'C');

// Numérotation de page (Bas Droite)
$pdf->SetXY($page_largeur - $marge_droite - 20, $page_hauteur - $marge_basse - 4);
$pdf->MultiCell(20, 3, $pdf->PageNo().' / '.$pdf->getAliasNbPages(), 0, 'R');

// ------------------------------------------------------------------
// GENERATION PDF
// ------------------------------------------------------------------
$pdf->Output('Fiche_Tarifaire.pdf', 'I');
