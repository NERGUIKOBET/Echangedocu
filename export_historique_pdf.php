<?php
session_start();
require_once 'db_connect.php';
require_once 'fpdf.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
    header('Content-Type: text/plain');
    echo 'Non connecté';
    exit;
}

$company_id = $_SESSION['company_id'];
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

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,'Historique des Echanges',0,1,'C');
$pdf->SetFont('Arial','B',10);
$pdf->Cell(30,8,'Date',1);
$pdf->Cell(35,8,'Document',1);
$pdf->Cell(20,8,'Action',1);
$pdf->Cell(30,8,'Utilisateur',1);
$pdf->Cell(35,8,'Source',1);
$pdf->Cell(35,8,'Destinataire',1);
$pdf->Cell(0,8,'Commentaire',1,1);
$pdf->SetFont('Arial','',9);
foreach ($rows as $r) {
    $pdf->Cell(30,8,substr($r['date_action'],0,16),1);
    $pdf->Cell(35,8,utf8_decode($r['document']),1);
    $pdf->Cell(20,8,$r['action'],1);
    $pdf->Cell(30,8,utf8_decode($r['utilisateur']),1);
    $pdf->Cell(35,8,utf8_decode($r['entreprise_source']),1);
    $pdf->Cell(35,8,utf8_decode($r['entreprise_dest']),1);
    $pdf->Cell(0,8,utf8_decode($r['commentaire']),1,1);
}
$pdf->Output('D','historique_echanges.pdf');
?>