<?php
require_once 'db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$docId = $_POST['edit-doc-id'] ?? null;
if (!$docId || !isset($_FILES['edit-document-file'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

try {
   
    $stmt = $pdo->prepare('SELECT chemin_fichier, file_path FROM documents WHERE id = ?');
    $stmt->execute([$docId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$doc) {
        echo json_encode(['success' => false, 'message' => 'Document introuvable']);
        exit;
    }
    $oldPath = $doc['chemin_fichier'] ?? $doc['file_path'];
    if ($oldPath && file_exists(__DIR__ . '/' . $oldPath)) {
        unlink(__DIR__ . '/' . $oldPath);
    }

    // Traiter le nouvel upload
    $file = $_FILES['edit-document-file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf','jpg','jpeg','png','zip','doc','docx'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé']);
        exit;
    }
    $newName = uniqid('doc_') . '.' . $ext;
    $storage = 'documents_storage/' . $newName;
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $storage)) {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l’enregistrement du fichier']);
        exit;
    }

    // Mettre à jour la base
    $stmt = $pdo->prepare('UPDATE documents SET nom_fichier = ?, chemin_fichier = ?, file_path = ?, type_fichier = ?, taille = ? WHERE id = ?');
    $stmt->execute([
        $file['name'],
        $storage,
        $storage,
        $file['type'],
        $file['size'],
        $docId
    ]);

    echo json_encode(['success' => true, 'message' => 'Fichier remplacé avec succès', 'path' => $storage]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur', 'detail' => $e->getMessage()]);
}
?>