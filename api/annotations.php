<?php
header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Access-Control-Allow-Credentials: true');
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('UPLOAD_DIR', __DIR__ . '/../data/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// ─── SMTP Config ─────────────────────────────────────────────────────────────
define('SMTP_HOST',     getenv('SMTP_HOST')     ?: 'nodels5-eu.n0c.com');
define('SMTP_PORT',     getenv('SMTP_PORT')     ?: 587);
define('SMTP_USER',     getenv('SMTP_USER')     ?: 'clement@qoma.fr');
define('SMTP_PASS',     getenv('SMTP_PASS')     ?: 'Jq1cE4WrwmCaa7eZA5kENh7yW@');
define('SMTP_FROM',     getenv('SMTP_FROM')     ?: 'clement@qoma.fr');
define('SMTP_FROM_NAME',getenv('SMTP_FROM_NAME')?: 'UX Note Cloud - Équinoxes');

// ─── Base de données ──────────────────────────────────────────────────────────
$db_path = __DIR__ . '/../data/uxnote.sqlite';
try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');
} catch (Exception $e) {
    json_error('Connexion DB impossible: ' . $e->getMessage(), 500);
}

// ─── Migrations ───────────────────────────────────────────────────────────────
$db->exec("
    CREATE TABLE IF NOT EXISTS annotations (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id   TEXT NOT NULL,
        page_url     TEXT NOT NULL,
        author_name  TEXT NOT NULL,
        author_email TEXT DEFAULT '',
        author_token TEXT DEFAULT '',
        comment      TEXT NOT NULL,
        pos_x        REAL DEFAULT 0,
        pos_y        REAL DEFAULT 0,
        status       TEXT DEFAULT 'open',
        file_name    TEXT DEFAULT '',
        file_path    TEXT DEFAULT '',
        file_size    INTEGER DEFAULT 0,
        xpath        TEXT DEFAULT '',
        rel_x        REAL DEFAULT 0.5,
        rel_y        REAL DEFAULT 0.5,
        assigned_to  INTEGER DEFAULT 0,
        created_at   INTEGER NOT NULL,
        updated_at   INTEGER NOT NULL
    );
    CREATE TABLE IF NOT EXISTS replies (
        id              INTEGER PRIMARY KEY AUTOINCREMENT,
        annotation_id   INTEGER NOT NULL,
        author_name     TEXT NOT NULL,
        author_email    TEXT DEFAULT '',
        author_token    TEXT DEFAULT '',
        comment         TEXT NOT NULL,
        created_at      INTEGER NOT NULL,
        FOREIGN KEY (annotation_id) REFERENCES annotations(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS logs (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        action      TEXT NOT NULL,
        project_id  TEXT DEFAULT '',
        author_name TEXT DEFAULT '',
        detail      TEXT DEFAULT '',
        created_at  INTEGER NOT NULL
    );
    CREATE TABLE IF NOT EXISTS projects (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id  TEXT NOT NULL UNIQUE,
        status      TEXT DEFAULT 'active',
        archived_at INTEGER DEFAULT 0,
        created_at  INTEGER NOT NULL
    );
    CREATE TABLE IF NOT EXISTS intervenants (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        prenom     TEXT NOT NULL,
        poste      TEXT DEFAULT '',
        email      TEXT NOT NULL,
        actif      INTEGER DEFAULT 1,
        created_at INTEGER NOT NULL
    );
    CREATE TABLE IF NOT EXISTS notifications (
        id             INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id     TEXT NOT NULL,
        intervenant_id INTEGER NOT NULL,
        last_sent_at   INTEGER DEFAULT 0,
        UNIQUE(project_id, intervenant_id)
    );
    CREATE TABLE IF NOT EXISTS notification_settings (
        id             INTEGER PRIMARY KEY AUTOINCREMENT,
        intervenant_id INTEGER NOT NULL,
        project_id     TEXT NOT NULL,
        enabled        INTEGER DEFAULT 1,
        cooldown_hours INTEGER DEFAULT 24,
        UNIQUE(intervenant_id, project_id)
    );
    CREATE INDEX IF NOT EXISTS idx_project_page ON annotations(project_id, page_url);
    CREATE INDEX IF NOT EXISTS idx_replies_annotation ON replies(annotation_id);
    CREATE INDEX IF NOT EXISTS idx_projects_status ON projects(status);
");

// Migrations colonnes manquantes
$cols = $db->query("PRAGMA table_info(annotations)")->fetchAll(PDO::FETCH_ASSOC);
$col_names = array_column($cols, 'name');
if (!in_array('author_token', $col_names)) $db->exec("ALTER TABLE annotations ADD COLUMN author_token TEXT DEFAULT ''");
if (!in_array('file_name',    $col_names)) $db->exec("ALTER TABLE annotations ADD COLUMN file_name TEXT DEFAULT ''");
if (!in_array('file_path',    $col_names)) $db->exec("ALTER TABLE annotations ADD COLUMN file_path TEXT DEFAULT ''");
if (!in_array('file_size',    $col_names)) $db->exec("ALTER TABLE annotations ADD COLUMN file_size INTEGER DEFAULT 0");
if (!in_array('xpath',        $col_names)) $db->exec("ALTER TABLE annotations ADD COLUMN xpath TEXT DEFAULT ''");
if (!in_array('rel_x',        $col_names)) $db->exec("ALTER TABLE annotations ADD COLUMN rel_x REAL DEFAULT 0.5");
if (!in_array('rel_y',        $col_names)) $db->exec("ALTER TABLE annotations ADD COLUMN rel_y REAL DEFAULT 0.5");
if (!in_array('assigned_to',  $col_names)) $db->exec("ALTER TABLE annotations ADD COLUMN assigned_to INTEGER DEFAULT 0");

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

// ─── Helpers ─────────────────────────────────────────────────────────────────
function ensureProject($db, $project_id) {
    $db->prepare("INSERT OR IGNORE INTO projects (project_id, status, created_at) VALUES (?, 'active', ?)")
       ->execute([$project_id, time()]);
}

// ─── Routing ─────────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET'  && isset($_GET['download']))       { serveFile($_GET['download']); }
if ($method === 'GET'  && isset($_GET['logs']))           { getLogs(); }
if ($method === 'GET'  && isset($_GET['replies']))        { getReplies(intval($_GET['replies'])); }
if ($method === 'GET'  && isset($_GET['projects']))       { getProjects(); }
if ($method === 'GET'  && isset($_GET['check_project']))  { checkProject($_GET['check_project']); }
if ($method === 'GET'  && isset($_GET['intervenants']))   { getIntervenants(); }
if ($method === 'POST' && isset($_GET['archive']))        { archiveProject(); }
if ($method === 'POST' && isset($_GET['unarchive']))      { unarchiveProject(); }
if ($method === 'POST' && isset($_GET['intervenant']))    { saveIntervenant(); }
if ($method === 'DELETE' && isset($_GET['intervenant']))  { deleteIntervenant(); }
if ($method === 'POST' && isset($_GET['notif_settings']))    { saveNotifSettings(); }
if ($method === 'POST' && isset($_GET['change_intervenant'])) { changeIntervenant(); }
if ($method === 'GET'  && isset($_GET['notif_settings'])) { getNotifSettings(); }

switch ($method) {
    case 'GET':
        if (isset($_GET['all'])) {
            $archived = isset($_GET['archived']) ? true : false;
            if ($archived) {
                $stmt = $db->query("SELECT project_id FROM projects WHERE status = 'archived'");
                $archived_ids = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'project_id');
                if (empty($archived_ids)) { json_ok(['annotations' => [], 'count' => 0]); }
                $placeholders = implode(',', array_fill(0, count($archived_ids), '?'));
                $stmt = $db->prepare("SELECT * FROM annotations WHERE project_id IN ($placeholders) ORDER BY created_at DESC");
                $stmt->execute($archived_ids);
            } else {
                $stmt = $db->prepare("
                    SELECT a.* FROM annotations a
                    LEFT JOIN projects p ON a.project_id = p.project_id
                    WHERE p.status = 'active' OR p.project_id IS NULL
                    ORDER BY a.created_at DESC
                ");
                $stmt->execute();
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$row) {
                $s = $db->prepare('SELECT COUNT(*) FROM replies WHERE annotation_id = ?');
                $s->execute([$row['id']]);
                $row['reply_count'] = (int)$s->fetchColumn();
                $s2 = $db->prepare('SELECT * FROM replies WHERE annotation_id = ? ORDER BY created_at ASC');
                $s2->execute([$row['id']]);
                $row['replies'] = $s2->fetchAll(PDO::FETCH_ASSOC);
                // Intervenant assigné
                if ($row['assigned_to']) {
                    $si = $db->prepare('SELECT prenom, poste FROM intervenants WHERE id = ?');
                    $si->execute([$row['assigned_to']]);
                    $row['intervenant'] = $si->fetch(PDO::FETCH_ASSOC);
                }
            }
            json_ok(['annotations' => $rows, 'count' => count($rows)]);
        }

        $project_id = $_GET['project_id'] ?? '';
        $page_url   = $_GET['page_url']   ?? '';
        if (!$project_id) json_error('project_id requis');
        $sql    = 'SELECT * FROM annotations WHERE project_id = ?';
        $params = [$project_id];
        if ($page_url) { $sql .= ' AND page_url = ?'; $params[] = $page_url; }
        $sql .= ' ORDER BY created_at ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $s = $db->prepare('SELECT * FROM replies WHERE annotation_id = ? ORDER BY created_at ASC');
            $s->execute([$row['id']]);
            $row['replies'] = $s->fetchAll(PDO::FETCH_ASSOC);
        }
        json_ok(['annotations' => $rows, 'count' => count($rows)]);
        break;

    case 'POST':
        // Réponse
        if (isset($_POST['action']) && $_POST['action'] === 'reply') {
            $annotation_id = intval($_POST['annotation_id'] ?? 0);
            $author_name   = sanitize($_POST['author_name']  ?? '');
            $author_email  = sanitize($_POST['author_email'] ?? '');
            $author_token  = sanitize($_POST['author_token'] ?? '');
            $comment       = sanitize($_POST['comment']      ?? '');
            if (!$annotation_id || !$author_name || !$comment) json_error('Champs requis manquants');
            $stmt = $db->prepare("INSERT INTO replies (annotation_id, author_name, author_email, author_token, comment, created_at) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$annotation_id, $author_name, $author_email, $author_token, $comment, time()]);
            addLog('reply', '', $author_name, "Réponse à l'annotation #$annotation_id");
            json_ok(['id' => $db->lastInsertId(), 'message' => 'Réponse ajoutée'], 201);
        }

        // Nouvelle annotation
        $project_id   = sanitize($_POST['project_id']   ?? '');
        $page_url     = sanitize($_POST['page_url']     ?? '');
        $author_name  = sanitize($_POST['author_name']  ?? '');
        $author_email = sanitize($_POST['author_email'] ?? '');
        $author_token = sanitize($_POST['author_token'] ?? '');
        $comment      = sanitize($_POST['comment']      ?? '');
        $pos_x        = floatval($_POST['pos_x']        ?? 0);
        $pos_y        = floatval($_POST['pos_y']        ?? 0);
        $xpath        = sanitize($_POST['xpath']        ?? '');
        $rel_x        = floatval($_POST['rel_x']        ?? 0.5);
        $rel_y        = floatval($_POST['rel_y']        ?? 0.5);
        $assigned_to  = intval($_POST['assigned_to']    ?? 0);

        if (!$project_id || !$page_url || !$author_name || !$comment) json_error('Champs requis manquants');

        $stmt = $db->prepare("SELECT status FROM projects WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $proj = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($proj && $proj['status'] === 'archived') json_error('Projet archivé', 403);

        ensureProject($db, $project_id);

        $file_name = ''; $file_path = ''; $file_size = 0;
        if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['file']['size'] > MAX_FILE_SIZE) json_error('Fichier trop volumineux (max 5Mo)');
            $ext       = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $safe_name = time() . '_' . uniqid() . '.' . preg_replace('/[^a-z0-9]/i', '', $ext);
            $dest      = UPLOAD_DIR . $safe_name;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                $file_name = $_FILES['file']['name'];
                $file_path = $safe_name;
                $file_size = $_FILES['file']['size'];
            }
        }

        $now  = time();
        $stmt = $db->prepare("
            INSERT INTO annotations
            (project_id, page_url, author_name, author_email, author_token, comment,
             pos_x, pos_y, xpath, rel_x, rel_y, status, file_name, file_path, file_size,
             assigned_to, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,'open',?,?,?,?,?,?)
        ");
        $stmt->execute([
            $project_id, $page_url, $author_name, $author_email, $author_token, $comment,
            $pos_x, $pos_y, $xpath, $rel_x, $rel_y,
            $file_name, $file_path, $file_size,
            $assigned_to, $now, $now
        ]);
        $id = $db->lastInsertId();
        addLog('create', $project_id, $author_name, "Annotation #$id sur $page_url");

        // Envoyer notification si intervenant assigné
        if ($assigned_to) {
            sendNotificationIfNeeded($db, $project_id, $assigned_to, $id, $author_name, $comment, $page_url);
        }

        json_ok(['id' => $id, 'message' => 'Annotation créée'], 201);
        break;

    case 'PATCH':
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body || empty($body['id'])) json_error('id requis');
        $id     = intval($body['id']);
        $status = $body['status'] ?? 'open';
        if (!in_array($status, ['open', 'resolved'])) json_error('Statut invalide');
        $stmt = $db->prepare('UPDATE annotations SET status = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$status, time(), $id]);
        if ($stmt->rowCount() === 0) json_error('Annotation non trouvée', 404);
        $actor = sanitize($body['actor'] ?? 'Dashboard');
        addLog('status', '', $actor, "Annotation #$id → $status");
        json_ok(['message' => 'Statut mis à jour']);
        break;

    case 'DELETE':
        $id    = intval($_GET['id']    ?? 0);
        $token = $_GET['token'] ?? '';
        if (!$id) json_error('id requis');
        if ($token) {
            $stmt = $db->prepare('SELECT author_token, author_name FROM annotations WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) json_error('Annotation non trouvée', 404);
            if ($row['author_token'] !== $token) json_error('Non autorisé', 403);
        }
        $stmt = $db->prepare('SELECT file_path, author_name, project_id FROM annotations WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['file_path']) {
            $f = UPLOAD_DIR . $row['file_path'];
            if (file_exists($f)) unlink($f);
        }
        $db->prepare('DELETE FROM annotations WHERE id = ?')->execute([$id]);
        addLog('delete', $row['project_id'] ?? '', $row['author_name'] ?? 'Dashboard', "Annotation #$id supprimée");
        json_ok(['message' => 'Annotation supprimée']);
        break;

    default:
        json_error('Méthode non supportée', 405);
}

// ─── Intervenants ─────────────────────────────────────────────────────────────
function getIntervenants() {
    global $db;
    $stmt = $db->query("SELECT * FROM intervenants ORDER BY prenom ASC");
    json_ok(['intervenants' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function saveIntervenant() {
    global $db;
    $body  = json_decode(file_get_contents('php://input'), true);
    $id     = intval($body['id']    ?? 0);
    $prenom = sanitize($body['prenom'] ?? '');
    $poste  = sanitize($body['poste']  ?? '');
    $email  = sanitize($body['email']  ?? '');
    $actif  = intval($body['actif']  ?? 1);
    if (!$prenom || !$email) json_error('Prénom et email requis');
    if ($id) {
        $db->prepare("UPDATE intervenants SET prenom=?, poste=?, email=?, actif=? WHERE id=?")
           ->execute([$prenom, $poste, $email, $actif, $id]);
        json_ok(['message' => 'Intervenant mis à jour']);
    } else {
        $db->prepare("INSERT INTO intervenants (prenom, poste, email, actif, created_at) VALUES (?,?,?,?,?)")
           ->execute([$prenom, $poste, $email, $actif, time()]);
        json_ok(['id' => $db->lastInsertId(), 'message' => 'Intervenant créé'], 201);
    }
}

function deleteIntervenant() {
    global $db;
    $id = intval($_GET['intervenant'] ?? 0);
    if (!$id) json_error('id requis');
    $db->prepare("DELETE FROM intervenants WHERE id = ?")->execute([$id]);
    json_ok(['message' => 'Intervenant supprimé']);
}

// ─── Paramètres notifications ─────────────────────────────────────────────────
function changeIntervenant() {
    global $db;
    $body          = json_decode(file_get_contents('php://input'), true);
    $annotation_id = intval($body['annotation_id'] ?? 0);
    $assigned_to   = intval($body['assigned_to']   ?? 0);
    if (!$annotation_id) json_error('annotation_id requis');

    // Récupérer l'annotation
    $stmt = $db->prepare('SELECT * FROM annotations WHERE id = ?');
    $stmt->execute([$annotation_id]);
    $ann = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ann) json_error('Annotation non trouvée', 404);

    // Mettre à jour
    $db->prepare('UPDATE annotations SET assigned_to = ?, updated_at = ? WHERE id = ?')
       ->execute([$assigned_to, time(), $annotation_id]);

    addLog('assign', $ann['project_id'], 'Dashboard', "Annotation #$annotation_id assignée à intervenant #$assigned_to");

    // Envoyer notification si nouvel intervenant
    if ($assigned_to) {
        sendNotificationIfNeeded($db, $ann['project_id'], $assigned_to, $annotation_id, $ann['author_name'], $ann['comment'], $ann['page_url']);
    }

    json_ok(['message' => 'Intervenant mis à jour']);
}

function getNotifSettings() {
    global $db;
    $intervenant_id = intval($_GET['notif_settings'] ?? 0);
    if (!$intervenant_id) {
        $stmt = $db->query("SELECT * FROM notification_settings");
    } else {
        $stmt = $db->prepare("SELECT * FROM notification_settings WHERE intervenant_id = ?");
        $stmt->execute([$intervenant_id]);
    }
    json_ok(['settings' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function saveNotifSettings() {
    global $db;
    $body           = json_decode(file_get_contents('php://input'), true);
    $intervenant_id = intval($body['intervenant_id'] ?? 0);
    $project_id     = sanitize($body['project_id']   ?? '');
    $enabled        = intval($body['enabled']        ?? 1);
    $cooldown_hours = intval($body['cooldown_hours'] ?? 24);
    if (!$intervenant_id) json_error('intervenant_id requis');
    $db->prepare("INSERT INTO notification_settings (intervenant_id, project_id, enabled, cooldown_hours)
                  VALUES (?,?,?,?)
                  ON CONFLICT(intervenant_id, project_id) DO UPDATE SET enabled=?, cooldown_hours=?")
       ->execute([$intervenant_id, $project_id, $enabled, $cooldown_hours, $enabled, $cooldown_hours]);
    json_ok(['message' => 'Paramètres sauvegardés']);
}

// ─── Envoi notification email ─────────────────────────────────────────────────
function sendNotificationIfNeeded($db, $project_id, $intervenant_id, $annotation_id, $author_name, $comment, $page_url) {
    // Récupérer l'intervenant
    $stmt = $db->prepare("SELECT * FROM intervenants WHERE id = ? AND actif = 1");
    $stmt->execute([$intervenant_id]);
    $interv = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$interv) return;

    // Récupérer les paramètres de notification
    $stmt = $db->prepare("SELECT * FROM notification_settings WHERE intervenant_id = ? AND project_id = ?");
    $stmt->execute([$intervenant_id, $project_id]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    $enabled        = $settings ? (bool)$settings['enabled']        : true;
    $cooldown_hours = $settings ? (int)$settings['cooldown_hours']  : 24;

    if (!$enabled) return;

    // Vérifier le cooldown
    $stmt = $db->prepare("SELECT last_sent_at FROM notifications WHERE project_id = ? AND intervenant_id = ?");
    $stmt->execute([$project_id, $intervenant_id]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);
    $cooldown_secs = $cooldown_hours * 3600;

    if ($notif && (time() - $notif['last_sent_at']) < $cooldown_secs) return; // Cooldown pas écoulé

    // Envoyer l'email
    $sent = sendEmail(
        $interv['email'],
        $interv['prenom'],
        $project_id,
        $annotation_id,
        $author_name,
        $comment,
        $page_url
    );

    if ($sent) {
        // Mettre à jour la date du dernier envoi
        $db->prepare("INSERT INTO notifications (project_id, intervenant_id, last_sent_at)
                      VALUES (?,?,?)
                      ON CONFLICT(project_id, intervenant_id) DO UPDATE SET last_sent_at=?")
           ->execute([$project_id, $intervenant_id, time(), time()]);
        addLog('notification', $project_id, $interv['prenom'], "Email envoyé à {$interv['email']} pour annotation #$annotation_id");
    }
}

function sendEmail($to_email, $to_name, $project_id, $annotation_id, $author_name, $comment, $page_url) {
    $subject = "[$project_id] Nouveau commentaire client — UX Note Cloud";
    $dashboard_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/';

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f4f5f7;font-family:\'Montserrat\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:32px 0;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(34,35,57,0.1);">
  <tr><td style="background:#222339;padding:24px 32px;border-bottom:4px solid #3ce65f;">
    <p style="margin:0;font-family:Georgia,serif;font-size:20px;font-weight:700;color:#fff;letter-spacing:0.02em;">UX Note Cloud</p>
    <p style="margin:4px 0 0;font-size:12px;color:#9b9dba;">Relecture collaborative — Équinoxes</p>
  </td></tr>
  <tr><td style="padding:28px 32px;">
    <p style="margin:0 0 8px;font-size:13px;color:#757686;">Bonjour <strong style="color:#222339;">' . htmlspecialchars($to_name) . '</strong>,</p>
    <p style="margin:0 0 20px;font-size:14px;color:#222339;">Un nouveau commentaire a été déposé sur le projet <strong>' . htmlspecialchars($project_id) . '</strong> et vous a été assigné.</p>

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;border-radius:8px;padding:16px;margin-bottom:20px;border-left:4px solid #222339;">
      <tr><td>
        <p style="margin:0 0 6px;font-size:11px;color:#757686;text-transform:uppercase;letter-spacing:0.06em;">Commentaire #' . $annotation_id . '</p>
        <p style="margin:0 0 10px;font-size:14px;color:#222339;line-height:1.6;">' . nl2br(htmlspecialchars($comment)) . '</p>
        <p style="margin:0;font-size:12px;color:#757686;">Par <strong>' . htmlspecialchars($author_name) . '</strong> sur <a href="' . htmlspecialchars($page_url) . '" style="color:#3ce65f;">' . htmlspecialchars($page_url) . '</a></p>
      </td></tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td width="48%">
          <a href="' . $dashboard_url . '" style="display:block;background:#222339;color:#fff;text-align:center;padding:12px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;border-left:3px solid #3ce65f;">
            📋 Voir le dashboard
          </a>
        </td>
        <td width="4%"></td>
        <td width="48%">
          <a href="' . htmlspecialchars($page_url) . '" style="display:block;background:#f4f5f7;color:#222339;text-align:center;padding:12px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;border:1px solid #e2e4ef;">
            🌐 Voir la page
          </a>
        </td>
      </tr>
    </table>
  </td></tr>
  <tr><td style="background:#f8f9fc;padding:16px 32px;border-top:1px solid #e2e4ef;">
    <p style="margin:0;font-size:11px;color:#9b9dba;text-align:center;">Équinoxes · UX Note Cloud · <a href="' . $dashboard_url . '" style="color:#3ce65f;text-decoration:none;">Gérer les notifications</a></p>
  </td></tr>
</table>
</td></tr>
</table>
</body></html>';

    return sendSmtp($to_email, $subject, $html);
}

function sendSmtp($to, $subject, $html) {
    $host      = SMTP_HOST;
    $username  = SMTP_USER;
    $password  = SMTP_PASS;
    $from      = SMTP_FROM;
    $from_name = SMTP_FROM_NAME;

    // Planet Hoster : SSL implicite port 465
    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ]
    ]);

    $socket = @stream_socket_client(
        'ssl://' . $host . ':465',
        $errno, $errstr, 15,
        STREAM_CLIENT_CONNECT,
        $ctx
    );

    if (!$socket) {
        // Fallback STARTTLS port 587
        $socket = @stream_socket_client(
            'tcp://' . $host . ':587',
            $errno, $errstr, 15
        );
        if (!$socket) {
            addLog('smtp_error', '', 'SMTP', "Connexion impossible : $errstr ($errno)");
            return false;
        }
        // EHLO + STARTTLS
        $resp = fgets($socket, 512);
        fwrite($socket, "EHLO " . gethostname() . "\r\n");
        // Lire toutes les lignes EHLO
        do { $line = fgets($socket, 512); } while (substr($line, 3, 1) === '-');
        fwrite($socket, "STARTTLS\r\n");
        $resp = fgets($socket, 512);
        if (strpos($resp, '220') === false) { fclose($socket); return false; }
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    } else {
        $resp = fgets($socket, 512); // Banner 220
        if (strpos($resp, '220') === false) { fclose($socket); return false; }
    }

    // EHLO
    fwrite($socket, "EHLO " . gethostname() . "\r\n");
    do { $line = fgets($socket, 512); } while ($line && substr($line, 3, 1) === '-');

    // AUTH LOGIN
    fwrite($socket, "AUTH LOGIN\r\n");
    fgets($socket, 512); // 334 Username
    fwrite($socket, base64_encode($username) . "\r\n");
    fgets($socket, 512); // 334 Password
    fwrite($socket, base64_encode($password) . "\r\n");
    $auth = fgets($socket, 512);
    if (strpos($auth, '235') === false) {
        addLog('smtp_error', '', 'SMTP', "Auth échouée : $auth");
        fclose($socket);
        return false;
    }

    // Message
    $boundary = md5(uniqid());
    $headers  = "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <$from>\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "X-Mailer: UXNote-Cloud/5.0\r\n";

    $body  = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $body .= strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)) . "\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $body .= $html . "\r\n";
    $body .= "--$boundary--\r\n";

    fwrite($socket, "MAIL FROM: <$from>\r\n"); fgets($socket, 512);
    fwrite($socket, "RCPT TO: <$to>\r\n");     $rcpt = fgets($socket, 512);
    if (strpos($rcpt, '250') === false && strpos($rcpt, '251') === false) {
        addLog('smtp_error', '', 'SMTP', "RCPT TO refusé : $rcpt");
        fclose($socket); return false;
    }
    fwrite($socket, "DATA\r\n"); fgets($socket, 512);
    fwrite($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
    $sent = fgets($socket, 512);
    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    if (strpos($sent, '250') !== false) {
        return true;
    }
    addLog('smtp_error', '', 'SMTP', "Envoi échoué : $sent");
    return false;
}

// ─── Autres fonctions ─────────────────────────────────────────────────────────
function getProjects() {
    global $db;
    $stmt = $db->query("
        SELECT p.*, COUNT(a.id) as annotation_count
        FROM projects p LEFT JOIN annotations a ON a.project_id = p.project_id
        GROUP BY p.project_id ORDER BY p.status ASC, p.created_at DESC
    ");
    json_ok(['projects' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function checkProject($project_id) {
    global $db;
    $stmt = $db->prepare("SELECT status FROM projects WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $row    = $stmt->fetch(PDO::FETCH_ASSOC);
    $status = $row ? $row['status'] : 'active';
    json_ok(['status' => $status, 'archived' => ($status === 'archived')]);
}

function archiveProject() {
    global $db;
    $body = json_decode(file_get_contents('php://input'), true);
    $pid  = sanitize($body['project_id'] ?? '');
    if (!$pid) json_error('project_id requis');
    $db->prepare("INSERT OR IGNORE INTO projects (project_id, status, created_at) VALUES (?, 'active', ?)")->execute([$pid, time()]);
    $db->prepare("UPDATE projects SET status = 'archived', archived_at = ? WHERE project_id = ?")->execute([time(), $pid]);
    addLog('archive', $pid, 'Dashboard', "Projet '$pid' archivé");
    json_ok(['message' => "Projet '$pid' archivé"]);
}

function unarchiveProject() {
    global $db;
    $body = json_decode(file_get_contents('php://input'), true);
    $pid  = sanitize($body['project_id'] ?? '');
    if (!$pid) json_error('project_id requis');
    $db->prepare("UPDATE projects SET status = 'active', archived_at = 0 WHERE project_id = ?")->execute([$pid]);
    addLog('unarchive', $pid, 'Dashboard', "Projet '$pid' réouvert");
    json_ok(['message' => "Projet '$pid' réouvert"]);
}

function getReplies($annotation_id) {
    global $db;
    $stmt = $db->prepare('SELECT * FROM replies WHERE annotation_id = ? ORDER BY created_at ASC');
    $stmt->execute([$annotation_id]);
    json_ok(['replies' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function getLogs() {
    global $db;
    $date_from = isset($_GET['date_from']) ? intval($_GET['date_from']) : 0;
    $date_to   = isset($_GET['date_to'])   ? intval($_GET['date_to'])   : 0;
    $archived  = isset($_GET['archived'])  ? true : false;
    $sql = 'SELECT * FROM logs WHERE 1=1'; $params = []; $i = 1;
    if ($date_from) { $sql .= " AND created_at >= ?"; $params[] = $date_from; }
    if ($date_to)   { $sql .= " AND created_at <= ?"; $params[] = $date_to + 86400; }
    if ($archived) {
        $stmt2 = $db->query("SELECT project_id FROM projects WHERE status = 'archived'");
        $ids   = array_column($stmt2->fetchAll(PDO::FETCH_ASSOC), 'project_id');
        if (!empty($ids)) {
            $pl = implode(',', array_fill(0, count($ids), '?'));
            $sql .= " AND project_id IN ($pl)";
            $params = array_merge($params, $ids);
        } else { json_ok(['logs' => []]); }
    }
    $sql .= ' ORDER BY created_at DESC LIMIT 500';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    json_ok(['logs' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function serveFile($safe_name) {
    $safe_name = basename($safe_name);
    $path = UPLOAD_DIR . $safe_name;
    if (!file_exists($path)) { http_response_code(404); echo 'Fichier non trouvé'; exit; }
    global $db;
    $stmt = $db->prepare('SELECT file_name FROM annotations WHERE file_path = ?');
    $stmt->execute([$safe_name]);
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    $name = $row ? $row['file_name'] : $safe_name;
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . addslashes($name) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path); exit;
}

function addLog($action, $project_id, $author_name, $detail) {
    global $db;
    $db->prepare('INSERT INTO logs (action, project_id, author_name, detail, created_at) VALUES (?,?,?,?,?)')
       ->execute([$action, $project_id, $author_name, $detail, time()]);
}

function sanitize($val) { return strip_tags(trim((string)$val)); }
function json_ok($data, $code = 200) { http_response_code($code); echo json_encode(array_merge(['success' => true], $data)); exit; }
function json_error($msg, $code = 400) { http_response_code($code); echo json_encode(['success' => false, 'error' => $msg]); exit; }
