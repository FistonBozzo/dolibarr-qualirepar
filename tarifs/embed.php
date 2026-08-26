<?php
/**
 * API publique de génération du PDF des tarifs ElectroJul.
 *
 * Cette URL est accessible sans connexion Dolibarr.
 *
 * Elle génère toujours le PDF à partir des tarifs actuellement
 * enregistrés dans Dolibarr.
 */


/*
 * -------------------------------------------------------------
 * CONFIGURATION
 * -------------------------------------------------------------
 */

define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);
define('NOREQUIREUSER', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);


/*
 * -------------------------------------------------------------
 * CHARGEMENT DOLIBARR
 * -------------------------------------------------------------
 */

require_once dirname(__DIR__, 2).'/../main.inc.php';


/*
 * -------------------------------------------------------------
 * CHARGEMENT DU GÉNÉRATEUR PDF
 * -------------------------------------------------------------
 */

require_once DOL_DOCUMENT_ROOT
	.'/custom/qualirepar/core/modules/pdf_tarifs.modules.php';


/*
 * -------------------------------------------------------------
 * CORS
 * -------------------------------------------------------------
 *
 * Le PDF peut être appelé directement depuis le navigateur.
 * L'autorisation CORS est conservée pour ton site GitLab Pages.
 */

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$originsAutorisees = array(
	'https://electrojul69.gitlab.io'
);

if (in_array($origin, $originsAutorisees, true)) {

	header(
		'Access-Control-Allow-Origin: '.$origin
	);

	header('Vary: Origin');
}


/*
 * -------------------------------------------------------------
 * OPTIONS CORS
 * -------------------------------------------------------------
 */

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {

	header(
		'Access-Control-Allow-Methods: GET, OPTIONS'
	);

	header(
		'Access-Control-Allow-Headers: Accept, Content-Type'
	);

	http_response_code(204);

	exit;
}


/*
 * -------------------------------------------------------------
 * AUTORISER UNIQUEMENT GET
 * -------------------------------------------------------------
 */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

	http_response_code(405);

	header('Content-Type: text/plain; charset=utf-8');

	echo 'Méthode non autorisée.';

	exit;
}


/*
 * -------------------------------------------------------------
 * LANGUE
 * -------------------------------------------------------------
 */

global $langs;

$langs->loadLangs(
	array(
		'main',
		'companies'
	)
);


/*
 * -------------------------------------------------------------
 * GÉNÉRATION DU PDF
 * -------------------------------------------------------------
 */

$pdfModel = new pdf_tarifs($db);


/*
 * Le générateur n'a pas besoin d'un objet devis/facture.
 * Le deuxième paramètre est simplement la langue Dolibarr.
 */

$result = $pdfModel->write_file(
	null,
	$langs
);


/*
 * -------------------------------------------------------------
 * ERREUR DE GÉNÉRATION
 * -------------------------------------------------------------
 */

if ($result < 0) {

	http_response_code(500);

	header(
		'Content-Type: text/plain; charset=utf-8'
	);

	echo 'Impossible de générer le PDF des tarifs.';

	if (!empty($pdfModel->error)) {

		/*
		 * On écrit l'erreur dans le log Dolibarr,
		 * mais on ne l'affiche pas publiquement.
		 */

		dol_syslog(
			'QualiRépar tarifs PDF : '.$pdfModel->error,
			LOG_ERR
		);
	}

	exit;
}


/*
 * -------------------------------------------------------------
 * RÉCUPÉRATION DU FICHIER
 * -------------------------------------------------------------
 */

if (
	empty($pdfModel->result)
	|| empty($pdfModel->result['fullpath'])
) {

	http_response_code(500);

	header(
		'Content-Type: text/plain; charset=utf-8'
	);

	echo 'Le fichier PDF n’a pas été créé.';

	exit;
}


$filepath =
	$pdfModel->result['fullpath'];


/*
 * Vérification finale.
 */

if (!is_readable($filepath)) {

	http_response_code(500);

	header(
		'Content-Type: text/plain; charset=utf-8'
	);

	echo 'Le fichier PDF est inaccessible.';

	exit;
}


/*
 * -------------------------------------------------------------
 * ENVOI DU PDF
 * -------------------------------------------------------------
 */

$filesize = filesize($filepath);


/*
 * Nettoyage d'éventuels buffers PHP.
 *
 * Très important pour éviter que des warnings ou du HTML
 * soient ajoutés avant les données PDF.
 */

while (ob_get_level() > 0) {
	ob_end_clean();
}


/*
 * En-têtes HTTP.
 */

header(
	'Content-Type: application/pdf'
);

header(
	'Content-Disposition: inline; filename="Tarifs_ElectroJul.pdf"'
);

if ($filesize !== false) {

	header(
		'Content-Length: '.$filesize
	);
}


/*
 * Le PDF est généré à chaque appel.
 *
 * On évite donc de conserver un cache navigateur
 * qui pourrait afficher d'anciens tarifs.
 */

header(
	'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
	'Pragma: no-cache'
);

header(
	'Expires: 0'
);


/*
 * -------------------------------------------------------------
 * ENVOI
 * -------------------------------------------------------------
 */

readfile($filepath);

exit;
