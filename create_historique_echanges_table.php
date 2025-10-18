<?php
require_once 'db_connect.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS historique_echanges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date_action DATETIME DEFAULT CURRENT_TIMESTAMP,
        action VARCHAR(50) NOT NULL,
        commentaire TEXT,
        utilisateur_id INT NOT NULL,
        document_id INT NOT NULL,
        INDEX idx_utilisateur (utilisateur_id),
        INDEX idx_document (document_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
    echo "Table 'historique_echanges' créée ou déjà existante.";
} catch (PDOException $e) {
    echo "Erreur lors de la création de la table: " . $e->getMessage();
}
?>