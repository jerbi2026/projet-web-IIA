<?php
require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/database.php';

// ── Actions POST ──────────────────────────────────────────────
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        try {
            if ($action === 'confirmer') {
                $pdo->prepare("UPDATE reservations SET statut='confirmee' WHERE id=?")->execute([$id]);
                $flash = ['type' => 'success', 'msg' => 'Réservation confirmée avec succès.'];
            } elseif ($action === 'annuler') {
                $pdo->prepare("UPDATE reservations SET statut='annulee' WHERE id=?")->execute([$id]);
                $flash = ['type' => 'warning', 'msg' => 'Réservation annulée.'];
            } elseif ($action === 'supprimer') {
                $pdo->prepare("DELETE FROM reservations WHERE id=?")->execute([$id]);
                $flash = ['type' => 'danger', 'msg' => 'Réservation supprimée définitivement.'];
            }
        } catch (PDOException $e) {
            $flash = ['type' => 'danger', 'msg' => 'Erreur : ' . $e->getMessage()];
        }
    }

    // Redirect PRG
    header('Location: reservations.php' . ($flash ? '?flash='.urlencode($flash['type'].':'.$flash['msg']) : ''));
    exit;
}

// Flash depuis redirect
if (!$flash && isset($_GET['flash'])) {
    [$type, $msg] = explode(':', $_GET['flash'], 2);
    $flash = ['type' => $type, 'msg' => $msg];
}

// ── Filtres GET ───────────────────────────────────────────────
$search  = trim($_GET['q'] ?? '');
$statut  = $_GET['statut'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

// ── Requête principale ────────────────────────────────────────
$where  = [];
$params = [];

if ($search) {
    $where[]  = '(prenom LIKE ? OR nom LIKE ? OR email LIKE ? OR ref LIKE ? OR circuit_nom LIKE ?)';
    $like     = "%$search%";
    $params   = array_merge($params, [$like,$like,$like,$like,$like]);
}
if ($statut) {
    $where[]  = 'statut = ?';
    $params[] = $statut;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = $pdo->prepare("SELECT COUNT(*) FROM reservations $whereSQL");
$total->execute($params);
$total = (int)$total->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT * FROM reservations $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

$pages = (int)ceil($total / $perPage);

// ── Helpers ───────────────────────────────────────────────────
function badge_statut(string $s): string {
    return match($s) {
        'confirmee' => '<span class="badge badge-success">✓ Confirmée</span>',
        'annulee'   => '<span class="badge badge-danger">✗ Annulée</span>',
        default     => '<span class="badge badge-warning">⏳ En attente</span>',
    };
}

function stat_count(PDO $pdo, string $s): int {
    return (int)$pdo->prepare("SELECT COUNT(*) FROM reservations WHERE statut=?")->execute([$s])
        ?: (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='$s'")->fetchColumn();
}
$cnt_attente   = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='en_attente'")->fetchColumn();
$cnt_confirmee = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='confirmee'")->fetchColumn();
$cnt_annulee   = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='annulee'")->fetchColumn();
$cnt_msg_nl    = (int)$pdo->query("SELECT COUNT(*) FROM messages_contact WHERE lu=0")->fetchColumn();
$cnt_temo_att  = (int)$pdo->query("SELECT COUNT(*) FROM temoignages WHERE approuve=0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Réservations — Escales Tunisiennes Admin</title>
  <link rel="stylesheet" href="admin_style.css">
</head>
<body>

<!-- ══ SIDEBAR ══════════════════════════════════════════════ -->
<aside class="admin-sidebar">
  <div class="sidebar-logo">
    <div class="logo-title">Escales Tunisiennes</div>
    <div class="logo-sub">Panneau d'administration</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Navigation</div>
    <a href="index.php"        class="nav-link"><span class="nav-icon">📊</span> Dashboard</a>
    <a href="reservations.php" class="nav-link active">
      <span class="nav-icon">📋</span> Réservations
      <?php if($cnt_attente > 0): ?><span class="nav-badge"><?= $cnt_attente ?></span><?php endif; ?>
    </a>
    <a href="messages.php" class="nav-link">
      <span class="nav-icon">✉️</span> Messages
      <?php if($cnt_msg_nl > 0): ?><span class="nav-badge"><?= $cnt_msg_nl ?></span><?php endif; ?>
    </a>
    <a href="testimonials.php" class="nav-link">
      <span class="nav-icon">⭐</span> Témoignages
      <?php if($cnt_temo_att > 0): ?><span class="nav-badge"><?= $cnt_temo_att ?></span><?php endif; ?>
    </a>
    <div class="nav-section-label" style="margin-top:.8rem">Site</div>
    <a href="../index.html" target="_blank" class="nav-link"><span class="nav-icon">🌐</span> Voir le site</a>
  </nav>
  <div class="sidebar-footer">
    Connecté : <strong><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></strong><br>
    <a href="../auth/logout.php">Se déconnecter</a>
  </div>
</aside>

<!-- ══ MAIN ══════════════════════════════════════════════════ -->
<div class="admin-main">
  <header class="admin-topbar">
    <div class="topbar-title">Réservations</div>
    <div class="topbar-right">
      <div class="topbar-admin">
        <div class="topbar-avatar">A</div>
        <span><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
      </div>
      <a href="../auth/logout.php" class="btn-logout">Déconnexion</a>
    </div>
  </header>

  <div class="admin-content">
    <div class="page-header">
      <h1>Gestion des réservations</h1>
      <p><?= $total ?> réservation<?= $total > 1 ? 's' : '' ?> au total</p>
    </div>

    <?php if($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>">
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <!-- Résumé statuts -->
    <div class="stats-grid" style="margin-bottom:1.4rem">
      <a href="reservations.php" class="stat-card" style="text-decoration:none">
        <div class="stat-icon ocre">📋</div>
        <div class="stat-info">
          <div class="stat-value"><?= $cnt_attente + $cnt_confirmee + $cnt_annulee ?></div>
          <div class="stat-label">Total</div>
        </div>
      </a>
      <a href="reservations.php?statut=en_attente" class="stat-card" style="text-decoration:none">
        <div class="stat-icon yellow">⏳</div>
        <div class="stat-info">
          <div class="stat-value"><?= $cnt_attente ?></div>
          <div class="stat-label">En attente</div>
        </div>
      </a>
      <a href="reservations.php?statut=confirmee" class="stat-card" style="text-decoration:none">
        <div class="stat-icon green">✅</div>
        <div class="stat-info">
          <div class="stat-value"><?= $cnt_confirmee ?></div>
          <div class="stat-label">Confirmées</div>
        </div>
      </a>
      <a href="reservations.php?statut=annulee" class="stat-card" style="text-decoration:none">
        <div class="stat-icon red">✗</div>
        <div class="stat-info">
          <div class="stat-value"><?= $cnt_annulee ?></div>
          <div class="stat-label">Annulées</div>
        </div>
      </a>
    </div>

    <div class="card">
      <!-- Filtres -->
      <form method="GET" class="filters-bar">
        <input type="text" name="q" class="search-input" placeholder="Rechercher (nom, email, ref…)" value="<?= htmlspecialchars($search) ?>">
        <select name="statut">
          <option value="">Tous les statuts</option>
          <option value="en_attente"  <?= $statut==='en_attente'  ? 'selected' : '' ?>>En attente</option>
          <option value="confirmee"   <?= $statut==='confirmee'   ? 'selected' : '' ?>>Confirmée</option>
          <option value="annulee"     <?= $statut==='annulee'     ? 'selected' : '' ?>>Annulée</option>
        </select>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <?php if($search || $statut): ?>
          <a href="reservations.php" class="btn btn-secondary">Réinitialiser</a>
        <?php endif; ?>
        <span class="filter-count"><?= $total ?> résultat<?= $total > 1 ? 's' : '' ?></span>
      </form>

      <!-- Table -->
      <div class="table-wrap">
        <?php if(empty($reservations)): ?>
          <div class="empty-state"><div class="empty-icon">📭</div><p>Aucune réservation trouvée.</p></div>
        <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Réf.</th>
              <th>Client</th>
              <th>Circuit</th>
              <th>Départ</th>
              <th>Participants</th>
              <th>Prix total</th>
              <th>Paiement</th>
              <th>Statut</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($reservations as $r): ?>
            <tr>
              <td><code style="font-size:.78rem;background:var(--sand);padding:.2rem .5rem;border-radius:4px"><?= htmlspecialchars($r['ref']) ?></code></td>
              <td>
                <strong><?= htmlspecialchars($r['prenom'].' '.$r['nom']) ?></strong><br>
                <span class="muted"><?= htmlspecialchars($r['email']) ?></span>
              </td>
              <td><?= htmlspecialchars($r['circuit_nom'] ?? '—') ?></td>
              <td class="muted"><?= $r['date_depart'] ? date('d/m/Y', strtotime($r['date_depart'])) : '—' ?></td>
              <td style="text-align:center"><?= $r['nb_participants'] ?></td>
              <td><?= $r['prix_total'] ? number_format($r['prix_total'],2,',',' ').' TND' : '—' ?></td>
              <td class="muted"><?= htmlspecialchars($r['mode_paiement'] ?? '—') ?></td>
              <td><?= badge_statut($r['statut']) ?></td>
              <td class="muted"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
              <td>
                <div class="btn-group">
                  <?php if($r['statut'] === 'en_attente'): ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="confirmer">
                    <input type="hidden" name="id"     value="<?= $r['id'] ?>">
                    <button class="btn btn-sm btn-success" title="Confirmer">✓</button>
                  </form>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="annuler">
                    <input type="hidden" name="id"     value="<?= $r['id'] ?>">
                    <button class="btn btn-sm btn-warning" title="Annuler">✗</button>
                  </form>
                  <?php endif; ?>
                  <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer définitivement cette réservation ?')">
                    <input type="hidden" name="action" value="supprimer">
                    <input type="hidden" name="id"     value="<?= $r['id'] ?>">
                    <button class="btn btn-sm btn-danger" title="Supprimer">🗑</button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

      <!-- Pagination -->
      <?php if($pages > 1): ?>
      <div class="pagination">
        <?php
        $base = 'reservations.php?' . http_build_query(array_filter(['q'=>$search,'statut'=>$statut]));
        for($i=1; $i<=$pages; $i++):
        ?>
          <?php if($i===$page): ?>
            <span class="current"><?= $i ?></span>
          <?php else: ?>
            <a href="<?= $base ?>&page=<?= $i ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>