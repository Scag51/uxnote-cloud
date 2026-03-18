<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ─── Base de données ────────────────────────────────────────────────────────
$db_path = __DIR__ . '/../data/uxnote.sqlite';

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA journal_mode=WAL');
} catch (Exception $e) {
    json_error('Connexion DB impossible: ' . $e->getMessage(), 500);
}

// Création tables
$db->exec("
    CREATE TABLE IF NOT EXISTS annotations (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id  TEXT NOT NULL,
        page_url    TEXT NOT NULL,
        author_name TEXT NOT NULL,
        author_email TEXT DEFAULT '',
        comment     TEXT NOT NULL,
        pos_x       REAL DEFAULT 0,
        pos_y       REAL DEFAULT 0,
        status      TEXT DEFAULT 'open',
        created_at  INTEGER NOT NULL,
        updated_at  INTEGER NOT NULL
    );
    CREATE INDEX IF NOT EXISTS idx_project_page ON annotations(project_id, page_url);
");

// ─── Routing ────────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        // Route dashboard : ?all=1 → toutes les annotations
        if (isset($_GET['all'])) {
            $stmt = $db->query('SELECT * FROM annotations ORDER BY created_at DESC');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            json_ok(['annotations' => $rows, 'count' => count($rows)]);
        }

        $project_id = $_GET['project_id'] ?? '';
        $page_url   = $_GET['page_url'] ?? '';

        if (!$project_id) json_error('project_id requis');

        $sql = 'SELECT * FROM annotations WHERE project_id = ?';
        $params = [$project_id];

        if ($page_url) {
            $sql .= ' AND page_url = ?';
            $params[] = $page_url;
        }
        $sql .= ' ORDER BY created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_ok(['annotations' => $rows, 'count' => count($rows)]);
        break;

    case 'POST':
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) json_error('Corps JSON invalide');

        $required = ['project_id', 'page_url', 'author_name', 'comment'];
        foreach ($required as $f) {
            if (empty($body[$f])) json_error("Champ requis manquant: $f");
        }

        $now = time();
        $stmt = $db->prepare("
            INSERT INTO annotations (project_id, page_url, author_name, author_email, comment, pos_x, pos_y, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'open', ?, ?)
        ");
        $stmt->execute([
            sanitize($body['project_id']),
            sanitize($body['page_url']),
            sanitize($body['author_name']),
            sanitize($body['author_email'] ?? ''),
            sanitize($body['comment']),
            floatval($body['pos_x'] ?? 0),
            floatval($body['pos_y'] ?? 0),
            $now, $now
        ]);

        $id = $db->lastInsertId();
        json_ok(['id' => $id, 'message' => 'Annotation créée'], 201);
        break;

    case 'PATCH':
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body || empty($body['id'])) json_error('id requis');

        $id = intval($body['id']);
        $allowed_status = ['open', 'resolved'];
        $status = $body['status'] ?? 'open';
        if (!in_array($status, $allowed_status)) json_error('Statut invalide');

        $stmt = $db->prepare('UPDATE annotations SET status = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$status, time(), $id]);

        if ($stmt->rowCount() === 0) json_error('Annotation non trouvée', 404);
        json_ok(['message' => 'Statut mis à jour']);
        break;

    case 'DELETE':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) json_error('id requis');

        $stmt = $db->prepare('DELETE FROM annotations WHERE id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) json_error('Annotation non trouvée', 404);
        json_ok(['message' => 'Annotation supprimée']);
        break;

    default:
        json_error('Méthode non supportée', 405);
}

// ─── Helpers ────────────────────────────────────────────────────────────────
function sanitize($val) {
    return htmlspecialchars(strip_tags(trim((string)$val)), ENT_QUOTES, 'UTF-8');
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
