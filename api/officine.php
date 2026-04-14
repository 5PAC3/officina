<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../classes/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->query("
    SELECT o.codice, o.denominazione, o.indirizzo, o.telefono, o.email
    FROM officina o
    ORDER BY o.denominazione
");

$officine = [];
while ($row = $stmt->fetch_assoc()) {
    $codice = $row['codice'];
    
    $stmt2 = $conn->prepare("
        SELECT s.codice, s.descrizione
        FROM servizio s
        INNER JOIN offre ON s.codice = offre.servizio_codice
        WHERE offre.officina_codice = ?
    ");
    $stmt2->bind_param("s", $codice);
    $stmt2->execute();
    $result = $stmt2->get_result();
    $servizi = [];
    while ($r = $result->fetch_assoc()) {
        $servizi[] = $r;
    }
    $row['servizi'] = $servizi;
    
    $stmt2 = $conn->prepare("
        SELECT p.codice, p.descrizione, pp.quantita
        FROM pezzo_ricambio p
        INNER JOIN presenza_pezzo pp ON p.codice = pp.pezzo_codice
        WHERE pp.officina_codice = ? AND pp.quantita > 0
    ");
    $stmt2->bind_param("s", $codice);
    $stmt2->execute();
    $result = $stmt2->get_result();
    $pezzi = [];
    while ($r = $result->fetch_assoc()) {
        $pezzi[] = $r;
    }
    $row['pezzi'] = $pezzi;
    
    $stmt2 = $conn->prepare("
        SELECT a.codice, a.descrizione, pa.quantita
        FROM accessorio a
        INNER JOIN presenza_accessorio pa ON a.codice = pa.accessorio_codice
        WHERE pa.officina_codice = ? AND pa.quantita > 0
    ");
    $stmt2->bind_param("s", $codice);
    $stmt2->execute();
    $result = $stmt2->get_result();
    $accessori = [];
    while ($r = $result->fetch_assoc()) {
        $accessori[] = $r;
    }
    $row['accessori'] = $accessori;
    
    $officine[] = $row;
    $stmt2->close();
}

echo json_encode(['success' => true, 'officine' => $officine]);
$stmt->close();
?>