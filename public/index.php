<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UX Note Cloud — Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Montserrat', -apple-system, sans-serif; background: #f4f5f7; color: #222339; min-height: 100vh; }

    /* ── Header ── */
    .header {
      background: linear-gradient(135deg, #222339 0%, #2d2f4a 100%);
      color: #fff; padding: 0 32px; height: 64px;
      display: flex; align-items: center; justify-content: space-between;
      box-shadow: 0 2px 12px rgba(34,35,57,0.3);
    }
    .header-logo { display: flex; align-items: center; gap: 12px; }
    .header-logo .logo-dot { width: 32px; height: 32px; background: #3ce65f; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .header h1 { font-family: 'Raleway', sans-serif; font-size: 18px; font-weight: 700; letter-spacing: 0.03em; }
    .header-sub { font-size: 11px; color: #757686; margin-top: 1px; font-weight: 300; }
    .header-actions { display: flex; gap: 12px; align-items: center; }
    .container { max-width: 1200px; margin: 0 auto; padding: 28px 24px; }

    /* ── Stats ── */
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat-card {
      background: #fff; border-radius: 12px; padding: 20px 22px;
      box-shadow: 0 1px 6px rgba(34,35,57,0.07); border-top: 3px solid transparent;
    }
    .stat-card.blue { border-top-color: #222339; }
    .stat-card.orange { border-top-color: #f59e0b; }
    .stat-card.green { border-top-color: #3ce65f; }
    .stat-card.slate { border-top-color: #757686; }
    .stat-card .label { font-size: 11px; color: #757686; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; font-weight: 500; }
    .stat-card .value { font-size: 30px; font-weight: 700; font-family: 'Raleway', sans-serif; }
    .stat-card.blue .value { color: #222339; }
    .stat-card.green .value { color: #2ab54a; }
    .stat-card.orange .value { color: #d97706; }
    .stat-card.slate .value { color: #757686; }

    /* ── Toolbar ── */
    .toolbar {
      background: #fff; border-radius: 12px; padding: 14px 20px;
      box-shadow: 0 1px 6px rgba(34,35,57,0.07); margin-bottom: 20px;
      display: flex; gap: 12px; flex-wrap: wrap; align-items: center;
    }
    .toolbar input, .toolbar select {
      padding: 9px 13px; border: 1px solid #e2e4ef; border-radius: 8px; font-size: 13px;
      background: #f8f9fc; outline: none; color: #222339; font-family: 'Montserrat', sans-serif;
      transition: border-color 0.15s;
    }
    .toolbar input:focus, .toolbar select:focus { border-color: #3ce65f; }
    .toolbar input { flex: 1; min-width: 220px; }

    /* ── Boutons ── */
    .btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.15s; font-family: 'Montserrat', sans-serif; }
    .btn-primary { background: #222339; color: #fff; }
    .btn-primary:hover { background: #2d2f4a; }
    .btn-accent { background: #3ce65f; color: #222339; }
    .btn-accent:hover { background: #2ab54a; color: #fff; }
    .btn-danger { background: #fee2e2; color: #dc2626; }
    .btn-danger:hover { background: #fecaca; }
    .btn-success { background: #dcfce7; color: #16a34a; }
    .btn-success:hover { background: #bbf7d0; }
    .btn-ghost { background: #f1f3f9; color: #757686; }
    .btn-ghost:hover { background: #e2e5f0; color: #222339; }
    .btn-outline { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,0.3); }
    .btn-outline:hover { background: rgba(255,255,255,0.1); }

    /* ── Table ── */
    .table-wrap { background: #fff; border-radius: 12px; box-shadow: 0 1px 6px rgba(34,35,57,0.07); overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    thead th {
      background: #f8f9fc; padding: 13px 16px; text-align: left;
      font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em;
      color: #757686; border-bottom: 1px solid #e2e4ef; font-weight: 600;
    }
    tbody tr { border-bottom: 1px solid #f4f5f7; transition: background 0.1s; }
    tbody tr:hover { background: #f8f9fc; }
    tbody tr:last-child { border-bottom: none; }
    tbody td { padding: 14px 16px; font-size: 13px; vertical-align: top; }
    .badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .badge.open { background: #e8eaf6; color: #222339; }
    .badge.resolved { background: #dcfce7; color: #15803d; }
    .project-tag { background: #f0f9f1; color: #222339; padding: 3px 9px; border-radius: 5px; font-size: 12px; font-weight: 600; border-left: 3px solid #3ce65f; }
    .comment-text { max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .url-text { max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #757686; font-size: 11px; }
    .actions { display: flex; gap: 6px; }
    .page-url-link { color: #222339; text-decoration: none; font-weight: 500; }
    .page-url-link:hover { color: #3ce65f; }

    /* ── Empty ── */
    .empty-state { text-align: center; padding: 60px 20px; color: #757686; }
    .empty-state svg { margin-bottom: 16px; opacity: 0.3; }
    .empty-state h3 { font-size: 16px; color: #757686; margin-bottom: 8px; font-family: 'Raleway', sans-serif; }

    /* ── Pagination ── */
    .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 20px; flex-wrap: wrap; }
    .pagination button { padding: 7px 13px; border: 1px solid #e2e4ef; border-radius: 8px; background: #fff; cursor: pointer; font-size: 13px; font-family: 'Montserrat', sans-serif; }
    .pagination button.active { background: #222339; color: #fff; border-color: #222339; }
    .pagination button:disabled { opacity: 0.4; cursor: default; }

    /* ── Toast ── */
    #toast { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); background: #222339; color: #fff; padding: 11px 22px; border-radius: 8px; font-size: 13px; opacity: 0; transition: opacity 0.3s; pointer-events: none; z-index: 9999; border-left: 3px solid #3ce65f; }
    #toast.show { opacity: 1; }

    /* ── Modal snippet ── */
    #snippet-modal { position: fixed; inset: 0; background: rgba(34,35,57,0.5); z-index: 9998; display: none; align-items: center; justify-content: center; }
    #snippet-modal.open { display: flex; }
    #snippet-modal-box { background: #fff; border-radius: 14px; padding: 28px; width: 580px; max-width: 95vw; box-shadow: 0 20px 60px rgba(34,35,57,0.25); }
    #snippet-modal-box h3 { margin-bottom: 8px; font-family: 'Raleway', sans-serif; color: #222339; }
    #snippet-modal-box pre { background: #f4f5f7; border: 1px solid #e2e4ef; border-radius: 8px; padding: 16px; font-size: 13px; overflow-x: auto; white-space: pre-wrap; word-break: break-all; color: #222339; margin-top: 14px; }

    @media (max-width: 640px) {
      .container { padding: 16px; }
      .header { padding: 0 16px; }
    }
  </style>
</head>
<body>

<div class="header">
  <div class="header-logo">
    <div class="logo-dot">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#222339" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
    </div>
    <div>
      <div class="header h1" style="font-family:'Raleway',sans-serif;font-size:17px;font-weight:700;letter-spacing:0.03em">UX Note Cloud</div>
      <div class="header-sub">Relecture collaborative — Équinoxes</div>
    </div>
  </div>
  <div class="header-actions">
    <button class="btn btn-outline" onclick="openSnippetModal()">📋 Intégrer le script</button>
  </div>
</div>

<div class="container">

  <div class="stats">
    <div class="stat-card blue"><div class="label">Total</div><div class="value" id="stat-total">—</div></div>
    <div class="stat-card orange"><div class="label">En cours</div><div class="value" id="stat-open">—</div></div>
    <div class="stat-card green"><div class="label">Résolues</div><div class="value" id="stat-resolved">—</div></div>
    <div class="stat-card slate"><div class="label">Projets</div><div class="value" id="stat-projects">—</div></div>
  </div>

  <div class="toolbar">
    <input type="text" id="filter-text" placeholder="🔍 Rechercher dans les commentaires..." oninput="applyFilters()" />
    <select id="filter-project" onchange="applyFilters()">
      <option value="">Tous les projets</option>
    </select>
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
          <th>#</th>
          <th>Projet</th>
          <th>Page</th>
          <th>Auteur</th>
          <th>Commentaire</th>
          <th>Statut</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="table-body">
        <tr><td colspan="8"><div class="empty-state"><p>Chargement…</p></div></td></tr>
      </tbody>
    </table>
  </div>

  <div class="pagination" id="pagination"></div>

</div>

<div id="toast"></div>

<div id="snippet-modal">
  <div id="snippet-modal-box">
    <h3>📋 Intégrer UX Note Cloud sur votre site WordPress</h3>
    <p style="color:#757686;font-size:13px">Ajoutez ce snippet via WPCode ou dans le footer de votre thème, juste avant <code>&lt;/body&gt;</code> :</p>
    <pre id="snippet-code"></pre>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
      <button class="btn btn-ghost" onclick="closeSnippetModal()">Fermer</button>
      <button class="btn btn-accent" onclick="copySnippet()">📋 Copier</button>
    </div>
  </div>
</div>

<script>
  const API = './api/annotations.php';
  const BASE_URL = window.location.origin + window.location.pathname.replace('index.php','').replace(/\/$/, '');
  let allAnnotations = [];
  let filtered = [];
  const PER_PAGE = 25;
  let currentPage = 1;

  // ─── Chargement ────────────────────────────────────────────────────────────
  async function loadAll() {
    try {
      const res = await fetch(`${API}?all=1`);
      const data = await res.json();
      allAnnotations = data.annotations || [];
    } catch(e) {
      allAnnotations = [];
    }
    updateStats();
    populateProjectFilter();
    applyFilters();
  }

  function updateStats() {
    const total = allAnnotations.length;
    const open = allAnnotations.filter(a => a.status === 'open').length;
    const resolved = allAnnotations.filter(a => a.status === 'resolved').length;
    const projects = new Set(allAnnotations.map(a => a.project_id)).size;
    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-open').textContent = open;
    document.getElementById('stat-resolved').textContent = resolved;
    document.getElementById('stat-projects').textContent = projects;
  }

  function populateProjectFilter() {
    const projects = [...new Set(allAnnotations.map(a => a.project_id))].sort();
    const sel = document.getElementById('filter-project');
    const current = sel.value;
    sel.innerHTML = '<option value="">Tous les projets</option>' +
      projects.map(p => `<option value="${esc(p)}" ${p === current ? 'selected':''}>${esc(p)}</option>`).join('');
  }

  function applyFilters() {
    const text = document.getElementById('filter-text').value.toLowerCase();
    const project = document.getElementById('filter-project').value;
    const status = document.getElementById('filter-status').value;
    filtered = allAnnotations.filter(a => {
      if (project && a.project_id !== project) return false;
      if (status && a.status !== status) return false;
      if (text && !a.comment.toLowerCase().includes(text) &&
          !a.author_name.toLowerCase().includes(text) &&
          !a.page_url.toLowerCase().includes(text)) return false;
      return true;
    });
    currentPage = 1;
    renderTable();
    renderPagination();
  }

  function renderTable() {
    const tbody = document.getElementById('table-body');
    if (filtered.length === 0) {
      tbody.innerHTML = `<tr><td colspan="8">
        <div class="empty-state">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          <h3>Aucune annotation trouvée</h3>
          <p>Ajoutez le script sur vos sites pour commencer à collecter du feedback.</p>
        </div>
      </td></tr>`;
      return;
    }
    const start = (currentPage - 1) * PER_PAGE;
    const page = filtered.slice(start, start + PER_PAGE);
    tbody.innerHTML = page.map((a, i) => `
      <tr>
        <td style="color:#757686;font-size:12px">${start + i + 1}</td>
        <td><span class="project-tag">${esc(a.project_id)}</span></td>
        <td>
          <a class="page-url-link" href="${esc(a.page_url)}" target="_blank" title="${esc(a.page_url)}">
            <span class="url-text">${shortUrl(a.page_url)}</span>
          </a>
        </td>
        <td>
          <div style="font-weight:600">${esc(a.author_name)}</div>
          ${a.author_email ? `<div style="font-size:11px;color:#757686">${esc(a.author_email)}</div>` : ''}
        </td>
        <td><div class="comment-text" title="${esc(a.comment)}">${esc(a.comment)}</div></td>
        <td><span class="badge ${a.status}">${a.status === 'resolved' ? '✓ Résolu' : '● En cours'}</span></td>
        <td style="font-size:12px;color:#757686;white-space:nowrap">${formatDate(a.created_at)}</td>
        <td>
          <div class="actions">
            ${a.status !== 'resolved'
              ? `<button class="btn btn-success" style="padding:5px 10px;font-size:12px" onclick="resolve(${a.id})">✓</button>`
              : `<button class="btn btn-ghost" style="padding:5px 10px;font-size:12px" onclick="unresolve(${a.id})">↩</button>`}
            <button class="btn btn-danger" style="padding:5px 10px;font-size:12px" onclick="deleteA(${a.id})">🗑</button>
          </div>
        </td>
      </tr>`).join('');
  }

  function renderPagination() {
    const total = Math.ceil(filtered.length / PER_PAGE);
    const pag = document.getElementById('pagination');
    if (total <= 1) { pag.innerHTML = ''; return; }
    let html = `<button ${currentPage===1?'disabled':''} onclick="goPage(${currentPage-1})">‹</button>`;
    for (let i = 1; i <= total; i++) {
      html += `<button class="${i===currentPage?'active':''}" onclick="goPage(${i})">${i}</button>`;
    }
    html += `<button ${currentPage===total?'disabled':''} onclick="goPage(${currentPage+1})">›</button>`;
    pag.innerHTML = html;
  }
  function goPage(p) { currentPage = p; renderTable(); renderPagination(); window.scrollTo(0,0); }

  async function resolve(id) { await patch(id,'resolved'); toast('✓ Annotation résolue'); }
  async function unresolve(id) { await patch(id,'open'); toast('↩ Annotation réouverte'); }
  async function patch(id, status) {
    await fetch(API, { method:'PATCH', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id,status}) });
    const a = allAnnotations.find(x => x.id == id);
    if (a) a.status = status;
    updateStats(); applyFilters();
  }
  async function deleteA(id) {
    if (!confirm('Supprimer cette annotation définitivement ?')) return;
    await fetch(`${API}?id=${id}`, {method:'DELETE'});
    allAnnotations = allAnnotations.filter(a => a.id != id);
    updateStats(); populateProjectFilter(); applyFilters();
    toast('🗑 Annotation supprimée');
  }

  function openSnippetModal() {
    const projectId = document.getElementById('filter-project').value || 'mon-site';
    document.getElementById('snippet-code').textContent =
      `<script src="${BASE_URL}/js/uxnote-cloud.js"\n  data-project-id="${projectId}"><\/script>`;
    document.getElementById('snippet-modal').classList.add('open');
  }
  function closeSnippetModal() { document.getElementById('snippet-modal').classList.remove('open'); }
  function copySnippet() {
    navigator.clipboard.writeText(document.getElementById('snippet-code').textContent);
    toast('📋 Snippet copié !');
  }
  document.getElementById('snippet-modal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeSnippetModal();
  });

  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function shortUrl(url) {
    try { const u = new URL(url); return u.hostname + u.pathname.substring(0,30) + (u.pathname.length>30?'…':''); }
    catch { return url.substring(0,40); }
  }
  function formatDate(ts) {
    const d = new Date(ts * 1000);
    return d.toLocaleDateString('fr-FR', {day:'2-digit',month:'2-digit',year:'2-digit',hour:'2-digit',minute:'2-digit'});
  }
  function toast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg; t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
  }

  loadAll();
  setInterval(loadAll, 20000);
</script>
</body>
</html>
