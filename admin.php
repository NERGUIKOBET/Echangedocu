<?php
// ==============================================================================
// 1. DÉSACTIVATION DE LA SÉCURITÉ PHP
// ==============================================================================
// Normalement, ce code aurait: session_start(); require_once 'security_check.php'; require_role('Admin');

// Initialisation des variables statiques pour le test (Accès Libre)
$username = 'ADMIN_TEST (ACCÈS LIBRE)';
$company_id = 999; // ID statique pour le test

require_once 'db_connect.php'; // On garde la connexion DB pour la logique de comptage

try {
    // 1. Compte des utilisateurs actifs dans l'entreprise de l'Admin
    // NOTE: Pour le test non sécurisé, on pourrait compter TOUS les utilisateurs, 
    // mais on garde le filtre company_id pour l'exemple.
    $sql_users = "SELECT COUNT(*) FROM users WHERE company_id = :cid";
    $stmt_users = $pdo->prepare($sql_users);
    $stmt_users->execute([':cid' => $company_id]);
    $user_count = $stmt_users->fetchColumn();

    // 2. Compte des documents partagés (envoyés OU reçus) par l'entreprise
    $sql_docs = "SELECT COUNT(*) FROM documents WHERE sender_company_id = :cid OR recipient_company_id = :cid";
    $stmt_docs = $pdo->prepare($sql_docs);
    $stmt_docs->execute([':cid' => $company_id]);
    $shared_docs_total = $stmt_docs->fetchColumn();
    
    // 3. Compte des documents en attente de traitement (reçus par l'entreprise)
    $sql_pending = "SELECT COUNT(*) FROM documents WHERE recipient_company_id = :cid AND status = 'pending'";
    $stmt_pending = $pdo->prepare($sql_pending);
    $stmt_pending->execute([':cid' => $company_id]);
    $pending_docs = $stmt_pending->fetchColumn();

} catch (PDOException $e) {
    error_log("Admin DB Error: " . $e->getMessage());
    // Fallback aux valeurs statiques ou affichage d'une erreur
    $user_count = 'Erreur';
    $shared_docs_total = 'Erreur';
    $pending_docs = 'Erreur';
}
// ==============================================================================
// 2. STRUCTURE HTML/CSS DE L'INTERFACE ADMIN
// ==============================================================================
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Administrateur - E-Doc Connect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styles CSS de la maquette précédente (admin.html) */
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --success: #2ecc71;
            --light: #ecf0f1;
            --sidebar-bg: #34495e;
            --card-bg: #ffffff;
        }
        
        body { font-family: sans-serif; background-color: var(--light); margin: 0; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background-color: var(--sidebar-bg); color: white; padding-top: 20px; position: fixed; height: 100%; box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);}
        .logo { text-align: center; font-size: 1.5rem; font-weight: bold; margin-bottom: 20px;}
        .menu-item { padding: 15px 20px; cursor: pointer; transition: background 0.2s; }
        .menu-item:hover, .menu-item.active { background-color: rgba(255, 255, 255, 0.1); border-left: 4px solid var(--success); }
        .menu-item a { color: white; text-decoration: none; display: block; }
        .main-content { flex: 1; margin-left: 250px; padding: 20px 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }

        /* SUPPRESSION DU STYLE .admin-alert CAR L'ALERTE EST SUPPRIMÉE */

        .stat-card-container { display: flex; gap: 20px; flex-wrap: wrap; }
        .stat-card { background-color: var(--card-bg); padding: 20px; border-left: 5px solid var(--secondary); border-radius: 4px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); min-width: 250px; }
        .stat-card h3 { color: var(--primary); margin-bottom: 10px; font-size: 1rem;}
        .stat-card p { font-size: 1.5rem; font-weight: bold; }
    </style>
</head>
<body>
    

    <div class="container">
        <div class="sidebar">
            <div class="logo">Admin Panel</div>
            <p style="color: #ccc; text-align: center; font-size: 0.9em; margin-bottom: 20px;">
                Connecté: <?php echo $username; ?> (Libre)<br>
                Entrep. ID: <?php echo $company_id; ?>
            </p>
            <ul class="menu">
                <li class="menu-item active"><i class="fas fa-tachometer-alt"></i> Tableau de bord Admin</li>
                <li class="menu-item"><a href="admin_users.php"><i class="fas fa-users-cog"></i> Gérer Utilisateurs (Mon Ent.)</a></li>
                                <li class="menu-item"><a href="admin_config.php"><i class="fas fa-cog"></i> Configurations Globales</a></li>
                <li class="menu-item"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <h2>Tableau de Bord Administrateur (Non Sécurisé)</h2>
                <span style="color: var(--secondary);">Entreprise ID: <?php echo $company_id; ?></span>
            </div>

                        
            <div class="stat-card-container">
                <div class="stat-card" style="border-left-color: var(--secondary);">
                    <h3>Utilisateurs actifs (Mon Ent.)</h3>
                    <p><?php echo $user_count; ?></p> 
                </div>
                <div class="stat-card" style="border-left-color: #e67e22;">
                    <h3>Documents partagés ce mois (Total)</h3>
                    <p><?php echo $shared_docs_total; ?></p>
                </div>
                <div class="stat-card" style="border-left-color: var(--success);">
                    <h3>Documents en attente (Mon Ent.)</h3>
                    <p><?php echo $pending_docs; ?></p>
                </div>
            </div>
            
            <div style="margin-top: 30px;">
                <h3>Aperçu des Derniers Logs</h3>
                <p>Ceci est un emplacement pour un tableau généré par PHP/AJAX affichant les 5 dernières activités importantes pour l'Entreprise <?php echo $company_id; ?>.</p>
                </div>

        </div>
    </div>

</body>
</html>