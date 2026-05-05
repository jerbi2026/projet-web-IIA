<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// ── Lecture JSON ou form-data ────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

function clean(array $data, string $key, string $default = ''): string {
    return trim(htmlspecialchars($data[$key] ?? $default, ENT_QUOTES, 'UTF-8'));
}

// ── Validation ────────────────────────────────────────────────
//  Champs correspondant aux IDs de contact.html :
//  c-prenom, c-nom, c-email, c-sujet, c-message, c-rgpd
$prenom  = clean($data, 'prenom');
$nom     = clean($data, 'nom');
$message = clean($data, 'message');
$sujet   = clean($data, 'sujet');

if (!$prenom || !$nom || !$message || !$sujet) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Champs obligatoires manquants.']);
    exit;
}

$email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Adresse email invalide.']);
    exit;
}

// Vérification consentement RGPD
if (empty($data['rgpd']) || $data['rgpd'] === 'false' || $data['rgpd'] === '0') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Consentement RGPD requis.']);
    exit;
}

// ── Extraction ────────────────────────────────────────────────
$telephone          = clean($data, 'telephone');
$preference_contact = clean($data, 'preference_contact', 'email');
$newsletter         = (!empty($data['newsletter']) && $data['newsletter'] !== 'false') ? 1 : 0;

// ── Insertion ─────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare(
        'INSERT INTO messages_contact
            (prenom, nom, email, telephone, sujet, preference_contact, message, newsletter)
         VALUES
            (:prenom, :nom, :email, :telephone, :sujet, :preference_contact, :message, :newsletter)'
    );

    $stmt->execute([
        ':prenom'             => $prenom,
        ':nom'                => $nom,
        ':email'              => $email,
        ':telephone'          => $telephone,
        ':sujet'              => $sujet,
        ':preference_contact' => $preference_contact,
        ':message'            => $message,
        ':newsletter'         => $newsletter,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Votre message a bien été envoyé ! Nous vous répondrons dans les plus brefs délais.',
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erreur lors de l\'enregistrement : ' . $e->getMessage(),
    ]);
}