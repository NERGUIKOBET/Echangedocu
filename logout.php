<?php
function logout() {
    // Démarre la session si elle n'est pas déjà démarrée
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Vide toutes les variables de session
    $_SESSION = array();

    // Détruit la session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
    
    // Redirige l'utilisateur vers la page d'accueil
    header("Location: index.html");
    exit;
}