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
$prenom  = clean($data, 'prenom');
$message = clean($data, 'message');
$note    = (int)($data['note'] ?? 0);

if (!$prenom || !$message) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Le prénom et le message sont obligatoires.']);
    exit;
}

if ($note < 1 || $note > 5) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'La note doit être comprise entre 1 et 5.']);
    exit;
}

// ── Extraction ────────────────────────────────────────────────
$ville   = clean($data, 'ville');
$circuit = clean($data, 'circuit');

// ── Insertion (approuve = 0 par défaut → modération admin) ───
try {
    $stmt = $pdo->prepare(
        'INSERT INTO temoignages (prenom, ville, circuit, note, message, approuve)
         VALUES (:prenom, :ville, :circuit, :note, :message, 0)'
    );

    $stmt->execute([
        ':prenom'  => $prenom,
        ':ville'   => $ville,
        ':circuit' => $circuit,
        ':note'    => $note,
        ':message' => $message,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Merci pour votre avis ! Il sera publié après validation par notre équipe.',
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erreur lors de l\'enregistrement : ' . $e->getMessage(),
    ]);
}