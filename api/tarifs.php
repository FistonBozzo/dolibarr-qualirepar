<?php
/**
 * API publique des tarifs QualiRépar
 *
 * Cette API est volontairement accessible sans connexion Dolibarr.
 * Elle permet uniquement de consulter les tarifs publics.
 */

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$originsAutorisees = [
    'https://electrojul69.gitlab.io'
];

if (in_array($origin, $originsAutorisees, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Vary: Origin");
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Accept, Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}


/*
 * Autoriser l'accès sans authentification Dolibarr
 */

define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);
define('NOREQUIREUSER', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);


/*
 * Charger Dolibarr
 */

require_once dirname(__DIR__, 2).'/../main.inc.php';


/*
 * Charger la classe des tarifs
 */

require_once DOL_DOCUMENT_ROOT.'/custom/qualirepar/class/tarifs.class.php';


/*
 * Réponse JSON
 */

header('Content-Type: application/json; charset=utf-8');

header('Cache-Control: public, max-age=300');


/*
 * Récupération des tarifs
 */

$tarifsObj = new QualiReparTarifs($db);

$tarifs = $tarifsObj->getTarifs();

$dateMiseAJour = $tarifsObj->getDateMiseAJour();


/*
 * Préparation des données publiques
 *
 * On ne renvoie volontairement pas :
 * - l'ID interne Dolibarr
 * - la référence produit
 * - la description
 * - le prix HT
 * - le taux de TVA
 */

$resultat = array();

foreach ($tarifs as $tarif) {

    $resultat[] = array(
        'nom' => $tarif['label'],
        'prix_ttc' => (float) $tarif['price_ttc'],
        'ordre' => (int) $tarif['ordre']
    );
}


/*
 * Réponse
 */

echo json_encode(
    array(
        'success' => true,
        'updated_at' => $dateMiseAJour,
        'tarifs' => $resultat
    ),
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES |
    JSON_PRETTY_PRINT
);
