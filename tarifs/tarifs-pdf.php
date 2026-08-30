<?php
/* Copyright (C) 2026 - Fiche Tarifaire Publique Electrojul */

define('NOLOGIN', '1'); 
define('NOCSRFCHECK', '1');
define('NOTOKENRENEWAL', '1');

// Chargement de l'environnement Dolibarr
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

// ------------------------------------------------------------------
// RÉCUPÉRATION DE LA DATE DE MAJ DEPUIS L'API TARIFS (Identique au site)
// ------------------------------------------------------------------
date_default_timezone_set('Europe/Paris');
$apiUrl = 'https://electrojul.duckdns.org/custom/qualirepar/api/tarifs.php';

$date_derniere_maj = '';

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Évite les blocages de certificat local
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$json = curl_exec($ch);
curl_close($ch);

if ($json !== false && !empty($json)) {
    $dataAPI = json_decode($json, true);
    if (is_array($dataAPI) && !empty($dataAPI['updated_at'])) {
        // Même formatage de date que sur votre site HTML
        $date_derniere_maj = date('d/m/Y à H:i', strtotime($dataAPI['updated_at']));
    }
}

// Sécurité si l'API ne répond pas
if (empty($date_derniere_maj)) {
    $date_derniere_maj = date('d/m/Y à H:i');
}

// ------------------------------------------------------------------
// CONFIGURATION PDF
// ------------------------------------------------------------------
$page_largeur = 210;
$page_hauteur = 297;
$marge_gauche = 10;
$marge_droite = 10;
$marge_haute = 10;
$marge_basse = 10;
$corner_radius = 2;

$pdf = pdf_getInstance('A4');
$default_font_size = pdf_getPDFFontSize($outputlangs);

$pdf->SetAutoPageBreak(false);
$pdf->SetMargins($marge_gauche, $marge_haute, $marge_droite);
$pdf->AddPage();

// ------------------------------------------------------------------
// EN-TÊTE DE PAGE
// ------------------------------------------------------------------
$posy = $marge_haute;
$w = 100;

// Logo
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

// Titre + Date du jour
$posx_title = $page_largeur - $marge_droite - $w;
$pdf->SetFont('', 'B', $default_font_size + 3);
$pdf->SetXY($posx_title, $posy);
$pdf->SetTextColor(0, 0, 60);
$pdf->MultiCell($w, 4, "FICHE TARIFAIRE", '', 'R');

$pdf->SetFont('', '', $default_font_size - 2);
$posy_date = $pdf->getY() + 2;
$pdf->SetXY($posx_title, $posy_date);
$pdf->MultiCell($w, 3, $outputlangs->transnoentities("Date")." : ".dol_print_date(time(), 'day', false, $outputlangs, true), '', 'R');

// Pavé Émetteur
$carac_emetteur = pdf_build_address($outputlangs, $mysoc, null, '', 0, 'source');

$posy_emetteur = 40;
$hautcadre = 38;
$widthrecbox = 82;

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('', '', $default_font_size - 2);
$pdf->SetXY($marge_gauche, $posy_emetteur - 5);
$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillFrom"), 0, 'L');

$pdf->SetFillColor(230, 230, 230);
$pdf->RoundedRect($marge_gauche, $posy_emetteur, $widthrecbox, $hautcadre, $corner_radius, '1234', 'F');

$pdf->SetTextColor(0, 0, 60);
$pdf->SetXY($marge_gauche + 2, $posy_emetteur + 3);
$pdf->SetFont('', 'B', $default_font_size);
$pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($mysoc->name), 0, 'L');

$pdf->SetXY($marge_gauche + 2, $pdf->getY());
$pdf->SetFont('', '', $default_font_size - 1);
$pdf->MultiCell($widthrecbox - 2, 3.5, $carac_emetteur, 0, 'L');

// ------------------------------------------------------------------
// TABLEAU DES TARIFS
// ------------------------------------------------------------------
$tab_top = 85;
$tab_width = $page_largeur - $marge_gauche - $marge_droite;

$pdf->SetXY($marge_gauche, $tab_top);
$pdf->SetFont('', 'B', $default_font_size - 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFillColor(230, 230, 230);

$pdf->Cell(150, 6, $outputlangs->transnoentities("Designation"), 1, 0, 'L', true);
$pdf->Cell(40, 6, "Tarif TTC", 1, 1, 'C', true);

$pdf->SetFont('', '', $default_font_size - 1);

$sql = "SELECT label, price_ttc FROM ".MAIN_DB_PREFIX."product WHERE tosell = 1 ORDER BY ref ASC";
$resql = $db->query($sql);

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $pdf->SetX($marge_gauche);
        $pdf->Cell(150, 5.5, $outputlangs->convToOutputCharset(dol_trunc($obj->label, 75)), 1, 0, 'L');
        $pdf->Cell(40, 5.5, price($obj->price_ttc), 1, 1, 'R');
    }
    $db->free($resql);
}

// ------------------------------------------------------------------
// MENTIONS CENTRÉES SOUS LE TABLEAU
// ------------------------------------------------------------------
$posy_note = $pdf->getY() + 5;
$pdf->SetXY($marge_gauche, $posy_note);

// Ligne 1 : En GRAS
$pdf->SetFont('', 'B', $default_font_size - 2);
$pdf->MultiCell($tab_width, 4, $outputlangs->convToOutputCharset("Le forfait total est dû lors d'une réparation réussie. *Le déplacement reste dû dans tous les cas"), 0, 'C');

// Lignes 2 & 3 : Texte normal + Date récupérée via l'API
$pdf->SetFont('', '', $default_font_size - 2);
$pdf->MultiCell($tab_width, 4, $outputlangs->convToOutputCharset("Les pièces sont garanties 3 mois dans le cadre d'une utilisation normale."), 0, 'C');
$pdf->MultiCell($tab_width, 4, $outputlangs->convToOutputCharset("Tarifs mis à jour le : ".$date_derniere_maj), 0, 'C');

// ------------------------------------------------------------------
// PIED DE PAGE PERSONNALISÉ
// ------------------------------------------------------------------
$url_tarifs = "https://electrojul69.gitlab.io/silex_Electrojul/#tarifs";

$pdf->SetXY($marge_gauche, $page_hauteur - $marge_basse - 14);
$pdf->SetFont('', '', $default_font_size - 3);
$pdf->SetTextColor(80, 80, 80);

$pdf->MultiCell($tab_width, 3, $outputlangs->convToOutputCharset("Paiement par espèces, chèque, carte bancaire ou virement"), 0, 'C');

$pdf->SetXY($marge_gauche, $pdf->getY());
$pdf->WriteHTMLCell($tab_width, 3, $marge_gauche, $pdf->getY(), 'Tarifs des prestations : <a href="'.$url_tarifs.'" style="color:#0000FF; text-decoration:underline;">Consulter mes tarifs à jour sur mon site internet</a>', 0, 1, false, true, 'C');

$pdf->SetXY($marge_gauche, $pdf->getY());
$pdf->MultiCell($tab_width, 3, $outputlangs->convToOutputCharset("SIRET: 808 240 048 00047   -   NAF-APE: 95.22Z"), 0, 'C');

// Numérotation
$pdf->SetXY($page_largeur - $marge_droite - 20, $page_hauteur - $marge_basse - 4);
$pdf->MultiCell(20, 3, '1 / 1', 0, 'R');

// Affichage PDF
$pdf->Output('Fiche_Tarifaire.pdf', 'I');
