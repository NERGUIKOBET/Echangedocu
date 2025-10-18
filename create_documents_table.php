<?php
require_once 'db_connect.php';

/**
 * This script attempts to create a `documents` table compatible with
 * the existing codebase. It will try to ensure the `entreprises` table
 * uses InnoDB (required for foreign keys), validate the `id` column,
 * and then create `documents`. If FK creation fails it will retry
 * creating the table without foreign key constraints and clearly
 * inform the operator.
 */

function println($s) { echo $s . "\n"; }

try {
    $messages = [];

    // Check if entreprises table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'entreprises'");
    $exists = (bool) $stmt->fetch();

    if (! $exists) {
        println("La table 'entreprises' est introuvable. Veuillez créer les entreprises d'abord (ex: creer_entreprises.php).");
        exit(1);
    }

    // Check engine of entreprises
    $stmt = $pdo->query("SHOW TABLE STATUS WHERE Name = 'entreprises'");
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    $engine = isset($status['Engine']) ? strtoupper($status['Engine']) : null;
    println("Table 'entreprises' trouvée, engine = " . ($engine ?? 'UNKNOWN'));

    if ($engine !== 'INNODB') {
        // Try converting to InnoDB so FKs can be added
        try {
            $pdo->exec("ALTER TABLE entreprises ENGINE=InnoDB");
            println("Conversion de 'entreprises' vers InnoDB réussie.");
        } catch (PDOException $e) {
            println("Impossible de convertir 'entreprises' en InnoDB: " . $e->getMessage());
            println("Le script va continuer et créer 'documents' sans contraintes de clé étrangère.");
            $engine = null; // mark as not suitable for FKs
        }
    }

    // Validate entreprises.id column
    $validId = false;
    try {
        $col = $pdo->query("SHOW COLUMNS FROM entreprises LIKE 'id'")->fetch(PDO::FETCH_ASSOC);
        if ($col) {
            $type = strtoupper($col['Type']);
            $isInt = strpos($type, 'INT') !== false;
            $isPK = stripos($col['Key'] ?? '', 'PRI') !== false;
            $validId = $isInt && $isPK;
        }
    } catch (PDOException $e) {
        // ignore
    }

    if (! $validId) {
        println("Attention: la colonne 'entreprises.id' n'est pas un INT PRIMARY KEY. Les contraintes FK peuvent échouer.");
        println("Le script va créer la table 'documents' sans contraintes FK pour éviter l'erreur.");
        $engine = null; // force fallback
    }

    // Prepare two SQL attempts: with FK (preferred) and without FK (fallback)
    $sqlWithFK = "CREATE TABLE IF NOT EXISTS documents (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nom_fichier VARCHAR(255) DEFAULT NULL,
        title VARCHAR(255) DEFAULT NULL,
        chemin_fichier VARCHAR(255) DEFAULT NULL,
        file_path VARCHAR(255) DEFAULT NULL,
        type_fichier VARCHAR(50) DEFAULT 'other',
        taille BIGINT DEFAULT 0,
        utilisateur_id INT DEFAULT NULL,
        sender_id INT DEFAULT NULL,
        entreprise_source_id INT DEFAULT NULL,
        sender_company_id INT DEFAULT NULL,
        entreprise_dest_id INT DEFAULT NULL,
        recipient_company_id INT DEFAULT NULL,
        statut VARCHAR(50) DEFAULT 'en_attente',
        status VARCHAR(50) DEFAULT 'pending',
        date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        description TEXT,
        INDEX idx_entreprise_source (entreprise_source_id),
        INDEX idx_entreprise_dest (entreprise_dest_id),
        INDEX idx_status (statut),
        INDEX idx_status2 (status),
        CONSTRAINT fk_docs_ent_src FOREIGN KEY (entreprise_source_id) REFERENCES entreprises(id) ON DELETE SET NULL ON UPDATE CASCADE,
        CONSTRAINT fk_docs_ent_dest FOREIGN KEY (entreprise_dest_id) REFERENCES entreprises(id) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $sqlNoFK = "CREATE TABLE IF NOT EXISTS documents (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nom_fichier VARCHAR(255) DEFAULT NULL,
        title VARCHAR(255) DEFAULT NULL,
        chemin_fichier VARCHAR(255) DEFAULT NULL,
        file_path VARCHAR(255) DEFAULT NULL,
        type_fichier VARCHAR(50) DEFAULT 'other',
        taille BIGINT DEFAULT 0,
        utilisateur_id INT DEFAULT NULL,
        sender_id INT DEFAULT NULL,
        entreprise_source_id INT DEFAULT NULL,
        sender_company_id INT DEFAULT NULL,
        entreprise_dest_id INT DEFAULT NULL,
        recipient_company_id INT DEFAULT NULL,
        statut VARCHAR(50) DEFAULT 'en_attente',
        status VARCHAR(50) DEFAULT 'pending',
        date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        description TEXT,
        INDEX idx_entreprise_source (entreprise_source_id),
        INDEX idx_entreprise_dest (entreprise_dest_id),
        INDEX idx_status (statut),
        INDEX idx_status2 (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // Try to create with FK if the entreprises table seems OK for FKs
    if ($engine === 'INNODB' && $validId) {
        try {
            $pdo->exec($sqlWithFK);
            println("Table 'documents' créée avec contraintes de clés étrangères.");
            exit(0);
        } catch (PDOException $e) {
            println("La création avec contraintes FK a échoué: " . $e->getMessage());
            println("Tentative de création sans contraintes FK...");
        }
    } else {
        println("Création avec FK sautée (entreprises pas prête pour FK). Création sans FK... ");
    }

    // Fallback: create without FK
    try {
        $pdo->exec($sqlNoFK);
        println("Table 'documents' créée (sans contraintes FK).\nAttention: aucune contrainte de clé étrangère n'a été ajoutée.");
        println("Si vous souhaitez activer les FK, assurez-vous que 'entreprises' est InnoDB et que 'id' est INT PRIMARY KEY, puis recréez ou modifiez la table.");
        exit(0);
    } catch (PDOException $e) {
        println("Échec de la création de la table 'documents' (même sans FK): " . $e->getMessage());
        exit(1);
    }

} catch (PDOException $e) {
    println("Erreur inattendue: " . $e->getMessage());
    exit(1);
}

?>