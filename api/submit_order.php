<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');             

// Refuser les méthodes non-POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

// ── Lecture du corps JSON ou form-data ───────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// Fallback si le JS envoie du form-data classique
if (!$data) {
    $data = $_POST;
}

// ── Helpers ──────────────────────────────────────────────────
function clean(array $data, string $key, string $default = ''): string {
    return trim(htmlspecialchars($data[$key] ?? $default, ENT_QUOTES, 'UTF-8'));
}

// ── Validation des champs obligatoires ───────────────────────
$required = ['prenom', 'nom', 'email', 'circuit_nom'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => "Champ obligatoire manquant : $field"]);
        exit;
    }
}

$email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Adresse email invalide.']);
    exit;
}

// ── Extraction des données ────────────────────────────────────
$prenom          = clean($data, 'prenom');
$nom             = clean($data, 'nom');
$telephone       = clean($data, 'telephone');
$nationalite     = clean($data, 'nationalite');
$nb_participants = max(1, (int)($data['nb_participants'] ?? 1));
$circuit_nom     = clean($data, 'circuit_nom');         
$circuit_prix    = (float)($data['circuit_prix'] ?? 0);  
$date_depart     = !empty($data['date_depart'])
                    ? date('Y-m-d', strtotime($data['date_depart']))
                    : null;
$hebergement     = clean($data, 'hebergement');
$demandes        = clean($data, 'demandes');
$mode_paiement   = clean($data, 'mode_paiement', 'Non précisé');
$prix_total      = $circuit_prix > 0 ? $circuit_prix * $nb_participants : null;

// ── Résolution du circuit_id (optionnel mais utile) ──────────
$circuit_id = null;
if ($circuit_nom) {
    $stmtC = $pdo->prepare('SELECT id FROM circuits WHERE nom = ? AND actif = 1 LIMIT 1');
    $stmtC->execute([$circuit_nom]);
    $c = $stmtC->fetch();
    if ($c) $circuit_id = (int)$c['id'];
}

// ── Génération de la référence unique ────────────────────────
//    Format : ET-YYYYMMDD-XXXXX  (ex: ET-20250506-83741)
function generateRef(object $pdo): string {
    $prefix = 'ET-' . date('Ymd') . '-';
    do {
        $ref  = $prefix . mt_rand(10000, 99999);
        $stmt = $pdo->prepare('SELECT id FROM reservations WHERE ref = ?');
        $stmt->execute([$ref]);
    } while ($stmt->fetch());
    return $ref;
}

$ref = generateRef($pdo);

// ── Insertion en base ─────────────────────────────────────────
try {
    $stmt = $pdo->prepare(
        'INSERT INTO reservations
            (ref, circuit_id, circuit_nom, prenom, nom, email, telephone,
             nationalite, nb_participants, date_depart, hebergement,
             demandes, mode_paiement, prix_total)
         VALUES
            (:ref, :circuit_id, :circuit_nom, :prenom, :nom, :email, :telephone,
             :nationalite, :nb_participants, :date_depart, :hebergement,
             :demandes, :mode_paiement, :prix_total)'
    );

    $stmt->execute([
        ':ref'             => $ref,
        ':circuit_id'      => $circuit_id,
        ':circuit_nom'     => $circuit_nom,
        ':prenom'          => $prenom,
        ':nom'             => $nom,
        ':email'           => $email,
        ':telephone'       => $telephone,
        ':nationalite'     => $nationalite,
        ':nb_participants' => $nb_participants,
        ':date_depart'     => $date_depart,
        ':hebergement'     => $hebergement,
        ':demandes'        => $demandes,
        ':mode_paiement'   => $mode_paiement,
        ':prix_total'      => $prix_total,
    ]);

    echo json_encode([
        'success' => true,
        'ref'     => $ref,
        'message' => 'Réservation enregistrée avec succès.',
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erreur lors de l\'enregistrement : ' . $e->getMessage(),
    ]);
}