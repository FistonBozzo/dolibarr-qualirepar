<?php
/**
 * Fiche tarifaire publique
 * Version intégration Google Sites
 */

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

    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
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

<div class="mentions-tarifs">

    <strong>Le forfait est dû lors d'une réparation réussie.</strong><br>
    Les pièces sont garanties 3 mois dans le cadre d'une utilisation normale.

</div>

<div class="separateur"></div>

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



</body>

</html>
