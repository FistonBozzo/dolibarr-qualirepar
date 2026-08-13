<?php
/**
 * Fiche tarifaire publique QualiRépar
 */

$apiUrl = '../../api/tarifs.php';

$json = @file_get_contents($apiUrl);

if ($json === false) {
    http_response_code(500);
    die('Impossible de récupérer les tarifs.');
}

$data = json_decode($json, true);

if (
    !is_array($data)
    || empty($data['success'])
) {
    http_response_code(500);
    die('Impossible de récupérer les tarifs.');
}

$tarifs = $data['tarifs'] ?? array();
$dateMiseAJour = $data['updated_at'] ?? null;

?>
<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Tarifs - Electrojul</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 20px;
    font-family: Arial, Helvetica, sans-serif;
    background: #f5f6f8;
    color: #222;
}

.container {
    max-width: 600px;
    margin: 0 auto;
}

.header {
    text-align: center;
    margin-bottom: 25px;
}

h1 {
    margin: 5px 0 8px;
    font-size: 28px;
}

.update {
    color: #666;
    font-size: 14px;
}

.tarif {
    background: white;
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 12px;

    box-shadow: 0 2px 8px rgba(0,0,0,0.08);

    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.tarif-nom {
    font-size: 17px;
    font-weight: 600;
    line-height: 1.3;
}

.tarif-prix {
    white-space: nowrap;
    font-size: 21px;
    font-weight: bold;
}

.actions {
    margin-top: 25px;

    display: flex;
    gap: 10px;
}

.action {
    flex: 1;

    text-align: center;

    padding: 14px;

    border-radius: 10px;

    text-decoration: none;

    font-weight: bold;
}

.tel {
    background: #222;
    color: white;
}

.sms {
    background: #e9e9e9;
    color: #222;
}

.whatsapp {
    background: #25D366;
    color: white;
}

.footer {
    text-align: center;

    margin-top: 25px;

    font-size: 13px;

    color: #777;
}

@media (max-width: 450px) {

    body {
        padding: 12px;
    }

    .tarif {
        padding: 15px;
    }

    .tarif-nom {
        font-size: 16px;
    }

    .tarif-prix {
        font-size: 19px;
    }

    .actions {
        flex-direction: column;
    }

}

</style>

</head>

<body>

<div class="container">

    <div class="header">

        <h1>Mes tarifs</h1>

        <?php if ($dateMiseAJour) { ?>

            <div class="update">

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


    <?php foreach ($tarifs as $tarif) { ?>

        <div class="tarif">

            <div class="tarif-nom">

                <?php
                echo htmlspecialchars(
                    $tarif['nom'],
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>

            </div>

            <div class="tarif-prix">

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


    <div class="actions">

        <a
            class="action tel"
            href="tel:+33759736080"
        >
            📞 Appeler
        </a>

        <a
            class="action sms"
            href="sms:+33759736080"
        >
            💬 SMS
        </a>

        <a
            class="action whatsapp"
            href="https://wa.me/33759736080"
        >
            WhatsApp
        </a>

    </div>


    <div class="footer">

        Electrojul · Tarifs TTC

    </div>

</div>

</body>

</html>
