<?php

session_start();

// Effacer toutes les variables de session
$_SESSION = [];

// Détruire le cookie de session
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Détruire la session serveur
session_destroy();

// Rediriger vers le login avec message
header('Location: /auth/login.php?msg=logout');
exit;