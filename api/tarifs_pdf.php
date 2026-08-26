<?php
/**
 * PDF des tarifs ElectroJul
 *
 * Génère une fiche tarifaire PDF à partir des produits
 * configurés dans QualiRépar :
 *
 *   - produit actif à la vente
 *   - extrafield afficher_site_tarif = 1
 *   - tri par ordre_site_tarif
 *
 * Le PDF est généré à chaque appel.
 */

// ------------------------------------------------------------
// Chargement de Dolibarr
// ------------------------------------------------------------

$res = 0;

if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}

if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}

if (!$res) {
	die("Erreur : impossible de charger Dolibarr.");
}


// ------------------------------------------------------------
// Classes nécessaires
// ------------------------------------------------------------

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/qualirepar/class/tarifs.class.php';


// ------------------------------------------------------------
// Langue
// ------------------------------------------------------------

$langs->loadLangs(array('main', 'companies'));

$outputlangs = $langs;


// ------------------------------------------------------------
// Récupération des tarifs
// ------------------------------------------------------------

$tarifsManager = new QualiReparTarifs($db);

$tarifs = $tarifsManager->getTarifs();

$dateMiseAJour = $tarifsManager->getDateMiseAJour();


// ------------------------------------------------------------
// Société
// ------------------------------------------------------------

$emetteur = $mysoc;


// ------------------------------------------------------------
// Création du PDF
// ------------------------------------------------------------

$pdf = pdf_getInstance('A4');

$default_font_size = pdf_getPDFFontSize($outputlangs);

$page_width  = 210;
$page_height = 297;

$marge_gauche = 12;
$marge_droite = 12;
$marge_haute = 12;
$marge_basse = 15;

$corner_radius = 2;

$pdf->SetMargins(
	$marge_gauche,
	$marge_haute,
	$marge_droite
);

$pdf->SetAutoPageBreak(false);

if (method_exists($pdf, 'setPrintHeader')) {
	$pdf->setPrintHeader(false);
}

if (method_exists($pdf, 'setPrintFooter')) {
	$pdf->setPrintFooter(false);
}

$pdf->SetCreator('Dolibarr');
$pdf->SetAuthor($emetteur->name);
$pdf->SetTitle('Tarifs ElectroJul');
$pdf->SetSubject('Tarifs ElectroJul');

$pdf->SetFont(
	pdf_getPDFFont($outputlangs),
	'',
	$default_font_size
);

$pdf->AddPage();


// ============================================================
// EN-TÊTE
// ============================================================

$posy = $marge_haute;

$w_title = 100;

$posx_title =
	$page_width
	- $marge_droite
	- $w_title;


// ------------------------------------------------------------
// Logo
// ------------------------------------------------------------

if (!getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO')) {

	$logo = '';

	if (!empty($emetteur->logo)) {

		$logodir = $conf->mycompany->dir_output;

		if (!empty($conf->mycompany->multidir_output[$conf->entity])) {
			$logodir =
				$conf->mycompany->multidir_output[$conf->entity];
		}

		if (!getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO')) {

			$logo =
				$logodir
				.'/logos/thumbs/'
				.$emetteur->logo_small;

		} else {

			$logo =
				$logodir
				.'/logos/'
				.$emetteur->logo;
		}
	}

	if (!empty($logo) && is_readable($logo)) {

		$height = pdf_getHeightForLogo($logo);

		/*
		 * Limitation de la hauteur du logo afin de ne pas
		 * pousser le tableau trop bas.
		 */
		if ($height > 28) {
			$height = 28;
		}

		$pdf->Image(
			$logo,
			$marge_gauche,
			$posy,
			0,
			$height
		);

	} else {

		$pdf->SetTextColor(0, 0, 60);

		$pdf->SetFont(
			'',
			'B',
			$default_font_size + 2
		);

		$pdf->SetXY(
			$marge_gauche,
			$posy
		);

		$pdf->MultiCell(
			75,
			5,
			$outputlangs->convToOutputCharset(
				$emetteur->name
			),
			0,
			'L'
		);
	}
}


// ------------------------------------------------------------
// Titre
// ------------------------------------------------------------

$pdf->SetTextColor(0, 0, 60);

$pdf->SetFont(
	'',
	'B',
	$default_font_size + 4
);

$pdf->SetXY(
	$posx_title,
	$posy
);

$pdf->MultiCell(
	$w_title,
	6,
	'TARIFS ELECTROJUL',
	0,
	'R'
);


// ------------------------------------------------------------
// Date de mise à jour
// ------------------------------------------------------------

$pdf->SetFont(
	'',
	'',
	$default_font_size - 1
);

$pdf->SetXY(
	$posx_title,
	$posy + 9
);

$dateAffichee = '';

if (!empty($dateMiseAJour)) {

	$dateAffichee = dol_print_date(
		dol_stringtotime($dateMiseAJour),
		'day',
		false,
		$outputlangs,
		true
	);

}

if (empty($dateAffichee)) {

	$dateAffichee = dol_print_date(
		dol_now(),
		'day',
		false,
		$outputlangs,
		true
	);
}

$pdf->MultiCell(
	$w_title,
	4,
	'Mise à jour : '.$dateAffichee,
	0,
	'R'
);


// ============================================================
// TABLEAU PRINCIPAL
// ============================================================

$total_width =
	$page_width
	- $marge_gauche
	- $marge_droite;

$tab_top = 52;

$header_height = 10;

$line_height = 9;

$price_width = 45;

$label_width =
	$total_width
	- $price_width;


// ------------------------------------------------------------
// Calcul de la hauteur du cadre
// ------------------------------------------------------------

$nb_tarifs = is_array($tarifs)
	? count($tarifs)
	: 0;

$mention_height = 25;

$tab_height =
	$header_height
	+ ($nb_tarifs * $line_height)
	+ $mention_height;


// ------------------------------------------------------------
// Cadre principal
// ------------------------------------------------------------

$pdf->SetDrawColor(
	128,
	128,
	128
);

$pdf->SetLineWidth(0.3);

$pdf->RoundedRect(
	$marge_gauche,
	$tab_top,
	$total_width,
	$tab_height,
	$corner_radius,
	'1234',
	'D'
);


// ============================================================
// TITRE DU CADRE
// ============================================================

$pdf->SetFillColor(
	230,
	230,
	230
);

$pdf->SetTextColor(
	0,
	0,
	60
);

$pdf->SetFont(
	'',
	'B',
	$default_font_size
);

$pdf->SetXY(
	$marge_gauche,
	$tab_top
);

$pdf->MultiCell(
	$total_width,
	$header_height,
	'NOS TARIFS',
	0,
	'C',
	true
);


// ============================================================
// EN-TÊTE DES COLONNES
// ============================================================

$y = $tab_top + $header_height;

$pdf->SetFillColor(
	248,
	248,
	248
);

$pdf->SetTextColor(
	0,
	0,
	0
);

$pdf->SetFont(
	'',
	'B',
	$default_font_size - 1
);


// Désignation

$pdf->SetXY(
	$marge_gauche,
	$y
);

$pdf->MultiCell(
	$label_width,
	$line_height,
	'Désignation',
	'B',
	'L',
	true
);


// Tarif

$pdf->SetXY(
	$marge_gauche + $label_width,
	$y
);

$pdf->MultiCell(
	$price_width,
	$line_height,
	'Tarif TTC',
	'BL',
	'R',
	true
);

$y += $line_height;


// ============================================================
// LIGNES DES TARIFS
// ============================================================

$pdf->SetFont(
	'',
	'',
	$default_font_size
);

$pdf->SetFillColor(
	255,
	255,
	255
);

if (!empty($tarifs) && is_array($tarifs)) {

	foreach ($tarifs as $tarif) {

		$label = '';

		$price_ttc = 0;

		if (isset($tarif['label'])) {
			$label = $tarif['label'];
		}

		if (isset($tarif['price_ttc'])) {
			$price_ttc = (float) $tarif['price_ttc'];
		}


		// ----------------------------------------------------
		// Désignation
		// ----------------------------------------------------

		$pdf->SetXY(
			$marge_gauche,
			$y
		);

		$pdf->MultiCell(
			$label_width,
			$line_height,
			$outputlangs->convToOutputCharset(
				$label
			),
			'B',
			'L',
			false
		);


		// ----------------------------------------------------
		// Prix
		// ----------------------------------------------------

		$pdf->SetXY(
			$marge_gauche + $label_width,
			$y
		);

		$pdf->MultiCell(
			$price_width,
			$line_height,
			price(
				$price_ttc,
				0,
				$outputlangs
			),
			'BL',
			'R',
			false
		);


		$y += $line_height;
	}
}


// ============================================================
// MENTIONS
// ============================================================

$y += 5;

$pdf->SetTextColor(
	40,
	40,
	40
);

$pdf->SetFont(
	'',
	'',
	$default_font_size - 1
);

$mention =
	'Le forfait est dû lors d’une réparation réussie.'
	."\n"
	.'Les pièces sont garanties 3 mois dans le cadre d’une utilisation normale.';

$pdf->SetXY(
	$marge_gauche + 3,
	$y
);

$pdf->MultiCell(
	$total_width - 6,
	5,
	$outputlangs->convToOutputCharset(
		$mention
	),
	0,
	'L',
	false
);


// ============================================================
// PIED DE PAGE
// ============================================================

$footer_y =
	$page_height
	- $marge_basse
	- 12;


// Ligne supérieure

$pdf->SetDrawColor(
	180,
	180,
	180
);

$pdf->line(
	$marge_gauche,
	$footer_y,
	$page_width - $marge_droite,
	$footer_y
);


// ------------------------------------------------------------
// Coordonnées
// ------------------------------------------------------------

$pdf->SetTextColor(
	80,
	80,
	80
);

$pdf->SetFont(
	'',
	'',
	$default_font_size - 2
);

$coordonnees = array();

if (!empty($emetteur->name)) {
	$coordonnees[] = $emetteur->name;
}

$adresse = '';

if (!empty($emetteur->address)) {
	$adresse .= $emetteur->address;
}

if (!empty($emetteur->zip)) {
	$adresse .= ($adresse ? ' - ' : '').$emetteur->zip;
}

if (!empty($emetteur->town)) {
	$adresse .= ($adresse ? ' ' : '').$emetteur->town;
}

if ($adresse) {
	$coordonnees[] = $adresse;
}

if (!empty($emetteur->phone)) {
	$coordonnees[] = 'Tél. : '.$emetteur->phone;
}

if (!empty($emetteur->email)) {
	$coordonnees[] = $emetteur->email;
}

$footer_text = implode(
	'  |  ',
	$coordonnees
);

$pdf->SetXY(
	$marge_gauche,
	$footer_y + 3
);

$pdf->MultiCell(
	$total_width - 25,
	4,
	$outputlangs->convToOutputCharset(
		$footer_text
	),
	0,
	'L'
);


// ------------------------------------------------------------
// Numéro de page
// ------------------------------------------------------------

$pdf->SetXY(
	$page_width - $marge_droite - 25,
	$footer_y + 3
);

$pdf->MultiCell(
	25,
	4,
	'Page 1/1',
	0,
	'R'
);


// ============================================================
// SORTIE DU PDF
// ============================================================

/*
 * "D" = téléchargement.
 *
 * Pour simplement afficher le PDF dans le navigateur,
 * remplacer "D" par "I".
 */

$pdf->Output(
	'tarifs-electrojul.pdf',
	'D'
);

exit;
