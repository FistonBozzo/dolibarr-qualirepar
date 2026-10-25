<?php
/* Copyright (C) 2026 Auto-entrepreneur
 *
 * Page personnalisée : Chiffre d'affaires encaissé (Mois et Trimestre en colonnes)
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
print '<table class="centpercent nobargin" style="margin-bottom: 20px;"><tr><td>';
print 'Année : <input type="number" name="annee" value="' . $annee . '" style="width: 80px;"> ';
print '<input type="submit" class="button" value="Filtrer">';
print '</td></tr></table>';
print '</form>';

// Tableau de correspondance des mois en français
$noms_mois = array(
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
);

// Base commune de la requête SQL (CTE)
$common_cte = "WITH invoice_totals AS (
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
)";


// ==========================================
// 1. TABLEAU : DÉTAIL PAR MOIS (EN COLONNES)
// ==========================================
print '<div style="font-size: 1.2em; font-weight: bold; margin: 20px 0 10px 0; border-bottom: 2px solid #eee; padding-bottom: 5px;">1. Détail par mois</div>';

$sql_mois = $common_cte . "
SELECT 
    MONTH(datep) AS mois_num,
    ROUND(SUM(CASE WHEN product_type = 0 THEN allocated_amount ELSE 0 END), 2) AS ca_produits,
    ROUND(SUM(CASE WHEN product_type = 1 THEN allocated_amount ELSE 0 END), 2) AS ca_services,
    ROUND(SUM(allocated_amount), 2) AS ca_total
FROM line_payments
GROUP BY MONTH(datep)
ORDER BY mois_num DESC;";

$resql_mois = $db->query($sql_mois);

if ($resql_mois) {
    print '<div class="div-table-responsive" style="margin-bottom: 30px;">';
    print '<table class="tagtable liste" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>Mois</td>';
    print '<td class="right">Produits / Marchandises (HT)</td>';
    print '<td class="right">Prestations de services (HT)</td>';
    print '<td class="right">Total Encaissé (HT)</td>';
    print '</tr>';

    if ($db->num_rows($resql_mois) > 0) {
        while ($obj = $db->fetch_object($resql_mois)) {
            $nom_mois = isset($noms_mois[$obj->mois_num]) ? $noms_mois[$obj->mois_num] : 'Mois ' . $obj->mois_num;
            print '<tr class="oddeven">';
            print '<td><b>' . $nom_mois . '</b></td>';
            print '<td class="right">' . price($obj->ca_produits) . ' €</td>';
            print '<td class="right">' . price($obj->ca_services) . ' €</td>';
            print '<td class="right"><b>' . price($obj->ca_total) . ' €</b></td>';
            print '</tr>';
        }
    } else {
        print '<tr><td colspan="4" class="opacitymedium">Aucun encaissement trouvé pour l\'année ' . $annee . '.</td></tr>';
    }
    print '</table>';
    print '</div>';
    $db->free($resql_mois);
} else {
    print '<div class="error">Erreur SQL (Mois) : ' . $db->error() . '</div>';
}


// ==========================================
// 2. TABLEAU : RÉCAPITULATIF PAR TRIMESTRE (EN COLONNES)
// ==========================================
print '<div style="font-size: 1.2em; font-weight: bold; margin: 30px 0 10px 0; border-bottom: 2px solid #eee; padding-bottom: 5px;">2. Récapitulatif par trimestre (Urssaf)</div>';

$sql_trimestre = $common_cte . "
SELECT 
    QUARTER(datep) AS trimestre,
    ROUND(SUM(CASE WHEN product_type = 0 THEN allocated_amount ELSE 0 END), 2) AS ca_produits,
    ROUND(SUM(CASE WHEN product_type = 1 THEN allocated_amount ELSE 0 END), 2) AS ca_services,
    ROUND(SUM(allocated_amount), 2) AS ca_total
FROM line_payments
GROUP BY QUARTER(datep)
ORDER BY trimestre ASC;";

$resql_trimestre = $db->query($sql_trimestre);

if ($resql_trimestre) {
    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>Trimestre</td>';
    print '<td class="right">Produits / Marchandises (HT)</td>';
    print '<td class="right">Prestations de services (HT)</td>';
    print '<td class="right">Total Encaissé (HT)</td>';
    print '</tr>';

    if ($db->num_rows($resql_trimestre) > 0) {
        while ($obj_trim = $db->fetch_object($resql_trimestre)) {
            print '<tr class="oddeven">';
            print '<td><b>T' . $obj_trim->trimestre . ' ' . $annee . '</b></td>';
            print '<td class="right">' . price($obj_trim->ca_produits) . ' €</td>';
            print '<td class="right">' . price($obj_trim->ca_services) . ' €</td>';
            print '<td class="right"><b>' . price($obj_trim->ca_total) . ' €</b></td>';
            print '</tr>';
        }
    } else {
        print '<tr><td colspan="4" class="opacitymedium">Aucun encaissement trimestriel trouvé pour l\'année ' . $annee . '.</td></tr>';
    }
    print '</table>';
    print '</div>';
    $db->free($resql_trimestre);
} else {
    print '<div class="error">Erreur SQL (Trimestre) : ' . $db->error() . '</div>';
}

// Pied de page Dolibarr
llxFooter();
?>