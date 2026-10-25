<?php
/* Copyright (C) 2026 Auto-entrepreneur
 *
 * Page personnalisée : Chiffre d'affaires encaissé (Produits / Services)
 */

// Charger l'environnement Dolibarr
$res = 0;
if (! $res && file_exists("../main.inc.php")) $res = @include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res = @include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res = @include("../../../main.inc.php");
if (! $res) die("Include of main fails");

// Sécurité : Accès réservé aux administrateurs ou utilisateurs autorisés
if (!$user->admin) {
    accessforbidden();
}

// Entête de la page Dolibarr
llxHeader('', 'CA Encaissé - Auto-entrepreneur');

print load_fiche_titre('Ventilation du Chiffre d\'Affaires Encaissé', '', 'title_accountancy.png');

// Récupération de l'année (par défaut l'année en cours, modifiable via l'URL ?annee=2026)
$annee = GETPOST('annee', 'int') ? GETPOST('annee', 'int') : date('Y');

// Formulaire de sélection de l'année
print '<form method="GET" action="' . $_SERVER["PHP_SELF"] . '">';
print '<table class="centpercent nobargin" style="margin-bottom: 15px;"><tr><td>';
print 'Année : <input type="number" name="annee" value="' . $annee . '" style="width: 80px;"> ';
print '<input type="submit" class="button" value="Filtrer">';
print '</td></tr></table>';
print '</form>';

// Requête SQL complète (Prorata + Avoirs + Date de paiement)
$sql = "WITH invoice_totals AS (
    SELECT fk_facture, SUM(total_ht) AS total_ht
    FROM " . MAIN_DB_PREFIX . "facturedet
    GROUP BY fk_facture
),
line_payments AS (
    SELECT 
        p.datep,
        fd.product_type,
        CASE WHEN f.type = 2 THEN -1 ELSE 1 END * 
        CASE WHEN it.total_ht > 0 THEN pf.amount * (fd.total_ht / it.total_ht) ELSE 0 END AS allocated_amount
    FROM " . MAIN_DB_PREFIX . "paiement p
    JOIN " . MAIN_DB_PREFIX . "paiement_facture pf ON pf.fk_paiement = p.rowid
    JOIN " . MAIN_DB_PREFIX . "facturedet fd ON fd.fk_facture = pf.fk_facture
    JOIN invoice_totals it ON it.fk_facture = pf.fk_facture
    JOIN " . MAIN_DB_PREFIX . "facture f ON f.rowid = pf.fk_facture
    WHERE YEAR(p.datep) = " . (int)$annee . "
)
SELECT 
    DATE_FORMAT(datep, '%Y-%m') AS mois,
    CASE product_type 
        WHEN 0 THEN 'Marchandises / Produits' 
        WHEN 1 THEN 'Prestations de services' 
        ELSE 'Autre' 
    END AS type_activite,
    ROUND(SUM(allocated_amount), 2) AS ca_encaisse
FROM line_payments
GROUP BY DATE_FORMAT(datep, '%Y-%m'), product_type
ORDER BY mois DESC, product_type;";

$resql = $db->query($sql);

if ($resql) {
    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>Mois</td>';
    print '<td>Type d\'activité</td>';
    print '<td class="right">CA Encaissé (HT)</td>';
    print '</tr>';

    $num = $db->num_rows($resql);
    if ($num > 0) {
        $total_annee = 0;
        while ($obj = $db->fetch_object($resql)) {
            $total_annee += $obj->ca_encaisse;
            print '<tr class="oddeven">';
            print '<td>' . $obj->mois . '</td>';
            print '<td>' . $obj->type_activite . '</td>';
            print '<td class="right"><b>' . price($obj->ca_encaisse) . ' €</b></td>';
            print '</tr>';
        }
    } else {
        print '<tr><td colspan="3" class="opacitymedium">Aucun encaissement trouvé pour l\'année ' . $annee . '.</td></tr>';
    }
    print '</table>';
    print '</div>';
} else {
    print '<div class="error">Erreur SQL : ' . $db->error() . '</div>';
}

// Pied de page Dolibarr
llxFooter();
$db->free($resql);
?>