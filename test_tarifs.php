<?php
/**
 * Test de récupération des tarifs publics
 *
 * Fichier temporaire de test
 */

$res = 0;

if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}

if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}

if (!$res) {
    die('Impossible de charger Dolibarr.');
}

require_once __DIR__.'/class/tarifs.class.php';

$tarifsObj = new QualiReparTarifs($db);
$tarifs = $tarifsObj->getTarifs();

llxHeader('', 'Test fiche tarifaire');

print '<div class="fiche">';

print '<h1>Test fiche tarifaire</h1>';

print '<p>';
print '<strong>Nombre de tarifs trouvés :</strong> ';
print count($tarifs);
print '</p>';

if (empty($tarifs)) {

    print '<div class="warning">';
    print 'Aucun produit trouvé.';
    print '<br><br>';
    print 'Vérifie que les produits sont actifs et que';
    print '<strong> Afficher sur le site </strong>';
    print 'est activé.';
    print '</div>';

} else {

    print '<table class="liste centpercent">';
    
    print '<tr class="liste_titre">';
    print '<th>Ordre</th>';
    print '<th>Référence</th>';
    print '<th>Produit</th>';
    print '<th>Prix HT</th>';
    print '<th>Prix TTC</th>';
    print '</tr>';

    foreach ($tarifs as $tarif) {

        print '<tr class="oddeven">';

        print '<td class="center">';
        print (int) $tarif['ordre'];
        print '</td>';

        print '<td>';
        print dol_escape_htmltag($tarif['ref']);
        print '</td>';

        print '<td>';
        print dol_escape_htmltag($tarif['label']);
        print '</td>';

        print '<td class="right">';
        print price($tarif['price']);
        print ' €';
        print '</td>';

        print '<td class="right">';
        print '<strong>';
        print price($tarif['price_ttc']);
        print ' € TTC';
        print '</strong>';
        print '</td>';

        print '</tr>';
    }

    print '</table>';
}

print '</div>';

llxFooter();

$db->close();
