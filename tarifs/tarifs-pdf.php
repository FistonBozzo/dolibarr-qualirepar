```php
<?php
/**
 * Fiche tarifaire publique Electrojul
 *
 * Génère un PDF public à partir des tarifs QualiRépar.
 *
 * URL :
 * https://electrojul.duckdns.org/custom/qualirepar/tarifs/tarifs-pdf.php
 */


/*
 * ==========================================================
 * 1. ACCÈS PUBLIC
 * ==========================================================
 */

define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);
define('NOREQUIREUSER', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);


/*
 * ==========================================================
 * 2. CHARGEMENT DE DOLIBARR
 * ==========================================================
 */

require_once dirname(__DIR__, 2).'/../main.inc.php';


/*
 * ==========================================================
 * 3. CHARGEMENT DE LA CLASSE DES TARIFS
 * ==========================================================
 */

require_once DOL_DOCUMENT_ROOT.'/custom/qualirepar/class/tarifs.class.php';


/*
 * ==========================================================
 * 4. RÉCUPÉRATION DES TARIFS
 * ==========================================================
 */

$tarifsObj = new QualiReparTarifs($db);

$tarifs = $tarifsObj->getTarifs();

$dateMiseAJour = $tarifsObj->getDateMiseAJour();


/*
 * ==========================================================
 * 5. CHARGEMENT DES CLASSES PDF
 * ==========================================================
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/*
 * TCPDF est normalement chargé par Dolibarr.
 * Vérification avant utilisation.
 */

if (!class_exists('TCPDF')) {
    http_response_code(500);
    die('Erreur : la classe TCPDF n\'est pas disponible.');
}


/*
 * ==========================================================
 * 6. INITIALISATION
 * ==========================================================
 */

date_default_timezone_set('Europe/Paris');

$langs->loadLangs(array('main', 'companies'));


/*
 * ==========================================================
 * 7. CRÉATION DU PDF
 * ==========================================================
 */

$pdf = new TCPDF(
    'P',
    'mm',
    'A4',
    true,
    'UTF-8',
    false
);


/*
 * Informations du document
 */

$pdf->SetCreator('Dolibarr');
$pdf->SetAuthor(
    !empty($conf->global->MAIN_INFO_SOCIETE_NOM)
        ? $conf->global->MAIN_INFO_SOCIETE_NOM
        : 'Electrojul'
);
$pdf->SetTitle('Tarifs Electrojul');
$pdf->SetSubject('Fiche tarifaire');
$pdf->SetKeywords('Electrojul, tarifs, réparation, électroménager');


/*
 * ==========================================================
 * 8. CONFIGURATION DE LA PAGE
 * ==========================================================
 */

$pdf->SetMargins(15, 15, 15);

$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(10);

$pdf->SetAutoPageBreak(true, 20);

$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);

$pdf->AddPage();


/*
 * ==========================================================
 * 9. COULEURS
 * ==========================================================
 */

$bleu = array(0, 0, 60);
$gris = array(100, 100, 100);
$grisClair = array(230, 230, 230);
$grisTresClair = array(248, 248, 248);
$blanc = array(255, 255, 255);


/*
 * ==========================================================
 * 10. LOGO ENTREPRISE
 * ==========================================================
 */

$logoExiste = false;

if (!empty($conf->mycompany->dir_output)) {

    $logodir = $conf->mycompany->dir_output;

    if (!empty($conf->mycompany->multidir_output[$conf->entity])) {
        $logodir = $conf->mycompany->multidir_output[$conf->entity];
    }

    if (!empty($this_dummy = $this_dummy ?? null)) {
        // Rien
    }

    if (!empty($GLOBALS['mysoc']->logo)) {

        $logo = $logodir.'/logos/'.$GLOBALS['mysoc']->logo;

        if (is_readable($logo)) {
            $logoExiste = true;
        }
    }
}


/*
 * ==========================================================
 * 11. EN-TÊTE
 * ==========================================================
 */

$top = 18;


/*
 * Logo
 */

if ($logoExiste) {

    $heightLogo = pdf_getHeightForLogo($logo);

    /*
     * On limite la hauteur du logo
     */
    if ($heightLogo > 25) {
        $heightLogo = 25;
    }

    $pdf->Image(
        $logo,
        15,
        $top,
        0,
        $heightLogo
    );

} else {

    /*
     * Si aucun logo n'est disponible,
     * afficher simplement le nom.
     */

    $pdf->SetFont(
        'helvetica',
        'B',
        18
    );

    $pdf->SetTextColor(
        $bleu[0],
        $bleu[1],
        $bleu[2]
    );

    $pdf->SetXY(15, $top);

    $pdf->Cell(
        70,
        8,
        'Electrojul',
        0,
        0,
        'L'
    );
}


/*
 * ==========================================================
 * 12. TITRE DU DOCUMENT
 * ==========================================================
 */

$pdf->SetTextColor(
    $bleu[0],
    $bleu[1],
    $bleu[2]
);

$pdf->SetFont(
    'helvetica',
    'B',
    20
);

$pdf->SetXY(
    95,
    $top + 2
);

$pdf->Cell(
    100,
    8,
    'TARIFS',
    0,
    1,
    'R'
);


$pdf->SetFont(
    'helvetica',
    '',
    10
);

$pdf->SetTextColor(
    $gris[0],
    $gris[1],
    $gris[2]
);

$pdf->SetXY(
    95,
    $top + 11
);

$pdf->Cell(
    100,
    5,
    'Réparation électroménager',
    0,
    1,
    'R'
);


/*
 * ==========================================================
 * 13. LIGNE DE SÉPARATION
 * ==========================================================
 */

$pdf->SetDrawColor(
    $grisClair[0],
    $grisClair[1],
    $grisClair[2]
);

$pdf->SetLineWidth(0.4);

$pdf->Line(
    15,
    48,
    195,
    48
);


/*
 * ==========================================================
 * 14. TITRE DE LA SECTION
 * ==========================================================
 */

$pdf->SetTextColor(
    $bleu[0],
    $bleu[1],
    $bleu[2]
);

$pdf->SetFont(
    'helvetica',
    'B',
    13
);

$pdf->SetXY(
    15,
    57
);

$pdf->Cell(
    180,
    7,
    'Nos tarifs',
    0,
    1,
    'L'
);


/*
 * ==========================================================
 * 15. TABLEAU DES TARIFS
 * ==========================================================
 */

$tableX = 15;
$tableY = 68;

$tableWidth = 180;

$colNom = 140;
$colPrix = 40;

$rowHeight = 12;


/*
 * En-tête du tableau
 */

$pdf->SetFillColor(
    $bleu[0],
    $bleu[1],
    $bleu[2]
);

$pdf->SetTextColor(
    $blanc[0],
    $blanc[1],
    $blanc[2]
);

$pdf->SetFont(
    'helvetica',
    'B',
    10
);

$pdf->SetXY(
    $tableX,
    $tableY
);

$pdf->Cell(
    $colNom,
    $rowHeight,
    'Prestation',
    1,
    0,
    'L',
    true
);

$pdf->Cell(
    $colPrix,
    $rowHeight,
    'Prix TTC',
    1,
    1,
    'R',
    true
);


/*
 * ==========================================================
 * 16. LIGNES TARIFS
 * ==========================================================
 */

$pdf->SetFont(
    'helvetica',
    '',
    10
);

$index = 0;

foreach ($tarifs as $tarif) {

    $nom = $tarif['label'] ?? '';

    $prix = (float) ($tarif['price_ttc'] ?? 0);

    /*
     * Format français
     */
    $prixTexte = number_format(
        $prix,
        2,
        ',',
        ' '
    ).' €';


    /*
     * Alternance des lignes
     */

    if ($index % 2 === 0) {

        $pdf->SetFillColor(
            $blanc[0],
            $blanc[1],
            $blanc[2]
        );

    } else {

        $pdf->SetFillColor(
            $grisTresClair[0],
            $grisTresClair[1],
            $grisTresClair[2]
        );
    }


    $pdf->SetTextColor(
        40,
        40,
        40
    );


    /*
     * Calcul de hauteur dynamique
     * pour les descriptions longues.
     */

    $nbLignes = max(
        1,
        ceil(
            $pdf->GetStringWidth($nom) / ($colNom - 6)
        )
    );

    $hauteur = max(
        $rowHeight,
        $nbLignes * 5
    );


    $x = $tableX;
    $y = $pdf->GetY();


    /*
     * Nom
     */

    $pdf->MultiCell(
        $colNom,
        $hauteur,
        $nom,
        1,
        'L',
        true,
        0,
        $x,
        $y,
        true,
        0,
        false,
        true,
        $hauteur,
        'M'
    );


    /*
     * Prix
     */

    $pdf->MultiCell(
        $colPrix,
        $hauteur,
        $prixTexte,
        1,
        'R',
        true,
        1,
        $x + $colNom,
        $y,
        true,
        0,
        false,
        true,
        $hauteur,
        'M'
    );


    $index++;
}


/*
 * ==========================================================
 * 17. MENTIONS TARIFAIRES
 * ==========================================================
 */

$posY = $pdf->GetY() + 10;


/*
 * Cadre des mentions
 */

$pdf->SetFillColor(
    248,
    248,
    248
);

$pdf->SetDrawColor(
    $grisClair[0],
    $grisClair[1],
    $grisClair[2]
);

$pdf->RoundedRect(
    15,
    $posY,
    180,
    32,
    2,
    '1234',
    'DF'
);


/*
 * Texte
 */

$pdf->SetTextColor(
    60,
    60,
    60
);

$pdf->SetFont(
    'helvetica',
    'B',
    9
);

$pdf->SetXY(
    20,
    $posY + 6
);

$pdf->MultiCell(
    170,
    5,
    "Le forfait total est dû lors d'une réparation réussie.\nLe déplacement reste dû dans tous les cas.",
    0,
    'C',
    false
);


$pdf->SetFont(
    'helvetica',
    '',
    9
);

$pdf->SetXY(
    20,
    $posY + 18
);

$pdf->MultiCell(
    170,
    5,
    "Les pièces sont garanties 3 mois dans le cadre d'une utilisation normale.",
    0,
    'C',
    false
);


/*
 * ==========================================================
 * 18. DATE DE MISE À JOUR
 * ==========================================================
 */

if (!empty($dateMiseAJour)) {

    $timestamp = strtotime($dateMiseAJour);

    if ($timestamp !== false) {

        $dateTexte = date(
            'd/m/Y à H:i',
            $timestamp
        );

    } else {

        $dateTexte = $dateMiseAJour;
    }

} else {

    $dateTexte = date(
        'd/m/Y à H:i'
    );
}


$posY += 40;


$pdf->SetTextColor(
    $gris[0],
    $gris[1],
    $gris[2]
);

$pdf->SetFont(
    'helvetica',
    '',
    8
);

$pdf->SetXY(
    15,
    $posY
);

$pdf->Cell(
    180,
    5,
    'Tarifs mis à jour le '.$dateTexte,
    0,
    1,
    'C'
);


/*
 * ==========================================================
 * 19. PIED DE PAGE
 * ==========================================================
 */

$pageHeight = $pdf->getPageHeight();


$pdf->SetDrawColor(
    $grisClair[0],
    $grisClair[1],
    $grisClair[2]
);

$pdf->Line(
    15,
    $pageHeight - 18,
    195,
    $pageHeight - 18
);


$pdf->SetTextColor(
    $gris[0],
    $gris[1],
    $gris[2]
);

$pdf->SetFont(
    'helvetica',
    '',
    8
);

$pdf->SetXY(
    15,
    $pageHeight - 15
);


/*
 * Nom de l'entreprise
 */

$nomEntreprise = 'Electrojul';

if (!empty($mysoc->name)) {
    $nomEntreprise = $mysoc->name;
}


$pdf->Cell(
    120,
    5,
    $nomEntreprise,
    0,
    0,
    'L'
);


/*
 * Page
 */

$pdf->Cell(
    60,
    5,
    'Tarifs - Page '.$pdf->getAliasNumPage().' / '.$pdf->getAliasNbPages(),
    0,
    1,
    'R'
);


/*
 * ==========================================================
 * 20. SORTIE DU PDF
 * ==========================================================
 */

$pdf->Output(
    'tarifs-electrojul.pdf',
    'I'
);

exit;
```
