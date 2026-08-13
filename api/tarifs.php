<?php
/**
 * API publique des tarifs QualiRépar
 *
 * Retourne uniquement les tarifs sélectionnés
 * pour la fiche tarifaire publique.
 */

// Charger Dolibarr
$res = 0;

if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}

if (!$res) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(array(
        'success' => false,
        'error' => 'Impossible de charger Dolibarr.'
    ));

    exit;
}

// Charger la classe de récupération des tarifs
require_once DOL_DOCUMENT_ROOT.'/custom/qualirepar/class/tarifs.class.php';

// Autoriser uniquement les requêtes GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(array(
        'success' => false,
        'error' => 'Méthode non autorisée.'
    ));

    exit;
}

// Récupération des tarifs
$tarifsObj = new QualiReparTarifs($db);
$tarifs = $tarifsObj->getTarifs();

// Récupération de la date réelle de dernière modification
$dateMiseAJour = $tarifsObj->getDateMiseAJour();

// Construire une réponse publique minimale
$result = array();

foreach ($tarifs as $tarif) {

    $result[] = array(
        'nom' => $tarif['label'],
        'prix_ttc' => $tarif['price_ttc'],
        'ordre' => $tarif['ordre']
    );
}

// Réponse JSON
header('Content-Type: application/json; charset=utf-8');

echo json_encode(
    array(
        'success' => true,
        'updated_at' => $dateMiseAJour,
        'tarifs' => $result
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

exit;
