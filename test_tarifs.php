<?php
/**
 * Diagnostic des produits tarifaires
 * Fichier temporaire
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

llxHeader('', 'Diagnostic tarifs');

print '<div class="fiche">';

print '<h1>Diagnostic des produits tarifaires</h1>';

$sql = "SELECT";
$sql .= " p.rowid,";
$sql .= " p.ref,";
$sql .= " p.label,";
$sql .= " p.tosell,";
$sql .= " p.price,";
$sql .= " p.price_ttc,";
$sql .= " e.afficher_site_tarif,";
$sql .= " e.ordre_site_tarif";
$sql .= " FROM ".MAIN_DB_PREFIX."product p";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields e";
$sql .= " ON e.fk_object = p.rowid";
$sql .= " ORDER BY p.label ASC";

$resql = $db->query($sql);

if (!$resql) {
    print '<div class="error">';
    print 'Erreur SQL : '.dol_escape_htmltag($db->lasterror());
    print '</div>';
} else {

    print '<p>';
    print '<strong>Produits trouvés : </strong>';
    print $db->num_rows($resql);
    print '</p>';

    if ($db->num_rows($resql) > 0) {

        print '<table class="liste centpercent">';

        print '<tr class="liste_titre">';
        print '<th>Référence</th>';
        print '<th>Produit</th>';
        print '<th>Actif / vente</th>';
        print '<th>Afficher site</th>';
        print '<th>Ordre</th>';
        print '<th>Prix HT</th>';
        print '<th>Prix TTC</th>';
        print '</tr>';

        while ($obj = $db->fetch_object($resql)) {

            print '<tr class="oddeven">';

            print '<td>';
            print dol_escape_htmltag($obj->ref);
            print '</td>';

            print '<td>';
            print dol_escape_htmltag($obj->label);
            print '</td>';

            print '<td class="center">';
            print ($obj->tosell ? 'OUI' : 'NON');
            print '</td>';

            print '<td class="center">';
            if ((int) $obj->afficher_site_tarif === 1) {
                print '<strong style="color:green;">OUI</strong>';
            } else {
                print '<strong style="color:red;">NON</strong>';
            }
            print '</td>';

            print '<td class="center">';
            print (int) $obj->ordre_site_tarif;
            print '</td>';

            print '<td class="right">';
            print price($obj->price).' €';
            print '</td>';

            print '<td class="right">';
            print '<strong>';
            print price($obj->price_ttc).' € TTC';
            print '</strong>';
            print '</td>';

            print '</tr>';
        }

        print '</table>';
    }
}

print '</div>';

llxFooter();

$db->close();
