<?php
require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';

// ── Actions POST ──────────────────────────────────────────────
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        try {
            if ($action === 'approuver') {
                $pdo->prepare("UPDATE temoignages SET approuve=1 WHERE id=?")->execute([$id]);
                $flash = ['type' => 'success', 'msg' => 'Témoignage approuvé et publié sur le site.'];
            } elseif ($action === 'refuser') {
                $pdo->prepare("UPDATE temoignages SET approuve=0 WHERE id=?")->execute([$id]);
                $flash = ['type' => 'warning', 'msg' => 'Témoignage retiré de la publication.'];
            } elseif ($action === 'supprimer') {
                $pdo->prepare("DELETE FROM temoignages WHERE id=?")->execute([$id]);
                $flash = ['type' => 'danger', 'msg' => 'Témoignage supprimé définitivement.'];
            } elseif ($action === 'tout_approuver') {
                // handled below
            }
        } catch (PDOException $e) {
            $flash = ['type' => 'danger', 'msg' => 'Erreur : ' . $e->getMessage()];
        }
    }

    if ($action === 'tout_approuver') {
        try {
            $pdo->exec("UPDATE temoignages SET approuve=1 WHERE approuve=0");
            $flash = ['type' => 'success', 'msg' => 'Tous les témoignages en attente ont été approuvés.'];
        } catch (PDOException $e) {
            $flash = ['type' => 'danger', 'msg' => 'Erreur : ' . $e->getMessage()];
        }
    }

    // PRG pattern
    $q = http_build_query(array_filter(['flash' => $flash ? $flash['type'] . ':' . $flash['msg'] : null]));
    header('Location: testimonials.php' . ($q ? "?$q" : ''));
    exit;
}

// Flash depuis redirect
if (!$flash && isset($_GET['flash'])) {
    [$type, $msg] = explode(':', $_GET['flash'], 2);
    $flash = ['type' => $type, 'msg' => $msg];
}

// ── Filtres GET ───────────────────────────────────────────────
$search      = trim($_GET['q'] ?? '');
$approuve_f  = $_GET['approuve'] ?? '';   // '' | '0' | '1'
$note_f      = (int)($_GET['note'] ?? 0); // 0 = tous, 1-5
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 12;
$offset      = ($page - 1) * $perPage;

// ── Requête principale ────────────────────────────────────────
$where  = [];
$params = [];

if ($search) {
    $where[]  = '(prenom LIKE ? OR ville LIKE ? OR circuit LIKE ? OR message LIKE ?)';
    $like     = "%$search%";
    $params   = array_merge($params, [$like,$like,$like,$like]);
}
if ($approuve_f !== '') {
    $where[]  = 'approuve = ?';
    $params[] = (int)$approuve_f;
}
if ($note_f >= 1 && $note_f <= 5) {
    $where[]  = 'note = ?';
    $params[] = $note_f;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM temoignages $whereSQL");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT * FROM temoignages $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$temoignages = $stmt->fetchAll();

$pages = (int)ceil($total / $perPage);

// ── Compteurs ─────────────────────────────────────────────────
$cnt_attente   = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='en_attente'")->fetchColumn();
$cnt_msg_nl    = (int)$pdo->query("SELECT COUNT(*) FROM messages_contact WHERE lu=0")->fetchColumn();
$cnt_temo_att  = (int)$pdo->query("SELECT COUNT(*) FROM temoignages WHERE approuve=0")->fetchColumn();
$cnt_temo_ok   = (int)$pdo->query("SELECT COUNT(*) FROM temoignages WHERE approuve=1")->fetchColumn();
$cnt_temo_tot  = $cnt_temo_att + $cnt_temo_ok;

// Note moyenne
$note_moy = (float)$pdo->query("SELECT COALESCE(AVG(note),0) FROM temoignages WHERE approuve=1")->fetchColumn();

// ── Helpers ───────────────────────────────────────────────────
function stars_full(int $n): string {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= $n
            ? '<span style="color:var(--ocre)">★</span>'
            : '<span style="color:#ddd">☆</span>';
    }
    return $html;
}

function stars_text(int $n): string {
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Témoignages — Escales Tunisiennes Admin</title>
  <link rel="stylesheet" href="admin_style.css">
  <style>
    /* ── Grille de cards témoignages ────────────────────── */
    .temo-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 1.2rem;
      padding: 1.5rem;
    }

    .temo-card {
      background: var(--cream);
      border-radius: 10px;
      border: 1px solid var(--border);
      padding: 1.2rem 1.3rem;
      display: flex;
      flex-direction: column;
      gap: .8rem;
      transition: box-shadow .2s;
    }

    .temo-card:hover { box-shadow: var(--shadow-md); }

    .temo-card.pending  { border-left: 3px solid var(--ocre); }
    .temo-card.approved { border-left: 3px solid var(--success); }
    .temo-card.refused  { border-left: 3px solid var(--danger); opacity: .75; }

    .temo-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: .6rem;
    }

    .temo-author {
      display: flex;
      align-items: center;
      gap: .7rem;
    }

    .temo-avatar {
      width: 40px; height: 40px;
      background: var(--navy);
      color: var(--ocre-light);
      border-radius: 50%;
      font-weight: 700; font-size: .88rem;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }

    .temo-name    { font-weight: 700; color: var(--navy); font-size: .9rem; }
    .temo-sub     { font-size: .78rem; color: var(--text-muted); margin-top: .1rem; }
    .temo-stars   { font-size: 1rem; letter-spacing: .05em; }
    .temo-message {
      font-size: .88rem; line-height: 1.65; color: var(--text);
      background: var(--white); border-radius: 8px;
      padding: .9rem 1rem; border: 1px solid var(--border);
      flex: 1;
    }

    .temo-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: .5rem;
    }

    .temo-date { font-size: .78rem; color: var(--text-muted); }

    /* Note filter pills */
    .note-pills { display: flex; gap: .4rem; flex-wrap: wrap; }
    .note-pill {
      padding: .28rem .7rem; border-radius: 20px; font-size: .8rem; font-weight: 600;
      border: 1px solid var(--border); background: var(--white); color: var(--text);
      text-decoration: none; transition: all .15s;
    }
    .note-pill:hover,
    .note-pill.active { background: var(--navy); color: #fff; border-color: var(--navy); }

    /* Stat note bar */
    .note-bar { display: flex; align-items: center; gap: .7rem; margin: .3rem 0; }
    .note-bar-label { width: 28px; font-size: .82rem; color: var(--ocre); font-weight: 700; text-align: right; }
    .note-bar-track { flex: 1; height: 8px; background: var(--sand); border-radius: 4px; overflow: hidden; }
    .note-bar-fill  { height: 100%; background: var(--ocre); border-radius: 4px; transition: width .4s; }
    .note-bar-count { width: 28px; font-size: .78rem; color: var(--text-muted); }
  </style>
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
    <a href="reservations.php" class="nav-link">
      <span class="nav-icon">📋</span> Réservations
      <?php if ($cnt_attente > 0): ?><span class="nav-badge"><?= $cnt_attente ?></span><?php endif; ?>
    </a>
    <a href="messages.php" class="nav-link">
      <span class="nav-icon">✉️</span> Messages
      <?php if ($cnt_msg_nl > 0): ?><span class="nav-badge"><?= $cnt_msg_nl ?></span><?php endif; ?>
    </a>
    <a href="testimonials.php" class="nav-link active">
      <span class="nav-icon">⭐</span> Témoignages
      <?php if ($cnt_temo_att > 0): ?><span class="nav-badge"><?= $cnt_temo_att ?></span><?php endif; ?>
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
    <div class="topbar-title">Témoignages <span>Modération</span></div>
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
      <h1>Modération des témoignages</h1>
      <p><?= $cnt_temo_tot ?> avis au total
        <?php if ($cnt_temo_att > 0): ?>
          — <strong style="color:var(--ocre)"><?= $cnt_temo_att ?> en attente</strong>
        <?php endif; ?>
      </p>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>">
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <!-- Compteurs + note moyenne -->
    <div class="stats-grid" style="margin-bottom:1.4rem">
      <a href="testimonials.php" class="stat-card" style="text-decoration:none">
        <div class="stat-icon navy">📝</div>
        <div class="stat-info">
          <div class="stat-value"><?= $cnt_temo_tot ?></div>
          <div class="stat-label">Total avis</div>
        </div>
      </a>
      <a href="testimonials.php?approuve=0" class="stat-card" style="text-decoration:none">
        <div class="stat-icon yellow">⏳</div>
        <div class="stat-info">
          <div class="stat-value"><?= $cnt_temo_att ?></div>
          <div class="stat-label">En attente</div>
        </div>
      </a>
      <a href="testimonials.php?approuve=1" class="stat-card" style="text-decoration:none">
        <div class="stat-icon green">✅</div>
        <div class="stat-info">
          <div class="stat-value"><?= $cnt_temo_ok ?></div>
          <div class="stat-label">Publiés</div>
        </div>
      </a>
      <div class="stat-card">
        <div class="stat-icon ocre">⭐</div>
        <div class="stat-info">
          <div class="stat-value"><?= $cnt_temo_ok > 0 ? number_format($note_moy, 1) . '/5' : '—' ?></div>
          <div class="stat-label">Note moyenne</div>
        </div>
      </div>
    </div>

    <!-- Distribution des notes (publiés) -->
    <?php if ($cnt_temo_ok > 0):
      $distrib = [];
      $rows = $pdo->query("SELECT note, COUNT(*) as nb FROM temoignages WHERE approuve=1 GROUP BY note ORDER BY note DESC")->fetchAll();
      foreach ($rows as $r) $distrib[$r['note']] = $r['nb'];
    ?>
    <div class="card" style="margin-bottom:1.4rem">
      <div class="card-header"><h2>📊 Distribution des notes (avis publiés)</h2></div>
      <div class="card-body" style="max-width:420px">
        <?php for ($n = 5; $n >= 1; $n--):
          $nb  = $distrib[$n] ?? 0;
          $pct = $cnt_temo_ok > 0 ? round($nb / $cnt_temo_ok * 100) : 0;
        ?>
        <div class="note-bar">
          <div class="note-bar-label"><?= $n ?>★</div>
          <div class="note-bar-track"><div class="note-bar-fill" style="width:<?= $pct ?>%"></div></div>
          <div class="note-bar-count"><?= $nb ?></div>
        </div>
        <?php endfor; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <!-- Filtres -->
      <form method="GET" class="filters-bar" style="align-items:center;gap:.8rem;flex-wrap:wrap">
        <input type="text" name="q" class="search-input"
               placeholder="Rechercher (nom, circuit…)"
               value="<?= htmlspecialchars($search) ?>">

        <select name="approuve">
          <option value="">Tous les statuts</option>
          <option value="0" <?= $approuve_f === '0' ? 'selected' : '' ?>>En attente</option>
          <option value="1" <?= $approuve_f === '1' ? 'selected' : '' ?>>Publiés</option>
        </select>

        <div class="note-pills">
          <a href="testimonials.php?<?= http_build_query(array_filter(['q'=>$search,'approuve'=>$approuve_f])) ?>"
             class="note-pill <?= $note_f === 0 ? 'active' : '' ?>">Toutes</a>
          <?php for ($n = 5; $n >= 1; $n--): ?>
          <a href="testimonials.php?<?= http_build_query(array_filter(['q'=>$search,'approuve'=>$approuve_f,'note'=>$n])) ?>"
             class="note-pill <?= $note_f === $n ? 'active' : '' ?>"><?= $n ?>★</a>
          <?php endfor; ?>
        </div>

        <button type="submit" class="btn btn-primary">Filtrer</button>
        <?php if ($search || $approuve_f !== '' || $note_f): ?>
          <a href="testimonials.php" class="btn btn-secondary">Réinitialiser</a>
        <?php endif; ?>
        <span class="filter-count"><?= $total ?> résultat<?= $total > 1 ? 's' : '' ?></span>

        <?php if ($cnt_temo_att > 0): ?>
        <form method="POST" style="margin-left:auto;display:inline">
          <input type="hidden" name="action" value="tout_approuver">
          <button type="submit" class="btn btn-success"
                  onclick="return confirm('Approuver tous les avis en attente ?')">
            ✔ Tout approuver (<?= $cnt_temo_att ?>)
          </button>
        </form>
        <?php endif; ?>
      </form>

      <!-- Grille de cards -->
      <?php if (empty($temoignages)): ?>
        <div class="empty-state">
          <div class="empty-icon">⭐</div>
          <p>Aucun témoignage trouvé.</p>
        </div>
      <?php else: ?>
      <div class="temo-grid">
        <?php foreach ($temoignages as $t):
          $cardClass = $t['approuve'] ? 'approved' : 'pending';
        ?>
        <div class="temo-card <?= $cardClass ?>">

          <!-- En-tête -->
          <div class="temo-header">
            <div class="temo-author">
              <div class="temo-avatar">
                <?= strtoupper(mb_substr($t['prenom'], 0, 1)) ?>
              </div>
              <div>
                <div class="temo-name"><?= htmlspecialchars($t['prenom']) ?></div>
                <div class="temo-sub">
                  <?php if ($t['ville']): ?>
                    📍 <?= htmlspecialchars($t['ville']) ?>
                  <?php endif; ?>
                  <?php if ($t['circuit']): ?>
                    <?= $t['ville'] ? ' · ' : '' ?>🗺 <?= htmlspecialchars($t['circuit']) ?>
                  <?php endif; ?>
                  <?php if (!$t['ville'] && !$t['circuit']): ?>
                    <em style="color:var(--text-muted)">Anonyme</em>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div style="text-align:right">
              <div class="temo-stars"><?= stars_full((int)$t['note']) ?></div>
              <div style="font-size:.8rem;color:var(--text-muted);margin-top:.2rem">
                <?= $t['note'] ?>/5
              </div>
            </div>
          </div>

          <!-- Message -->
          <div class="temo-message"><?= htmlspecialchars($t['message']) ?></div>

          <!-- Footer -->
          <div class="temo-footer">
            <div>
              <div class="temo-date"><?= date('d/m/Y à H:i', strtotime($t['created_at'])) ?></div>
              <?= $t['approuve']
                  ? '<span class="badge badge-success" style="margin-top:.3rem">✓ Publié</span>'
                  : '<span class="badge badge-warning" style="margin-top:.3rem">⏳ En attente</span>' ?>
            </div>
            <div class="btn-group">
              <?php if (!$t['approuve']): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="approuver">
                <input type="hidden" name="id"     value="<?= $t['id'] ?>">
                <button class="btn btn-sm btn-success" title="Approuver et publier">✔ Approuver</button>
              </form>
              <?php else: ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="refuser">
                <input type="hidden" name="id"     value="<?= $t['id'] ?>">
                <button class="btn btn-sm btn-warning" title="Dépublier">↩ Retirer</button>
              </form>
              <?php endif; ?>
              <form method="POST" style="display:inline"
                    onsubmit="return confirm('Supprimer définitivement cet avis ?')">
                <input type="hidden" name="action" value="supprimer">
                <input type="hidden" name="id"     value="<?= $t['id'] ?>">
                <button class="btn btn-sm btn-danger" title="Supprimer">🗑</button>
              </form>
            </div>
          </div>

        </div><!-- /temo-card -->
        <?php endforeach; ?>
      </div><!-- /temo-grid -->
      <?php endif; ?>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
      <div class="pagination">
        <?php
        $base = 'testimonials.php?' . http_build_query(array_filter(['q'=>$search,'approuve'=>$approuve_f,'note'=>$note_f ?: null]));
        for ($i = 1; $i <= $pages; $i++):
        ?>
          <?php if ($i === $page): ?>
            <span class="current"><?= $i ?></span>
          <?php else: ?>
            <a href="<?= $base ?>&page=<?= $i ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
      <?php endif; ?>

    </div><!-- /card -->
  </div>
</div>

</body>
</html>