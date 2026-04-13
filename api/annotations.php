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

$db_path = __DIR__ . '/../data/uxnote.sqlite';
try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');
} catch (Exception $e) {
    json_error('Connexion DB impossible: ' . $e->getMessage(), 500);
}

// ─── Migrations ─────────────────────────────────────────────────────────────
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

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

// ─── Auto-enregistrement des projets ────────────────────────────────────────
function ensureProject($db, $project_id) {
    $stmt = $db->prepare('INSERT OR IGNORE INTO projects (project_id, status, created_at) VALUES (?, \'active\', ?)');
    $stmt->execute([$project_id, time()]);
}

// ─── Routing ────────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && isset($_GET['download']))        { serveFile($_GET['download']); }
if ($method === 'GET' && isset($_GET['logs']))            { getLogs(); }
if ($method === 'GET' && isset($_GET['replies']))         { getReplies(intval($_GET['replies'])); }
if ($method === 'GET' && isset($_GET['projects']))        { getProjects(); }
if ($method === 'GET' && isset($_GET['check_project']))   { checkProject($_GET['check_project']); }
if ($method === 'POST' && isset($_GET['archive']))        { archiveProject(); }
if ($method === 'POST' && isset($_GET['unarchive']))      { unarchiveProject(); }

switch ($method) {
    case 'GET':
        if (isset($_GET['all'])) {
            $archived = isset($_GET['archived']) ? 1 : 0;
            if ($archived) {
                // Projets archivés
                $stmt = $db->query("SELECT project_id FROM projects WHERE status = 'archived'");
                $archived_ids = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'project_id');
                if (empty($archived_ids)) { json_ok(['annotations' => [], 'count' => 0]); }
                $placeholders = implode(',', array_fill(0, count($archived_ids), '?'));
                $stmt = $db->prepare("SELECT * FROM annotations WHERE project_id IN ($placeholders) ORDER BY created_at DESC");
                $stmt->execute($archived_ids);
            } else {
                // Projets actifs uniquement
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

        if (!$project_id || !$page_url || !$author_name || !$comment) json_error('Champs requis manquants');

        // Vérifier que le projet n'est pas archivé
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
        $stmt = $db->prepare("INSERT INTO annotations (project_id, page_url, author_name, author_email, author_token, comment, pos_x, pos_y, xpath, rel_x, rel_y, status, file_name, file_path, file_size, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,'open',?,?,?,?,?)");
        $stmt->execute([$project_id, $page_url, $author_name, $author_email, $author_token, $comment, $pos_x, $pos_y, $xpath, $rel_x, $rel_y, $file_name, $file_path, $file_size, $now, $now]);
        $id = $db->lastInsertId();
        addLog('create', $project_id, $author_name, "Annotation #$id sur $page_url");
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

// ─── Fonctions ───────────────────────────────────────────────────────────────
function getProjects() {
    global $db;
    $stmt = $db->query("
        SELECT p.*, COUNT(a.id) as annotation_count
        FROM projects p
        LEFT JOIN annotations a ON a.project_id = p.project_id
        GROUP BY p.project_id
        ORDER BY p.status ASC, p.created_at DESC
    ");
    json_ok(['projects' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function checkProject($project_id) {
    global $db;
    $stmt = $db->prepare("SELECT status FROM projects WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $status = $row ? $row['status'] : 'active';
    json_ok(['status' => $status, 'archived' => ($status === 'archived')]);
}

function archiveProject() {
    global $db;
    $body = json_decode(file_get_contents('php://input'), true);
    $project_id = sanitize($body['project_id'] ?? '');
    if (!$project_id) json_error('project_id requis');
    $db->prepare("INSERT OR IGNORE INTO projects (project_id, status, created_at) VALUES (?, 'active', ?)")->execute([$project_id, time()]);
    $db->prepare("UPDATE projects SET status = 'archived', archived_at = ? WHERE project_id = ?")->execute([time(), $project_id]);
    addLog('archive', $project_id, 'Dashboard', "Projet '$project_id' archivé");
    json_ok(['message' => "Projet '$project_id' archivé"]);
}

function unarchiveProject() {
    global $db;
    $body = json_decode(file_get_contents('php://input'), true);
    $project_id = sanitize($body['project_id'] ?? '');
    if (!$project_id) json_error('project_id requis');
    $db->prepare("UPDATE projects SET status = 'active', archived_at = 0 WHERE project_id = ?")->execute([$project_id]);
    addLog('unarchive', $project_id, 'Dashboard', "Projet '$project_id' réouvert");
    json_ok(['message' => "Projet '$project_id' réouvert"]);
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
    $archived  = isset($_GET['archived'])  ? 1 : 0;

    $sql    = 'SELECT * FROM logs WHERE 1=1';
    $params = [];

    if ($date_from) { $sql .= ' AND created_at >= ?'; $params[] = $date_from; }
    if ($date_to)   { $sql .= ' AND created_at <= ?'; $params[] = $date_to + 86400; }

    if ($archived) {
        // Logs des projets archivés
        $stmt2 = $db->query("SELECT project_id FROM projects WHERE status = 'archived'");
        $ids   = array_column($stmt2->fetchAll(PDO::FETCH_ASSOC), 'project_id');
        if (!empty($ids)) {
            $pl   = implode(',', array_fill(0, count($ids), '?'));
            $sql .= " AND project_id IN ($pl)";
            $params = array_merge($params, $ids);
        } else {
            json_ok(['logs' => []]);
        }
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
    readfile($path);
    exit;
}

function addLog($action, $project_id, $author_name, $detail) {
    global $db;
    $db->prepare('INSERT INTO logs (action, project_id, author_name, detail, created_at) VALUES (?,?,?,?,?)')
       ->execute([$action, $project_id, $author_name, $detail, time()]);
}

function sanitize($val) {
    // strip_tags uniquement — le JS gère l'échappement à l'affichage via escHtml()
    return strip_tags(trim((string)$val));
}
function json_ok($data, $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}
function json_error($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}
