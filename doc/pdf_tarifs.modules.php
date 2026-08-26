<?php
/**
 * Générateur PDF public des tarifs Electrojul
 *
 * Fichier :
 * custom/qualirepar/core/modules/qualirepar/doc/pdf_tarifs.modules.php
 *
 * Génère une fiche tarifaire publique indépendante des devis/factures.
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/pdf/modules_pdf.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

require_once DOL_DOCUMENT_ROOT.'/custom/qualirepar/class/tarifs.class.php';


/**
 * Classe de génération du PDF des tarifs
 */
class pdf_tarifs extends ModelePDFFacture
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
	public $description = 'Fiche tarifaire publique Electrojul';

	/**
	 * @var string
	 */
	public $type = 'pdf';

	/**
	 * @var int
	 */
	public $page_largeur;

	/**
	 * @var int
	 */
	public $page_hauteur;

	/**
	 * @var int
	 */
	public $marge_gauche;

	/**
	 * @var int
	 */
	public $marge_droite;

	/**
	 * @var int
	 */
	public $marge_haute;

	/**
	 * @var int
	 */
	public $marge_basse;

	/**
	 * @var float
	 */
	public $corner_radius = 2;


	/**
	 * Constructeur
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		$this->name = 'tarifs';
		$this->description = 'Fiche tarifaire publique Electrojul';
		$this->type = 'pdf';

		// Format A4 portrait
		$this->page_largeur = 210;
		$this->page_hauteur = 297;

		// Marges
		$this->marge_gauche = 10;
		$this->marge_droite = 10;
		$this->marge_haute = 10;
		$this->marge_basse = 15;
	}


	/**
	 * Génération du document PDF
	 *
	 * @param object $object Objet Dolibarr utilisé uniquement pour l'identification
	 * @param Translate $outputlangs Langue
	 * @param string $srctemplatepath Chemin template
	 * @param int $hidedetails Masquer détails
	 * @param int $hidedesc Masquer description
	 * @param int $hideref Masquer référence
	 * @return int 1 si OK, -1 si erreur
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
		// Chargement des langues
		// -----------------------------------------------------------------

		$outputlangs->loadLangs(array('main', 'companies', 'products'));


		// -----------------------------------------------------------------
		// Récupération des tarifs
		// -----------------------------------------------------------------

		$tarifsObj = new QualiReparTarifs($this->db);

		$tarifs = $tarifsObj->getTarifs();

		$dateMiseAJour = $tarifsObj->getDateMiseAJour();


		// -----------------------------------------------------------------
		// Création du PDF
		// -----------------------------------------------------------------

		$pdf = pdf_getInstance(
			array(
				$this->page_largeur,
				$this->page_hauteur
			),
			'P',
			'mm',
			true,
			'UTF-8'
		);

		if (method_exists($pdf, 'SetCreator')) {
			$pdf->SetCreator('Dolibarr');
		}

		if (method_exists($pdf, 'SetAuthor')) {
			$pdf->SetAuthor($mysoc->name);
		}

		$pdf->SetTitle('Tarifs '.$mysoc->name);
		$pdf->SetSubject('Tarifs et prestations');
		$pdf->SetKeywords('tarifs, Electrojul, réparation, électroménager');

		$pdf->SetMargins(
			$this->marge_gauche,
			$this->marge_haute,
			$this->marge_droite
		);

		$pdf->SetAutoPageBreak(
			true,
			$this->marge_basse
		);

		$pdf->SetPrintHeader(false);
		$pdf->SetPrintFooter(false);

		$pdf->AddPage();


		// -----------------------------------------------------------------
		// Paramètres graphiques
		// -----------------------------------------------------------------

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$bleu = array(0, 0, 60);
		$gris_fond = array(230, 230, 230);
		$gris_clair = array(248, 248, 248);
		$gris_ligne = array(128, 128, 128);


		// -----------------------------------------------------------------
		// EN-TÊTE
		// -----------------------------------------------------------------

		$posy = $this->marge_haute;

		$largeur_titre = 90;

		$posx_titre = $this->page_largeur
			- $this->marge_droite
			- $largeur_titre;


		// -------------------------------------------------------------
		// Logo
		// -------------------------------------------------------------

		$logo_affiche = false;

		if (!getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO')) {

			if (!empty($mysoc->logo)) {

				$logodir = $conf->mycompany->dir_output;

				if (!empty($conf->mycompany->multidir_output[$mysoc->entity ?? $conf->entity])) {
					$logodir = $conf->mycompany->multidir_output[$mysoc->entity ?? $conf->entity];
				}

				if (!getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO')) {
					$logo = $logodir.'/logos/thumbs/'.$mysoc->logo_small;
				} else {
					$logo = $logodir.'/logos/'.$mysoc->logo;
				}

				if (is_readable($logo)) {

					$height = pdf_getHeightForLogo($logo);

					// Limite raisonnable pour l'en-tête
					if ($height > 25) {
						$height = 25;
					}

					$pdf->Image(
						$logo,
						$this->marge_gauche,
						$posy,
						0,
						$height
					);

					$logo_affiche = true;
				}
			}
		}


		// -------------------------------------------------------------
		// Nom de l'entreprise si aucun logo
		// -------------------------------------------------------------

		if (!$logo_affiche) {

			$pdf->SetTextColor(
				$bleu[0],
				$bleu[1],
				$bleu[2]
			);

			$pdf->SetFont(
				'',
				'B',
				$default_font_size + 2
			);

			$pdf->SetXY(
				$this->marge_gauche,
				$posy
			);

			$pdf->MultiCell(
				90,
				5,
				$outputlangs->convToOutputCharset($mysoc->name),
				0,
				'L'
			);
		}


		// -------------------------------------------------------------
		// Titre TARIFS
		// -------------------------------------------------------------

		$pdf->SetTextColor(
			$bleu[0],
			$bleu[1],
			$bleu[2]
		);

		$pdf->SetFont(
			'',
			'B',
			$default_font_size + 5
		);

		$pdf->SetXY(
			$posx_titre,
			$posy
		);

		$pdf->MultiCell(
			$largeur_titre,
			6,
			'TARIFS',
			0,
			'R'
		);


		// -------------------------------------------------------------
		// Sous-titre
		// -------------------------------------------------------------

		$pdf->SetFont(
			'',
			'',
			$default_font_size - 1
		);

		$pdf->SetTextColor(
			$bleu[0],
			$bleu[1],
			$bleu[2]
		);

		$pdf->SetXY(
			$posx_titre,
			$posy + 8
		);

		$pdf->MultiCell(
			$largeur_titre,
			4,
			'Prestations et tarifs',
			0,
			'R'
		);


		// -------------------------------------------------------------
		// Date de mise à jour
		// -------------------------------------------------------------

		if (!empty($dateMiseAJour)) {

			$timestamp = dol_stringtotime($dateMiseAJour);

			if ($timestamp > 0) {

				$dateTexte = dol_print_date(
					$timestamp,
					'day',
					false,
					$outputlangs,
					true
				);

			} else {

				$dateTexte = $dateMiseAJour;
			}

			$pdf->SetXY(
				$posx_titre,
				$posy + 13
			);

			$pdf->SetFont(
				'',
				'',
				$default_font_size - 2
			);

			$pdf->MultiCell(
				$largeur_titre,
				4,
				'Mis à jour le '.$dateTexte,
				0,
				'R'
			);
		}


		// -----------------------------------------------------------------
		// Ligne sous l'en-tête
		// -----------------------------------------------------------------

		$posy_table = 42;

		$pdf->SetDrawColor(
			$gris_ligne[0],
			$gris_ligne[1],
			$gris_ligne[2]
		);

		$pdf->Line(
			$this->marge_gauche,
			$posy_table - 5,
			$this->page_largeur - $this->marge_droite,
			$posy_table - 5
		);


		// -----------------------------------------------------------------
		// TITRE DU TABLEAU
		// -----------------------------------------------------------------

		$table_x = $this->marge_gauche;
		$table_width = $this->page_largeur
			- $this->marge_gauche
			- $this->marge_droite;

		$col_prix = 42;
		$col_nom = $table_width - $col_prix;

		$ligne_hauteur = 8;


		// -----------------------------------------------------------------
		// Cadre extérieur
		// -----------------------------------------------------------------

		// On calcule la hauteur approximative du tableau.
		// Les lignes peuvent ensuite être réparties normalement.

		$nb_tarifs = count($tarifs);

		$hauteur_entete = 10;

		$hauteur_tableau_estimee =
			$hauteur_entete +
			($nb_tarifs * $ligne_hauteur) +
			12;


		// Si le tableau est raisonnablement court, on dessine le cadre.
		// Pour les très grandes listes, le tableau pourra continuer sur
		// une page suivante sans sortir de la page.

		$pdf->SetDrawColor(
			$gris_ligne[0],
			$gris_ligne[1],
			$gris_ligne[2]
		);


		// -----------------------------------------------------------------
		// En-tête du tableau
		// -----------------------------------------------------------------

		$pdf->SetFillColor(
			$gris_fond[0],
			$gris_fond[1],
			$gris_fond[2]
		);

		$pdf->SetTextColor(
			$bleu[0],
			$bleu[1],
			$bleu[2]
		);

		$pdf->SetFont(
			'',
			'B',
			$default_font_size - 1
		);

		$pdf->SetXY(
			$table_x,
			$posy_table
		);

		$pdf->MultiCell(
			$col_nom,
			$hauteur_entete,
			'Prestations',
			1,
			'L',
			true
		);

		$pdf->SetXY(
			$table_x + $col_nom,
			$posy_table
		);

		$pdf->MultiCell(
			$col_prix,
			$hauteur_entete,
			'Prix TTC',
			1,
			'R',
			true
		);


		// -----------------------------------------------------------------
		// Lignes des tarifs
		// -----------------------------------------------------------------

		$current_y = $posy_table + $hauteur_entete;

		$pdf->SetFont(
			'',
			'',
			$default_font_size - 1
		);

		$pdf->SetTextColor(0, 0, 0);


		if (empty($tarifs)) {

			$pdf->SetFillColor(
				255,
				255,
				255
			);

			$pdf->SetXY(
				$table_x,
				$current_y
			);

			$pdf->MultiCell(
				$table_width,
				$ligne_hauteur,
				'Aucun tarif disponible.',
				1,
				'L',
				true
			);

			$current_y += $ligne_hauteur;

		} else {

			$i = 0;

			foreach ($tarifs as $tarif) {

				// -----------------------------------------------------
				// Nouvelle page si nécessaire
				// -----------------------------------------------------

				if (
					$current_y + $ligne_hauteur + 20
					>
					$this->page_hauteur - $this->marge_basse
				) {

					$this->_pagefoot(
						$pdf,
						$outputlangs
					);

					$pdf->AddPage();

					$current_y = $this->marge_haute + 5;

					$pdf->SetFont(
						'',
						'',
						$default_font_size - 1
					);

					$pdf->SetTextColor(
						0,
						0,
						0
					);
				}


				// Alternance légère des lignes
				if ($i % 2 == 0) {

					$pdf->SetFillColor(
						255,
						255,
						255
					);

				} else {

					$pdf->SetFillColor(
						$gris_clair[0],
						$gris_clair[1],
						$gris_clair[2]
					);
				}


				// -----------------------------------------------------
				// Nom de la prestation
				// -----------------------------------------------------

				$nom = trim(
					$outputlangs->convToOutputCharset(
						$tarif['label']
					)
				);

				if ($nom === '') {
					$nom = 'Prestation';
				}


				$pdf->SetXY(
					$table_x,
					$current_y
				);

				$pdf->MultiCell(
					$col_nom,
					$ligne_hauteur,
					$nom,
					1,
					'L',
					true
				);


				// -----------------------------------------------------
				// Prix TTC
				// -----------------------------------------------------

				$prix = price(
					$tarif['price_ttc'],
					0,
					$outputlangs
				);

				$pdf->SetXY(
					$table_x + $col_nom,
					$current_y
				);

				$pdf->MultiCell(
					$col_prix,
					$ligne_hauteur,
					$prix,
					1,
					'R',
					true
				);


				$current_y += $ligne_hauteur;

				$i++;
			}
		}


		// -----------------------------------------------------------------
		// Mentions sous le tableau
		// -----------------------------------------------------------------

		$current_y += 8;

		$pdf->SetTextColor(
			$bleu[0],
			$bleu[1],
			$bleu[2]
		);

		$pdf->SetFont(
			'',
			'B',
			$default_font_size - 1
		);

		$pdf->SetXY(
			$table_x,
			$current_y
		);

		$pdf->MultiCell(
			$table_width,
			5,
			'Informations tarifaires',
			0,
			'L'
		);

		$current_y = $pdf->getY() + 2;


		// -----------------------------------------------------------------
		// Texte informatif
		// -----------------------------------------------------------------

		$pdf->SetFont(
			'',
			'',
			$default_font_size - 1
		);

		$pdf->SetTextColor(
			0,
			0,
			0
		);

		$mentions =
			'Le forfait est dû lors d’une réparation réussie.'
			."\n"
			.'Les pièces sont garanties 3 mois dans le cadre d’une utilisation normale.';


		$pdf->SetXY(
			$table_x,
			$current_y
		);

		$pdf->MultiCell(
			$table_width,
			5,
			$mentions,
			0,
			'L'
		);


		// -----------------------------------------------------------------
		// PIED DE PAGE
		// -----------------------------------------------------------------

		$this->_pagefoot(
			$pdf,
			$outputlangs
		);


		// -----------------------------------------------------------------
		// Nom du fichier
		// -----------------------------------------------------------------

		$filename = 'tarifs.pdf';


		// -----------------------------------------------------------------
		// Sortie
		// -----------------------------------------------------------------

		$pdf->Output(
			$filename,
			'D'
		);


		return 1;
	}


	/**
	 * Pied de page personnalisé
	 *
	 * @param TCPDF $pdf PDF
	 * @param Translate $outputlangs Langue
	 * @return void
	 */
	protected function _pagefoot(&$pdf, $outputlangs)
	{
		global $mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$y = $this->page_hauteur - $this->marge_basse + 2;

		// Ligne supérieure
		$pdf->SetDrawColor(
			128,
			128,
			128
		);

		$pdf->Line(
			$this->marge_gauche,
			$y - 3,
			$this->page_largeur - $this->marge_droite,
			$y - 3
		);


		// -------------------------------------------------------------
		// Nom entreprise
		// -------------------------------------------------------------

		$pdf->SetTextColor(
			0,
			0,
			60
		);

		$pdf->SetFont(
			'',
			'B',
			$default_font_size - 2
		);

		$pdf->SetXY(
			$this->marge_gauche,
			$y
		);

		$pdf->MultiCell(
			90,
			4,
			$outputlangs->convToOutputCharset($mysoc->name),
			0,
			'L'
		);


		// -------------------------------------------------------------
		// Coordonnées
		// -------------------------------------------------------------

		$coordonnees = array();

		if (!empty($mysoc->phone)) {
			$coordonnees[] = 'Tél. : '.$mysoc->phone;
		}

		if (!empty($mysoc->email)) {
			$coordonnees[] = $mysoc->email;
		}

		$texte_coordonnees = implode('  •  ', $coordonnees);


		if ($texte_coordonnees !== '') {

			$pdf->SetFont(
				'',
				'',
				$default_font_size - 2
			);

			$pdf->SetXY(
				$this->marge_gauche,
				$y + 4
			);

			$pdf->MultiCell(
				150,
				4,
				$outputlangs->convToOutputCharset(
					$texte_coordonnees
				),
				0,
				'L'
			);
		}


		// -------------------------------------------------------------
		// Page
		// -------------------------------------------------------------

		$pdf->SetFont(
			'',
			'',
			$default_font_size - 2
		);

		$pdf->SetXY(
			$this->page_largeur - $this->marge_droite - 40,
			$y
		);

		$pdf->MultiCell(
			40,
			4,
			'Page '.$pdf->getAliasNumPage().' / '.$pdf->getAliasNbPages(),
			0,
			'R'
		);
	}
}
