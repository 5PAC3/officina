<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../configs/config.php';

class OTP {
    public static function genera(): string {
        return bin2hex(random_bytes(16));
    }

    public static function invia(string $email, string $codice, string $tipo = 'verify'): bool {
        $scadenza = date('Y-m-d H:i:s', time() + 24 * 3600);
        
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("UPDATE cliente SET codiceOTP = ?, scadenzaOTP = ? WHERE email = ?");
        $stmt->bind_param("sss", $codice, $scadenza, $email);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) return false;

        require_once __DIR__ . '/mailer.php';
        $link = APP_URL . "/verify.php?email=$email&codice=$codice";
        $oggetto = $tipo === 'reset' ? 'Reset Password' : 'Conferma Registrazione';
        $messaggio = $tipo === 'reset'
            ? "Clicca per resettare la password: <a href='$link'>$link</a>"
            : "Clicca per confermare l'email: <a href='$link'>$link</a>";

        return Mailer::invia($email, $oggetto, $messaggio);
    }

    public static function verifica(string $email, string $codice): bool {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT scadenzaOTP FROM cliente WHERE email = ? AND codiceOTP = ?");
        $stmt->bind_param("ss", $email, $codice);
        $stmt->execute();
        $result = $stmt->get_result();
        $valido = $result->fetch_assoc() && strtotime($result->fetch_assoc()['scadenzaOTP']) > time();
        $stmt->close();
        return $valido;
    }

    public static function attiva(string $email): bool {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("UPDATE cliente SET isActive = 1 WHERE email = ?");
        $stmt->bind_param("s", $email);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public static function aggiornaPassword(string $email, string $password): bool {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("UPDATE cliente SET password = ?, codiceOTP = NULL, scadenzaOTP = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hash, $email);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
?>