<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

// Lecture des données JSON envoyées
$data = json_decode(file_get_contents('php://input'), true);
$docId = isset($data['docId']) ? intval($data['docId']) : 0;
$status = isset($data['status']) ? $data['status'] : '';

if (!$docId || !in_array($status, ['approved', 'refused'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

// Vérifier que le document appartient à la société connectée (réception)
$sql = "SELECT recipient_company_id, status FROM documents WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$docId]);
$doc = $stmt->fetch();
if (!$doc || $doc['recipient_company_id'] != $_SESSION['company_id'] || $doc['status'] != 'pending') {
    echo json_encode(['success' => false, 'message' => 'Action non autorisée']);
    exit;
}

// Mise à jour du statut
$sql = "UPDATE documents SET status = ? WHERE id = ?";
$stmt = $pdo->prepare($sql);
if ($stmt->execute([$status, $docId])) {
    // Journaliser l'action dans historique_echanges
    $action = $status === 'approved' ? 'reception' : 'refus';
    $sqlHist = "INSERT INTO historique_echanges (document_id, action, utilisateur_id, date_action) VALUES (?, ?, ?, NOW())";
    $stmtHist = $pdo->prepare($sqlHist);
    $stmtHist->execute([$docId, $action, $_SESSION['user_id']]);
    echo json_encode(['success' => true, 'message' => 'Statut mis à jour et action journalisée']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
}
?>