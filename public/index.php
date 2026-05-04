<?php
define('DASHBOARD_PASSWORD', 'equinoxes2024');
session_start();
$auth_error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dashboard_pwd'])) {
    if ($_POST['dashboard_pwd'] === DASHBOARD_PASSWORD) { $_SESSION['uxnote_auth'] = true; }
    else { $auth_error = true; }
}
if (isset($_POST['logout'])) { unset($_SESSION['uxnote_auth']); }
$is_auth = !empty($_SESSION['uxnote_auth']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UX Note Cloud — Dashboard Équinoxes</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    body { font-family:'Montserrat',sans-serif; background:#f4f5f7; color:#222339; min-height:100vh; }

    /* ── Login ── */
    .login-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#222339 0%,#2d2f4a 100%); }
    .login-box { background:#fff; border-radius:16px; padding:36px 32px; width:360px; box-shadow:0 20px 60px rgba(34,35,57,0.3); text-align:center; border-top:4px solid #3ce65f; }
    .login-logo { width:44px; height:44px; background:#3ce65f; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; }
    .login-box h1 { font-family:'Raleway',sans-serif; font-size:20px; color:#222339; margin-bottom:6px; }
    .login-box p { font-size:13px; color:#757686; margin-bottom:24px; }
    .login-box input[type=password] { width:100%; padding:11px 14px; border:1px solid #e2e4ef; border-radius:8px; font-size:14px; margin-bottom:12px; outline:none; text-align:center; letter-spacing:0.08em; font-family:'Montserrat',sans-serif; }
    .login-box input:focus { border-color:#3ce65f; }
    .login-box button { width:100%; padding:11px; background:#222339; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:14px; font-weight:600; font-family:'Montserrat',sans-serif; border-left:3px solid #3ce65f; }
    .login-box button:hover { background:#2d2f4a; }
    .login-error { color:#e63946; font-size:12px; margin-bottom:10px; }

    /* ── Header ── */
    .header { background:linear-gradient(135deg,#222339 0%,#2d2f4a 100%); color:#fff; padding:0 32px; height:64px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 2px 12px rgba(34,35,57,0.25); }
    .header-left { display:flex; align-items:center; gap:14px; }
    .header-dot { width:34px; height:34px; background:#3ce65f; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .header-title { font-family:'Raleway',sans-serif; font-size:17px; font-weight:700; letter-spacing:0.03em; }
    .header-sub { font-size:11px; color:#9b9dba; margin-top:1px; }
    .header-actions { display:flex; gap:10px; align-items:center; }

    /* ── Layout ── */
    .container { max-width:1280px; margin:0 auto; padding:28px 24px; }
    .tabs { display:inline-flex; gap:4px; margin-bottom:24px; background:#fff; border-radius:10px; padding:4px; box-shadow:0 1px 6px rgba(34,35,57,0.07); }
    .tab-btn { padding:8px 18px; border-radius:7px; border:none; cursor:pointer; font-size:13px; font-weight:600; background:transparent; color:#757686; font-family:'Montserrat',sans-serif; transition:all 0.15s; }
    .tab-btn.active { background:#222339; color:#fff; }
    .tab-btn:hover:not(.active) { background:#f4f5f7; color:#222339; }
    .tab-btn.archive-tab.active { background:#757686; }
    .tab-content { display:none; }
    .tab-content.active { display:block; }

    /* ── Stats ── */
    .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:24px; }
    .stat-card { background:#fff; border-radius:12px; padding:20px 22px; box-shadow:0 1px 6px rgba(34,35,57,0.07); border-top:3px solid transparent; }
    .stat-card.c1{border-top-color:#222339} .stat-card.c2{border-top-color:#f59e0b}
    .stat-card.c3{border-top-color:#3ce65f} .stat-card.c4{border-top-color:#757686}
    .stat-label { font-size:11px; color:#757686; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px; font-weight:500; }
    .stat-value { font-size:30px; font-weight:700; font-family:'Raleway',sans-serif; }
    .stat-card.c1 .stat-value{color:#222339} .stat-card.c2 .stat-value{color:#d97706}
    .stat-card.c3 .stat-value{color:#2ab54a} .stat-card.c4 .stat-value{color:#757686}

    /* ── Toolbar ── */
    .toolbar { background:#fff; border-radius:12px; padding:14px 20px; box-shadow:0 1px 6px rgba(34,35,57,0.07); margin-bottom:20px; display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
    .toolbar input, .toolbar select { padding:9px 13px; border:1px solid #e2e4ef; border-radius:8px; font-size:13px; background:#f8f9fc; outline:none; color:#222339; font-family:'Montserrat',sans-serif; transition:border-color 0.15s; }
    .toolbar input[type=date] { padding:8px 10px; }
    .toolbar input:focus, .toolbar select:focus { border-color:#3ce65f; }
    .toolbar input[type=text] { flex:1; min-width:180px; }
    .toolbar-sep { width:1px; height:28px; background:#e2e4ef; }
    .toolbar-label { font-size:12px; color:#757686; font-weight:500; white-space:nowrap; }

    /* ── Boutons ── */
    .btn { padding:8px 16px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:600; transition:all 0.15s; font-family:'Montserrat',sans-serif; }
    .btn-primary { background:#222339; color:#fff; border-left:2px solid #3ce65f; }
    .btn-accent  { background:#3ce65f; color:#222339; } .btn-accent:hover  { background:#2ab54a; color:#fff; }
    .btn-danger  { background:#fee2e2; color:#dc2626; } .btn-danger:hover  { background:#fecaca; }
    .btn-success { background:#dcfce7; color:#16a34a; } .btn-success:hover { background:#bbf7d0; }
    .btn-ghost   { background:#f1f3f9; color:#757686; } .btn-ghost:hover   { background:#e2e5f0; color:#222339; }
    .btn-outline { background:transparent; color:#fff; border:1px solid rgba(255,255,255,0.3); }
    .btn-outline:hover { background:rgba(255,255,255,0.1); }
    .btn-download { background:#f0f9f1; color:#2ab54a; border:1px solid #c7f0d2; }
    .btn-download:hover { background:#dcfce7; }
    .btn-archive  { background:#f0f1f8; color:#3d3f5a; border:1px solid #c8cadf; }
    .btn-archive:hover { background:#e2e4ef; }
    .btn-unarchive { background:#fff8e6; color:#b45309; border:1px solid #fde68a; }
    .btn-unarchive:hover { background:#fef3c7; }

    /* ── Table ── */
    .table-wrap { background:#fff; border-radius:12px; box-shadow:0 1px 6px rgba(34,35,57,0.07); overflow:hidden; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#f8f9fc; padding:13px 16px; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:0.06em; color:#757686; border-bottom:1px solid #e2e4ef; font-weight:600; }
    thead th.sortable { cursor:pointer; user-select:none; }
    thead th.sortable:hover { background:#eef0f8; color:#222339; }
    thead th.sort-asc::after  { content:' ↑'; color:#3ce65f; }
    thead th.sort-desc::after { content:' ↓'; color:#3ce65f; }
    tbody tr.main-row { border-bottom:1px solid #f4f5f7; transition:background 0.1s; cursor:pointer; }
    tbody tr.main-row:hover { background:#f8f9fc; }
    tbody tr.detail-row { display:none; background:#f8f9fc; }
    tbody tr.detail-row.open { display:table-row; }
    tbody td { padding:13px 16px; font-size:13px; vertical-align:top; }
    .badge { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; }
    .badge.open     { background:#e8eaf6; color:#222339; }
    .badge.resolved { background:#dcfce7; color:#15803d; }
    .badge.archived { background:#f0f1f8; color:#757686; }
    .project-tag { background:#f0f9f1; color:#222339; padding:3px 9px; border-radius:5px; font-size:12px; font-weight:600; border-left:3px solid #3ce65f; display:inline-block; }
    .project-tag.archived { border-left-color:#757686; background:#f4f5f7; color:#757686; }
    .comment-preview { max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .reply-count { font-size:11px; color:#757686; margin-top:3px; }
    .url-link { color:#222339; text-decoration:none; font-size:12px; font-weight:500; }
    .url-link:hover { color:#3ce65f; }
    .actions { display:flex; gap:5px; flex-wrap:wrap; }
    .expand-btn { background:none; border:none; cursor:pointer; color:#757686; font-size:16px; padding:0 4px; transition:transform 0.2s; }
    .expand-btn.open { transform:rotate(90deg); }

    /* ── Détail ── */
    .detail-cell { padding:0 !important; }
    .detail-inner { padding:16px 20px 16px 48px; }
    .detail-comment { font-size:14px; color:#222339; line-height:1.6; margin-bottom:12px; background:#fff; padding:12px 14px; border-radius:8px; border-left:3px solid #222339; }
    .detail-replies h4 { font-size:12px; text-transform:uppercase; letter-spacing:0.05em; color:#757686; margin-bottom:8px; font-weight:600; }
    .reply-item { background:#fff; border-radius:8px; padding:10px 14px; margin-bottom:6px; border-left:3px solid #3ce65f; }
    .reply-meta { font-size:11px; color:#757686; margin-bottom:4px; }
    .reply-meta strong { color:#222339; }
    .reply-text { font-size:13px; color:#222339; }

    /* ── Projets (onglet archives) ── */
    .projects-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px; }
    .project-card { background:#fff; border-radius:12px; padding:18px 20px; box-shadow:0 1px 6px rgba(34,35,57,0.07); border-left:4px solid #3ce65f; }
    .project-card.archived { border-left-color:#757686; opacity:0.8; }
    .project-card-title { font-family:'Raleway',sans-serif; font-size:15px; font-weight:700; color:#222339; margin-bottom:6px; }
    .project-card-meta { font-size:12px; color:#757686; margin-bottom:12px; }
    .project-card-actions { display:flex; gap:8px; }

    /* ── Logs ── */
    .log-filters { background:#fff; border-radius:12px; padding:14px 20px; box-shadow:0 1px 6px rgba(34,35,57,0.07); margin-bottom:20px; display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
    .log-item { display:flex; gap:12px; align-items:flex-start; padding:12px 16px; border-bottom:1px solid #f4f5f7; font-size:13px; }
    .log-item:last-child { border-bottom:none; }
    .log-icon { width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; }
    .log-icon.create   { background:#e8eaf6; } .log-icon.delete   { background:#fee2e2; }
    .log-icon.status   { background:#dcfce7; } .log-icon.reply    { background:#fef3c7; }
    .log-icon.archive  { background:#f0f1f8; } .log-icon.unarchive{ background:#fff8e6; }
    .log-detail strong { color:#222339; }
    .log-time { font-size:11px; color:#757686; margin-top:2px; }

    /* ── Empty / pagination / toast / snippet ── */
    .empty-state { text-align:center; padding:60px 20px; color:#757686; }
    .empty-state h3 { font-family:'Raleway',sans-serif; font-size:16px; margin-bottom:8px; }
    .pagination { display:flex; justify-content:center; gap:6px; margin-top:20px; }
    .pagination button { padding:7px 13px; border:1px solid #e2e4ef; border-radius:8px; background:#fff; cursor:pointer; font-size:13px; font-family:'Montserrat',sans-serif; }
    .pagination button.active { background:#222339; color:#fff; border-color:#222339; }
    .pagination button:disabled { opacity:0.4; cursor:default; }
    #toast { position:fixed; bottom:24px; left:50%; transform:translateX(-50%); background:#222339; color:#fff; padding:11px 22px; border-radius:8px; font-size:13px; opacity:0; transition:opacity 0.3s; pointer-events:none; z-index:9999; border-left:3px solid #3ce65f; }
    #toast.show { opacity:1; }
    #snippet-modal { position:fixed; inset:0; background:rgba(34,35,57,0.5); z-index:9998; display:none; align-items:center; justify-content:center; }
    #snippet-modal.open { display:flex; }
    #snippet-modal-box { background:#fff; border-radius:14px; padding:28px; width:600px; max-width:95vw; box-shadow:0 20px 60px rgba(34,35,57,0.25); border-top:4px solid #3ce65f; }
    #snippet-modal-box h3 { margin-bottom:8px; font-family:'Raleway',sans-serif; }
    #snippet-modal-box pre { background:#f4f5f7; border:1px solid #e2e4ef; border-radius:8px; padding:16px; font-size:12px; overflow-x:auto; white-space:pre-wrap; word-break:break-all; margin-top:14px; }
    .pwd-option { display:flex; gap:10px; margin:12px 0; align-items:center; font-size:13px; }
    .pwd-option input[type=checkbox] { width:16px; height:16px; accent-color:#3ce65f; }
    .pwd-option input[type=text] { flex:1; padding:8px 12px; border:1px solid #e2e4ef; border-radius:6px; font-size:13px; outline:none; }
    @media (max-width:640px) { .container{padding:16px} .header{padding:0 16px} }
  </style>
</head>
<body>

<?php if (!$is_auth): ?>
<div class="login-wrap">
  <div class="login-box">
    <div class="login-logo">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#222339" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
    </div>
    <h1>UX Note Cloud</h1>
    <p>Relecture collaborative — Équinoxes</p>
    <?php if ($auth_error): ?><div class="login-error">Mot de passe incorrect</div><?php endif; ?>
    <form method="POST">
      <input type="password" name="dashboard_pwd" placeholder="Mot de passe" autofocus />
      <button type="submit">Accéder au dashboard</button>
    </form>
  </div>
</div>

<?php else: ?>
<div class="header">
  <div class="header-left">
    <div class="header-dot">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#222339" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
    </div>
    <div>
      <div class="header-title">UX Note Cloud</div>
      <div class="header-sub">Relecture collaborative — Équinoxes</div>
    </div>
  </div>
  <div class="header-actions">
    <button class="btn btn-outline" onclick="openSnippetModal()">📋 Intégrer le script</button>
    <form method="POST" style="display:inline">
      <button type="submit" name="logout" class="btn btn-outline">Déconnexion</button>
    </form>
  </div>
</div>

<div class="container">
  <div class="tabs">
    <button class="tab-btn active"      onclick="switchTab('annotations',this)">📋 Annotations</button>
    <button class="tab-btn"             onclick="switchTab('logs',this)">📜 Journal</button>
    <button class="tab-btn archive-tab" onclick="switchTab('archives',this)">📦 Archives</button>
    <button class="tab-btn"             onclick="switchTab('intervenants',this)">👥 Intervenants</button>
  </div>

  <!-- ── TAB ANNOTATIONS ── -->
  <div id="tab-annotations" class="tab-content active">
    <div class="stats">
      <div class="stat-card c1"><div class="stat-label">Total</div><div class="stat-value" id="stat-total">—</div></div>
      <div class="stat-card c2"><div class="stat-label">En cours</div><div class="stat-value" id="stat-open">—</div></div>
      <div class="stat-card c3"><div class="stat-label">Résolues</div><div class="stat-value" id="stat-resolved">—</div></div>
      <div class="stat-card c4"><div class="stat-label">Projets actifs</div><div class="stat-value" id="stat-projects">—</div></div>
    </div>
    <div class="toolbar">
      <input type="text" id="filter-text" placeholder="🔍 Rechercher..." oninput="applyFilters()" />
      <select id="filter-project" onchange="applyFilters()"><option value="">Tous les projets</option></select>
      <select id="filter-author"  onchange="applyFilters()"><option value="">Tous les auteurs</option></select>
      <select id="filter-status"  onchange="applyFilters()">
        <option value="">Tous les statuts</option>
        <option value="open">En cours</option>
        <option value="resolved">Résolus</option>
      </select>
      <button class="btn btn-ghost" onclick="loadAll()">↻ Actualiser</button>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:32px"></th>
            <th class="sortable" onclick="sortBy('id')">#</th>
            <th class="sortable" onclick="sortBy('project_id')">Projet</th>
            <th class="sortable" onclick="sortBy('page_url')">Page</th>
            <th class="sortable" onclick="sortBy('author_name')">Auteur</th>
            <th>Commentaire</th>
            <th class="sortable" onclick="sortBy('file_name')">Fichier</th>
            <th class="sortable" onclick="sortBy('status')">Statut</th>
            <th class="sortable" onclick="sortBy('created_at')">Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="table-body">
          <tr><td colspan="10"><div class="empty-state"><p>Chargement…</p></div></td></tr>
        </tbody>
      </table>
    </div>
    <div class="pagination" id="pagination"></div>
  </div>

  <!-- ── TAB JOURNAL ── -->
  <div id="tab-logs" class="tab-content">
    <div class="log-filters toolbar">
      <span class="toolbar-label">Période :</span>
      <input type="date" id="log-date-from" onchange="loadLogs()" />
      <span class="toolbar-label">→</span>
      <input type="date" id="log-date-to" onchange="loadLogs()" />
      <button class="btn btn-ghost" onclick="clearLogDates()">✕ Effacer</button>
      <button class="btn btn-ghost" onclick="loadLogs()">↻ Actualiser</button>
    </div>
    <div class="table-wrap">
      <div id="logs-body"><div class="empty-state"><p>Chargement…</p></div></div>
    </div>
  </div>

  <!-- ── TAB ARCHIVES ── -->
  <div id="tab-archives" class="tab-content">
    <div class="toolbar" style="margin-bottom:24px">
      <input type="text" id="archive-search" placeholder="🔍 Rechercher dans les archives..." oninput="filterArchives()" />
      <select id="archive-view" onchange="renderArchiveView()">
        <option value="annotations">Annotations archivées</option>
        <option value="projects">Projets</option>
        <option value="logs">Journal archivé</option>
      </select>
      <button class="btn btn-ghost" onclick="loadArchives()">↻ Actualiser</button>
    </div>

    <!-- Vue annotations archivées -->
    <div id="archive-annotations-view">
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th style="width:32px"></th><th>#</th><th>Projet</th><th>Page</th><th>Auteur</th><th>Commentaire</th><th>Statut</th><th>Date</th></tr>
          </thead>
          <tbody id="archive-table-body">
            <tr><td colspan="8"><div class="empty-state"><p>Chargement…</p></div></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Vue projets -->
    <div id="archive-projects-view" style="display:none">
      <div id="projects-grid" class="projects-grid"></div>
    </div>

    <!-- Vue logs archivés -->
    <div id="archive-logs-view" style="display:none">
      <div class="table-wrap">
        <div id="archive-logs-body"><div class="empty-state"><p>Chargement…</p></div></div>
      </div>
    </div>
  </div>
  <!-- ── TAB INTERVENANTS ── -->
  <div id="tab-intervenants" class="tab-content">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

      <!-- Formulaire ajout/édition -->
      <div class="table-wrap" style="padding:24px">
        <h3 style="font-family:'Raleway',sans-serif;font-size:16px;margin-bottom:20px;color:#222339" id="interv-form-title">➕ Ajouter un intervenant</h3>
        <input type="hidden" id="interv-id" value="">
        <div style="margin-bottom:12px">
          <label style="font-size:12px;color:#757686;font-weight:600;display:block;margin-bottom:4px">Prénom *</label>
          <input type="text" id="interv-prenom" placeholder="Prénom" style="width:100%;padding:9px 12px;border:1px solid #e2e4ef;border-radius:8px;font-size:13px;font-family:'Montserrat',sans-serif;outline:none;box-sizing:border-box">
        </div>
        <div style="margin-bottom:12px">
          <label style="font-size:12px;color:#757686;font-weight:600;display:block;margin-bottom:4px">Poste</label>
          <input type="text" id="interv-poste" placeholder="ex: Développeur, Chef de projet..." style="width:100%;padding:9px 12px;border:1px solid #e2e4ef;border-radius:8px;font-size:13px;font-family:'Montserrat',sans-serif;outline:none;box-sizing:border-box">
        </div>
        <div style="margin-bottom:16px">
          <label style="font-size:12px;color:#757686;font-weight:600;display:block;margin-bottom:4px">Email *</label>
          <input type="email" id="interv-email" placeholder="prenom@equinoxes.fr" style="width:100%;padding:9px 12px;border:1px solid #e2e4ef;border-radius:8px;font-size:13px;font-family:'Montserrat',sans-serif;outline:none;box-sizing:border-box">
        </div>
        <div style="display:flex;gap:10px">
          <button class="btn btn-primary" onclick="saveIntervenant()" style="flex:1">💾 Sauvegarder</button>
          <button class="btn btn-ghost" onclick="resetIntervForm()" id="interv-cancel-btn" style="display:none">✕ Annuler</button>
        </div>
      </div>

      <!-- Liste intervenants -->
      <div class="table-wrap" style="padding:24px">
        <h3 style="font-family:'Raleway',sans-serif;font-size:16px;margin-bottom:20px;color:#222339">👥 Équipe Équinoxes</h3>
        <div id="interv-list"><p style="color:#757686;font-size:13px">Chargement…</p></div>
      </div>
    </div>

    <!-- Paramètres notifications par intervenant -->
    <div class="table-wrap" style="margin-top:24px;padding:24px">
      <h3 style="font-family:'Raleway',sans-serif;font-size:16px;margin-bottom:6px;color:#222339">🔔 Paramètres de notifications</h3>
      <p style="font-size:13px;color:#757686;margin-bottom:20px">Configurez le délai de cooldown entre deux emails pour chaque intervenant (par défaut : 24h par projet).</p>
      <div id="notif-settings-list"><p style="color:#757686;font-size:13px">Sélectionnez un intervenant pour configurer.</p></div>
    </div>
  </div>
</div>

<div id="toast"></div>

<div id="snippet-modal">
  <div id="snippet-modal-box">
    <h3>📋 Intégrer UX Note Cloud</h3>
    <p style="color:#757686;font-size:13px">Ajoutez ce snippet via WPCode juste avant &lt;/body&gt; :</p>
    <div class="pwd-option">
      <input type="checkbox" id="use-pwd" onchange="updateSnippet()">
      <label for="use-pwd" style="font-weight:500">Activer un mot de passe</label>
      <input type="text" id="pwd-value" placeholder="ex: moncode" oninput="updateSnippet()" style="display:none">
    </div>
    <pre id="snippet-code"></pre>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
      <button class="btn btn-ghost" onclick="closeSnippetModal()">Fermer</button>
      <button class="btn btn-accent" onclick="copySnippet()">📋 Copier</button>
    </div>
  </div>
</div>

<script>
  const API      = './api/annotations.php';
  const BASE_URL = window.location.origin + window.location.pathname.replace('index.php','').replace(/\/$/, '');
  let allAnnotations = [], filtered = [], allProjects = [], archivedAnnotations = [], allArchiveLogs = [];
  const PER_PAGE = 25;
  let currentPage = 1;
  let sortField = 'created_at', sortDir = 'desc';

  // ── Chargement actif ──
  // ── Intervenants ──
  let allIntervenants = [];

  async function loadIntervenants() {
    try {
      const res  = await fetch(`${API}?intervenants=1`);
      const data = await res.json();
      allIntervenants = data.intervenants || [];
      renderIntervList();
    } catch(e) {}
  }

  function renderIntervList() {
    const el = document.getElementById('interv-list');
    if (!el) return;
    if (!allIntervenants.length) {
      el.innerHTML = '<p style="color:#757686;font-size:13px">Aucun intervenant. Ajoutez-en un !</p>';
      return;
    }
    el.innerHTML = allIntervenants.map(function(i) {
      var inactif = i.actif ? '' : '<span style="color:#e63946;font-size:11px"> (inactif)</span>';
      var poste   = i.poste ? esc(i.poste) + ' &middot; ' : '';
      var toggle  = i.actif ? '&#9646;&#9646;' : '&#9654;';
      return '<div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f4f5f7">'
        + '<div style="flex:1">'
        + '<div style="font-weight:600;font-size:13px;color:#222339">' + esc(i.prenom) + inactif + '</div>'
        + '<div style="font-size:11px;color:#757686">' + poste + esc(i.email) + '</div>'
        + '</div>'
        + '<div style="display:flex;gap:6px">'
        + '<button class="btn btn-ghost" style="padding:4px 8px;font-size:11px" onclick="editIntervenant(' + i.id + ')">✏️</button>'
        + '<button class="btn btn-ghost" style="padding:4px 8px;font-size:11px" onclick="toggleIntervenant(' + i.id + ',' + i.actif + ')">' + toggle + '</button>'
        + '<button class="btn btn-danger" style="padding:4px 8px;font-size:11px" onclick="deleteIntervenant(' + i.id + ')">🗑</button>'
        + '</div>'
        + '<button class="btn btn-ghost" style="padding:4px 8px;font-size:11px" onclick="showNotifSettings(' + i.id + ',\'' + esc(i.prenom) + '\')">🔔</button>'
        + '</div>';
    }).join('');
  }

  function resetIntervForm() {
    document.getElementById('interv-id').value    = '';
    document.getElementById('interv-prenom').value = '';
    document.getElementById('interv-poste').value  = '';
    document.getElementById('interv-email').value  = '';
    document.getElementById('interv-form-title').textContent = '➕ Ajouter un intervenant';
    document.getElementById('interv-cancel-btn').style.display = 'none';
  }

  function editIntervenant(id) {
    const i = allIntervenants.find(x => x.id == id);
    if (!i) return;
    document.getElementById('interv-id').value    = i.id;
    document.getElementById('interv-prenom').value = i.prenom;
    document.getElementById('interv-poste').value  = i.poste;
    document.getElementById('interv-email').value  = i.email;
    document.getElementById('interv-form-title').textContent = "✏️ Modifier l'intervenant";
    document.getElementById('interv-cancel-btn').style.display = 'inline-flex';
  }

  async function saveIntervenant() {
    const id     = document.getElementById('interv-id').value;
    const prenom = document.getElementById('interv-prenom').value.trim();
    const poste  = document.getElementById('interv-poste').value.trim();
    const email  = document.getElementById('interv-email').value.trim();
    if (!prenom || !email) { alert('Prénom et email requis'); return; }
    await fetch(API + '?intervenant=1', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ id: id ? parseInt(id) : 0, prenom, poste, email, actif: 1 })
    });
    resetIntervForm();
    await loadIntervenants();
    toast('✅ Intervenant sauvegardé');
  }

  async function deleteIntervenant(id) {
    if (!confirm('Supprimer cet intervenant ?')) return;
    await fetch(`${API}?intervenant=${id}`, { method: 'DELETE' });
    await loadIntervenants();
    toast('🗑 Intervenant supprimé');
  }

  async function toggleIntervenant(id, actif) {
    const i = allIntervenants.find(x => x.id == id);
    if (!i) return;
    await fetch(API + '?intervenant=1', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ id, prenom: i.prenom, poste: i.poste, email: i.email, actif: actif ? 0 : 1 })
    });
    await loadIntervenants();
  }

  async function showNotifSettings(intervenant_id, prenom) {
    const el = document.getElementById('notif-settings-list');
    el.innerHTML = `<p style="color:#757686;font-size:13px">Chargement des paramètres de <strong>${esc(prenom)}</strong>…</p>`;
    const projects = [...new Set(allAnnotations.map(a => a.project_id))].sort();
    const res  = await fetch(`${API}?notif_settings=${intervenant_id}`);
    const data = await res.json();
    const settings = {};
    (data.settings || []).forEach(s => { settings[s.project_id] = s; });

    var rows = projects.map(function(p) {
      var s = settings[p] || { enabled: 1, cooldown_hours: 24 };
      var chk = s.enabled ? 'checked' : '';
      var opts = [1,4,12,24,48,0].map(function(h) {
        var lbl = h === 0 ? 'Toujours' : h + 'h';
        var sel = s.cooldown_hours == h ? 'selected' : '';
        return '<option value="' + h + '" ' + sel + '>' + lbl + '</option>';
      }).join('');
      return '<div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f4f5f7">'
        + '<span class="project-tag" style="min-width:120px">' + esc(p) + '</span>'
        + '<label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">'
        + '<input type="checkbox" ' + chk + ' id="notif-enabled-' + intervenant_id + '-' + p + '" style="accent-color:#3ce65f"> Actif'
        + '</label>'
        + '<label style="font-size:12px;color:#757686;display:flex;align-items:center;gap:6px">Cooldown :'
        + '<select id="notif-hours-' + intervenant_id + '-' + p + '" style="padding:4px 8px;border:1px solid #e2e4ef;border-radius:6px;font-size:12px">' + opts + '</select>'
        + '</label>'
        + '<button class="btn btn-primary" style="padding:4px 10px;font-size:11px" onclick="saveNotifSetting(' + intervenant_id + ',\'' + p + '\')">💾</button>'
        + '</div>';
    }).join('');
    el.innerHTML = '<p style="font-size:13px;font-weight:600;color:#222339;margin-bottom:16px">Notifications pour <strong>' + esc(prenom) + '</strong></p>' + rows;
  }

  async function saveNotifSetting(intervenant_id, project_id) {
    const enabled = document.getElementById(`notif-enabled-${intervenant_id}-${project_id}`)?.checked ? 1 : 0;
    const cooldown = parseInt(document.getElementById(`notif-hours-${intervenant_id}-${project_id}`)?.value || 24);
    await fetch(`${API}?notif_settings=1`, {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ intervenant_id, project_id, enabled, cooldown_hours: cooldown })
    });
    toast('✅ Paramètre sauvegardé');
  }

  function sortBy(field) {
    if (sortField === field) {
      sortDir = sortDir === 'asc' ? 'desc' : 'asc';
    } else {
      sortField = field;
      sortDir   = field === 'created_at' ? 'desc' : 'asc';
    }
    // Mettre à jour les classes sur les en-têtes
    document.querySelectorAll('thead th.sortable').forEach(th => {
      th.classList.remove('sort-asc', 'sort-desc');
    });
    event.currentTarget.classList.add(sortDir === 'asc' ? 'sort-asc' : 'sort-desc');
    applyFilters();
  }

  async function loadAll() {
    try {
      const res  = await fetch(`${API}?all=1`);
      const data = await res.json();
      allAnnotations = data.annotations || [];
    } catch(e) { allAnnotations = []; }
    updateStats(); populateFilters(); applyFilters();
  }

  // ── Chargement archives ──
  async function loadArchives() {
    try {
      const [resA, resP, resL] = await Promise.all([
        fetch(`${API}?all=1&archived=1`),
        fetch(`${API}?projects=1`),
        fetch(`${API}?logs=1&archived=1`)
      ]);
      const [dA, dP, dL] = await Promise.all([resA.json(), resP.json(), resL.json()]);
      archivedAnnotations = dA.annotations || [];
      allProjects         = dP.projects    || [];
      allArchiveLogs      = dL.logs        || [];
    } catch(e) {}
    renderArchiveView();
  }

  function updateStats() {
    const total    = allAnnotations.length;
    const open     = allAnnotations.filter(a=>a.status==='open').length;
    const resolved = allAnnotations.filter(a=>a.status==='resolved').length;
    const projects = new Set(allAnnotations.map(a=>a.project_id)).size;
    document.getElementById('stat-total').textContent    = total;
    document.getElementById('stat-open').textContent     = open;
    document.getElementById('stat-resolved').textContent = resolved;
    document.getElementById('stat-projects').textContent = projects;
  }

  function populateFilters() {
    const projects = [...new Set(allAnnotations.map(a=>a.project_id))].sort();
    const authors  = [...new Set(allAnnotations.map(a=>a.author_name))].sort();
    const selP = document.getElementById('filter-project');
    const selA = document.getElementById('filter-author');
    const curP = selP.value, curA = selA.value;
    selP.innerHTML = '<option value="">Tous les projets</option>' + projects.map(p=>`<option value="${esc(p)}" ${p===curP?'selected':''}>${esc(p)}</option>`).join('');
    selA.innerHTML = '<option value="">Tous les auteurs</option>'  + authors.map(a=>`<option value="${esc(a)}" ${a===curA?'selected':''}>${esc(a)}</option>`).join('');
  }

  function applyFilters() {
    const text    = document.getElementById('filter-text').value.toLowerCase();
    const project = document.getElementById('filter-project').value;
    const author  = document.getElementById('filter-author').value;
    const status  = document.getElementById('filter-status').value;
    filtered = allAnnotations.filter(a => {
      if (project && a.project_id  !== project) return false;
      if (author  && a.author_name !== author)  return false;
      if (status  && a.status      !== status)  return false;
      if (text) {
        const searchId = text.replace(/^#/, ''); // accepte "38" ou "#38"
        const matchId  = String(a.id) === searchId || ('#'+a.id) === text;
        if (!matchId &&
            !a.comment.toLowerCase().includes(text) &&
            !a.author_name.toLowerCase().includes(text) &&
            !a.page_url.toLowerCase().includes(text) &&
            !(a.file_name||'').toLowerCase().includes(text)) return false;
      }
      return true;
    });
    // Appliquer le tri
    filtered.sort((a, b) => {
      let va = a[sortField] || '', vb = b[sortField] || '';
      if (typeof va === 'string') va = va.toLowerCase();
      if (typeof vb === 'string') vb = vb.toLowerCase();
      if (va < vb) return sortDir === 'asc' ? -1 :  1;
      if (va > vb) return sortDir === 'asc' ?  1 : -1;
      return 0;
    });
    currentPage = 1; renderTable(); renderPagination();
  }

  function renderTable() {
    const tbody = document.getElementById('table-body');
    if (filtered.length === 0) {
      tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state"><h3>Aucune annotation</h3><p>Ajoutez le script sur vos sites pour collecter du feedback.</p></div></td></tr>`;
      return;
    }
    const start = (currentPage-1)*PER_PAGE;
    const page  = filtered.slice(start, start+PER_PAGE);
    let html = '';
    page.forEach((a,i) => {
      const replies    = a.replies || [];
      const replyCount = a.reply_count || replies.length;
      const hasDetail  = a.comment.length > 60 || replyCount > 0 || a.file_name;
      const rowId      = `row-${a.id}`;
      html += `
        <tr class="main-row" onclick="${hasDetail?`toggleDetail('${rowId}')`:''}" >
          <td>${hasDetail?`<button class="expand-btn" id="btn-${rowId}" onclick="event.stopPropagation();toggleDetail('${rowId}')">›</button>`:''}</td>
          <td style="color:#757686;font-size:12px">#${a.id}</td>
          <td><span class="project-tag">${esc(a.project_id)}</span></td>
          <td><a class="url-link" href="${esc(a.page_url)}" target="_blank" onclick="event.stopPropagation()">${shortUrl(a.page_url)}</a></td>
          <td>
            <div style="font-weight:600">${esc(a.author_name)}</div>
            ${a.author_email?`<div style="font-size:11px;color:#757686">${esc(a.author_email)}</div>`:''}
            ${a.intervenant?`<div style="font-size:11px;color:#3ce65f;margin-top:2px">👤 ${esc(a.intervenant.prenom)}</div>`:''}
          </td>
          <td>
            <div class="comment-preview" title="${esc(a.comment)}">${esc(a.comment)}</div>
            ${replyCount>0?`<div class="reply-count">↩ ${replyCount} réponse${replyCount>1?'s':''}</div>`:''}
          </td>
          <td>${a.file_name?`<button class="btn btn-download" style="padding:4px 10px;font-size:11px" onclick="event.stopPropagation();downloadFile('${esc(a.file_path)}')">⬇ ${esc(a.file_name.length>12?a.file_name.substring(0,12)+'…':a.file_name)}</button>`:'<span style="color:#e2e4ef">—</span>'}</td>
          <td><span class="badge ${a.status}">${a.status==='resolved'?'✓ Résolu':'● En cours'}</span></td>
          <td style="font-size:12px;color:#757686;white-space:nowrap">${formatDate(a.created_at)}</td>
          <td onclick="event.stopPropagation()">
            <div class="actions">
              ${a.status!=='resolved'
                ?`<button class="btn btn-success" style="padding:4px 9px;font-size:11px" onclick="resolve(${a.id})">✓</button>`
                :`<button class="btn btn-ghost"   style="padding:4px 9px;font-size:11px" onclick="unresolve(${a.id})">↩</button>`}
              <button class="btn btn-danger"  style="padding:4px 9px;font-size:11px" onclick="deleteA(${a.id})">🗑</button>
              <button class="btn btn-archive" style="padding:4px 9px;font-size:11px" onclick="archiveProject('${esc(a.project_id)}')" title="Archiver le projet ${esc(a.project_id)}">📦</button>
            </div>
          </td>
        </tr>`;
      if (hasDetail) {
        const repliesHtml = replies.map(r=>`
          <div class="reply-item">
            <div class="reply-meta"><strong>${esc(r.author_name)}</strong> · ${formatDate(r.created_at)}</div>
            <div class="reply-text">${esc(r.comment)}</div>
          </div>`).join('');
        html += `
          <tr class="detail-row" id="${rowId}">
            <td class="detail-cell" colspan="10">
              <div class="detail-inner">
                <div class="detail-comment">${esc(a.comment)}</div>
                ${a.file_name?`<div style="margin-bottom:10px"><button class="btn btn-download" style="font-size:12px" onclick="downloadFile('${esc(a.file_path)}')">⬇ ${esc(a.file_name)}</button></div>`:''}
                ${repliesHtml?`<div class="detail-replies"><h4>↩ Réponses (${replies.length})</h4>${repliesHtml}</div>`:''}
              </div>
            </td>
          </tr>`;
      }
    });
    tbody.innerHTML = html;
  }

  function toggleDetail(rowId) {
    document.getElementById(rowId)?.classList.toggle('open');
    document.getElementById('btn-'+rowId)?.classList.toggle('open');
  }

  function renderPagination() {
    const total = Math.ceil(filtered.length/PER_PAGE);
    const pag   = document.getElementById('pagination');
    if (total<=1) { pag.innerHTML=''; return; }
    let html = `<button ${currentPage===1?'disabled':''} onclick="goPage(${currentPage-1})">‹</button>`;
    for (let i=1; i<=total; i++) html += `<button class="${i===currentPage?'active':''}" onclick="goPage(${i})">${i}</button>`;
    html += `<button ${currentPage===total?'disabled':''} onclick="goPage(${currentPage+1})">›</button>`;
    pag.innerHTML = html;
  }
  function goPage(p) { currentPage=p; renderTable(); renderPagination(); window.scrollTo(0,0); }

  // ── Actions annotations ──
  async function resolve(id)   { await patch(id,'resolved'); toast('✓ Annotation résolue'); }
  async function unresolve(id) { await patch(id,'open');     toast('↩ Annotation réouverte'); }
  async function patch(id, status) {
    await fetch(API, {method:'PATCH', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id,status,actor:'Dashboard'})});
    const a = allAnnotations.find(x=>x.id==id);
    if (a) a.status = status;
    updateStats(); applyFilters();
  }
  async function deleteA(id) {
    if (!confirm('Supprimer définitivement ?')) return;
    await fetch(`${API}?id=${id}`, {method:'DELETE'});
    allAnnotations = allAnnotations.filter(a=>a.id!=id);
    updateStats(); populateFilters(); applyFilters();
    toast('🗑 Annotation supprimée');
  }
  function downloadFile(fp) { window.location.href = `${API}?download=${encodeURIComponent(fp)}`; }

  // ── Archivage ──
  async function archiveProject(projectId) {
    if (!confirm(`Archiver le projet "${projectId}" ?\nLe widget disparaîtra sur les sites concernés.`)) return;
    await fetch(`${API}?archive=1`, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({project_id:projectId})});
    toast(`📦 Projet "${projectId}" archivé`);
    await loadAll();
  }
  async function unarchiveProject(projectId) {
    if (!confirm(`Réouvrir le projet "${projectId}" ?`)) return;
    await fetch(`${API}?unarchive=1`, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({project_id:projectId})});
    toast(`✅ Projet "${projectId}" réouvert`);
    await loadArchives();
    await loadAll();
  }

  // ── Vue archives ──
  function renderArchiveView() {
    const view = document.getElementById('archive-view').value;
    document.getElementById('archive-annotations-view').style.display = view==='annotations' ? 'block' : 'none';
    document.getElementById('archive-projects-view').style.display    = view==='projects'    ? 'block' : 'none';
    document.getElementById('archive-logs-view').style.display        = view==='logs'        ? 'block' : 'none';

    if (view === 'annotations') renderArchivedAnnotations();
    if (view === 'projects')    renderProjectsGrid();
    if (view === 'logs')        renderArchivedLogs();
  }

  function filterArchives() {
    renderArchiveView();
  }

  function renderArchivedAnnotations() {
    const search = document.getElementById('archive-search').value.toLowerCase();
    const list   = archivedAnnotations.filter(a =>
      !search || a.comment.toLowerCase().includes(search) ||
      a.author_name.toLowerCase().includes(search) ||
      a.project_id.toLowerCase().includes(search)
    );
    const tbody = document.getElementById('archive-table-body');
    if (list.length === 0) {
      tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><h3>Aucune annotation archivée</h3></div></td></tr>`;
      return;
    }
    tbody.innerHTML = list.map((a,i) => {
      const replies    = a.replies || [];
      const replyCount = a.reply_count || replies.length;
      const rowId      = `arch-row-${a.id}`;
      const hasDetail  = a.comment.length > 60 || replyCount > 0;
      let html = `
        <tr class="main-row" onclick="${hasDetail?`toggleDetail('${rowId}')`:''}" style="opacity:0.85">
          <td>${hasDetail?`<button class="expand-btn" id="btn-${rowId}" onclick="event.stopPropagation();toggleDetail('${rowId}')">›</button>`:''}</td>
          <td style="color:#757686;font-size:12px">${i+1}</td>
          <td><span class="project-tag archived">${esc(a.project_id)}</span></td>
          <td><a class="url-link" href="${esc(a.page_url)}" target="_blank" onclick="event.stopPropagation()">${shortUrl(a.page_url)}</a></td>
          <td><div style="font-weight:600">${esc(a.author_name)}</div></td>
          <td>
            <div class="comment-preview">${esc(a.comment)}</div>
            ${replyCount>0?`<div class="reply-count">↩ ${replyCount} réponse${replyCount>1?'s':''}</div>`:''}
          </td>
          <td><span class="badge ${a.status}">${a.status==='resolved'?'✓ Résolu':'● En cours'}</span></td>
          <td style="font-size:12px;color:#757686;white-space:nowrap">${formatDate(a.created_at)}</td>
        </tr>`;
      if (hasDetail) {
        const repliesHtml = replies.map(r=>`
          <div class="reply-item">
            <div class="reply-meta"><strong>${esc(r.author_name)}</strong> · ${formatDate(r.created_at)}</div>
            <div class="reply-text">${esc(r.comment)}</div>
          </div>`).join('');
        html += `
          <tr class="detail-row" id="${rowId}">
            <td class="detail-cell" colspan="8">
              <div class="detail-inner">
                <div class="detail-comment">${esc(a.comment)}</div>
                ${repliesHtml?`<div class="detail-replies"><h4>↩ Réponses</h4>${repliesHtml}</div>`:''}
              </div>
            </td>
          </tr>`;
      }
      return html;
    }).join('');
  }

  function renderProjectsGrid() {
    const search = document.getElementById('archive-search').value.toLowerCase();
    const list   = allProjects.filter(p => !search || p.project_id.toLowerCase().includes(search));
    const grid   = document.getElementById('projects-grid');
    if (list.length === 0) {
      grid.innerHTML = '<div class="empty-state"><h3>Aucun projet</h3></div>'; return;
    }
    grid.innerHTML = list.map(p => `
      <div class="project-card ${p.status==='archived'?'archived':''}">
        <div class="project-card-title">${esc(p.project_id)}</div>
        <div class="project-card-meta">
          ${p.annotation_count} annotation${p.annotation_count!=1?'s':''}
          · ${p.status==='archived'?'Archivé le '+formatDate(p.archived_at):'Actif'}
        </div>
        <div class="project-card-actions">
          ${p.status==='active'
            ?`<button class="btn btn-archive" onclick="archiveProject('${esc(p.project_id)}')">📦 Archiver</button>`
            :`<button class="btn btn-unarchive" onclick="unarchiveProject('${esc(p.project_id)}')">↩ Réouvrir</button>`}
        </div>
      </div>`).join('');
  }

  function renderArchivedLogs() {
    const search = document.getElementById('archive-search').value.toLowerCase();
    const list   = allArchiveLogs.filter(l => !search || l.detail.toLowerCase().includes(search) || l.author_name.toLowerCase().includes(search));
    const icons  = {create:'➕', delete:'🗑', status:'✓', reply:'↩', archive:'📦', unarchive:'↩'};
    const labels = {create:'Création', delete:'Suppression', status:'Statut', reply:'Réponse', archive:'Archivage', unarchive:'Réouverture'};
    document.getElementById('archive-logs-body').innerHTML = list.length===0
      ? '<div class="empty-state"><h3>Aucun log archivé</h3></div>'
      : list.map(l=>`
        <div class="log-item">
          <div class="log-icon ${l.action}">${icons[l.action]||'·'}</div>
          <div class="log-detail">
            <strong>${esc(l.author_name||'Système')}</strong> — ${labels[l.action]||l.action}
            ${l.detail?`<br><span style="color:#757686;font-size:12px">${esc(l.detail)}</span>`:''}
            <div class="log-time">${formatDate(l.created_at)}</div>
          </div>
        </div>`).join('');
  }

  // ── Journal ──
  async function loadLogs() {
    const from = document.getElementById('log-date-from').value;
    const to   = document.getElementById('log-date-to').value;
    let url    = `${API}?logs=1`;
    if (from) url += `&date_from=${Math.floor(new Date(from).getTime()/1000)}`;
    if (to)   url += `&date_to=${Math.floor(new Date(to).getTime()/1000)}`;
    try {
      const res  = await fetch(url);
      const data = await res.json();
      const logs = data.logs || [];
      const icons  = {create:'➕', delete:'🗑', status:'✓', reply:'↩', archive:'📦', unarchive:'↩'};
      const labels = {create:'Création', delete:'Suppression', status:'Statut modifié', reply:'Réponse', archive:'Projet archivé', unarchive:'Projet réouvert'};
      document.getElementById('logs-body').innerHTML = logs.length===0
        ? '<div class="empty-state"><h3>Aucune activité</h3></div>'
        : logs.map(l=>`
          <div class="log-item">
            <div class="log-icon ${l.action}">${icons[l.action]||'·'}</div>
            <div class="log-detail">
              <strong>${esc(l.author_name||'Système')}</strong> — ${labels[l.action]||l.action}
              ${l.detail?`<br><span style="color:#757686;font-size:12px">${esc(l.detail)}</span>`:''}
              <div class="log-time">${formatDate(l.created_at)}</div>
            </div>
          </div>`).join('');
    } catch(e) {}
  }

  function clearLogDates() {
    document.getElementById('log-date-from').value = '';
    document.getElementById('log-date-to').value   = '';
    loadLogs();
  }

  // ── Tabs ──
  function switchTab(name, btn) {
    document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById('tab-'+name).classList.add('active');
    btn.classList.add('active');
    if (name==='logs')         loadLogs();
    if (name==='archives')     loadArchives();
    if (name==='intervenants') loadIntervenants();
  }

  // ── Snippet ──
  function openSnippetModal() {
    document.getElementById('snippet-modal').classList.add('open');
    document.getElementById('use-pwd').checked = false;
    document.getElementById('pwd-value').style.display = 'none';
    updateSnippet();
  }
  function closeSnippetModal() { document.getElementById('snippet-modal').classList.remove('open'); }
  function updateSnippet() {
    const usePwd = document.getElementById('use-pwd').checked;
    const pwdEl  = document.getElementById('pwd-value');
    pwdEl.style.display = usePwd ? 'block' : 'none';
    const pwd  = usePwd && pwdEl.value ? `\n  data-password="${pwdEl.value}"` : '';
    const proj = document.getElementById('filter-project').value || 'mon-site';
    document.getElementById('snippet-code').textContent =
      `<script src="${BASE_URL}/js/uxnote-cloud.js"\n  data-project-id="${proj}"${pwd}><\/script>`;
  }
  function copySnippet() {
    navigator.clipboard.writeText(document.getElementById('snippet-code').textContent);
    toast('📋 Snippet copié !');
  }
  document.getElementById('snippet-modal').addEventListener('click', e => {
    if (e.target===e.currentTarget) closeSnippetModal();
  });

  // ── Utils ──
  function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
  function shortUrl(url) {
    try { const u=new URL(url); return u.hostname+u.pathname.substring(0,20)+(u.pathname.length>20?'…':''); }
    catch { return url.substring(0,30); }
  }
  function formatDate(ts) {
    return new Date(ts*1000).toLocaleDateString('fr-FR',{day:'2-digit',month:'2-digit',year:'2-digit',hour:'2-digit',minute:'2-digit'});
  }
  function toast(msg) {
    const t=document.getElementById('toast');
    t.textContent=msg; t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'),2800);
  }

  loadAll();

  // Pause du refresh si l'utilisateur interagit — évite de perdre sa pagination
  let userActive = false;
  let refreshInterval;

  function resetActivity() {
    userActive = true;
    clearTimeout(window._activityTimer);
    window._activityTimer = setTimeout(() => { userActive = false; }, 30000); // 30s d'inactivité
  }

  document.addEventListener('click',    resetActivity);
  document.addEventListener('keydown',  resetActivity);
  document.addEventListener('mousemove', resetActivity);

  function smartRefresh() {
    if (!userActive) loadAll();
  }

  refreshInterval = setInterval(smartRefresh, 20000);
</script>

<?php endif; ?>
</body>
</html>
