<?php
require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';

// ── Stats générales ───────────────────────────────────────────
$stats = [];

// Réservations
$stats['total_resa']    = $pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn();
$stats['resa_attente']  = $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='en_attente'")->fetchColumn();
$stats['resa_confirmee']= $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='confirmee'")->fetchColumn();

// Messages
$stats['total_msg']     = $pdo->query('SELECT COUNT(*) FROM messages_contact')->fetchColumn();
$stats['msg_non_lus']   = $pdo->query('SELECT COUNT(*) FROM messages_contact WHERE lu=0')->fetchColumn();

// Témoignages
$stats['total_temo']    = $pdo->query('SELECT COUNT(*) FROM temoignages')->fetchColumn();
$stats['temo_attente']  = $pdo->query('SELECT COUNT(*) FROM temoignages WHERE approuve=0')->fetchColumn();

// Chiffre d'affaires confirmé
$stats['ca'] = $pdo->query("SELECT COALESCE(SUM(prix_total),0) FROM reservations WHERE statut='confirmee'")->fetchColumn();

// ── Dernières réservations (5) ────────────────────────────────
$recent_resa = $pdo->query(
    "SELECT prenom, nom, circuit_nom, statut, created_at
     FROM reservations ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

// ── Derniers messages (5) ─────────────────────────────────────
$recent_msgs = $pdo->query(
    "SELECT prenom, nom, sujet, lu, created_at
     FROM messages_contact ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

// ── Témoignages en attente (3) ────────────────────────────────
$pending_temo = $pdo->query(
    "SELECT prenom, ville, circuit, note, message, created_at
     FROM temoignages WHERE approuve=0 ORDER BY created_at DESC LIMIT 3"
)->fetchAll();

// ── Helpers ───────────────────────────────────────────────────
function badge_statut(string $s): string {
    return match($s) {
        'confirmee'  => '<span class="badge badge-success">✓ Confirmée</span>',
        'annulee'    => '<span class="badge badge-danger">✗ Annulée</span>',
        default      => '<span class="badge badge-warning">⏳ En attente</span>',
    };
}

function stars(int $n): string {
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}

function initiales(string $p, string $n): string {
    return strtoupper(mb_substr($p,0,1) . mb_substr($n,0,1));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard — Escales Tunisiennes Admin</title>
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
    <a href="index.php"        class="nav-link active"><span class="nav-icon">📊</span> Dashboard</a>
    <a href="reservations.php" class="nav-link">
      <span class="nav-icon">📋</span> Réservations
      <?php if($stats['resa_attente'] > 0): ?>
        <span class="nav-badge"><?= $stats['resa_attente'] ?></span>
      <?php endif; ?>
    </a>
    <a href="messages.php" class="nav-link">
      <span class="nav-icon">✉️</span> Messages
      <?php if($stats['msg_non_lus'] > 0): ?>
        <span class="nav-badge"><?= $stats['msg_non_lus'] ?></span>
      <?php endif; ?>
    </a>
    <a href="testimonials.php" class="nav-link">
      <span class="nav-icon">⭐</span> Témoignages
      <?php if($stats['temo_attente'] > 0): ?>
        <span class="nav-badge"><?= $stats['temo_attente'] ?></span>
      <?php endif; ?>
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
    <div class="topbar-title">Dashboard <span>Admin</span></div>
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
      <h1>Tableau de bord</h1>
      <p>Bienvenue ! Voici un aperçu de l'activité d'Escales Tunisiennes.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon ocre">📋</div>
        <div class="stat-info">
          <div class="stat-value"><?= $stats['total_resa'] ?></div>
          <div class="stat-label">Réservations totales</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon yellow">⏳</div>
        <div class="stat-info">
          <div class="stat-value"><?= $stats['resa_attente'] ?></div>
          <div class="stat-label">En attente</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div class="stat-info">
          <div class="stat-value"><?= $stats['resa_confirmee'] ?></div>
          <div class="stat-label">Confirmées</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon navy">💰</div>
        <div class="stat-info">
          <div class="stat-value"><?= number_format($stats['ca'], 0, ',', ' ') ?> TND</div>
          <div class="stat-label">Chiffre d'affaires</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red">✉️</div>
        <div class="stat-info">
          <div class="stat-value"><?= $stats['msg_non_lus'] ?></div>
          <div class="stat-label">Messages non lus</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon yellow">⭐</div>
        <div class="stat-info">
          <div class="stat-value"><?= $stats['temo_attente'] ?></div>
          <div class="stat-label">Avis à modérer</div>
        </div>
      </div>
    </div>

    <!-- 2 colonnes -->
    <div class="dashboard-grid">

      <!-- Dernières réservations -->
      <div class="card">
        <div class="card-header">
          <h2>📋 Dernières réservations</h2>
          <a href="reservations.php" class="btn btn-sm btn-secondary">Voir tout →</a>
        </div>
        <?php if(empty($recent_resa)): ?>
          <div class="empty-state"><div class="empty-icon">📭</div><p>Aucune réservation.</p></div>
        <?php else: ?>
        <ul class="recent-list" style="padding:0 1.5rem">
          <?php foreach($recent_resa as $r): ?>
          <li>
            <div class="recent-avatar"><?= initiales($r['prenom'],$r['nom']) ?></div>
            <div class="recent-info">
              <div class="recent-name"><?= htmlspecialchars($r['prenom'].' '.$r['nom']) ?></div>
              <div class="recent-sub"><?= htmlspecialchars($r['circuit_nom'] ?? '—') ?></div>
            </div>
            <div class="recent-meta">
              <?= badge_statut($r['statut']) ?>
              <div class="recent-date"><?= date('d/m/Y', strtotime($r['created_at'])) ?></div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>

      <!-- Derniers messages -->
      <div class="card">
        <div class="card-header">
          <h2>✉️ Derniers messages</h2>
          <a href="messages.php" class="btn btn-sm btn-secondary">Voir tout →</a>
        </div>
        <?php if(empty($recent_msgs)): ?>
          <div class="empty-state"><div class="empty-icon">📭</div><p>Aucun message.</p></div>
        <?php else: ?>
        <ul class="recent-list" style="padding:0 1.5rem">
          <?php foreach($recent_msgs as $m): ?>
          <li>
            <div class="recent-avatar"><?= initiales($m['prenom'],$m['nom']) ?></div>
            <div class="recent-info">
              <div class="recent-name">
                <?= htmlspecialchars($m['prenom'].' '.$m['nom']) ?>
                <?php if(!$m['lu']): ?><span class="badge badge-info" style="margin-left:.4rem">Nouveau</span><?php endif; ?>
              </div>
              <div class="recent-sub"><?= htmlspecialchars($m['sujet'] ?? '—') ?></div>
            </div>
            <div class="recent-meta">
              <div class="recent-date"><?= date('d/m/Y', strtotime($m['created_at'])) ?></div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>

    </div><!-- /dashboard-grid -->

    <!-- Témoignages en attente -->
    <?php if(!empty($pending_temo)): ?>
    <div class="card">
      <div class="card-header">
        <h2>⭐ Avis en attente de modération</h2>
        <a href="testimonials.php" class="btn btn-sm btn-warning">Modérer →</a>
      </div>
      <div class="card-body">
        <?php foreach($pending_temo as $t): ?>
        <div class="testimonial-card">
          <div class="tc-header">
            <div>
              <span class="tc-name"><?= htmlspecialchars($t['prenom']) ?></span>
              <?php if($t['ville']): ?><span class="tc-meta"> — <?= htmlspecialchars($t['ville']) ?></span><?php endif; ?>
              <?php if($t['circuit']): ?><span class="tc-meta"> · <?= htmlspecialchars($t['circuit']) ?></span><?php endif; ?>
            </div>
            <div>
              <span class="stars"><?= stars((int)$t['note']) ?></span>
              <span class="tc-meta" style="margin-left:.5rem"><?= date('d/m/Y', strtotime($t['created_at'])) ?></span>
            </div>
          </div>
          <div class="tc-text"><?= htmlspecialchars($t['message']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

</body>
</html>