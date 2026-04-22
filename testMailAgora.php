 
 <?php

$url = "https://agora.ismonnet.it/sendMail/send.php"; // cambia con il path reale

$data = [
    "mail_invio" => "esercizio-5ainf@ismonnet.eu",
    "mail_destinazione" => "natosic810@donumart.com",
    "oggetto" => "Test invio mail",
    "body" => "Questa è una mail di test inviata via API e CURL da <b>PHP</b>"
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "Errore cURL: " . curl_error($ch);
} else {
    echo "Risposta server: " . $response;
}
