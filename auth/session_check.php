<?php
require_once '../config/auth.php';

// Vérifier si la session est expirée
if (isSessionExpired()) {
    logout();
    header('Location: login.php?msg=expired');
    exit;
}

// Protéger la page - rediriger si non connecté
requireLogin();