<?php
/**
 * Fiche tarifaire PDF publique Electrojul
 *
 * Accessible sans connexion Dolibarr.
 *
 * URL :
 * https://electrojul.duckdns.org/custom/qualirepar/tarifs/tarifs-pdf.php
 */

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

/*
 * Accès public
 */
define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);
define('NOREQUIREUSER', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);


/*
 * Fuseau horaire
 */
date_default_timezone_set('Europe/Paris');


/*
 * Charger Dolibarr
 */
require_once dirname(__DIR__, 2).'/../main.inc.php';


/*
 * Charger les fonctions PDF Dolibarr
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/*
 * Charger la classe des tarifs QualiRépar
 */
require_once DOL_DOCUMENT_ROOT.'/custom/qualirepar/class/tarifs.class.php';


/*
 * Vérification de la fonction PDF
 */
if (!function_exists('pdf_getInstance')) {
    http_response_code(500);
    exit('Impossible de charger le moteur PDF.');
}


/*
 * Récupération des tarifs
 */
$tarifsObj = new QualiReparTarifs($db);

$tarifs = $tarifsObj->getTarifs();

$dateMiseAJour = $tarifsObj->getDateMiseAJour();


/*
 * Création du PDF
 */
$pdf = pdf_getInstance('A4');

if (!$pdf) {
    http_response_code(500);
    exit('Impossible de créer le PDF.');
}


/*
 * Configuration du document
 */
$pdf->SetCreator('Electrojul');
$pdf->SetAuthor('Electrojul');
$pdf->SetTitle('Tarifs Electrojul');
$pdf->SetSubject('Tarifs des prestations Electrojul');

$pdf->SetMargins(18, 18, 18);
$pdf->SetAutoPageBreak(true, 18);

$pdf->AddPage();


/*
 * Police
 */
$font = pdf_getPDFFont($langs);


/*
 * ==========================================================
 * ENTÊTE TYPE DOLIBARR
 * ==========================================================
 */

$marginleft = 18;
$margintop  = 18;

$pageWidth = $pdf->getPageWidth();

$pdf->SetDrawColor(210,210,210);
$pdf->SetFillColor(255,255,255);

$pdf->Rect(
    $marginleft,
    $margintop,
    $pageWidth - ($marginleft * 2),
    45
);


/*
 * Titre
 */
$pdf->SetXY($marginleft, $margintop + 6);

$pdf->SetFont($font,'B',18);

$pdf->Cell(
    $pageWidth - ($marginleft * 2),
    8,
    'FICHE TARIFAIRE',
    0,
    1,
    'C'
);


/*
 * Sous-titre
 */
$pdf->SetFont($font,'',10);

$pdf->Cell(
    $pageWidth - ($marginleft * 2),
    6,
    'Prestations et forfaits Electrojul',
    0,
    1,
    'C'
);

$pdf->Ln(18);


/*
 * Fonction d'écriture d'un tarif
 */
foreach ($tarifs as $tarif) {

    $nom = $tarif['label'] ?? '';

    $prix = isset($tarif['price_ttc'])
        ? (float) $tarif['price_ttc']
        : 0;


    /*
     * Cadre du tarif
     */
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    $largeur = 174;
    $hauteur = 22;


    /*
     * Fond blanc + bordure
     */
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetDrawColor(225, 225, 225);

    $pdf->RoundedRect(
        $x,
        $y,
        $largeur,
        $hauteur,
        3,
        '1111',
        'DF'
    );


    /*
     * Nom du tarif
     */
    $pdf->SetXY($x + 5, $y + 5);

    $pdf->SetFont($font, 'B', 11);

    $pdf->MultiCell(
        125,
        6,
        $nom,
        0,
        'L',
        false,
        0
    );


    /*
     * Prix
     */
    $pdf->SetXY($x + 135, $y + 6);

    $pdf->SetFont($font, 'B', 13);

    $prixTexte = number_format(
        $prix,
        2,
        ',',
        ' '
    ).' EUR TTC';

    $pdf->Cell(
        34,
        8,
        $prixTexte,
        0,
        0,
        'R'
    );


    /*
     * Espace entre les tarifs
     */
    $pdf->SetY($y + $hauteur + 5);
}


/*
 * Mention tarifaire
 */
$pdf->Ln(5);

$pdf->SetFont($font, 'B', 9);

$mention1 =
    "Le forfait total est dû lors d'une réparation réussie.\n".
    "Le déplacement reste dû dans tous les cas.";

$pdf->MultiCell(
    0,
    5,
    $mention1,
    0,
    'C'
);

$pdf->Ln(2);

$pdf->SetFont($font, '', 9);

$pdf->MultiCell(
    0,
    5,
    "Les pièces sont garanties 3 mois dans le cadre d'une utilisation normale.",
    0,
    'C'
);


/*
 * Séparateur
 */
$pdf->Ln(5);

$pdf->SetDrawColor(225, 225, 225);

$pdf->Line(
    45,
    $pdf->GetY(),
    165,
    $pdf->GetY()
);


/*
 * Date de mise à jour
 */
if (!empty($dateMiseAJour)) {

    $timestamp = strtotime($dateMiseAJour);

    if ($timestamp !== false) {

        $dateTexte = 'Tarifs mis à jour le '.date(
            'd/m/Y à H:i',
            $timestamp
        );

        $pdf->Ln(5);

        $pdf->SetFont($font, '', 8);

        $pdf->SetTextColor(110, 110, 110);

        $pdf->Cell(
            0,
            5,
            $dateTexte,
            0,
            1,
            'C'
        );
    }
}


/*
 * Remettre la couleur du texte normale
 */
$pdf->SetTextColor(0, 0, 0);


/*
 * Génération du PDF
 *
 * IMPORTANT :
 * aucun echo / HTML / texte ne doit être envoyé
 * avant cette instruction.
 */
$pdf->Output(
    'tarifs-electrojul.pdf',
    'I'
);

exit;
