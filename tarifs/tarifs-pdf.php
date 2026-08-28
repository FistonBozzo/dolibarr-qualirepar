<?php
/**
 * ElectroJul - PDF des tarifs
 *
 * Compatible Dolibarr 23.x
 * Fichier : tarifs-pdf.php
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
    die('This file must be called from Dolibarr.');
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

class TarifsPdf
{
    /** @var DoliDB */
    public $db;

    /** @var Societe */
    public $emetteur;

    public $page_largeur = 210;
    public $page_hauteur = 297;
    public $marge_gauche = 10;
    public $marge_droite = 10;
    public $marge_haute = 15;
    public $marge_basse = 15;

    public function __construct($db)
    {
        $this->db = $db;

        $this->emetteur = new Societe($db);
        $idSociete = getDolGlobalInt('MAIN_INFO_SOCIETE_ID');

        if ($idSociete > 0) {
            $this->emetteur->fetch($idSociete);
        }
    }

    /**
     * Génère le PDF.
     *
     * @param string $file Chemin complet du fichier PDF
     * @return int 1 si OK, -1 si erreur
     */
    public function generate($file)
    {
        global $langs;

        if (empty($file)) {
            return -1;
        }

        $outputlangs = clone $langs;
        $outputlangs->loadLangs(array('main', 'companies', 'propal'));

        $pdf = pdf_getInstance('', 'mm', 'P');
        if (!is_object($pdf)) {
            return -1;
        }

        if (method_exists($pdf, 'setPrintHeader')) {
            $pdf->setPrintHeader(false);
        }
        if (method_exists($pdf, 'setPrintFooter')) {
            $pdf->setPrintFooter(false);
        }

        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
        $pdf->SetAutoPageBreak(true, $this->marge_basse);
        $pdf->SetFont(pdf_getPDFFont($outputlangs), '', pdf_getPDFFontSize($outputlangs));
        $pdf->SetTitle($outputlangs->convToOutputCharset('Tarifs - ' . $this->emetteur->name));
        $pdf->SetSubject($outputlangs->convToOutputCharset('Tarifs ElectroJul'));
        $pdf->SetCreator('Dolibarr ' . (defined('DOL_VERSION') ? DOL_VERSION : ''));
        $pdf->SetAuthor($outputlangs->convToOutputCharset($this->emetteur->name));

        $pdf->AddPage();

        $this->drawHeader($pdf, $outputlangs);
        $this->drawTarifs($pdf, $outputlangs);
        $this->drawFooter($pdf, $outputlangs);

        $dir = dirname($file);
        if (!is_dir($dir)) {
            if (dol_mkdir($dir) < 0) {
                return -1;
            }
        }

        $pdf->Output($file, 'F');

        return file_exists($file) ? 1 : -1;
    }

    /**
     * En-tête avec logo et informations société.
     */
    protected function drawHeader(&$pdf, $outputlangs)
    {
        global $conf;

        $fontSize = pdf_getPDFFontSize($outputlangs);
        $x = $this->marge_gauche;
        $y = $this->marge_haute;
        $rightX = $this->page_largeur - $this->marge_droite - 90;

        // Logo Dolibarr de la société.
        if (!getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO') && !empty($this->emetteur->logo)) {
            $logodir = $conf->mycompany->dir_output;

            if (!empty($conf->mycompany->multidir_output[$conf->entity])) {
                $logodir = $conf->mycompany->multidir_output[$conf->entity];
            }

            if (!getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO') && !empty($this->emetteur->logo_small)) {
                $logo = $logodir . '/logos/thumbs/' . $this->emetteur->logo_small;
            } else {
                $logo = $logodir . '/logos/' . $this->emetteur->logo;
            }

            if (is_readable($logo)) {
                $height = pdf_getHeightForLogo($logo);
                $height = min($height, 28);
                $pdf->Image($logo, $x, $y, 0, $height);
            }
        } else {
            $pdf->SetFont('', 'B', $fontSize + 3);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->SetXY($x, $y);
            $pdf->MultiCell(90, 6, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
        }

        // Titre.
        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', $fontSize + 5);
        $pdf->SetXY($rightX, $y);
        $pdf->MultiCell(90, 7, 'TARIFS', 0, 'R');

        $pdf->SetFont('', '', $fontSize - 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($rightX, $y + 9);
        $pdf->MultiCell(90, 4, 'Dépannage électroménager', 0, 'R');

        // Coordonnées sous le logo.
        $infoY = $y + 30;
        $infos = array();

        if (!empty($this->emetteur->address)) {
            $infos[] = $this->emetteur->address;
        }

        $city = trim(($this->emetteur->zip ?? '') . ' ' . ($this->emetteur->town ?? ''));
        if ($city !== '') {
            $infos[] = $city;
        }

        if (!empty($this->emetteur->phone)) {
            $infos[] = 'Tél. : ' . $this->emetteur->phone;
        }

        if (!empty($this->emetteur->email)) {
            $infos[] = $this->emetteur->email;
        }

        if (!empty($infos)) {
            $pdf->SetXY($x, $infoY);
            $pdf->SetFont('', '', $fontSize - 1);
            $pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset(implode("\n", $infos)), 0, 'L');
        }

        $lineY = max($pdf->getY() + 5, 52);
        $pdf->SetDrawColor(128, 128, 128);
        $pdf->line($x, $lineY, $this->page_largeur - $this->marge_droite, $lineY);
        $pdf->SetY($lineY + 7);
    }

    /**
     * Tableau des tarifs ElectroJul.
     */
    protected function drawTarifs(&$pdf, $outputlangs)
    {
        $fontSize = pdf_getPDFFontSize($outputlangs);
        $x = $this->marge_gauche;
        $w = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
        $col1 = 125;
        $col2 = $w - $col1;

        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', $fontSize + 1);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetXY($x, $pdf->getY());
        $pdf->MultiCell($w, 8, 'Tarifs des prestations', 0, 'L', true);

        $y = $pdf->getY() + 5;

        $pdf->SetFont('', 'B', $fontSize);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(224, 224, 224);

        $pdf->SetXY($x, $y);
        $pdf->MultiCell($col1, 8, 'Prestation', 1, 'L', true);
        $pdf->SetXY($x + $col1, $y);
        $pdf->MultiCell($col2, 8, 'Tarif TTC', 1, 'R', true);

        $tarifs = array(
            array('Forfait Gros Électroménager + Déplacement', '79,00 €'),
            array('Forfait Petit Électroménager + Déplacement', '59,00 €'),
            array('Livraison / Installation / Recyclage', '30,00 €'),
            array('Installation intégrable', '79,00 €'),
            array('Déplacement au-delà de 20 km', '2,00 € / km'),
        );

        $y += 8;
        $pdf->SetFont('', '', $fontSize);

        foreach ($tarifs as $i => $tarif) {
            if ($i % 2 === 0) {
                $pdf->SetFillColor(255, 255, 255);
            } else {
                $pdf->SetFillColor(248, 248, 248);
            }

            $pdf->SetXY($x, $y);
            $pdf->MultiCell($col1, 9, $outputlangs->convToOutputCharset($tarif[0]), 1, 'L', true);
            $pdf->SetXY($x + $col1, $y);
            $pdf->MultiCell($col2, 9, $tarif[1], 1, 'R', true);
            $y += 9;
        }

        $pdf->SetY($y + 8);
        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', $fontSize);
        $pdf->MultiCell($w, 6, 'Informations importantes', 0, 'L');

        $pdf->SetFont('', '', $fontSize - 1);
        $pdf->SetTextColor(0, 0, 0);

        $mentions = array(
            "Le forfait est dû lors d'une réparation réussie.",
            'Les pièces sont garanties 3 mois dans le cadre d’une utilisation normale.',
            'Les tarifs indiqués sont TTC.',
            'Le déplacement au-delà de 20 km est facturé à 2 € par kilomètre supplémentaire.',
        );

        foreach ($mentions as $mention) {
            $pdf->SetX($x);
            $pdf->MultiCell($w, 5, '• ' . $outputlangs->convToOutputCharset($mention), 0, 'L');
        }

        $pdf->Ln(4);
        $pdf->SetFont('', 'I', $fontSize - 2);
        $pdf->SetTextColor(90, 90, 90);
        $pdf->MultiCell($w, 4, 'Tarifs susceptibles d’évoluer. Un devis peut être établi avant intervention.', 0, 'L');
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Pied de page proche du rendu Dolibarr.
     */
    protected function drawFooter(&$pdf, $outputlangs)
    {
        $fontSize = pdf_getPDFFontSize($outputlangs);
        $x = $this->marge_gauche;
        $w = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
        $y = $this->page_hauteur - $this->marge_basse + 1;

        $pdf->SetDrawColor(128, 128, 128);
        $pdf->line($x, $y - 5, $x + $w, $y - 5);

        $pdf->SetFont('', '', $fontSize - 2);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetXY($x, $y);

        $left = $this->emetteur->name;
        if (!empty($this->emetteur->phone)) {
            $left .= ' - ' . $this->emetteur->phone;
        }

        $pdf->Cell($w / 2, 4, $outputlangs->convToOutputCharset($left), 0, 0, 'L');
        $pdf->Cell($w / 2, 4, 'Page ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, 0, 'R');
        $pdf->SetTextColor(0, 0, 0);
    }
}
