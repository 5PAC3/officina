<?php
// Tipo di risposta: JSON (non HTML)
header('Content-Type: application/json');
// CORS: permette richieste da qualsiasi dominio (in produzione meglio specificare)
header('Access-Control-Allow-Origin: *');
// Metodi HTTP accettati
header('Access-Control-Allow-Methods: POST');
// Header acceptati nelle richieste
header('Access-Control-Allow-Headers: Content-Type');

require_once '../classes/database.php';

$db = Database::getInstance();

// Ricevi i dati in JSON
$data = json_decode(file_get_contents('php://input'), true);

$tipo = $data['tipo'] ?? '';      // 'servizio', 'pezzo', 'accessorio'
$codice = $data['codice'] ?? '';
$mostra_tutti = $data['mostra_tutti'] ?? false;

if (empty($tipo) || empty($codice)) {
    echo json_encode(['error' => 'Dati incompleti']);
    exit;
}

$officine = [];

$conn = $db->getConnection();

switch($tipo) {
    case 'servizio':
        if ($mostra_tutti) {
            $stmt = $conn->prepare("SELECT o.codice, o.denominazione, o.indirizzo, o.telefono, s.costo_orario
                    FROM servizio s
                    LEFT JOIN offre ofr ON s.codice = ofr.servizio_codice
                    LEFT JOIN officina o ON ofr.officina_codice = o.codice
                    WHERE s.codice = ?");
        } else {
            $stmt = $conn->prepare("SELECT o.codice, o.denominazione, o.indirizzo, o.telefono, s.costo_orario
                    FROM officina o
                    INNER JOIN offre ofr ON o.codice = ofr.officina_codice
                    INNER JOIN servizio s ON ofr.servizio_codice = s.codice
                    WHERE s.codice = ?");
        }
        $stmt->bind_param("s", $codice);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            if ($mostra_tutti && empty($row['codice'])) {
                continue;
            }
            $row['tipo_servizio'] = $row['codice'] ? 'Servizio offerto' : 'Non disponibile';
            $row['costo'] = $row['costo_orario'] . ' €/ora';
            unset($row['costo_orario']);
            $officine[] = $row;
        }
        $stmt->close();
        break;
        
    case 'pezzo':
        if ($mostra_tutti) {
            $stmt = $conn->prepare("SELECT o.codice, o.denominazione, o.indirizzo, o.telefono, 
                    COALESCE(pp.quantita, 0) as quantita, p.costo_unitario
                    FROM pezzo_ricambio p
                    LEFT JOIN presenza_pezzo pp ON p.codice = pp.pezzo_codice
                    LEFT JOIN officina o ON pp.officina_codice = o.codice
                    WHERE p.codice = ? AND (o.codice IS NULL OR pp.quantita > 0)");
        } else {
            $stmt = $conn->prepare("SELECT o.codice, o.denominazione, o.indirizzo, o.telefono, pp.quantita, p.costo_unitario
                    FROM officina o
                    INNER JOIN presenza_pezzo pp ON o.codice = pp.officina_codice
                    INNER JOIN pezzo_ricambio p ON pp.pezzo_codice = p.codice
                    WHERE p.codice = ? AND pp.quantita > 0");
        }
        $stmt->bind_param("s", $codice);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            if ($mostra_tutti && empty($row['codice'])) {
                continue;
            }
            $row['tipo_servizio'] = ($row['quantita'] ?? 0) > 0 ? 'Pezzo disponibile' : 'Pezzo non disponibile';
            $row['costo'] = $row['costo_unitario'] . ' €/pezzo';
            $row['info'] = 'Quantità: ' . ($row['quantita'] ?? 0);
            unset($row['costo_unitario'], $row['quantita']);
            $officine[] = $row;
        }
        $stmt->close();
        break;
        
    case 'accessorio':
        if ($mostra_tutti) {
            $stmt = $conn->prepare("SELECT o.codice, o.denominazione, o.indirizzo, o.telefono, 
                    COALESCE(pa.quantita, 0) as quantita, a.costo_unitario
                    FROM accessorio a
                    LEFT JOIN presenza_accessorio pa ON a.codice = pa.accessorio_codice
                    LEFT JOIN officina o ON pa.officina_codice = o.codice
                    WHERE a.codice = ? AND (o.codice IS NULL OR pa.quantita > 0)");
        } else {
            $stmt = $conn->prepare("SELECT o.codice, o.denominazione, o.indirizzo, o.telefono, pa.quantita, a.costo_unitario
                    FROM officina o
                    INNER JOIN presenza_accessorio pa ON o.codice = pa.officina_codice
                    INNER JOIN accessorio a ON pa.accessorio_codice = a.codice
                    WHERE a.codice = ? AND pa.quantita > 0");
        }
        $stmt->bind_param("s", $codice);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            if ($mostra_tutti && empty($row['codice'])) {
                continue;
            }
            $row['tipo_servizio'] = ($row['quantita'] ?? 0) > 0 ? 'Accessorio disponibile' : 'Non disponibile';
            $row['costo'] = $row['costo_unitario'] . ' €';
            $row['info'] = 'Quantità: ' . ($row['quantita'] ?? 0);
            unset($row['costo_unitario'], $row['quantita']);
            $officine[] = $row;
        }
        $stmt->close();
        break;
}

echo json_encode(['success' => true, 'officine' => $officine]);
?>
