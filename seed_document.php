<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

try {
    // ensure storage dir exists
    $storage = __DIR__ . DIRECTORY_SEPARATOR . 'documents_storage';
    if (!is_dir($storage)) {
        mkdir($storage, 0755, true);
    }

    // write a tiny placeholder PDF (not a full PDF but enough for list/download tests)
    $filename = 'test-placeholder.pdf';
    $path = $storage . DIRECTORY_SEPARATOR . $filename;
    if (!file_exists($path)) {
        $data = "%PDF-1.4\n%âãÏÓ\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] /Contents 4 0 R >>\nendobj\n4 0 obj\n<< /Length 44 >>\nstream\nBT /F1 24 Tf 72 100 Td (Test) Tj ET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f \n0000000010 00000 n \n0000000061 00000 n \n0000000116 00000 n \n0000000221 00000 n \ntrailer\n<< /Root 1 0 R /Size 5 >>\nstartxref\n315\n%%EOF";
        file_put_contents($path, $data);
    }

    // insert into documents table
    $nom = 'test-placeholder.pdf';
    $chemin = 'documents_storage/' . $filename;
    $type = 'application/pdf';
    $taille = filesize($path);

    $stmt = $pdo->prepare("INSERT INTO documents (nom_fichier, chemin_fichier, file_path, type_fichier, taille, entreprise_source_id, entreprise_dest_id, statut, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // choose entreprise ids 1 and 2 if exist, else NULL
    $ent_src = 1; $ent_dest = 2;

    $stmt->execute([$nom, $chemin, $chemin, $type, $taille, $ent_src, $ent_dest, 'en_attente', 'Document de test créé par seed_document.php']);

    echo json_encode(['success' => true, 'message' => 'Document de test inséré', 'path' => $chemin]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>