<?php
/**
 * Générateur PDF de la fiche tarifaire ElectroJul
 *
 * @package QualiRepar
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/qualirepar/class/tarifs.class.php';


/**
 * Classe de génération du PDF des tarifs.
 */
class pdf_tarifs extends ModelePDF
{
	/**
	 * @var DoliDB
	 */
	public $db;

	/**
	 * Nom du modèle.
	 *
	 * @var string
	 */
	public $name = 'tarifs';

	/**
	 * Description du modèle.
	 *
	 * @var string
	 */
	public $description = 'Fiche tarifaire ElectroJul';

	/**
	 * Type de document.
	 *
	 * @var string
	 */
	public $type = 'pdf_tarifs';

	/**
	 * Extension.
	 *
	 * @var string
	 */
	public $extension = 'pdf';

	/**
	 * Format papier.
	 *
	 * @var string
	 */
	public $format = 'A4';

	/**
	 * Orientation.
	 *
	 * @var string
	 */
	public $orientation = 'P';

	/**
	 * Marges.
	 */
	public $marge_gauche = 10;
	public $marge_droite = 10;
	public $marge_haute = 10;
	public $marge_basse = 12;

	/**
	 * Rayon des coins.
	 *
	 * @var float
	 */
	public $corner_radius = 2;

	/**
	 * Largeur page.
	 *
	 * @var float
	 */
	public $page_largeur;

	/**
	 * Hauteur page.
	 *
	 * @var float
	 */
	public $page_hauteur;

	/**
	 * Constructeur.
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		$this->page_largeur = 210;
		$this->page_hauteur = 297;

		$this->marge_gauche = 10;
		$this->marge_droite = 10;
		$this->marge_haute = 10;
		$this->marge_basse = 12;

		$this->name = 'tarifs';
		$this->description = 'Fiche tarifaire ElectroJul';
		$this->type = 'pdf_tarifs';
		$this->extension = 'pdf';
		$this->format = 'A4';
		$this->orientation = 'P';
	}


	/**
	 * Génère le document PDF.
	 *
	 * @param object $object Objet éventuellement transmis par Dolibarr
	 * @param Translate $outputlangs Langue
	 * @param string $srctemplatepath Chemin modèle source
	 * @param string $hidedetails Masquer détails
	 * @param string $hidedesc Masquer description
	 * @param string $hideref Masquer référence
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

		// -----------------------------------------------------------------
		// Sécurité
		// -----------------------------------------------------------------

		if (!is_object($outputlangs)) {
			global $langs;
			$outputlangs = $langs;
		}

		$outputlangs->loadLangs(array('main', 'companies'));

		// -----------------------------------------------------------------
		// Création du PDF
		// -----------------------------------------------------------------

		require_once TCPDF_PATH.'/tcpdf.php';

		$pdf = new TCPDF(
			$this->orientation,
			'mm',
			$this->format,
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
		$pdf->SetKeywords('ElectroJul, tarifs, réparation, électroménager');

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

		// -----------------------------------------------------------------
		// Couleurs
		// -----------------------------------------------------------------

		$bleu = array(0, 0, 60);
		$gris = array(224, 224, 224);
		$grisClair = array(248, 248, 248);
		$blanc = array(255, 255, 255);

		// -----------------------------------------------------------------
		// En-tête
		// -----------------------------------------------------------------

		$this->drawHeader(
			$pdf,
			$outputlangs,
			$bleu
		);

		// -----------------------------------------------------------------
		// Récupération des tarifs
		// -----------------------------------------------------------------

		$tarifsObj = new QualiReparTarifs($this->db);

		$tarifs = $tarifsObj->getTarifs();

		$dateMiseAJour = $tarifsObj->getDateMiseAJour();

		// -----------------------------------------------------------------
		// Position du tableau
		// -----------------------------------------------------------------

		$posy = 63;

		// -----------------------------------------------------------------
		// Titre de la section
		// -----------------------------------------------------------------

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont(
			pdf_getPDFFont($outputlangs),
			'B',
			pdf_getPDFFontSize($outputlangs) + 1
		);

		$pdf->SetXY(
			$this->marge_gauche,
			$posy
		);

		$pdf->MultiCell(
			$this->page_largeur
				- $this->marge_gauche
				- $this->marge_droite,
			6,
			'Nos tarifs',
			0,
			'L'
		);

		$posy += 8;

		// -----------------------------------------------------------------
		// Cadre principal
		// -----------------------------------------------------------------

		$cadreX = $this->marge_gauche;
		$cadreY = $posy;

		$cadreW =
			$this->page_largeur
			- $this->marge_gauche
			- $this->marge_droite;

		// Hauteur calculée après récupération des lignes.
		$hauteurLigne = 9;

		$nombreTarifs = count($tarifs);

		$hauteurEntete = 10;

		$hauteurCadre =
			$hauteurEntete
			+ ($nombreTarifs * $hauteurLigne)
			+ 12;

		// Sécurité si aucun tarif.
		if ($nombreTarifs === 0) {
			$hauteurCadre = 35;
		}

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetLineWidth(0.25);

		$pdf->RoundedRect(
			$cadreX,
			$cadreY,
			$cadreW,
			$hauteurCadre,
			$this->corner_radius,
			'1234',
			'D'
		);

		// -----------------------------------------------------------------
		// En-tête du tableau
		// -----------------------------------------------------------------

		$pdf->SetFillColor(
			$gris[0],
			$gris[1],
			$gris[2]
		);

		$pdf->SetTextColor(
			0,
			0,
			60
		);

		$pdf->SetFont(
			pdf_getPDFFont($outputlangs),
			'B',
			pdf_getPDFFontSize($outputlangs) - 1
		);

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

		// Ligne sous l'en-tête
		$pdf->SetDrawColor(128, 128, 128);

		$pdf->Line(
			$cadreX,
			$cadreY + $hauteurEntete,
			$cadreX + $cadreW,
			$cadreY + $hauteurEntete
		);

		// -----------------------------------------------------------------
		// Lignes des tarifs
		// -----------------------------------------------------------------

		$y = $cadreY + $hauteurEntete;

		$pdf->SetFont(
			pdf_getPDFFont($outputlangs),
			'',
			pdf_getPDFFontSize($outputlangs)
		);

		if ($nombreTarifs > 0) {

			foreach ($tarifs as $index => $tarif) {

				// Alternance légère des lignes.
				if ($index % 2 === 1) {

					$pdf->SetFillColor(
						$grisClair[0],
						$grisClair[1],
						$grisClair[2]
					);

					$pdf->Rect(
						$cadreX,
						$y,
						$cadreW,
						$hauteurLigne,
						'F'
					);
				}

				$pdf->SetTextColor(
					40,
					40,
					40
				);

				// Désignation
				$pdf->SetXY(
					$cadreX + 4,
					$y + 1.5
				);

				$label = trim(
					(string) $tarif['label']
				);

				$pdf->MultiCell(
					$cadreW - 53,
					$hauteurLigne - 2,
					$outputlangs->convToOutputCharset($label),
					0,
					'L',
					false
				);

				// Prix
				$pdf->SetXY(
					$cadreX + $cadreW - 45,
					$y + 1.5
				);

				$prix = price(
					(float) $tarif['price_ttc'],
					0,
					$outputlangs
				);

				$pdf->MultiCell(
					41,
					$hauteurLigne - 2,
					$prix,
					0,
					'R',
					false
				);

				// Ligne séparatrice
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
				$y + 4
			);

			$pdf->MultiCell(
				$cadreW - 8,
				8,
				'Aucun tarif disponible.',
				0,
				'L'
			);

			$y += 12;
		}

		// -----------------------------------------------------------------
		// Information sous le tableau
		// -----------------------------------------------------------------

		$y += 6;

		$pdf->SetTextColor(
			80,
			80,
			80
		);

		$pdf->SetFont(
			pdf_getPDFFont($outputlangs),
			'',
			pdf_getPDFFontSize($outputlangs) - 2
		);

		$pdf->SetXY(
			$cadreX + 4,
			$y
		);

		$pdf->MultiCell(
			$cadreW - 8,
			4,
			'Tarifs TTC.',
			0,
			'L'
		);

		if (!empty($dateMiseAJour)) {

			$date = dol_print_date(
				dol_stringtotime($dateMiseAJour),
				'dayhour',
				false,
				$outputlangs
			);

			$pdf->SetXY(
				$cadreX + 4,
				$y + 4
			);

			$pdf->MultiCell(
				$cadreW - 8,
				4,
				'Dernière mise à jour : '.$date,
				0,
				'L'
			);
		}

		// -----------------------------------------------------------------
		// Mentions tarifaires
		// -----------------------------------------------------------------

		$y += 12;

		$pdf->SetTextColor(
			40,
			40,
			40
		);

		$pdf->SetFont(
			pdf_getPDFFont($outputlangs),
			'',
			pdf_getPDFFontSize($outputlangs) - 1
		);

		$mentions = array();

		$mentions[] =
			'Le forfait est dû lors d’une réparation réussie.';

		$mentions[] =
			'Les pièces sont garanties 3 mois dans le cadre d’une utilisation normale.';

		$mentionsText = '';

		foreach ($mentions as $mention) {

			$mentionsText .= '• '.$mention."\n";
		}

		$pdf->SetXY(
			$this->marge_gauche,
			$y
		);

		$pdf->MultiCell(
			$cadreW,
			5,
			$mentionsText,
			0,
			'L'
		);

		// -----------------------------------------------------------------
		// Pied de page
		// -----------------------------------------------------------------

		$this->drawFooter(
			$pdf,
			$outputlangs
		);

		// -----------------------------------------------------------------
		// Nom du fichier
		// -----------------------------------------------------------------

		$filename = 'Tarifs_ElectroJul.pdf';

		// -----------------------------------------------------------------
		// Création du dossier
		// -----------------------------------------------------------------

		$dir = '';

		if (!empty($conf->qualirepar->dir_output)) {
			$dir = $conf->qualirepar->dir_output;
		} elseif (!empty($conf->mycompany->dir_output)) {
			$dir = $conf->mycompany->dir_output;
		}

		if (empty($dir)) {
			$dir = DOL_DATA_ROOT.'/qualirepar';
		}

		if (!is_dir($dir)) {
			dol_mkdir($dir);
		}

		$filepath = $dir.'/'.$filename;

		// -----------------------------------------------------------------
		// Sauvegarde
		// -----------------------------------------------------------------

		$pdf->Output(
			$filepath,
			'F'
		);

		if (!is_readable($filepath)) {
			$this->error = 'Impossible de créer le fichier PDF : '.$filepath;
			return -1;
		}

		// Pour permettre à Dolibarr de récupérer le fichier.
		$this->result = array(
			'fullpath' => $filepath,
			'filename' => $filename
		);

		return 1;
	}


	/**
	 * Dessine l'en-tête.
	 *
	 * @param TCPDF $pdf PDF
	 * @param Translate $outputlangs Langue
	 * @param array $bleu Couleur
	 * @return void
	 */
	private function drawHeader(&$pdf, $outputlangs, $bleu)
	{
		global $conf, $mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$posy = $this->marge_haute;

		// -------------------------------------------------------------
		// Logo
		// -------------------------------------------------------------

		$logo = '';

		if (!getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO')) {

			if (!empty($mysoc->logo)) {

				$logodir = $conf->mycompany->dir_output;

				if (!empty($conf->mycompany->multidir_output[$mysoc->entity ?? $conf->entity])) {
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

				$height = pdf_getHeightForLogo($logo);

				// Limite de hauteur du logo.
				$height = min($height, 28);

				$pdf->Image(
					$logo,
					$this->marge_gauche,
					$posy,
					0,
					$height
				);

			} else {

				// Si aucun logo, afficher le nom de l'entreprise.
				$pdf->SetTextColor(
					$bleu[0],
					$bleu[1],
					$bleu[2]
				);

				$pdf->SetFont(
					pdf_getPDFFont($outputlangs),
					'B',
					$default_font_size + 4
				);

				$pdf->SetXY(
					$this->marge_gauche,
					$posy
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

		// -------------------------------------------------------------
		// Titre à droite
		// -------------------------------------------------------------

		$w = 95;

		$posx =
			$this->page_largeur
			- $this->marge_droite
			- $w;

		$pdf->SetTextColor(
			$bleu[0],
			$bleu[1],
			$bleu[2]
		);

		$pdf->SetFont(
			pdf_getPDFFont($outputlangs),
			'B',
			$default_font_size + 5
		);

		$pdf->SetXY(
			$posx,
			$posy + 3
		);

		$pdf->MultiCell(
			$w,
			8,
			'Tarifs ElectroJul',
			0,
			'R'
		);

		// -------------------------------------------------------------
		// Sous-titre
		// -------------------------------------------------------------

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
			$posy + 13
		);

		$pdf->MultiCell(
			$w,
			5,
			'Réparation électroménager à domicile',
			0,
			'R'
		);

		// -------------------------------------------------------------
		// Ligne sous l'en-tête
		// -------------------------------------------------------------

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

		$page = $pdf->getPage();

		$y =
			$this->page_hauteur
			- $this->marge_basse
			+ 2;

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

		$pdf->SetTextColor(
			90,
			90,
			90
		);

		$pdf->SetFont(
			pdf_getPDFFont($outputlangs),
			'',
			pdf_getPDFFontSize($outputlangs) - 2
		);

		// -------------------------------------------------------------
		// Coordonnées
		// -------------------------------------------------------------

		$footer = '';

		if (!empty($mysoc->name)) {
			$footer .= $mysoc->name;
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
			$outputlangs->convToOutputCharset($footer),
			0,
			'L'
		);

		// -------------------------------------------------------------
		// Numéro de page
		// -------------------------------------------------------------

		$pdf->SetXY(
			$this->page_largeur - $this->marge_droite - 20,
			$y
		);

		$pdf->MultiCell(
			20,
			4,
			'Page '.$page,
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
