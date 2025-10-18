<?php
// --- Partie PHP de Sécurité et de Session ---

// 1. Inclusion des fichiers de sécurité
require_once 'security_check.php';

// 2. Vérification obligatoire de connexion (RBAC minimal)
require_login(); 

// 3. Récupération des informations de session pour l'affichage
$username = htmlspecialchars($_SESSION['username'] ?? 'Utilisateur');
$role = htmlspecialchars($_SESSION['role'] ?? 'Opérateur');
$company_id = htmlspecialchars($_SESSION['company_id'] ?? 'N/A');

// --- FIN de la Partie PHP ---
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Espace Client</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Styles CSS inchangés - Ils sont bien structurés */
        :root {
            --primary: #2c3e50; /* Couleur principale (sidebar) */
            --secondary: #3498db; /* Bleu */
            --accent: #e74c3c; /* Rouge */
            --success: #2ecc71; /* Vert */
            --background: #f4f7f9;
            --sidebar-bg: #2c3e50;
        }

        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--background); margin: 0; }
        .dashboard-container { display: flex; min-height: 100vh; } 
        
        /* Sidebar (position fixe pour un menu permanent) */
        .sidebar { width: 250px; background-color: var(--sidebar-bg); color: white; padding-top: 20px; box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1); position: fixed; height: 100%; top: 0; }
        .logo { text-align: center; font-size: 1.5rem; font-weight: bold; margin-bottom: 30px; }
        .menu { list-style: none; padding: 0; }
        .menu-item { padding: 15px 20px; cursor: pointer; transition: background 0.2s; display: flex; align-items: center;}
        .menu-item i { margin-right: 10px; }
        .menu-item:hover, .menu-item.active { background-color: rgba(255, 255, 255, 0.1); border-left: 4px solid var(--success); }
        .menu-item a { color: white; text-decoration: none; display: flex; align-items: center; width: 100%; }
        
        /* Main Content */
        .main-content { flex: 1; margin-left: 250px; padding: 20px 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .card-container { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .card { flex: 1; min-width: 250px; background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); }
        .card h3 { color: var(--primary); margin-bottom: 10px; }
        
        /* Tableau */
        .documents-table { width: 100%; border-collapse: collapse; background-color: white; border-radius: 8px; overflow: hidden; margin-top: 20px; }
        .documents-table th, .documents-table td { padding: 15px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        .documents-table th { background-color: var(--secondary); color: white; }
        
        /* Boutons */
        .btn { padding: 10px 18px; border-radius: 5px; cursor: pointer; border: none; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center;}
        .btn i { margin-right: 8px; }
        .btn-success { background-color: var(--success); color: white; }
        .btn-primary { background-color: var(--secondary); color: white; }
        
        /* Modals */
        .modal-overlay { display: none; /* CACHÉ par défaut */ position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: white; padding: 30px; border-radius: 10px; width: 100%; max-width: 500px; position: relative; }
        .form-group { margin-bottom: 15px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
    </style>
</head>
<body>
    
    <div class="dashboard-container">

        <aside class="sidebar">
            <div class="logo">E-Doc Connect</div>
            <p style="color: #ccc; text-align: center; font-size: 0.9em; margin-bottom: 20px;">
                Connecté: **<?php echo $username; ?>** (<?php echo $role; ?>)<br>
                Entrep. ID: <?php echo $company_id; ?>
            </p>

            <ul class="menu">
                <li class="menu-item active" id="menu-inbox"><i class="fas fa-inbox"></i> Boîte de Réception</li>
                <li class="menu-item" id="menu-sent"><i class="fas fa-paper-plane"></i> Documents Envoyés</li>
                <li class="menu-item" id="menu-upload"><i class="fas fa-upload"></i> Nouveau Document</li>
                <li class="menu-item" id="menu-historique"><i class="fas fa-history"></i> Transactions / Historique</li>
                <li class="menu-item logout">
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Tableau de Bord</h1>
                <button class="btn btn-success" id="open-upload-modal">
                    <i class="fas fa-plus"></i> Nouveau Partage
                </button>
            </div>

            <div class="card-container">
                <div class="card">
                    <h3>Documents en Attente</h3>
                    <p id="pending-count" style="font-size: 2em; color: orange;">0</p>
                </div>
                <div class="card">
                    <h3>Documents Approuvés</h3>
                    <p id="approved-count" style="font-size: 2em; color: var(--success);">0</p>
                </div>
                <div class="card">
                    <h3>Mon Rôle</h3>
                    <p style="font-size: 2em; color: var(--secondary);"><?php echo $role; ?></p>
                </div>
            </div>

            <div id="content-display">
                <p style="text-align: center;">Veuillez patienter pendant le chargement des données...</p>
            </div>

            <!-- Modal pour modification de document -->
            <div class="modal-overlay" id="edit-document-modal" style="display:none;">
                <div class="modal-content">
                    <h3 style="color: var(--primary); margin-top: 0;">Modifier le fichier du document</h3>
                    <span style="position: absolute; top: 10px; right: 20px; font-size: 1.5rem; cursor: pointer;" 
                          onclick="document.getElementById('edit-document-modal').style.display='none'">&times;</span>
                    <form id="edit-document-form" enctype="multipart/form-data"> 
                        <input type="hidden" id="edit-doc-id" name="edit-doc-id" />
                        <div class="form-group">
                            <label for="edit-document-file">Nouveau fichier (PDF, Image, Zip...)</label>
                            <input type="file" id="edit-document-file" name="edit-document-file" class="form-control" required>
                        </div>
                        <p id="edit-form-status" style="margin-top: 15px; font-weight: bold;"></p>
                        <button type="submit" id="submit-edit-btn" class="btn btn-primary" style="margin-top: 15px;">
                            <i class="fas fa-pencil-alt"></i> Remplacer le fichier
                        </button>
                    </form>
                </div>
            </div>
        </main>
        
    </div> 
    
    <!-- Modal de prévisualisation des documents -->
    <div class="modal-overlay" id="preview-modal" style="display:none;">
        <div class="modal-content" style="max-width:900px; width:95%;">
            <span style="position: absolute; top: 10px; right: 20px; font-size: 1.5rem; cursor: pointer;" 
                  onclick="document.getElementById('preview-modal').style.display='none'">&times;</span>
            <h3 id="preview-title" style="color: var(--primary); margin-top: 0;">Aperçu</h3>
            <div id="preview-content" style="min-height:200px; max-height:80vh; overflow:auto;"></div>
            <p style="margin-top:10px; text-align:right;"><a id="preview-download" href="#" target="_blank" class="btn btn-primary">Télécharger</a></p>
        </div>
    </div>

    <div class="modal-overlay" id="new-document-modal">
        <div class="modal-content">
            <h3 style="color: var(--primary); margin-top: 0;">Partager un nouveau document</h3>
            <span style="position: absolute; top: 10px; right: 20px; font-size: 1.5rem; cursor: pointer;" 
                  onclick="document.getElementById('new-document-modal').style.display='none'">&times;</span>

            <form id="new-document-form" enctype="multipart/form-data"> 
                
                <div class="form-group">
                    <label for="document-title">Titre du Document</label>
                    <input type="text" id="document-title" name="document-title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="document-recipient">Destinataire (Partenaire)</label>
                    <select id="document-recipient" name="document-recipient" class="form-control" required>
                        <option value="<?php echo $company_id == 1 ? 2 : 1; ?>">
                            Entreprise Partenaire (ID <?php echo $company_id == 1 ? 2 : 1; ?>)
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="document-file">Sélectionner le Fichier (PDF, Image, Zip...)</label>
                    <input type="file" id="document-file" name="document-file" class="form-control" required>
                </div>
                
                <p id="form-status" style="margin-top: 15px; font-weight: bold;"></p>
                <button type="submit" id="submit-doc-btn" class="btn btn-primary" style="margin-top: 15px;">
                    <i class="fas fa-paper-plane"></i> Envoyer
                </button>
            </form>

        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const openModalButton = document.getElementById('open-upload-modal');
            const modal = document.getElementById('new-document-modal');

            if (openModalButton && modal) {
                openModalButton.addEventListener('click', function() {
                    modal.style.display = 'flex';
                });
            }
        });
    </script>

    <script>
        // Exposer quelques variables PHP au JS
        window.company_id = <?php echo json_encode(intval($_SESSION['company_id'] ?? 0)); ?>;
        window.username = <?php echo json_encode($_SESSION['username'] ?? ''); ?>;
    </script>

    <script src="js/main.js"></script> 
</body>
</html>