<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db_connect.php';

try {
    // session fallback: try both keys used across the codebase
    $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
    $company_id = $_SESSION['company_id'] ?? $_SESSION['entreprise_id'] ?? null;

    if (!$user_id || !$company_id) {
        echo json_encode(['success' => false, 'message' => 'Non connecté']);
        exit;
    }

    // Try two possible schemas for the documents table
    $queries = [
        // modern/normalized schema
        "SELECT d.id, d.title AS nom, d.file_path AS chemin, d.status AS statut, d.sender_id, d.sender_company_id, d.recipient_company_id, d.created_at,
                e.nom AS sender_company, e2.nom AS recipient_company
            FROM documents d
            JOIN entreprises e ON d.sender_company_id = e.id
            JOIN entreprises e2 ON d.recipient_company_id = e2.id
            WHERE d.sender_company_id = :company_id OR d.recipient_company_id = :company_id
            ORDER BY d.created_at DESC LIMIT 100",

        // older schema used earlier in this repo
        "SELECT d.id, d.nom_fichier AS nom, d.chemin_fichier AS chemin, d.statut AS statut, d.utilisateur_id AS sender_id, d.entreprise_source_id AS sender_company_id, d.entreprise_dest_id AS recipient_company_id, d.date_envoi AS created_at,
                e.nom AS sender_company, e2.nom AS recipient_company
            FROM documents d
            JOIN entreprises e ON d.entreprise_source_id = e.id
            JOIN entreprises e2 ON d.entreprise_dest_id = e2.id
            WHERE d.entreprise_source_id = :company_id OR d.entreprise_dest_id = :company_id
            ORDER BY d.date_envoi DESC LIMIT 100"
    ];

    $docs = [];
    foreach ($queries as $sql) {
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute(['company_id' => $company_id])) {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $docs = $rows;
                break;
            }
        }
    }

    // Normalize documents array to expected JSON shape
    $normalized = [];
    foreach ($docs as $d) {
        $normalized[] = [
            'id' => $d['id'] ?? null,
            'nom' => $d['nom'] ?? ($d['title'] ?? null),
            'chemin' => $d['chemin'] ?? ($d['file_path'] ?? null),
            'statut' => strtolower($d['statut'] ?? $d['status'] ?? 'unknown'),
            'sender_id' => $d['sender_id'] ?? null,
            'sender_company_id' => $d['sender_company_id'] ?? null,
            'recipient_company_id' => $d['recipient_company_id'] ?? null,
            'created_at' => $d['created_at'] ?? null,
            'sender_company' => $d['sender_company'] ?? null,
            'recipient_company' => $d['recipient_company'] ?? null,
        ];
    }

    // Statistics
    $stats = ['pending' => 0, 'approved' => 0, 'refused' => 0];
    foreach ($normalized as $doc) {
        if ($doc['statut'] === 'pending' || $doc['statut'] === 'en_attente') $stats['pending']++;
        if ($doc['statut'] === 'approved' || $doc['statut'] === 'recu') $stats['approved']++;
        if ($doc['statut'] === 'refused' || $doc['statut'] === 'refuse') $stats['refused']++;
    }

    echo json_encode(['success' => true, 'documents' => $normalized, 'stats' => $stats]);

} catch (Exception $e) {
    // Return JSON error (avoid sending raw HTML)
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur', 'detail' => $e->getMessage()]);
}

?>