<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
    header('Content-Type: text/plain');
    echo 'Non connecté';
    exit;
}

$company_id = $_SESSION['company_id'];
// Récupérer les filtres GET
$filters = $_GET;
$where = "(d.sender_company_id = :company_id OR d.recipient_company_id = :company_id)";
$params = ['company_id' => $company_id];
if (!empty($filters['action'])) {
    $where .= " AND h.action = :action";
    $params['action'] = $filters['action'];
}
if (!empty($filters['utilisateur'])) {
    $where .= " AND u.nom LIKE :utilisateur";
    $params['utilisateur'] = '%' . $filters['utilisateur'] . '%';
}
if (!empty($filters['date_debut'])) {
    $where .= " AND h.date_action >= :date_debut";
    $params['date_debut'] = $filters['date_debut'] . ' 00:00:00';
}
if (!empty($filters['date_fin'])) {
    $where .= " AND h.date_action <= :date_fin";
    $params['date_fin'] = $filters['date_fin'] . ' 23:59:59';
}
$sql = "SELECT h.date_action, h.action, h.commentaire, u.nom AS utilisateur, d.title AS document, e.nom AS entreprise_source, e2.nom AS entreprise_dest
        FROM historique_echanges h
        JOIN utilisateurs u ON h.utilisateur_id = u.id
        JOIN documents d ON h.document_id = d.id
        JOIN entreprises e ON d.sender_company_id = e.id
        JOIN entreprises e2 ON d.recipient_company_id = e2.id
        WHERE $where
        ORDER BY h.date_action DESC LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
// Générer le CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="historique_echanges.csv"');
$output = fopen('php://output', 'w');
fputcsv($output, ['Date', 'Document', 'Action', 'Utilisateur', 'Entreprise Source', 'Entreprise Dest', 'Commentaire']);
foreach ($rows as $r) {
    fputcsv($output, [
        $r['date_action'],
        $r['document'],
        $r['action'],
        $r['utilisateur'],
        $r['entreprise_source'],
        $r['entreprise_dest'],
        $r['commentaire']
    ]);
}
fclose($output);
?>