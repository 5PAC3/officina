class Mailer {
    private const API_URL = "https://agora.ismonnet.it/sendMail/send.php";
    private const MAIL_INVIO = "esercizio-5binf@ismonnet.eu";

    public static function invia(string $to, string $subject, string $body): bool {
        $ch = curl_init(self::API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "mail_invio" => self::MAIL_INVIO,
            "mail_destinazione" => $to,
            "oggetto" => $subject,
            "body" => $body
        ]));

        $response = curl_exec($ch);
        $ok = !curl_errno($ch) && $response !== false;
        curl_close($ch);

        return $ok;
    }
}
?>
