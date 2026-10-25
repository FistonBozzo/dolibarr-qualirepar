<?php
/* Copyright (C) 2026 Auto-entrepreneur
 *
 * Page personnalisée : Chiffre d'affaires encaissé (Mois et Trimestre)
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


// Base commune de la requête SQL (CTE) pour éviter les doublons
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
// 1. TABLEAU : DÉTAIL PAR MOIS
// ==========================================
print '<div style="font-size: 1.2em; font-weight: bold; margin: 20px 0 10px 0; border-bottom: 2px solid #eee; padding-bottom: 5px;">1. Détail par mois</div>';

$sql_mois = $common_cte . "
SELECT 
    DATE_FORMAT(datep, '%Y-%m') AS periode,
    CASE product_type 
        WHEN 0 THEN 'Marchandises / Produits' 
        WHEN 1 THEN 'Prestations de services' 
        ELSE 'Autre' 
    END AS type_activite,
    ROUND(SUM(allocated_amount), 2) AS ca_encaisse
FROM line_payments
GROUP BY DATE_FORMAT(datep, '%Y-%m'), product_type
ORDER BY periode DESC, product_type;";

$resql_mois = $db->query($sql_mois);

if ($resql_mois) {
    print '<div class="div-table-responsive" style="margin-bottom: 30px;">';
    print '<table class="tagtable liste" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>Mois</td>';
    print '<td>Type d\'activité</td>';
    print '<td class="right">CA Encaissé (HT)</td>';
    print '</tr>';

    if ($db->num_rows($resql_mois) > 0) {
        while ($obj = $db->fetch_object($resql_mois)) {
            print '<tr class="oddeven">';
            print '<td>' . $obj->periode . '</td>';
            print '<td>' . $obj->type_activite . '</td>';
            print '<td class="right"><b>' . price($obj->ca_encaisse) . ' €</b></td>';
            print '</tr>';
        }
    } else {
        print '<tr><td colspan="3" class="opacitymedium">Aucun encaissement trouvé pour l\'année ' . $annee . '.</td></tr>';
    }
    print '</table>';
    print '</div>';
    $db->free($resql_mois);
} else {
    print '<div class="error">Erreur SQL (Mois) : ' . $db->error() . '</div>';
}


// ==========================================
// 2. TABLEAU : RÉCAPITULATIF PAR TRIMESTRE
// ==========================================
print '<div style="font-size: 1.2em; font-weight: bold; margin: 30px 0 10px 0; border-bottom: 2px solid #eee; padding-bottom: 5px;">2. Récapitulatif par trimestre (Urssaf)</div>';

$sql_trimestre = $common_cte . "
SELECT 
    QUARTER(datep) AS trimestre,
    CASE product_type 
        WHEN 0 THEN 'Marchandises / Produits' 
        WHEN 1 THEN 'Prestations de services' 
        ELSE 'Autre' 
    END AS type_activite,
    ROUND(SUM(allocated_amount), 2) AS ca_encaisse
FROM line_payments
GROUP BY QUARTER(datep), product_type
ORDER BY trimestre ASC, product_type;";

$resql_trimestre = $db->query($sql_trimestre);

if ($resql_trimestre) {
    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>Trimestre</td>';
    print '<td>Type d\'activité</td>';
    print '<td class="right">CA Encaissé (HT)</td>';
    print '</tr>';

    if ($db->num_rows($resql_trimestre) > 0) {
        while ($obj_trim = $db->fetch_object($resql_trimestre)) {
            print '<tr class="oddeven">';
            print '<td>T' . $obj_trim->trimestre . ' ' . $annee . '</td>';
            print '<td>' . $obj_trim->type_activite . '</td>';
            print '<td class="right"><b>' . price($obj_trim->ca_encaisse) . ' €</b></td>';
            print '</tr>';
        }
    } else {
        print '<tr><td colspan="3" class="opacitymedium">Aucun encaissement trimestriel trouvé pour l\'année ' . $annee . '.</td></tr>';
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