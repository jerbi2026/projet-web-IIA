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
            if ($action === 'marquer_lu') {
                $pdo->prepare("UPDATE messages_contact SET lu=1 WHERE id=?")->execute([$id]);
                $flash = ['type' => 'success', 'msg' => 'Message marqué comme lu.'];
            } elseif ($action === 'marquer_non_lu') {
                $pdo->prepare("UPDATE messages_contact SET lu=0 WHERE id=?")->execute([$id]);
                $flash = ['type' => 'warning', 'msg' => 'Message marqué comme non lu.'];
            } elseif ($action === 'supprimer') {
                $pdo->prepare("DELETE FROM messages_contact WHERE id=?")->execute([$id]);
                $flash = ['type' => 'danger', 'msg' => 'Message supprimé définitivement.'];
            } elseif ($action === 'tout_lire') {
                $pdo->exec("UPDATE messages_contact SET lu=1 WHERE lu=0");
                $flash = ['type' => 'success', 'msg' => 'Tous les messages ont été marqués comme lus.'];
            }
        } catch (PDOException $e) {
            $flash = ['type' => 'danger', 'msg' => 'Erreur : ' . $e->getMessage()];
        }
    } elseif ($action === 'tout_lire') {
        try {
            $pdo->exec("UPDATE messages_contact SET lu=1 WHERE lu=0");
            $flash = ['type' => 'success', 'msg' => 'Tous les messages ont été marqués comme lus.'];
        } catch (PDOException $e) {
            $flash = ['type' => 'danger', 'msg' => 'Erreur : ' . $e->getMessage()];
        }
    }

    // PRG pattern
    $q = http_build_query(array_filter([
        'flash' => $flash ? $flash['type'] . ':' . $flash['msg'] : null,
    ]));
    header('Location: messages.php' . ($q ? "?$q" : ''));
    exit;
}

// Flash depuis redirect
if (!$flash && isset($_GET['flash'])) {
    [$type, $msg] = explode(':', $_GET['flash'], 2);
    $flash = ['type' => $type, 'msg' => $msg];
}

// ── Filtres GET ───────────────────────────────────────────────
$search  = trim($_GET['q'] ?? '');
$lu_filtre = $_GET['lu'] ?? '';          // '' | '0' | '1'
$sujet   = trim($_GET['sujet'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

// ── Requête principale ────────────────────────────────────────
$where  = [];
$params = [];

if ($search) {
    $where[]  = '(prenom LIKE ? OR nom LIKE ? OR email LIKE ? OR sujet LIKE ? OR message LIKE ?)';
    $like     = "%$search%";
    $params   = array_merge($params, [$like,$like,$like,$like,$like]);
}
if ($lu_filtre !== '') {
    $where[]  = 'lu = ?';
    $params[] = (int)$lu_filtre;
}
if ($sujet) {
    $where[]  = 'sujet = ?';
    $params[] = $sujet;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM messages_contact $whereSQL");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT * FROM messages_contact $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$messages = $stmt->fetchAll();

$pages = (int)ceil($total / $perPage);

// ── Compteurs sidebar ─────────────────────────────────────────
$cnt_attente   = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='en_attente'")->fetchColumn();
$cnt_msg_nl    = (int)$pdo->query("SELECT COUNT(*) FROM messages_contact WHERE lu=0")->fetchColumn();
$cnt_msg_total = (int)$pdo->query("SELECT COUNT(*) FROM messages_contact")->fetchColumn();
$cnt_temo_att  = (int)$pdo->query("SELECT COUNT(*) FROM temoignages WHERE approuve=0")->fetchColumn();

// ── Sujets distincts pour le filtre ──────────────────────────
$sujets_dispo = $pdo->query("SELECT DISTINCT sujet FROM messages_contact WHERE sujet IS NOT NULL AND sujet != '' ORDER BY sujet")->fetchAll(PDO::FETCH_COLUMN);

// ── Helpers ───────────────────────────────────────────────────
function initiales(string $p, string $n): string {
    return strtoupper(mb_substr($p, 0, 1) . mb_substr($n, 0, 1));
}

function sujet_badge(string $s): string {
    $colors = [
        'reservation'   => 'badge-navy',
        'information'   => 'badge-info',
        'reclamation'   => 'badge-danger',
        'partenariat'   => 'badge-warning',
        'autre'         => 'badge-muted',
    ];
    $cls = $colors[$s] ?? 'badge-muted';
    return '<span class="badge ' . $cls . '">' . htmlspecialchars(ucfirst($s)) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Messages — Escales Tunisiennes Admin</title>
  <link rel="stylesheet" href="admin_style.css">
  <style>
    .modal-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(26,39,68,.45); z-index: 200;
      align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
      background: #fff; border-radius: 12px;
      box-shadow: 0 8px 48px rgba(26,39,68,.22);
      width: 100%; max-width: 600px;
      max-height: 90vh; overflow-y: auto;
      padding: 2rem; position: relative;
    }
    .modal-close {
      position: absolute; top: 1rem; right: 1rem;
      background: var(--sand); border: none; border-radius: 50%;
      width: 32px; height: 32px; font-size: 1.1rem;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: var(--text);
    }
    .modal-close:hover { background: var(--border); }
    .modal-title { font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; font-weight: 700; color: var(--navy); margin-bottom: 1rem; }
    .modal-meta { display: flex; flex-wrap: wrap; gap: .6rem 1.4rem; margin-bottom: 1.2rem; font-size: .85rem; color: var(--text-muted); }
    .modal-meta strong { color: var(--text); }
    .modal-message { background: var(--cream); border-radius: 8px; padding: 1.1rem 1.3rem; font-size: .92rem; line-height: 1.75; color: var(--text); border: 1px solid var(--border); white-space: pre-wrap; }
    .modal-actions { display: flex; gap: .7rem; margin-top: 1.4rem; flex-wrap: wrap; }
    .nl-dot { display: inline-block; width: 8px; height: 8px; background: var(--ocre); border-radius: 50%; margin-right: .4rem; vertical-align: middle; }
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
    <a href="messages.php" class="nav-link active">
      <span class="nav-icon">✉️</span> Messages
      <?php if ($cnt_msg_nl > 0): ?><span class="nav-badge"><?= $cnt_msg_nl ?></span><?php endif; ?>
    </a>
    <a href="testimonials.php" class="nav-link">
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
    <div class="topbar-title">Messages <span>Contact</span></div>
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
      <h1>Messages de contact</h1>
      <p><?= $cnt_msg_total ?> message<?= $cnt_msg_total > 1 ? 's' : '' ?> au total
        <?php if ($cnt_msg_nl > 0): ?>
          — <strong style="color:var(--ocre)"><?= $cnt_msg_nl ?> non lu<?= $cnt_msg_nl > 1 ? 's' : '' ?></strong>
        <?php endif; ?>
      </p>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>">
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <!-- Compteurs rapides -->
    <div class="stats-grid" style="margin-bottom:1.4rem">
      <a href="messages.php" class="stat-card" style="text-decoration:none">
        <div class="stat-icon navy">✉️</div>
        <div class="stat-info">
          <div class="stat-value"><?= $cnt_msg_total ?></div>
          <div class="stat-label">Total messages</div>
        </div>
      </a>
      <a href="messages.php?lu=0" class="stat-card" style="text-decoration:none">
        <div class="stat-icon ocre">🔔</div>
        <div class="stat-info">
          <div class="stat-value"><?= $cnt_msg_nl ?></div>
          <div class="stat-label">Non lus</div>
        </div>
      </a>
      <a href="messages.php?lu=1" class="stat-card" style="text-decoration:none">
        <div class="stat-icon green">✔️</div>
        <div class="stat-info">
          <div class="stat-value"><?= $cnt_msg_total - $cnt_msg_nl ?></div>
          <div class="stat-label">Lus</div>
        </div>
      </a>
    </div>

    <div class="card">
      <!-- Filtres -->
      <form method="GET" class="filters-bar">
        <input type="text" name="q" class="search-input"
               placeholder="Rechercher (nom, email, sujet…)"
               value="<?= htmlspecialchars($search) ?>">

        <select name="lu">
          <option value="">Tous</option>
          <option value="0" <?= $lu_filtre === '0' ? 'selected' : '' ?>>Non lus</option>
          <option value="1" <?= $lu_filtre === '1' ? 'selected' : '' ?>>Lus</option>
        </select>

        <?php if (!empty($sujets_dispo)): ?>
        <select name="sujet">
          <option value="">Tous les sujets</option>
          <?php foreach ($sujets_dispo as $sj): ?>
            <option value="<?= htmlspecialchars($sj) ?>" <?= $sujet === $sj ? 'selected' : '' ?>>
              <?= htmlspecialchars(ucfirst($sj)) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary">Filtrer</button>
        <?php if ($search || $lu_filtre !== '' || $sujet): ?>
          <a href="messages.php" class="btn btn-secondary">Réinitialiser</a>
        <?php endif; ?>
        <span class="filter-count"><?= $total ?> résultat<?= $total > 1 ? 's' : '' ?></span>

        <?php if ($cnt_msg_nl > 0): ?>
        <form method="POST" style="margin-left:auto">
          <input type="hidden" name="action" value="tout_lire">
          <button type="submit" class="btn btn-info">✔ Tout marquer comme lu</button>
        </form>
        <?php endif; ?>
      </form>

      <!-- Table -->
      <div class="table-wrap">
        <?php if (empty($messages)): ?>
          <div class="empty-state">
            <div class="empty-icon">📭</div>
            <p>Aucun message trouvé.</p>
          </div>
        <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Expéditeur</th>
              <th>Email</th>
              <th>Sujet</th>
              <th>Aperçu</th>
              <th>Newsletter</th>
              <th>Reçu le</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($messages as $m): ?>
            <tr style="<?= !$m['lu'] ? 'font-weight:600;background:#fffbf5' : '' ?>">
              <td>
                <div style="display:flex;align-items:center;gap:.6rem">
                  <div class="recent-avatar" style="width:32px;height:32px;font-size:.78rem">
                    <?= initiales($m['prenom'], $m['nom']) ?>
                  </div>
                  <div>
                    <?php if (!$m['lu']): ?><span class="nl-dot"></span><?php endif; ?>
                    <?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?>
                    <?php if ($m['telephone']): ?>
                      <br><span class="muted" style="font-size:.78rem;font-weight:400"><?= htmlspecialchars($m['telephone']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td class="muted"><?= htmlspecialchars($m['email']) ?></td>
              <td><?= $m['sujet'] ? sujet_badge($m['sujet']) : '<span class="muted">—</span>' ?></td>
              <td>
                <div class="msg-preview">
                  <?= htmlspecialchars(mb_substr($m['message'], 0, 80)) ?><?= mb_strlen($m['message']) > 80 ? '…' : '' ?>
                </div>
              </td>
              <td style="text-align:center">
                <?= $m['newsletter'] ? '<span class="badge badge-success">Oui</span>' : '<span class="badge badge-muted">Non</span>' ?>
              </td>
              <td class="muted"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
              <td>
                <?= $m['lu']
                    ? '<span class="badge badge-muted">Lu</span>'
                    : '<span class="badge badge-warning">Nouveau</span>' ?>
              </td>
              <td>
                <div class="btn-group">
                  <!-- Bouton Lire / voir -->
                  <button class="btn btn-sm btn-secondary"
                          onclick="openModal(<?= htmlspecialchars(json_encode($m)) ?>)"
                          title="Lire le message">👁</button>

                  <?php if (!$m['lu']): ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="marquer_lu">
                    <input type="hidden" name="id"     value="<?= $m['id'] ?>">
                    <button class="btn btn-sm btn-success" title="Marquer comme lu">✔</button>
                  </form>
                  <?php else: ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="marquer_non_lu">
                    <input type="hidden" name="id"     value="<?= $m['id'] ?>">
                    <button class="btn btn-sm btn-warning" title="Marquer comme non lu">↩</button>
                  </form>
                  <?php endif; ?>

                  <form method="POST" style="display:inline"
                        onsubmit="return confirm('Supprimer définitivement ce message ?')">
                    <input type="hidden" name="action" value="supprimer">
                    <input type="hidden" name="id"     value="<?= $m['id'] ?>">
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
      <?php if ($pages > 1): ?>
      <div class="pagination">
        <?php
        $base = 'messages.php?' . http_build_query(array_filter(['q'=>$search,'lu'=>$lu_filtre,'sujet'=>$sujet]));
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

<!-- ══ MODAL MESSAGE ══════════════════════════════════════════ -->
<div class="modal-overlay" id="msgModal" onclick="if(event.target===this)closeModal()">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal()">✕</button>
    <div class="modal-title" id="modal-sujet">Sujet</div>
    <div class="modal-meta" id="modal-meta"></div>
    <div class="modal-message" id="modal-message"></div>
    <div class="modal-actions" id="modal-actions"></div>
  </div>
</div>

<script>
function openModal(m) {
  document.getElementById('modal-sujet').textContent = (m.sujet ? m.sujet.charAt(0).toUpperCase() + m.sujet.slice(1) : 'Message') + ' de ' + m.prenom + ' ' + m.nom;

  const date = new Date(m.created_at).toLocaleString('fr-FR', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'});

  document.getElementById('modal-meta').innerHTML =
    '<span>✉️ <strong>' + escHtml(m.email) + '</strong></span>' +
    (m.telephone ? '<span>📞 <strong>' + escHtml(m.telephone) + '</strong></span>' : '') +
    (m.preference_contact ? '<span>Contact préféré : <strong>' + escHtml(m.preference_contact) + '</strong></span>' : '') +
    '<span>📅 ' + date + '</span>' +
    '<span>Newsletter : <strong>' + (m.newsletter == 1 ? 'Oui' : 'Non') + '</strong></span>';

  document.getElementById('modal-message').textContent = m.message;

  let actions = '';
  if (!parseInt(m.lu)) {
    actions += `<form method="POST" style="display:inline">
      <input type="hidden" name="action" value="marquer_lu">
      <input type="hidden" name="id" value="${m.id}">
      <button class="btn btn-success">✔ Marquer comme lu</button>
    </form>`;
  }
  actions += `<a href="mailto:${encodeURIComponent(m.email)}" class="btn btn-primary">✉️ Répondre par email</a>`;
  document.getElementById('modal-actions').innerHTML = actions;

  document.getElementById('msgModal').classList.add('open');
}

function closeModal() {
  document.getElementById('msgModal').classList.remove('open');
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>

</body>
</html>