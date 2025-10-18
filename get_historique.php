<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$company_id = $_SESSION['company_id'];

// Récupérer les filtres envoyés en POST (JSON)
$filters = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filters = json_decode(file_get_contents('php://input'), true);
}


// Essayer d'abord avec sender_company_id/recipient_company_id
$where1 = "(d.sender_company_id = :company_id OR d.recipient_company_id = :company_id)";
$where2 = "(d.entreprise_source_id = :company_id OR d.entreprise_dest_id = :company_id)";
$params = ['company_id' => $company_id];

if (!empty($filters['action'])) {
    $where1 .= " AND h.action = :action";
    $where2 .= " AND h.action = :action";
    $params['action'] = $filters['action'];
}
if (!empty($filters['utilisateur'])) {
    $where1 .= " AND u.nom LIKE :utilisateur";
    $where2 .= " AND u.nom LIKE :utilisateur";
    $params['utilisateur'] = '%' . $filters['utilisateur'] . '%';
}
if (!empty($filters['date_debut'])) {
    $where1 .= " AND h.date_action >= :date_debut";
    $where2 .= " AND h.date_action >= :date_debut";
    $params['date_debut'] = $filters['date_debut'] . ' 00:00:00';
}
if (!empty($filters['date_fin'])) {
    $where1 .= " AND h.date_action <= :date_fin";
    $where2 .= " AND h.date_action <= :date_fin";
    $params['date_fin'] = $filters['date_fin'] . ' 23:59:59';
}

$sql1 = "SELECT h.id, h.date_action, h.action, h.commentaire, u.nom AS utilisateur, d.title AS document, e.nom AS entreprise_source, e2.nom AS entreprise_dest
        FROM historique_echanges h
        JOIN utilisateurs u ON h.utilisateur_id = u.id
        JOIN documents d ON h.document_id = d.id
        JOIN entreprises e ON d.sender_company_id = e.id
        JOIN entreprises e2 ON d.recipient_company_id = e2.id
        WHERE $where1
        ORDER BY h.date_action DESC LIMIT 50";

$sql2 = "SELECT h.id, h.date_action, h.action, h.commentaire, u.nom AS utilisateur, d.nom_fichier AS document, e.nom AS entreprise_source, e2.nom AS entreprise_dest
        FROM historique_echanges h
        JOIN utilisateurs u ON h.utilisateur_id = u.id
        JOIN documents d ON h.document_id = d.id
        JOIN entreprises e ON d.entreprise_source_id = e.id
        JOIN entreprises e2 ON d.entreprise_dest_id = e2.id
        WHERE $where2
        ORDER BY h.date_action DESC LIMIT 50";

$rows = [];
try {
    $stmt = $pdo->prepare($sql1);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute($params);
        $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur', 'detail' => $e->getMessage()]);
    exit;
}

echo json_encode(['success' => true, 'historique' => $rows]);
?>