<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);
define('NOREQUIREUSER', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);

require_once dirname(__DIR__, 2).'/../main.inc.php';

echo 'DOLIBARR CHARGE CORRECTEMENT';
