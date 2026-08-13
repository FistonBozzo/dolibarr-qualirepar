<?php

$apiUrl = 'https://electrojul.duckdns.org/custom/qualirepar/api/tarifs.php';

$ch = curl_init($apiUrl);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$json = curl_exec($ch);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

echo '<pre>';
echo "HTTP CODE : " . $httpCode . "\n\n";

if ($curlError) {
    echo "ERREUR CURL :\n";
    echo $curlError;
    echo "\n\n";
}

echo "REPONSE RECUE :\n";
echo htmlspecialchars($json ?? '', ENT_QUOTES, 'UTF-8');

echo '</pre>';
