<?php
/**
 * Copyright (C) 2026 ElectroJul
 *
 * PDF des tarifs ElectroJul
 *
 * Génère une grille tarifaire à partir des tarifs
 * gérés par le module QualiRépar.
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/pdf/modules_pdf.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

require_once DOL_DOCUMENT_ROOT.'/custom/qualirepar/class/tarifs.class.php';


/**
 * Classe de génération du PDF des tarifs ElectroJul.
 */
class pdf_tarifs extends ModelePDFTarifs
{
	/**
	 * @var DoliDB
	 */
	public $db;

	/**
	 * @var string
	 */
	public $name = 'tarifs';

	/**
	 * @var string
	 */
	public $description = 'Grille tarifaire ElectroJul';

	/**
	 * @var string
	 */
	public $type = 'pdf';

	/**
	 * @var string
	 */
	public $page_largeur;

	/**
	 * @var string
	 */
	public $page_hauteur;

	/**
	 * @var float
	 */
	public $marge_gauche;

	/**
	 * @var float
	 */
	public $marge_droite;

	/**
	 * @var float
	 */
	public $marge_haute;

	/**
	 * @var float
	 */
	public $marge_basse;

	/**
	 * @var float
	 */
	public $corner_radius = 2;


	/**
	 * Constructeur.
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		$this->name = 'tarifs';
		$this->description = 'Grille tarifaire ElectroJul';

		$this->format = 'A4';

		$this->page_largeur = 210;
		$this->page_hauteur = 297;

		$this->marge_gauche = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
		$this->marge_droite = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
		$this->marge_haute = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
		$this->marge_basse = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);

		$this->option_logo = 1;
	}


	/**
	 * Génération du PDF.
	 *
	 * @param object    $object       Objet transmis par Dolibarr.
	 * @param Translate $outputlangs  Langue.
	 * @return int
	 */
	public function write_file($object, $outputlangs)
	{
		global $conf, $langs, $user;

		$error = 0;

		/*
		 * ----------------------------------------------------------
		 * Chargement de la langue
		 * ----------------------------------------------------------
		 */

		$outputlangs->loadLangs(array('main', 'companies'));

		/*
		 * ----------------------------------------------------------
		 * Chargement des tarifs QualiRépar
		 * ----------------------------------------------------------
		 */

		$tarifsManager = new QualiReparTarifs($this->db);

		$tarifs = $tarifsManager->getTarifs();

		$date_mise_a_jour = $tarifsManager->getDateMiseAJour();


		/*
		 * ----------------------------------------------------------
		 * Création du PDF
		 * ----------------------------------------------------------
		 */

		$pdf = pdf_getInstance($this->format);

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->setAutoPageBreak(true, 0);

		if (class_exists('TCPDF')) {
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}

		$pdf->SetFont(pdf_getPDFFont($outputlangs));

		$pdf->SetMargins(
			$this->marge_gauche,
			$this->marge_haute,
			$this->marge_droite
		);

		$pdf->SetTitle('Tarifs ElectroJul');
		$pdf->SetSubject('Tarifs ElectroJul');
		$pdf->SetCreator('Dolibarr '.DOL_VERSION);

		if (!getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
			$pdf->SetCompression(true);
		}

		$pdf->Open();

		/*
		 * ----------------------------------------------------------
		 * Première page
		 * ----------------------------------------------------------
		 */

		$pdf->AddPage();

		/*
		 * ----------------------------------------------------------
		 * EN-TÊTE
		 * ----------------------------------------------------------
		 */

		$posy = $this->marge_haute;
		$posx_logo = $this->marge_gauche;

		$w_title = 100;
		$posx_title = $this->page_largeur
			- $this->marge_droite
			- $w_title;


		/*
		 * Logo Dolibarr
		 *
		 * On reprend exactement le principe utilisé
		 * par les modèles PDF standards.
		 */

		if (!getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO')) {

			if (!empty($this->emetteur->logo)) {

				$logodir = $conf->mycompany->dir_output;

				if (!empty($conf->mycompany->multidir_output[$conf->entity])) {
					$logodir =
						$conf->mycompany->multidir_output[$conf->entity];
				}

				if (!getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO')) {
					$logo =
						$logodir.'/logos/thumbs/'.
						$this->emetteur->logo_small;
				} else {
					$logo =
						$logodir.'/logos/'.
						$this->emetteur->logo;
				}

				if (is_readable($logo)) {

					$height = pdf_getHeightForLogo($logo);

					$pdf->Image(
						$logo,
						$posx_logo,
						$posy,
						0,
						$height
					);
				}
			} else {

				$pdf->SetTextColor(0, 0, 60);
				$pdf->SetFont('', 'B', $default_font_size + 2);

				$pdf->SetXY(
					$posx_logo,
					$posy
				);

				$pdf->MultiCell(
					80,
					5,
					$outputlangs->convToOutputCharset(
						$this->emetteur->name
					),
					0,
					'L'
				);
			}
		}


		/*
		 * ----------------------------------------------------------
		 * TITRE
		 * ----------------------------------------------------------
		 */

		$pdf->SetXY(
			$posx_title,
			$posy
		);

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont(
			'',
			'B',
			$default_font_size + 4
		);

		$pdf->MultiCell(
			$w_title,
			6,
			'TARIFS ELECTROJUL',
			0,
			'R'
		);


		/*
		 * Date de mise à jour
		 */

		$pdf->SetFont(
			'',
			'',
			$default_font_size - 1
		);

		$pdf->SetXY(
			$posx_title,
			$posy + 9
		);

		$date_text = 'Mise à jour : ';

		if (!empty($date_mise_a_jour)) {
			$date_text .= $date_mise_a_jour;
		} else {
			$date_text .= dol_print_date(
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
			$date_text,
			0,
			'R'
		);


		/*
		 * ----------------------------------------------------------
		 * Position de départ du tableau
		 * ----------------------------------------------------------
		 */

		$tab_top = max(
			48,
			$pdf->getY() + 10
		);

		/*
		 * Hauteur dynamique du tableau.
		 */

		$header_height = 10;
		$line_height = 9;

		$nb_tarifs = is_array($tarifs)
			? count($tarifs)
			: 0;

		$tab_height =
			$header_height +
			($nb_tarifs * $line_height) +
			28;


		/*
		 * Ne pas dépasser la zone imprimable.
		 */

		$max_height =
			$this->page_hauteur
			- $tab_top
			- $this->marge_basse
			- 25;

		if ($tab_height > $max_height) {
			$tab_height = $max_height;
		}


		/*
		 * ----------------------------------------------------------
		 * CADRE PRINCIPAL
		 * ----------------------------------------------------------
		 */

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetLineWidth(0.3);

		$pdf->RoundedRect(
			$this->marge_gauche,
			$tab_top,
			$this->page_largeur
				- $this->marge_gauche
				- $this->marge_droite,
			$tab_height,
			$this->corner_radius,
			'1234',
			'D'
		);


		/*
		 * ----------------------------------------------------------
		 * TITRE DU TABLEAU
		 * ----------------------------------------------------------
		 */

		$pdf->SetFillColor(230, 230, 230);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont(
			'',
			'B',
			$default_font_size
		);

		$pdf->SetXY(
			$this->marge_gauche,
			$tab_top
		);

		$pdf->MultiCell(
			$this->page_largeur
				- $this->marge_gauche
				- $this->marge_droite,
			$header_height,
			'NOS TARIFS',
			0,
			'C',
			true
		);


		/*
		 * ----------------------------------------------------------
		 * COLONNES
		 * ----------------------------------------------------------
		 */

		$left = $this->marge_gauche;

		$total_width =
			$this->page_largeur
			- $this->marge_gauche
			- $this->marge_droite;

		$price_width = 42;

		$label_width =
			$total_width
			- $price_width;


		/*
		 * En-tête des colonnes
		 */

		$y = $tab_top + $header_height;

		$pdf->SetFillColor(248, 248, 248);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont(
			'',
			'B',
			$default_font_size - 1
		);

		$pdf->SetXY(
			$left,
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

		$pdf->SetXY(
			$left + $label_width,
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


		/*
		 * ----------------------------------------------------------
		 * LIGNES DES TARIFS
		 * ----------------------------------------------------------
		 */

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

				if (is_object($tarif)) {

					$label =
						isset($tarif->label)
							? $tarif->label
							: '';

					$price_ttc =
						isset($tarif->price_ttc)
							? $tarif->price_ttc
							: 0;

				} elseif (is_array($tarif)) {

					$label =
						isset($tarif['label'])
							? $tarif['label']
							: '';

					$price_ttc =
						isset($tarif['price_ttc'])
							? $tarif['price_ttc']
							: 0;
				}


				/*
				 * Désignation
				 */

				$pdf->SetXY(
					$left,
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


				/*
				 * Prix
				 */

				$pdf->SetXY(
					$left + $label_width,
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
					'B',
					'R',
					false
				);

				$y += $line_height;


				/*
				 * Sécurité pagination.
				 */

				if (
					$y >
					$this->page_hauteur
					- $this->marge_basse
					- 35
				) {
					break;
				}
			}
		}


		/*
		 * ----------------------------------------------------------
		 * MENTION SOUS LE TABLEAU
		 * ----------------------------------------------------------
		 */

		$y += 6;

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
			$left + 3,
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


		/*
		 * ----------------------------------------------------------
		 * PIED DE PAGE
		 * ----------------------------------------------------------
		 */

		$this->_pagefoot(
			$pdf,
			$object,
			$outputlangs
		);


		/*
		 * ----------------------------------------------------------
		 * SAUVEGARDE
		 * ----------------------------------------------------------
		 *
		 * Le chemin est volontairement construit comme les
		 * documents Dolibarr classiques.
		 */

		$filename = 'tarifs-electrojul.pdf';

		$upload_dir =
			$conf->qualirepar->dir_output
			?? $conf->mycompany->dir_output;

		if (!is_dir($upload_dir)) {
			dol_mkdir($upload_dir);
		}

		$file = $upload_dir.'/'.$filename;

		$pdf->Output(
			$file,
			'F'
		);

		if (!is_readable($file)) {
			$this->error =
				'Impossible de créer le PDF des tarifs : '.$file;

			return -1;
		}

		return 1;
	}


	/**
	 * Pied de page.
	 *
	 * On utilise le mécanisme natif Dolibarr afin de conserver
	 * le même rendu général que les devis/factures.
	 *
	 * @param TCPDF    $pdf
	 * @param object   $object
	 * @param Translate $outputlangs
	 * @return int
	 */
	protected function _pagefoot(
		&$pdf,
		$object,
		$outputlangs
	) {
		global $conf;

		$showdetails =
			getDolGlobalInt(
				'MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS',
				0
			);

		return pdf_pagefoot(
			$pdf,
			$outputlangs,
			'PROPOSAL_FREE_TEXT',
			$this->emetteur,
			$this->marge_basse,
			$this->marge_gauche,
			$this->page_hauteur,
			$object,
			$showdetails,
			0,
			$this->page_largeur,
			''
		);
	}
}
