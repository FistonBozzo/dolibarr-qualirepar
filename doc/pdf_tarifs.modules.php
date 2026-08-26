<?php
/**
 * Générateur PDF des tarifs publics ElectroJul
 *
 * Module QualiRépar
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/qualirepar/class/tarifs.class.php';


/**
 * Générateur du PDF des tarifs.
 */
class pdf_tarifs
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
	public $description = 'Fiche tarifaire ElectroJul';

	/**
	 * @var string
	 */
	public $type = 'pdf_tarifs';

	/**
	 * @var string
	 */
	public $extension = 'pdf';

	/**
	 * @var string
	 */
	public $format = 'A4';

	/**
	 * @var string
	 */
	public $orientation = 'P';

	/**
	 * Marges.
	 */
	public $marge_gauche = 10;
	public $marge_droite = 10;
	public $marge_haute = 10;
	public $marge_basse = 15;

	/**
	 * Dimensions A4.
	 */
	public $page_largeur = 210;
	public $page_hauteur = 297;

	/**
	 * Rayon des cadres.
	 */
	public $corner_radius = 2;


	/**
	 * Constructeur.
	 *
	 * @param DoliDB $db Database
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Génère le PDF.
	 *
	 * @param object|null $object Objet Dolibarr éventuel
	 * @param Translate $outputlangs Langue
	 * @param string $srctemplatepath Chemin modèle
	 * @param int $hidedetails Masquer détails
	 * @param int $hidedesc Masquer descriptions
	 * @param int $hideref Masquer références
	 *
	 * @return int
	 */
	public function write_file(
		$object,
		$outputlangs,
		$srctemplatepath = '',
		$hidedetails = 0,
		$hidedesc = 0,
		$hideref = 0
	) {
		global $conf, $mysoc;

		/*
		 * Langue
		 */
		if (!is_object($outputlangs)) {
			global $langs;
			$outputlangs = $langs;
		}

		$outputlangs->loadLangs(array('main', 'companies'));

		/*
		 * Vérification TCPDF
		 */
		if (!class_exists('TCPDF')) {
			require_once TCPDF_PATH.'/tcpdf.php';
		}

		/*
		 * Création du PDF
		 */
		$pdf = new TCPDF(
			'P',
			'mm',
			'A4',
			true,
			'UTF-8',
			false
		);

		$pdf->SetCreator('Dolibarr');
		$pdf->SetAuthor(
			!empty($mysoc->name) ? $mysoc->name : 'ElectroJul'
		);
		$pdf->SetTitle('Tarifs ElectroJul');
		$pdf->SetSubject('Tarifs ElectroJul');
		$pdf->SetKeywords('ElectroJul, tarifs, réparation électroménager');

		/*
		 * Pas d'en-tête TCPDF.
		 * Nous dessinons nous-mêmes l'en-tête.
		 */
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		$pdf->SetMargins(
			$this->marge_gauche,
			$this->marge_haute,
			$this->marge_droite
		);

		$pdf->SetAutoPageBreak(
			true,
			$this->marge_basse
		);

		$pdf->SetFont(
			pdf_getPDFFont($outputlangs),
			'',
			pdf_getPDFFontSize($outputlangs)
		);

		$pdf->AddPage();

		/*
		 * -------------------------------------------------------------
		 * EN-TÊTE
		 * -------------------------------------------------------------
		 */

		$this->drawHeader($pdf, $outputlangs);


		/*
		 * -------------------------------------------------------------
		 * RÉCUPÉRATION DES TARIFS
		 * -------------------------------------------------------------
		 */

		$tarifsObj = new QualiReparTarifs($this->db);

		$tarifs = $tarifsObj->getTarifs();

		$dateMiseAJour = $tarifsObj->getDateMiseAJour();


		/*
		 * -------------------------------------------------------------
		 * TITRE
		 * -------------------------------------------------------------
		 */

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$bleuR = 0;
		$bleuG = 0;
		$bleuB = 60;

		$pdf->SetTextColor(
			$bleuR,
			$bleuG,
			$bleuB
		);

		$pdf->SetFont(
			pdf_getPDFFont($outputlangs),
			'B',
			$default_font_size + 2
		);

		$pdf->SetXY(
			$this->marge_gauche,
			55
		);

		$pdf->MultiCell(
			$this->page_largeur
				- $this->marge_gauche
				- $this->marge_droite,
			7,
			'Tarifs des prestations',
			0,
			'L'
		);


		/*
		 * -------------------------------------------------------------
		 * CADRE DU TABLEAU
		 * -------------------------------------------------------------
		 */

		$cadreX = $this->marge_gauche;
		$cadreY = 65;

		$cadreW =
			$this->page_largeur
			- $this->marge_gauche
			- $this->marge_droite;

		$hauteurEntete = 10;
		$hauteurLigne = 9;

		$nombreTarifs = count($tarifs);

		/*
		 * Hauteur du cadre.
		 *
		 * Une marge supplémentaire est conservée sous les tarifs
		 * pour afficher les informations de mise à jour.
		 */
		$hauteurCadre =
			$hauteurEntete
			+ ($nombreTarifs * $hauteurLigne)
			+ 18;

		if ($nombreTarifs === 0) {
			$hauteurCadre = 40;
		}


		/*
		 * Cadre extérieur
		 */
		$pdf->SetDrawColor(
			128,
			128,
			128
		);

		$pdf->SetLineWidth(0.3);

		$pdf->RoundedRect(
			$cadreX,
			$cadreY,
			$cadreW,
			$hauteurCadre,
			$this->corner_radius,
			'1234',
			'D'
		);


		/*
		 * -------------------------------------------------------------
		 * EN-TÊTE DU TABLEAU
		 * -------------------------------------------------------------
		 */

		$pdf->SetFillColor(
			224,
			224,
			224
		);

		$pdf->SetTextColor(
			0,
			0,
			60
		);

		$pdf->SetFont(
			pdf_getPDFFont($outputlangs),
			'B',
			$default_font_size - 1
		);

		/*
		 * Désignation
		 */
		$pdf->SetXY(
			$cadreX,
			$cadreY
		);

		$pdf->MultiCell(
			$cadreW - 45,
			$hauteurEntete,
			'Désignation',
			0,
			'L',
			true
		);

		/*
		 * Prix
		 */
		$pdf->SetXY(
			$cadreX + $cadreW - 45,
			$cadreY
		);

		$pdf->MultiCell(
			45,
			$hauteurEntete,
			'Prix TTC',
			0,
			'R',
			true
		);


		/*
		 * Ligne verticale séparatrice de la colonne prix.
		 */
		$pdf->SetDrawColor(
			180,
			180,
			180
		);

		$pdf->Line(
			$cadreX + $cadreW - 45,
			$cadreY,
			$cadreX + $cadreW - 45,
			$cadreY + $hauteurEntete + ($nombreTarifs * $hauteurLigne)
		);


		/*
		 * Ligne horizontale sous l'en-tête.
		 */
		$pdf->Line(
			$cadreX,
			$cadreY + $hauteurEntete,
			$cadreX + $cadreW,
			$cadreY + $hauteurEntete
		);


		/*
		 * -------------------------------------------------------------
		 * TARIFS
		 * -------------------------------------------------------------
		 */

		$y = $cadreY + $hauteurEntete;

		$pdf->SetFont(
			pdf_getPDFFont($outputlangs),
			'',
			$default_font_size
		);

		$pdf->SetTextColor(
			40,
			40,
			40
		);


		if ($nombreTarifs > 0) {

			foreach ($tarifs as $index => $tarif) {

				/*
				 * Alternance de fond.
				 */
				if (($index % 2) === 1) {

					$pdf->SetFillColor(
						248,
						248,
						248
					);

					$pdf->Rect(
						$cadreX,
						$y,
						$cadreW,
						$hauteurLigne,
						'F'
					);
				}


				/*
				 * Désignation.
				 */
				$label = trim(
					(string) $tarif['label']
				);

				$pdf->SetXY(
					$cadreX + 4,
					$y + 2
				);

				$pdf->MultiCell(
					$cadreW - 53,
					5,
					$outputlangs->convToOutputCharset($label),
					0,
					'L',
					false
				);


				/*
				 * Prix TTC.
				 */
				$prix = price(
					(float) $tarif['price_ttc'],
					0,
					$outputlangs
				);

				$pdf->SetXY(
					$cadreX + $cadreW - 41,
					$y + 2
				);

				$pdf->MultiCell(
					37,
					5,
					$prix,
					0,
					'R',
					false
				);


				/*
				 * Ligne horizontale.
				 */
				if ($index < $nombreTarifs - 1) {

					$pdf->SetDrawColor(
						210,
						210,
						210
					);

					$pdf->Line(
						$cadreX,
						$y + $hauteurLigne,
						$cadreX + $cadreW,
						$y + $hauteurLigne
					);
				}

				$y += $hauteurLigne;
			}

		} else {

			$pdf->SetTextColor(
				100,
				100,
				100
			);

			$pdf->SetXY(
				$cadreX + 4,
				$y + 5
			);

			$pdf->MultiCell(
				$cadreW - 8,
				6,
				'Aucun tarif disponible.',
				0,
				'L'
			);

			$y += 12;
		}


		/*
		 * -------------------------------------------------------------
		 * INFORMATIONS SOUS LES TARIFS
		 * -------------------------------------------------------------
		 */

		$infoY =
			$cadreY
			+ $hauteurEntete
			+ ($nombreTarifs * $hauteurLigne)
			+ 4;

		$pdf->SetFont(
			pdf_getPDFFont($outputlangs),
			'',
			$default_font_size - 2
		);

		$pdf->SetTextColor(
			80,
			80,
			80
		);

		$pdf->SetXY(
			$cadreX + 4,
			$infoY
		);

		$pdf->MultiCell(
			$cadreW - 8,
			4,
			'Tarifs indiqués TTC.',
			0,
			'L'
		);


		/*
		 * Date de mise à jour.
		 */
		if (!empty($dateMiseAJour)) {

			$timestamp = dol_stringtotime($dateMiseAJour);

			if ($timestamp) {

				$dateTexte = dol_print_date(
					$timestamp,
					'dayhour',
					false,
					$outputlangs
				);

				$pdf->SetXY(
					$cadreX + 4,
					$infoY + 5
				);

				$pdf->MultiCell(
					$cadreW - 8,
					4,
					'Dernière mise à jour : '.$dateTexte,
					0,
					'L'
				);
			}
		}


		/*
		 * -------------------------------------------------------------
		 * MENTIONS
		 * -------------------------------------------------------------
		 */

		$mentionY =
			$cadreY
			+ $hauteurCadre
			+ 8;

		$pdf->SetTextColor(
			40,
			40,
			40
		);

		$pdf->SetFont(
			pdf_getPDFFont($outputlangs),
			'',
			$default_font_size - 1
		);

		$mentions =
			"• Le forfait est dû lors d'une réparation réussie.\n"
			."• Les pièces sont garanties 3 mois dans le cadre d'une utilisation normale.";

		$pdf->SetXY(
			$this->marge_gauche,
			$mentionY
		);

		$pdf->MultiCell(
			$cadreW,
			5,
			$mentions,
			0,
			'L'
		);


		/*
		 * -------------------------------------------------------------
		 * PIED DE PAGE
		 * -------------------------------------------------------------
		 */

		$this->drawFooter(
			$pdf,
			$outputlangs
		);


		/*
		 * -------------------------------------------------------------
		 * NOM DU FICHIER
		 * -------------------------------------------------------------
		 */

		$filename = 'Tarifs_ElectroJul.pdf';


		/*
		 * -------------------------------------------------------------
		 * DOSSIER DE SORTIE
		 * -------------------------------------------------------------
		 *
		 * On utilise le dossier du module QualiRépar.
		 */

		$dir = '';

		if (!empty($conf->qualirepar->dir_output)) {

			$dir = $conf->qualirepar->dir_output;

		} else {

			$dir = DOL_DATA_ROOT.'/qualirepar';
		}


		if (!is_dir($dir)) {

			if (!dol_mkdir($dir)) {

				$this->error =
					'Impossible de créer le dossier : '.$dir;

				return -1;
			}
		}


		/*
		 * Chemin final.
		 */
		$filepath = $dir.'/'.$filename;


		/*
		 * -------------------------------------------------------------
		 * ÉCRITURE DU PDF
		 * -------------------------------------------------------------
		 */

		$pdf->Output(
			$filepath,
			'F'
		);


		/*
		 * Vérification.
		 */
		if (!is_readable($filepath)) {

			$this->error =
				'Le PDF n’a pas pu être créé : '.$filepath;

			return -1;
		}


		/*
		 * Informations disponibles pour l'API.
		 */
		$this->result = array(
			'fullpath' => $filepath,
			'filename' => $filename
		);

		return 1;
	}


	/**
	 * Dessine l'en-tête du document.
	 *
	 * @param TCPDF $pdf PDF
	 * @param Translate $outputlangs Langue
	 * @return void
	 */
	private function drawHeader(&$pdf, $outputlangs)
	{
		global $conf, $mysoc;

		$default_font_size =
			pdf_getPDFFontSize($outputlangs);

		/*
		 * Couleur type Dolibarr.
		 */
		$bleuR = 0;
		$bleuG = 0;
		$bleuB = 60;


		/*
		 * -------------------------------------------------------------
		 * LOGO
		 * -------------------------------------------------------------
		 */

		$logo = '';

		if (!getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO')) {

			if (!empty($mysoc->logo)) {

				$logodir =
					$conf->mycompany->dir_output;

				if (
					!empty(
						$conf->mycompany->multidir_output[
							$mysoc->entity ?? $conf->entity
						]
					)
				) {

					$logodir =
						$conf->mycompany->multidir_output[
							$mysoc->entity ?? $conf->entity
						];
				}


				if (!getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO')) {

					$logo =
						$logodir
						.'/logos/thumbs/'
						.$mysoc->logo_small;

				} else {

					$logo =
						$logodir
						.'/logos/'
						.$mysoc->logo;
				}
			}


			if (!empty($logo) && is_readable($logo)) {

				$height =
					pdf_getHeightForLogo($logo);

				/*
				 * Évite un logo trop haut.
				 */
				if ($height > 28) {
					$height = 28;
				}

				$pdf->Image(
					$logo,
					$this->marge_gauche,
					$this->marge_haute,
					0,
					$height
				);

			} else {

				/*
				 * Pas de logo :
				 * affichage du nom de l'entreprise.
				 */

				$pdf->SetTextColor(
					$bleuR,
					$bleuG,
					$bleuB
				);

				$pdf->SetFont(
					pdf_getPDFFont($outputlangs),
					'B',
					$default_font_size + 4
				);

				$pdf->SetXY(
					$this->marge_gauche,
					$this->marge_haute
				);

				$pdf->MultiCell(
					80,
					8,
					!empty($mysoc->name)
						? $outputlangs->convToOutputCharset($mysoc->name)
						: 'ElectroJul',
					0,
					'L'
				);
			}
		}


		/*
		 * -------------------------------------------------------------
		 * TITRE À DROITE
		 * -------------------------------------------------------------
		 */

		$w = 100;

		$posx =
			$this->page_largeur
			- $this->marge_droite
			- $w;

		$pdf->SetTextColor(
			$bleuR,
			$bleuG,
			$bleuB
		);

		$pdf->SetFont(
			pdf_getPDFFont($outputlangs),
			'B',
			$default_font_size + 4
		);

		$pdf->SetXY(
			$posx,
			$this->marge_haute + 2
		);

		$pdf->MultiCell(
			$w,
			7,
			'Tarifs ElectroJul',
			0,
			'R'
		);


		/*
		 * Sous-titre.
		 */
		$pdf->SetFont(
			pdf_getPDFFont($outputlangs),
			'',
			$default_font_size - 1
		);

		$pdf->SetTextColor(
			70,
			70,
			70
		);

		$pdf->SetXY(
			$posx,
			$this->marge_haute + 11
		);

		$pdf->MultiCell(
			$w,
			5,
			'Réparation électroménager à domicile',
			0,
			'R'
		);


		/*
		 * -------------------------------------------------------------
		 * LIGNE DE SÉPARATION
		 * -------------------------------------------------------------
		 */

		$pdf->SetDrawColor(
			128,
			128,
			128
		);

		$pdf->SetLineWidth(0.3);

		$pdf->Line(
			$this->marge_gauche,
			47,
			$this->page_largeur - $this->marge_droite,
			47
		);
	}


	/**
	 * Dessine le pied de page.
	 *
	 * @param TCPDF $pdf PDF
	 * @param Translate $outputlangs Langue
	 * @return void
	 */
	private function drawFooter(&$pdf, $outputlangs)
	{
		global $mysoc;

		$default_font_size =
			pdf_getPDFFontSize($outputlangs);

		$y =
			$this->page_hauteur
			- $this->marge_basse
			+ 2;


		/*
		 * Ligne supérieure.
		 */
		$pdf->SetDrawColor(
			180,
			180,
			180
		);

		$pdf->SetLineWidth(0.2);

		$pdf->Line(
			$this->marge_gauche,
			$y - 4,
			$this->page_largeur - $this->marge_droite,
			$y - 4
		);


		/*
		 * Texte.
		 */
		$pdf->SetTextColor(
			90,
			90,
			90
		);

		$pdf->SetFont(
			pdf_getPDFFont($outputlangs),
			'',
			$default_font_size - 2
		);


		/*
		 * Coordonnées.
		 */
		$footer = '';

		if (!empty($mysoc->name)) {

			$footer .=
				$outputlangs->convToOutputCharset(
					$mysoc->name
				);
		}

		if (!empty($mysoc->phone)) {

			$footer .=
				($footer ? '  •  ' : '')
				.$mysoc->phone;
		}

		if (!empty($mysoc->email)) {

			$footer .=
				($footer ? '  •  ' : '')
				.$mysoc->email;
		}


		/*
		 * Adresse si disponible.
		 */
		$address = '';

		if (!empty($mysoc->address)) {

			$address .=
				$mysoc->address;
		}

		if (!empty($mysoc->zip)) {

			$address .=
				($address ? '  ' : '')
				.$mysoc->zip;
		}

		if (!empty($mysoc->town)) {

			$address .=
				($address ? ' ' : '')
				.$mysoc->town;
		}


		if ($address) {

			$footer .=
				($footer ? '  •  ' : '')
				.$address;
		}


		/*
		 * Texte pied de page.
		 */
		$pdf->SetXY(
			$this->marge_gauche,
			$y
		);

		$pdf->MultiCell(
			$this->page_largeur
				- $this->marge_gauche
				- $this->marge_droite
				- 20,
			4,
			$footer,
			0,
			'L'
		);


		/*
		 * Numéro de page.
		 */
		$pdf->SetXY(
			$this->page_largeur
				- $this->marge_droite
				- 20,
			$y
		);

		$pdf->MultiCell(
			20,
			4,
			'Page '.$pdf->getPage(),
			0,
			'R'
		);


		$pdf->SetTextColor(
			0,
			0,
			0
		);
	}
}
