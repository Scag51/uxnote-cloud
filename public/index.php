<?php
define('DASHBOARD_PASSWORD', 'equinoxes2024');
session_start();
$auth_error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dashboard_pwd'])) {
    if ($_POST['dashboard_pwd'] === DASHBOARD_PASSWORD) {
        $_SESSION['uxnote_auth'] = true;
    } else {
        $auth_error = true;
    }
}
if (isset($_POST['logout'])) {
    unset($_SESSION['uxnote_auth']);
}
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
    .login-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#222339 0%,#2d2f4a 100%); }
    .login-box { background:#fff; border-radius:16px; padding:36px 32px; width:360px; box-shadow:0 20px 60px rgba(34,35,57,0.3); text-align:center; border-top:4px solid #3ce65f; }
    .login-logo { width:44px; height:44px; background:#3ce65f; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; }
    .login-box h1 { font-family:'Raleway',sans-serif; font-size:20px; color:#222339; margin-bottom:6px; }
    .login-box p { font-size:13px; color:#757686; margin-bottom:24px; }
    .login-box input { width:100%; padding:11px 14px; border:1px solid #e2e4ef; border-radius:8px; font-size:14px; margin-bottom:12px; outline:none; text-align:center; letter-spacing:0.08em; font-family:'Montserrat',sans-serif; }
    .login-box input:focus { border-color:#3ce65f; }
    .login-box button { width:100%; padding:11px; background:#222339; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:14px; font-weight:600; font-family:'Montserrat',sans-serif; border-left:3px solid #3ce65f; }
    .login-box button:hover { background:#2d2f4a; }
    .login-error { color:#e63946; font-size:12px; margin-bottom:10px; }
    .header { background:linear-gradient(135deg,#222339 0%,#2d2f4a 100%); color:#fff; padding:0 32px; height:64px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 2px 12px rgba(34,35,57,0.25); }
    .header-left { display:flex; align-items:center; gap:14px; }
    .header-dot { width:34px; height:34px; background:#3ce65f; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .header-title { font-family:'Raleway',sans-serif; font-size:17px; font-weight:700; letter-spacing:0.03em; }
    .header-sub { font-size:11px; color:#9b9dba; margin-top:1px; }
    .header-actions { display:flex; gap:10px; align-items:center; }
    .container { max-width:1280px; margin:0 auto; padding:28px 24px; }
    .tabs { display:inline-flex; gap:4px; margin-bottom:24px; background:#fff; border-radius:10px; padding:4px; box-shadow:0 1px 6px rgba(34,35,57,0.07); }
    .tab-btn { padding:8px 18px; border-radius:7px; border:none; cursor:pointer; font-size:13px; font-weight:600; background:transparent; color:#757686; font-family:'Montserrat',sans-serif; transition:all 0.15s; }
    .tab-btn.active { background:#222339; color:#fff; }
    .tab-btn:hover:not(.active) { background:#f4f5f7; color:#222339; }
    .tab-content { display:none; }
    .tab-content.active { display:block; }
    .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:24px; }
    .stat-card { background:#fff; border-radius:12px; padding:20px 22px; box-shadow:0 1px 6px rgba(34,35,57,0.07); border-top:3px solid transparent; }
    .stat-card.c1 { border-top-color:#222339; }
    .stat-card.c2 { border-top-color:#f59e0b; }
    .stat-card.c3 { border-top-color:#3ce65f; }
    .stat-card.c4 { border-top-color:#757686; }
    .stat-label { font-size:11px; color:#757686; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px; font-weight:500; }
    .stat-value { font-size:30px; font-weight:700; font-family:'Raleway',sans-serif; }
    .stat-card.c1 .stat-value { color:#222339; }
    .stat-card.c2 .stat-value { color:#d97706; }
    .stat-card.c3 .stat-value { color:#2ab54a; }
    .stat-card.c4 .stat-value { color:#757686; }
    .toolbar { background:#fff; border-radius:12px; padding:14px 20px; box-shadow:0 1px 6px rgba(34,35,57,0.07); margin-bottom:20px; display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
    .toolbar input, .toolbar select { padding:9px 13px; border:1px solid #e2e4ef; border-radius:8px; font-size:13px; background:#f8f9fc; outline:none; color:#222339; font-family:'Montserrat',sans-serif; transition:border-color 0.15s; }
    .toolbar input:focus, .toolbar select:focus { border-color:#3ce65f; }
    .toolbar input { flex:1; min-width:220px; }
    .btn { padding:8px 16px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:600; transition:all 0.15s; font-family:'Montserrat',sans-serif; }
    .btn-primary { background:#222339; color:#fff; border-left:2px solid #3ce65f; }
    .btn-primary:hover { background:#2d2f4a; }
    .btn-accent { background:#3ce65f; color:#222339; }
    .btn-accent:hover { background:#2ab54a; color:#fff; }
    .btn-danger { background:#fee2e2; color:#dc2626; }
    .btn-danger:hover { background:#fecaca; }
    .btn-success { background:#dcfce7; color:#16a34a; }
    .btn-success:hover { background:#bbf7d0; }
    .btn-ghost { background:#f1f3f9; color:#757686; }
    .btn-ghost:hover { background:#e2e5f0; color:#222339; }
    .btn-outline { background:transparent; color:#fff; border:1px solid rgba(255,255,255,0.3); }
    .btn-outline:hover { background:rgba(255,255,255,0.1); }
    .btn-download { background:#f0f9f1; color:#2ab54a; border:1px solid #c7f0d2; }
    .btn-download:hover { background:#dcfce7; }
    .table-wrap { background:#fff; border-radius:12px; box-shadow:0 1px 6px rgba(34,35,57,0.07); overflow:hidden; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#f8f9fc; padding:13px 16px; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:0.06em; color:#757686; border-bottom:1px solid #e2e4ef; font-weight:600; }
    tbody tr { border-bottom:1px solid #f4f5f7; transition:background 0.1s; }
    tbody tr:hover { background:#f8f9fc; }
    tbody tr:last-child { border-bottom:none; }
    tbody td { padding:13px 16px; font-size:13px; vertical-align:top; }
    .badge { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; }
    .badge.open { background:#e8eaf6; color:#222339; }
    .badge.resolved { background:#dcfce7; color:#15803d; }
    .project-tag { background:#f0f9f1; color:#222339; padding:3px 9px; border-radius:5px; font-size:12px; font-weight:600; border-left:3px solid #3ce65f; display:inline-block; }
    .comment-cell { max-width:240px; }
    .comment-text { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .reply-count { font-size:11px; color:#757686; margin-top:3px; }
    .url-link { color:#222339; text-decoration:none; font-size:12px; font-weight:500; }
    .url-link:hover { color:#3ce65f; }
    .actions { display:flex; gap:5px; flex-wrap:wrap; }
    .empty-state { text-align:center; padding:60px 20px; color:#757686; }
    .empty-state h3 { font-family:'Raleway',sans-serif; font-size:16px; margin-bottom:8px; }
    .pagination { display:flex; justify-content:center; gap:6px; margin-top:20px; }
    .pagination button { padding:7px 13px; border:1px solid #e2e4ef; border-radius:8px; background:#fff; cursor:pointer; font-size:13px; font-family:'Montserrat',sans-serif; }
    .pagination button.active { background:#222339; color:#fff; border-color:#222339; }
    .pagination button:disabled { opacity:0.4; cursor:default; }
    .log-item { display:flex; gap:12px; align-items:flex-start; padding:12px 16px; border-bottom:1px solid #f4f5f7; font-size:13px; }
    .log-item:last-child { border-bottom:none; }
    .log-icon { width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; }
    .log-icon.create { background:#e8eaf6; }
    .log-icon.delete { background:#fee2e2; }
    .log-icon.status { background:#dcfce7; }
    .log-icon.reply  { background:#fef3c7; }
    .log-detail { flex:1; }
    .log-detail strong { color:#222339; }
    .log-time { font-size:11px; color:#757686; margin-top:2px; }
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
    .pwd-option input[type=text]:focus { border-color:#3ce65f; }
    @media (max-width:640px) { .container { padding:16px; } .header { padding:0 16px; } }
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
    <?php if ($auth_error): ?>
      <div class="login-error">Mot de passe incorrect</div>
    <?php endif; ?>
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
    <button class="tab-btn active" onclick="switchTab('annotations',this)">📋 Annotations</button>
    <button class="tab-btn" onclick="switchTab('logs',this)">📜 Journal</button>
  </div>

  <div id="tab-annotations" class="tab-content active">
    <div class="stats">
      <div class="stat-card c1"><div class="stat-label">Total</div><div class="stat-value" id="stat-total">—</div></div>
      <div class="stat-card c2"><div class="stat-label">En cours</div><div class="stat-value" id="stat-open">—</div></div>
      <div class="stat-card c3"><div class="stat-label">Résolues</div><div class="stat-value" id="stat-resolved">—</div></div>
      <div class="stat-card c4"><div class="stat-label">Projets</div><div class="stat-value" id="stat-projects">—</div></div>
    </div>
    <div class="toolbar">
      <input type="text" id="filter-text" placeholder="🔍 Rechercher..." oninput="applyFilters()" />
      <select id="filter-project" onchange="applyFilters()"><option value="">Tous les projets</option></select>
      <select id="filter-status" onchange="applyFilters()">
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
            <th>#</th><th>Projet</th><th>Page</th><th>Auteur</th>
            <th>Commentaire</th><th>Fichier</th><th>Statut</th><th>Date</th><th>Actions</th>
          </tr>
        </thead>
        <tbody id="table-body">
          <tr><td colspan="9"><div class="empty-state"><p>Chargement…</p></div></td></tr>
        </tbody>
      </table>
    </div>
    <div class="pagination" id="pagination"></div>
  </div>

  <div id="tab-logs" class="tab-content">
    <div class="table-wrap">
      <div id="logs-body"><div class="empty-state"><p>Chargement…</p></div></div>
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
      <label for="use-pwd" style="font-weight:500">Activer un mot de passe sur ce site</label>
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
  let allAnnotations = [], filtered = [];
  const PER_PAGE = 25;
  let currentPage = 1;

  async function loadAll() {
    try {
      const res  = await fetch(`${API}?all=1`);
      const data = await res.json();
      allAnnotations = data.annotations || [];
    } catch(e) { allAnnotations = []; }
    updateStats(); populateProjectFilter(); applyFilters();
  }

  function updateStats() {
    const total    = allAnnotations.length;
    const open     = allAnnotations.filter(a => a.status==='open').length;
    const resolved = allAnnotations.filter(a => a.status==='resolved').length;
    const projects = new Set(allAnnotations.map(a => a.project_id)).size;
    document.getElementById('stat-total').textContent    = total;
    document.getElementById('stat-open').textContent     = open;
    document.getElementById('stat-resolved').textContent = resolved;
    document.getElementById('stat-projects').textContent = projects;
  }

  function populateProjectFilter() {
    const projects = [...new Set(allAnnotations.map(a => a.project_id))].sort();
    const sel = document.getElementById('filter-project');
    const cur = sel.value;
    sel.innerHTML = '<option value="">Tous les projets</option>' +
      projects.map(p => `<option value="${esc(p)}" ${p===cur?'selected':''}>${esc(p)}</option>`).join('');
  }

  function applyFilters() {
    const text    = document.getElementById('filter-text').value.toLowerCase();
    const project = document.getElementById('filter-project').value;
    const status  = document.getElementById('filter-status').value;
    filtered = allAnnotations.filter(a => {
      if (project && a.project_id !== project) return false;
      if (status  && a.status !== status)       return false;
      if (text && !a.comment.toLowerCase().includes(text) &&
          !a.author_name.toLowerCase().includes(text) &&
          !a.page_url.toLowerCase().includes(text)) return false;
      return true;
    });
    currentPage = 1; renderTable(); renderPagination();
  }

  function renderTable() {
    const tbody = document.getElementById('table-body');
    if (filtered.length === 0) {
      tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><h3>Aucune annotation trouvée</h3><p>Ajoutez le script sur vos sites pour collecter du feedback.</p></div></td></tr>`;
      return;
    }
    const start = (currentPage-1) * PER_PAGE;
    const page  = filtered.slice(start, start+PER_PAGE);
    tbody.innerHTML = page.map((a,i) => `
      <tr>
        <td style="color:#757686;font-size:12px">${start+i+1}</td>
        <td><span class="project-tag">${esc(a.project_id)}</span></td>
        <td><a class="url-link" href="${esc(a.page_url)}" target="_blank" title="${esc(a.page_url)}">${shortUrl(a.page_url)}</a></td>
        <td>
          <div style="font-weight:600;font-size:13px">${esc(a.author_name)}</div>
          ${a.author_email?`<div style="font-size:11px;color:#757686">${esc(a.author_email)}</div>`:''}
        </td>
        <td class="comment-cell">
          <div class="comment-text" title="${esc(a.comment)}">${esc(a.comment)}</div>
          ${a.reply_count>0?`<div class="reply-count">↩ ${a.reply_count} réponse${a.reply_count>1?'s':''}</div>`:''}
        </td>
        <td>
          ${a.file_name
            ?`<button class="btn btn-download" style="padding:4px 10px;font-size:11px" onclick="downloadFile('${esc(a.file_path)}')">⬇ ${esc(a.file_name.length>15?a.file_name.substring(0,15)+'…':a.file_name)}</button>`
            :'<span style="color:#e2e4ef">—</span>'}
        </td>
        <td><span class="badge ${a.status}">${a.status==='resolved'?'✓ Résolu':'● En cours'}</span></td>
        <td style="font-size:12px;color:#757686;white-space:nowrap">${formatDate(a.created_at)}</td>
        <td>
          <div class="actions">
            ${a.status!=='resolved'
              ?`<button class="btn btn-success" style="padding:4px 9px;font-size:11px" onclick="resolve(${a.id})">✓</button>`
              :`<button class="btn btn-ghost"   style="padding:4px 9px;font-size:11px" onclick="unresolve(${a.id})">↩</button>`}
            <button class="btn btn-danger" style="padding:4px 9px;font-size:11px" onclick="deleteA(${a.id})">🗑</button>
          </div>
        </td>
      </tr>`).join('');
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

  async function resolve(id)   { await patch(id,'resolved'); toast('✓ Annotation résolue'); }
  async function unresolve(id) { await patch(id,'open');     toast('↩ Annotation réouverte'); }
  async function patch(id, status) {
    await fetch(API, {method:'PATCH', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id,status,actor:'Dashboard'})});
    const a = allAnnotations.find(x=>x.id==id);
    if (a) a.status = status;
    updateStats(); applyFilters();
  }
  async function deleteA(id) {
    if (!confirm('Supprimer cette annotation définitivement ?')) return;
    await fetch(`${API}?id=${id}`, {method:'DELETE'});
    allAnnotations = allAnnotations.filter(a=>a.id!=id);
    updateStats(); populateProjectFilter(); applyFilters();
    toast('🗑 Annotation supprimée');
  }
  function downloadFile(filePath) {
    window.location.href = `${API}?download=${encodeURIComponent(filePath)}`;
  }

  async function loadLogs() {
    try {
      const res  = await fetch(`${API}?logs=1`);
      const data = await res.json();
      const logs = data.logs || [];
      const icons  = {create:'➕', delete:'🗑', status:'✓', reply:'↩'};
      const labels = {create:'Création', delete:'Suppression', status:'Statut modifié', reply:'Réponse'};
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

  function switchTab(name, btn) {
    document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById('tab-'+name).classList.add('active');
    btn.classList.add('active');
    if (name==='logs') loadLogs();
  }

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
    const pwd    = usePwd && pwdEl.value ? `\n  data-password="${pwdEl.value}"` : '';
    const proj   = document.getElementById('filter-project').value || 'mon-site';
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

  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function shortUrl(url) {
    try { const u=new URL(url); return u.hostname+u.pathname.substring(0,25)+(u.pathname.length>25?'…':''); }
    catch { return url.substring(0,35); }
  }
  function formatDate(ts) {
    return new Date(ts*1000).toLocaleDateString('fr-FR',{day:'2-digit',month:'2-digit',year:'2-digit',hour:'2-digit',minute:'2-digit'});
  }
  function toast(msg) {
    const t=document.getElementById('toast');
    t.textContent=msg; t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'),2500);
  }

  loadAll();
  setInterval(loadAll, 20000);
</script>

<?php endif; ?>
</body>
</html>
