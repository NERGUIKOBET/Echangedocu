<?php
/**
 * Script de traitement de l'upload de documents via AJAX (fetch).
 * Gère la validation, le stockage sécurisé du fichier, et l'enregistrement DB.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db_connect.php';       // Connexion PDO
require_once 'security_check.php'; // Fonctions de vérification de connexion

// Buffer any accidental output (warnings, notices) so we can return it inside JSON
ob_start();

// Définit l'en-tête pour que le navigateur sache que la réponse est au format JSON
header('Content-Type: application/json; charset=utf-8');

// Vérifie la session manuellement pour éviter que require_login() fasse une redirection HTML
if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
    // flush buffer and collect any stray output
    $buf = ob_get_clean();
    $debug = $buf ? strip_tags($buf) : null;
    echo json_encode(['success' => false, 'message' => 'Non connecté', 'debug' => $debug]);
    exit;
}

// --- Configuration ---
// Répertoire où les fichiers seront stockés (Créez ce dossier à la racine)
$uploadDir = 'documents_storage/'; 
// Taille maximale autorisée (ex: 10 mégaoctets)
$maxFileSize = 10 * 1024 * 1024; 
// Response template
$response = ['success' => false, 'message' => ''];

// Informations de l'utilisateur connecté via la session
$sender_id = $_SESSION['user_id'];
$sender_company_id = $_SESSION['company_id'];

// 1. Récupération et validation des données du formulaire
$title = filter_input(INPUT_POST, 'document-title', FILTER_SANITIZE_STRING);
$description = filter_input(INPUT_POST, 'document-description', FILTER_SANITIZE_STRING);
$recipientId = filter_input(INPUT_POST, 'document-recipient', FILTER_VALIDATE_INT);

// Validation minimale des champs
if (empty($title) || empty($recipientId)) {
    http_response_code(400); // Bad Request
    $response['message'] = 'Le titre et le destinataire sont obligatoires.';
    echo json_encode($response);
    exit;
}

try {
    // 2. Gestion de l'upload de fichier
    if (isset($_FILES['document-file']) && $_FILES['document-file']['error'] === UPLOAD_ERR_OK) {
    // Helper: convert php.ini size string (e.g. 2M) to bytes
    function iniBytes(string $val): int {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $num = (int)$val;
        switch($last) {
            case 'g': return $num * 1024 * 1024 * 1024;
            case 'm': return $num * 1024 * 1024;
            case 'k': return $num * 1024;
            default: return (int)$val;
        }
    }

    // Map PHP upload error codes to human messages
    function uploadErrMsg(int $code): string {
        $map = [
            UPLOAD_ERR_OK => 'Aucune erreur, tout est OK.',
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse upload_max_filesize dans php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la directive MAX_FILE_SIZE spécifiée dans le formulaire.',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement transféré.',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été envoyé.',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant.',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque.',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté l\'upload du fichier.'
        ];
        return $map[$code] ?? 'Erreur inconnue lors de l\'upload.';
    }

    // Quick check for post_max_size / upload_max_filesize exceeded by content length
    $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
    $postMax = iniBytes(ini_get('post_max_size') ?: '8M');
    $uploadMax = iniBytes(ini_get('upload_max_filesize') ?: '2M');

    if ($contentLength > 0 && $contentLength > $postMax) {
        http_response_code(413);
        $response['message'] = 'Requête trop volumineuse (post_max_size dépassé).';
        $response['debug'] = ['CONTENT_LENGTH' => $contentLength, 'post_max_size' => ini_get('post_max_size')];
        echo json_encode($response);
        exit;
    }

    if (!isset($_FILES['document-file'])) {
        http_response_code(400);
        $response['message'] = 'Aucun fichier envoyé (clef POST manquante).';
        $response['debug'] = ['expected_field' => 'document-file'];
        echo json_encode($response);
        exit;
    }

    if ($_FILES['document-file']['error'] !== UPLOAD_ERR_OK) {
        $errcode = (int)$_FILES['document-file']['error'];
        http_response_code(400);
        $response['message'] = 'Échec de l\'upload du fichier: ' . uploadErrMsg($errcode);
        $response['debug'] = [
            'php_error_code' => $errcode,
            'php_error_message' => uploadErrMsg($errcode),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_tmp_dir' => ini_get('upload_tmp_dir')
        ];
        echo json_encode($response);
        exit;
    }

    // At this point file exists and uploaded OK
    $file = $_FILES['document-file'];
    $file = $_FILES['document-file'];
    
    // Vérification de la taille
    if ($file['size'] > $maxFileSize) { 
        http_response_code(400);
        $response['message'] = 'Fichier trop volumineux (Max 10MB).';
        echo json_encode($response);
        exit;
    }
    
    // Créer le répertoire de stockage s'il n'existe pas
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            http_response_code(500);
            $response['message'] = "Erreur: Impossible de créer le répertoire de stockage.";
            echo json_encode($response);
            exit;
        }
    }
    
    // Vérifier l'extension du fichier
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf','jpg','jpeg','png','zip','doc','docx'];
    if (!in_array($extension, $allowed)) {
        http_response_code(400);
        $response['message'] = "Type de fichier non autorisé.";
        echo json_encode($response);
        exit;
    }

    // Créer un nom de fichier SÉCURISÉ et unique
    $safeFileName = uniqid('doc_') . '_' . $sender_id . '.' . $extension;
    $targetPath = $uploadDir . $safeFileName;

    // Vérifier que le dossier est accessible en écriture
    if (!is_writable($uploadDir)) {
        http_response_code(500);
        $response['message'] = "Erreur: Le dossier de stockage n'est pas accessible en écriture. Vérifiez les permissions.";
        echo json_encode($response);
        exit;
    }

    // Déplacer le fichier uploadé du dossier temporaire vers le stockage sécurisé
    if (is_uploaded_file($file['tmp_name']) && move_uploaded_file($file['tmp_name'], $targetPath)) {
        // 3. Insertion des métadonnées dans la Base de Données
        $sql = "INSERT INTO documents (title, description, file_path, sender_id, sender_company_id, recipient_company_id, status) 
                VALUES (:title, :description, :file_path, :sender_id, :sender_company_id, :recipient_company_id, 'pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':file_path', $targetPath);
        $stmt->bindParam(':sender_id', $sender_id, PDO::PARAM_INT);
        $stmt->bindParam(':sender_company_id', $sender_company_id, PDO::PARAM_INT);
        $stmt->bindParam(':recipient_company_id', $recipientId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            http_response_code(201); // Created
            $response['success'] = true;
            $response['message'] = "Document '{$title}' envoyé avec succès. En attente d'approbation.";
        } else {
            // Si l'insertion échoue, nettoyer en supprimant le fichier téléchargé
            if (file_exists($targetPath)) @unlink($targetPath);
            throw new Exception("Erreur d'enregistrement dans la base de données.");
        }
    } else {
        // Provide better diagnostics when move_uploaded_file fails
        $debugInfo = [
            'tmp_name_exists' => file_exists($file['tmp_name']),
            'is_uploaded_file' => is_uploaded_file($file['tmp_name']),
            'tmp_name' => $file['tmp_name'] ?? null,
            'targetPath' => $targetPath,
            'upload_dir_writable' => is_writable($uploadDir),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size')
        ];
        http_response_code(500);
        $response['message'] = "Erreur interne : Échec du déplacement du fichier sur le serveur.";
        $response['debug'] = $debugInfo;
    }
    } else {
        // Erreur d'upload générale (fichier manquant, ou erreur PHP)
        http_response_code(400);
        $response['message'] = "Échec de l'upload du fichier. Veuillez réessayer.";
    }

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Erreur serveur: ' . $e->getMessage();
}

// Capture any buffered output (warnings/notices) and include a cleaned version in JSON
$buf = ob_get_clean();
if (!empty($buf)) {
    // Remove HTML tags to keep JSON safe
    $response['debug'] = trim(strip_tags($buf));
}

echo json_encode($response);
?>