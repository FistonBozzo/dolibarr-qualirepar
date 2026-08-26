<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);
define('NOREQUIREUSER', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);

/*
 * Chargement de Dolibarr
 */
require_once dirname(__DIR__, 2).'/../main.inc.php';

/*
 * Chargement de la classe des tarifs
 */
require_once DOL_DOCUMENT_ROOT.'/custom/qualirepar/class/tarifs.class.php';

/*
 * Test de la classe
 */
$tarifsObj = new QualiReparTarifs($db);

/*
 * Récupération des tarifs
 */
$tarifs = $tarifsObj->getTarifs();

echo '<pre>';
print_r($tarifs);
echo '</pre>';
