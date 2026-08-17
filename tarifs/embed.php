<?php
/**
 * Fiche tarifaire publique
 */
date_default_timezone_set('Europe/Paris');
$apiUrl = 'https://electrojul.duckdns.org/custom/qualirepar/api/tarifs.php';


/*
 * Récupération des tarifs via l'API publique
 */

$ch = curl_init($apiUrl);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$json = curl_exec($ch);

curl_close($ch);


/*
 * Vérification de la réponse
 */

if ($json === false || empty($json)) {
    http_response_code(500);
    die('Impossible de récupérer les tarifs.');
}

$data = json_decode($json, true);

if (!is_array($data) || empty($data['success'])) {
    http_response_code(500);
    die('Réponse API invalide.');
}


$tarifs = $data['tarifs'] ?? array();

$dateMiseAJour = $data['updated_at'] ?? null;

?>
<!DOCTYPE html>

<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Tarifs Electrojul</title>


<style>

/* =========================================
   RESET
   ========================================= */

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    padding: 0;
    width: 100%;
}


/* =========================================
   PAGE
   ========================================= */

body {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: transparent;

    color: #222;

}


/* =========================================
   CONTENEUR
   ========================================= */

.tarifs {

    width: 100%;

    max-width: 700px;

    margin: 10px auto 0 auto;

}


/* =========================================
   LIGNE TARIF
   ========================================= */

.tarif {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    width: 100%;

    padding: 16px 18px;

    margin: 0 0 10px 0;

    background: #ffffff;

    border: 1px solid #e5e5e5;

    border-radius: 10px;

    box-shadow:
        0 2px 6px rgba(0, 0, 0, 0.06);

}


/* =========================================
   PREMIÈRE LIGNE
   ========================================= */

.tarif:first-child {

    margin-top: 0;

}


/* =========================================
   NOM DU TARIF
   ========================================= */

.nom {

    font-size: 16px;

    line-height: 1.35;

    font-weight: 600;

}


/* =========================================
   PRIX
   ========================================= */

.prix {

    white-space: nowrap;

    font-size: 19px;

    font-weight: 700;

}


/* =========================================
   MENTION
   ========================================= */

.mentions-tarifs {

    margin-top: 8px;

    padding: 0 10px;

    text-align: center;

    font-size: 12px;

    line-height: 1.5;

    color: #666;

}


/* =========================================
   TRAIT DE SÉPARATION
   ========================================= */

.separateur {

    width: 80%;

    max-width: 500px;

    height: 1px;

    margin: 12px auto;

    background: #e5e5e5;

}


/* =========================================
   DATE DE MISE À JOUR
   ========================================= */

.date {

    text-align: center;

    font-size: 12px;

    color: #777;

}


/* =========================================
   MOBILE
   ========================================= */

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


    .mentions-tarifs {

        font-size: 12px;

    }

}

</style>

</head>


<body>


<div class="tarifs">


<?php if (empty($tarifs)) { ?>

    <div class="tarif">

        <div class="nom">

            Aucun tarif disponible.

        </div>

    </div>

<?php } ?>


<?php foreach ($tarifs as $tarif) { ?>

    <div class="tarif">

        <div class="nom">

            <?php

            echo htmlspecialchars(

                $tarif['nom'],

                ENT_QUOTES,

                'UTF-8'

            );

            ?>

        </div>


        <div class="prix">

            <?php

            echo number_format(

                (float) $tarif['prix_ttc'],

                2,

                ',',

                ' '

            );

            ?>

            € TTC

        </div>

    </div>

<?php } ?>


<!-- =====================================
     MENTION TARIFAIRE
     ===================================== -->

<div class="mentions-tarifs">

    <strong>
        Le forfait total est dû lors d'une réparation réussie.
        Le déplacement reste dû dans tous les cas
    </strong>

    <br>

    Les pièces sont garanties 3 mois
    dans le cadre d'une utilisation normale.

</div>


<!-- =====================================
     SÉPARATEUR
     ===================================== -->

<div class="separateur"></div>


<!-- =====================================
     DATE DE MISE À JOUR
     ===================================== -->

<?php if ($dateMiseAJour) { ?>

    <div class="date">

        Tarifs mis à jour le

        <?php

        echo date(

            'd/m/Y',

            strtotime($dateMiseAJour)

        );

        ?>

    </div>

<?php } ?>


</div>


</body>

</html>
```
