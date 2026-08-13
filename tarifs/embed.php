<?php
/**
 * Fiche tarifaire - intégration Google Sites
 */

$res = 0;

if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}

if (!$res) {
    http_response_code(500);
    die('Impossible de charger Dolibarr.');
}

require_once DOL_DOCUMENT_ROOT.'/custom/qualirepar/class/tarifs.class.php';

$tarifsObj = new QualiReparTarifs($db);

$tarifs = $tarifsObj->getTarifs();
$dateMiseAJour = $tarifsObj->getDateMiseAJour();

?>
<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Tarifs Electrojul</title>

<style>

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    padding: 0;
    width: 100%;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: transparent;
    color: #222;
}

.tarifs {
    width: 100%;
    max-width: 700px;
    margin: 0 auto;
}

.tarif {
    display: flex;
    justify-content: space-between;
    align-items: center;

    gap: 20px;

    padding: 16px 18px;

    margin-bottom: 10px;

    background: #ffffff;

    border: 1px solid #e5e5e5;

    border-radius: 10px;

    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}

.nom {
    font-size: 16px;
    line-height: 1.35;
    font-weight: 600;
}

.prix {
    white-space: nowrap;

    font-size: 19px;

    font-weight: 700;
}

.date {
    margin-top: 15px;

    text-align: center;

    font-size: 12px;

    color: #777;
}


/*
 * Adaptation téléphone
 */

@media (max-width: 500px) {

    .tarif {
        padding: 14px;
        gap: 12px;
    }

    .nom {
        font-size: 15px;
    }

    .prix {
        font-size: 18px;
    }

}

</style>

</head>

<body>

<div class="tarifs">

<?php foreach ($tarifs as $tarif) { ?>

    <div class="tarif">

        <div class="nom">
            <?php echo dol_escape_htmltag($tarif['label']); ?>
        </div>

        <div class="prix">
            <?php echo price($tarif['price_ttc']); ?> € TTC
        </div>

    </div>

<?php } ?>

<?php if ($dateMiseAJour) { ?>

    <div class="date">

        Tarifs mis à jour le
        <?php echo dol_print_date(
            dol_stringtotime($dateMiseAJour),
            '%d/%m/%Y'
        ); ?>

    </div>

<?php } ?>

</div>

</body>

</html>
