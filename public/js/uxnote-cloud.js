/**
 * UX Note Cloud v4 — Script client
 * Charte graphique Equinoxes — equinoxes.fr
 * MIT License
 */
(function () {
  'use strict';

  const currentScript = document.currentScript || (function () {
    const s = document.getElementsByTagName('script');
    return s[s.length - 1];
  })();

  const API_BASE   = currentScript.src.replace(/\/js\/uxnote-cloud\.js.*/, '');
  const PROJECT_ID = currentScript.getAttribute('data-project-id') || window.location.hostname;
  const PASSWORD   = currentScript.getAttribute('data-password') || '';
  const PAGE_URL   = window.location.href;
  const API        = API_BASE + '/api/annotations.php';

  let userToken = localStorage.getItem('uxnote_token');
  if (!userToken) {
    userToken = 'tok_' + Math.random().toString(36).substr(2, 16) + Date.now().toString(36);
    localStorage.setItem('uxnote_token', userToken);
  }

  let currentUser    = localStorage.getItem('uxnote_user') ? JSON.parse(localStorage.getItem('uxnote_user')) : null;
  let annotationMode = false;
  let annotations    = [];
  let pinElements    = [];
  let pendingPos     = null;
  let authenticated  = !PASSWORD;
  let projectArchived = false;
  let intervenants   = [];
  let filterStatus   = 'all'; // 'all', 'open', 'resolved'

  const C = {
    primary: '#222339', accent: '#3ce65f', slate: '#757686',
    danger: '#e63946', light: '#f4f5f7', white: '#ffffff',
    shadow: 'rgba(34,35,57,0.15)',
  };

  const style = document.createElement('style');
  style.textContent = `
    @import url('https://fonts.googleapis.com/css2?family=Raleway:wght@600;700&family=Montserrat:wght@300;400;500;600&display=swap');

    #uxnote-bar { position:fixed; bottom:24px; left:20px; z-index:999999; font-family:'Montserrat',sans-serif; }
    #uxnote-toggle-btn {
      background:${C.primary}; color:${C.white}; border:none; border-radius:50px;
      padding:11px 20px; cursor:pointer; font-size:13px; font-weight:600;
      box-shadow:0 4px 16px ${C.shadow}; display:flex; align-items:center; gap:8px;
      transition:all 0.2s; letter-spacing:0.03em; font-family:'Montserrat',sans-serif;
      border-left:3px solid ${C.accent};
    }
    #uxnote-toggle-btn:hover { background:#2d2f4a; transform:translateY(-1px); }
    #uxnote-toggle-btn.active { background:${C.danger}; border-left-color:#ff8fa3; }
    #uxnote-toggle-btn.archived { background:${C.slate}; border-left-color:#9b9dba; cursor:default; }
    #uxnote-toggle-btn.archived:hover { transform:none; background:${C.slate}; }
    #uxnote-btn-label { color:${C.white}; font-weight:600; }

    #uxnote-panel {
      position:fixed; top:0; right:0; width:360px; height:100vh;
      background:${C.white}; box-shadow:-4px 0 28px ${C.shadow};
      z-index:999998; display:none; flex-direction:column;
      font-family:'Montserrat',sans-serif; font-size:14px; overflow:hidden;
    }
    #uxnote-panel.open { display:flex; }
    #uxnote-panel-header {
      background:${C.primary}; color:${C.white}; padding:18px 20px;
      display:flex; justify-content:space-between; align-items:center;
      flex-shrink:0; border-bottom:3px solid ${C.accent};
    }
    #uxnote-panel-header.archived-header { border-bottom-color:${C.slate}; background:#3d3f5a; }
    #uxnote-panel-header h3 {
      font-family:'Raleway',sans-serif; font-size:16px; font-weight:700;
      display:flex; align-items:center; gap:8px; color:${C.white};
    }
    #uxnote-panel-header h3::before {
      content:''; display:inline-block; width:8px; height:8px;
      background:${C.accent}; border-radius:50%;
    }
    #uxnote-panel-header.archived-header h3::before { background:${C.slate}; }
    #uxnote-close-panel { background:none; border:none; color:${C.white}; cursor:pointer; font-size:20px; opacity:0.7; }
    #uxnote-close-panel:hover { opacity:1; }
    #uxnote-panel-body { flex:1; overflow-y:auto; padding:16px; background:${C.light}; }
    #uxnote-panel-footer { padding:12px 16px; border-top:1px solid #e2e4ef; background:${C.white}; flex-shrink:0; }
    #uxnote-filter-bar {
      display: flex; gap: 6px; margin-bottom: 10px;
    }
    .uxnote-filter-btn {
      all: initial;
      flex: 1; padding: 6px 4px; border-radius: 6px; border: 1px solid #e2e4ef;
      background: ${C.white}; cursor: pointer; font-size: 11px; font-weight: 600;
      font-family: 'Montserrat', sans-serif; color: ${C.slate};
      text-align: center; transition: all 0.15s; box-sizing: border-box;
    }
    .uxnote-filter-btn.active { background: ${C.primary} !important; color: ${C.white} !important; border-color: ${C.primary} !important; }
    .uxnote-filter-btn:hover:not(.active) { background: ${C.light} !important; }
    #uxnote-modal select {
      width: 100%; box-sizing: border-box; padding: 10px 12px;
      border: 1px solid #e2e4ef; border-radius: 8px; font-size: 13px;
      margin-bottom: 10px; outline: none; font-family: 'Montserrat', sans-serif;
      color: ${C.primary}; background: ${C.white}; appearance: auto;
    }
    #uxnote-modal select:focus { border-color: ${C.accent}; }

    .uxnote-archived-banner {
      background:#f0f1f8; border:1px solid #c8cadf; border-radius:8px;
      padding:12px 14px; margin-bottom:12px; font-size:12px; color:#3d3f5a;
      display:flex; align-items:center; gap:8px;
    }

    .uxnote-annotation-item {
      border:1px solid #e2e4ef; border-radius:10px; padding:13px 14px;
      margin-bottom:10px; background:${C.white}; border-left:3px solid ${C.primary};
    }
    .uxnote-annotation-item.resolved { border-left-color:${C.accent}; opacity:0.65; }
    .uxnote-annotation-item.mine { border-left-color:#9b5de5; }
    .uxnote-annotation-meta { font-size:11px; color:${C.slate}; margin-bottom:6px; font-weight:500; }
    .uxnote-annotation-meta strong { color:${C.primary}; }
    .uxnote-annotation-text { color:${C.primary}; line-height:1.55; margin-bottom:8px; font-size:13px; }
    .uxnote-file-attach {
      display:inline-flex; align-items:center; gap:5px;
      background:#f0f9f1; border:1px solid #c7f0d2; border-radius:5px;
      padding:4px 9px; font-size:11px; color:#2ab54a; margin-bottom:8px; font-weight:500;
      text-decoration:none; cursor:pointer; transition:background 0.15s;
    }
    .uxnote-file-attach:hover { background:#dcfce7; }
    .uxnote-replies { margin-top:10px; padding-top:10px; border-top:1px dashed #e2e4ef; }
    .uxnote-reply-item { background:${C.light}; border-radius:7px; padding:8px 10px; margin-bottom:6px; font-size:12px; }
    .uxnote-reply-meta { color:${C.slate}; margin-bottom:3px; font-size:11px; }
    .uxnote-reply-text { color:${C.primary}; }
    .uxnote-reply-form { margin-top:8px; display:none; }
    .uxnote-reply-form.open { display:block; }
    .uxnote-reply-form textarea {
      width:100%; padding:8px; border:1px solid #e2e4ef; border-radius:6px;
      font-size:12px; font-family:'Montserrat',sans-serif; resize:vertical;
      min-height:60px; margin-bottom:6px; box-sizing:border-box;
    }
    .uxnote-reply-form textarea:focus { outline:none; border-color:${C.accent}; }
    .uxnote-reply-actions { display:flex; gap:6px; justify-content:flex-end; }

    .uxnote-annotation-actions { display:flex; gap:6px; flex-wrap:wrap; margin-top:8px; }
    /* Reset complet des boutons — évite l'héritage des styles du site client */
    .uxnote-btn-sm {
      all: initial;
      display: inline-flex !important;
      align-items: center !important;
      gap: 4px !important;
      padding: 5px 11px !important;
      border-radius: 6px !important;
      border: 1px solid #e2e4ef !important;
      background: ${C.white} !important;
      cursor: pointer !important;
      font-size: 11px !important;
      font-family: 'Montserrat', sans-serif !important;
      font-weight: 500 !important;
      color: #475569 !important;
      transition: all 0.15s !important;
      line-height: 1.4 !important;
      box-sizing: border-box !important;
      white-space: nowrap !important;
      text-decoration: none !important;
    }
    .uxnote-btn-sm:hover { background: ${C.light} !important; }
    /* Voir — bleu foncé sobre */
    .uxnote-btn-sm.see-btn   { background: ${C.primary} !important; color: ${C.white} !important; border-color: ${C.primary} !important; }
    .uxnote-btn-sm.see-btn:hover { background: #2d2f4a !important; }
    /* Répondre — bleu foncé sobre */
    .uxnote-btn-sm.reply-btn { background: ${C.primary} !important; color: ${C.white} !important; border-color: ${C.primary} !important; }
    .uxnote-btn-sm.reply-btn:hover { background: #2d2f4a !important; }
    /* Résoudre — vert sobre */
    .uxnote-btn-sm.resolve   { background: ${C.primary} !important; color: ${C.white} !important; border-color: ${C.primary} !important; }
    .uxnote-btn-sm.resolve:hover { background: #2d2f4a !important; }
    /* Réouvrir */
    .uxnote-btn-sm.unresolve { background: ${C.light} !important; color: ${C.slate} !important; border-color: #e2e4ef !important; }
    .uxnote-btn-sm.unresolve:hover { background: #e2e5f0 !important; }
    /* Supprimer */
    .uxnote-btn-sm.delete    { background: ${C.light} !important; color: ${C.slate} !important; border-color: #e2e4ef !important; }
    .uxnote-btn-sm.delete:hover { background: #fff0f1 !important; color: ${C.danger} !important; border-color: ${C.danger} !important; }

    .uxnote-pin {
      position:absolute;
      width:28px; height:28px;
      border-radius:0 50% 50% 50%;
      transform:rotate(-45deg);
      display:flex; align-items:center; justify-content:center;
      cursor:pointer; z-index:99999;
      border:2px solid ${C.white};
      box-shadow:0 2px 10px ${C.shadow};
      transition:transform 0.15s, box-shadow 0.15s;
    }
    .uxnote-pin:hover { transform:rotate(-45deg) scale(1.15); box-shadow:0 4px 16px ${C.shadow}; }
    .uxnote-pin-number {
      transform:rotate(45deg); color:${C.white};
      font-size:11px; font-weight:700; font-family:'Montserrat',sans-serif;
      line-height:1; user-select:none;
    }
    .uxnote-pin.status-open      { background:${C.primary}; }
    .uxnote-pin.status-open.mine { background:${C.primary}; }
    .uxnote-pin.status-resolved  { background:${C.accent}; }

    #uxnote-cursor-hint {
      position:fixed; top:50%; left:50%; transform:translate(-50%,-50%);
      background:${C.primary}; color:${C.white}; padding:14px 24px;
      border-radius:10px; font-size:14px; font-weight:600; z-index:999997;
      pointer-events:none; display:none; font-family:'Montserrat',sans-serif;
      box-shadow:0 8px 24px ${C.shadow}; border-left:4px solid ${C.accent};
    }

    #uxnote-modal-overlay {
      position:fixed; inset:0; background:rgba(34,35,57,0.45); z-index:9999999;
      display:none; align-items:center; justify-content:center;
    }
    #uxnote-modal-overlay.open { display:flex; }
    #uxnote-modal {
      background:${C.white}; border-radius:14px; padding:26px; width:380px;
      box-shadow:0 20px 50px ${C.shadow}; font-family:'Montserrat',sans-serif;
      border-top:4px solid ${C.accent};
    }
    #uxnote-modal h4 { margin:0 0 16px; font-size:15px; color:${C.primary}; font-family:'Raleway',sans-serif; font-weight:700; }
    #uxnote-modal input, #uxnote-modal textarea {
      width:100%; box-sizing:border-box; padding:10px 12px;
      border:1px solid #e2e4ef; border-radius:8px; font-size:13px;
      margin-bottom:10px; outline:none; font-family:'Montserrat',sans-serif; color:${C.primary};
    }
    #uxnote-modal input:focus, #uxnote-modal textarea:focus { border-color:${C.accent}; }
    #uxnote-modal textarea { resize:vertical; min-height:90px; }
    .uxnote-file-label {
      display:flex; align-items:center; gap:8px; cursor:pointer;
      padding:9px 12px; border:1px dashed #e2e4ef; border-radius:8px;
      font-size:12px; color:${C.slate}; margin-bottom:10px;
      transition:border-color 0.15s; background:${C.light};
    }
    .uxnote-file-label:hover { border-color:${C.accent}; color:${C.primary}; }
    .uxnote-file-label input { display:none; }
    #uxnote-file-selected { font-size:11px; color:#2ab54a; font-weight:600; margin-bottom:8px; min-height:16px; }
    #uxnote-modal-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:4px; }
    .uxnote-modal-btn {
      all: initial;
      display: inline-flex !important;
      align-items: center !important;
      gap: 6px !important;
      padding: 9px 18px !important;
      border-radius: 8px !important;
      border: none !important;
      cursor: pointer !important;
      font-size: 13px !important;
      font-weight: 600 !important;
      font-family: 'Montserrat', sans-serif !important;
      transition: all 0.15s !important;
      box-sizing: border-box !important;
      line-height: 1.4 !important;
    }
    .uxnote-modal-btn.cancel { background:${C.light} !important; color:${C.slate} !important; }
    .uxnote-modal-btn.cancel:hover { background:#e2e4ef !important; }
    .uxnote-modal-btn.submit { background:${C.primary} !important; color:${C.white} !important; border-left:3px solid ${C.accent} !important; }
    .uxnote-modal-btn.submit:hover { background:#2d2f4a !important; }

    #uxnote-pwd-overlay {
      position:fixed; inset:0; background:rgba(34,35,57,0.6); z-index:9999998;
      display:none; align-items:center; justify-content:center;
    }
    #uxnote-pwd-overlay.open { display:flex; }
    #uxnote-pwd-box {
      background:${C.white}; border-radius:14px; padding:28px; width:320px;
      box-shadow:0 20px 50px ${C.shadow}; text-align:center;
      border-top:4px solid ${C.accent}; font-family:'Montserrat',sans-serif;
    }
    #uxnote-pwd-box h4 { font-family:'Raleway',sans-serif; color:${C.primary}; margin-bottom:16px; font-size:16px; }
    #uxnote-pwd-input {
      width:100%; padding:10px 12px; border:1px solid #e2e4ef; border-radius:8px;
      font-size:14px; margin-bottom:10px; outline:none; text-align:center;
      letter-spacing:0.1em; font-family:'Montserrat',sans-serif; box-sizing:border-box;
    }
    #uxnote-pwd-input:focus { border-color:${C.accent}; }
    #uxnote-pwd-error { color:${C.danger}; font-size:12px; margin-bottom:8px; display:none; }
    #uxnote-pwd-submit {
      width:100%; padding:10px; background:${C.primary}; color:${C.white};
      border:none; border-radius:8px; cursor:pointer; font-size:14px;
      font-weight:600; font-family:'Montserrat',sans-serif; border-left:3px solid ${C.accent};
    }
    #uxnote-pwd-submit:hover { background:#2d2f4a; }

    .uxnote-empty { text-align:center; color:${C.slate}; padding:32px 16px; }
    .uxnote-empty p { font-size:13px; }
    body.uxnote-mode-active { cursor:crosshair !important; }
    body.uxnote-mode-active * { cursor:crosshair !important; }

    /* Cadre bleu sur les éléments annotés — comme l'original */
    .uxnote-annotated {
      outline: 2px solid #3ce65f !important;
      outline-offset: 2px;
      box-shadow: 0 0 0 3px rgba(60,230,95,0.12);
    }

    /* Hover outline en mode annotation — comme wn-annot-outline */
    #uxnote-hover-outline {
      position: fixed;
      pointer-events: none;
      border: 2px dashed #3ce65f;
      background: rgba(60,230,95,0.08);
      z-index: 999990;
      display: none;
      box-sizing: border-box;
    }

    /* Marker layer en position:fixed — clé du bon positionnement */
    #uxnote-marker-layer {
      position: fixed;
      left: 0; top: 0;
      width: 100%; height: 100%;
      pointer-events: none;
      z-index: 99998;
    }
    #uxnote-marker-layer .uxnote-pin { pointer-events: auto; }
    #uxnote-add-btn {
      all: initial;
      display: block !important;
      width: 100% !important;
      padding: 10px !important;
      background: ${C.primary} !important;
      color: ${C.white} !important;
      border: none !important;
      border-left: 3px solid ${C.accent} !important;
      border-radius: 8px !important;
      cursor: pointer !important;
      font-size: 13px !important;
      font-weight: 600 !important;
      font-family: 'Montserrat', sans-serif !important;
      transition: background 0.15s !important;
      text-align: center !important;
      box-sizing: border-box !important;
    }
    #uxnote-add-btn:hover { background:#2d2f4a !important; }
    #uxnote-add-btn:disabled { background:${C.slate} !important; border-left-color:#9b9dba !important; cursor:default !important; }
  `;
  document.head.appendChild(style);

  function createUI() {
    const bar = document.createElement('div');
    bar.id = 'uxnote-bar';
    bar.innerHTML = `
      <button id="uxnote-toggle-btn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="${C.white}" stroke-width="2.5">
          <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
        </svg>
        <span id="uxnote-btn-label">Annoter</span>
      </button>`;
    document.body.appendChild(bar);

    const panel = document.createElement('div');
    panel.id = 'uxnote-panel';
    panel.innerHTML = `
      <div id="uxnote-panel-header">
        <h3 id="uxnote-panel-title">Annotations</h3>
        <button id="uxnote-close-panel" title="Fermer">✕</button>
      </div>
      <div id="uxnote-panel-body">
        <div class="uxnote-empty"><p>Aucune annotation sur cette page.</p></div>
      </div>
      <div id="uxnote-panel-footer">
        <div id="uxnote-filter-bar">
          <button class="uxnote-filter-btn active" onclick="setFilter('all',this)">Tous</button>
          <button class="uxnote-filter-btn" onclick="setFilter('open',this)">● En cours</button>
          <button class="uxnote-filter-btn" onclick="setFilter('resolved',this)">✓ Résolus</button>
        </div>
        <button id="uxnote-add-btn">+ Ajouter une annotation</button>
      </div>`;
    document.body.appendChild(panel);

    const hint = document.createElement('div');
    hint.id = 'uxnote-cursor-hint';
    hint.textContent = 'Cliquez sur la zone à annoter — Échap pour annuler';
    document.body.appendChild(hint);

    // Marker layer en position:fixed (comme wn-annot-marker-layer)
    const markerLayer = document.createElement('div');
    markerLayer.id = 'uxnote-marker-layer';
    document.body.appendChild(markerLayer);

    // Hover outline (comme wn-annot-outline)
    const hoverOutline = document.createElement('div');
    hoverOutline.id = 'uxnote-hover-outline';
    document.body.appendChild(hoverOutline);

    const modalOv = document.createElement('div');
    modalOv.id = 'uxnote-modal-overlay';
    modalOv.innerHTML = `
      <div id="uxnote-modal">
        <h4>Nouvelle annotation</h4>
        <div id="uxnote-user-fields">
          <input id="uxnote-input-name"  type="text"  placeholder="Votre prénom / nom *" />
          <input id="uxnote-input-email" type="email" placeholder="Votre email (optionnel)" />
        </div>
        <select id="uxnote-input-intervenant">
          <option value="">— Assigner à un intervenant Équinoxes —</option>
        </select>
        <textarea id="uxnote-input-text" placeholder="Décrivez votre annotation..."></textarea>
        <label class="uxnote-file-label">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
          </svg>
          Joindre un fichier (max 5 Mo)
          <input type="file" id="uxnote-file-input" accept="*/*" />
        </label>
        <div id="uxnote-file-selected"></div>
        <div id="uxnote-modal-actions">
          <button class="uxnote-modal-btn cancel" id="uxnote-modal-cancel">Annuler</button>
          <button class="uxnote-modal-btn submit" id="uxnote-modal-submit">Envoyer</button>
        </div>
      </div>`;
    document.body.appendChild(modalOv);

    const pwdOv = document.createElement('div');
    pwdOv.id = 'uxnote-pwd-overlay';
    pwdOv.innerHTML = `
      <div id="uxnote-pwd-box">
        <h4>Accès annotations</h4>
        <input type="password" id="uxnote-pwd-input" placeholder="Mot de passe" />
        <div id="uxnote-pwd-error">Mot de passe incorrect</div>
        <button id="uxnote-pwd-submit">Accéder</button>
      </div>`;
    document.body.appendChild(pwdOv);
  }

  function getSortedAnnotations() {
    return [...annotations].sort((a, b) => {
      // Trier par position Y réelle de l'élément dans le document (comme l'original)
      const elA  = a.xpath ? findNodeByXPath(a.xpath) : null;
      const elB  = b.xpath ? findNodeByXPath(b.xpath) : null;
      const rectA = elA ? elA.getBoundingClientRect() : null;
      const rectB = elB ? elB.getBoundingClientRect() : null;
      const yA = rectA ? (rectA.top + window.scrollY) : (a.pos_y || 0);
      const yB = rectB ? (rectB.top + window.scrollY) : (b.pos_y || 0);
      return yA - yB;
    });
  }

  function renderPanel() {
    const body   = document.getElementById('uxnote-panel-body');
    const header = document.getElementById('uxnote-panel-header');
    const addBtn = document.getElementById('uxnote-add-btn');
    if (!body) return;

    if (projectArchived) {
      header.classList.add('archived-header');
      if (addBtn) addBtn.disabled = true;
    } else {
      header.classList.remove('archived-header');
      if (addBtn) addBtn.disabled = false;
    }

    const sorted = getSortedAnnotations();
    if (sorted.length === 0) {
      body.innerHTML = `
        ${projectArchived ? '<div class="uxnote-archived-banner">📦 Ce projet est archivé — lecture seule</div>' : ''}
        <div class="uxnote-empty"><p>Aucune annotation sur cette page.</p></div>`;
      return;
    }

    const archivedBanner = projectArchived
      ? '<div class="uxnote-archived-banner">📦 Ce projet est archivé — lecture seule</div>'
      : '';

    body.innerHTML = archivedBanner + sorted.map((a, i) => {
      const isMine  = a.author_token === userToken;
      const replies = (a.replies || []).map(r => `
        <div class="uxnote-reply-item">
          <div class="uxnote-reply-meta"><strong>${escHtml(r.author_name)}</strong> · ${formatDate(r.created_at)}</div>
          <div class="uxnote-reply-text">${escHtml(r.comment)}</div>
        </div>`).join('');

      return `
        <div class="uxnote-annotation-item ${a.status==='resolved'?'resolved':''} ${isMine?'mine':''}" data-id="${a.id}">
          <div class="uxnote-annotation-meta">
            <strong>#${a.id} ${escHtml(a.author_name)}</strong>
            ${isMine?' <span style="color:#9b5de5;font-weight:700">(moi)</span>':''}
            · ${formatDate(a.created_at)}
            ${a.status==='resolved'?' · <span style="color:#2ab54a">✓ Résolu</span>':''}
          </div>
          <div class="uxnote-annotation-text">${escHtml(a.comment)}</div>
          ${a.file_name?`<a class="uxnote-file-attach" href="${API_BASE}/api/annotations.php?download=${encodeURIComponent(a.file_path)}" download="${escHtml(a.file_name)}" target="_blank">📎 ${escHtml(a.file_name)} ⬇</a>`:''}
          ${replies?`<div class="uxnote-replies">${replies}</div>`:''}
          ${!projectArchived ? `
          <div class="uxnote-reply-form" id="reply-form-${a.id}">
            <textarea id="reply-text-${a.id}" placeholder="Votre réponse..."></textarea>
            <div class="uxnote-reply-actions">
              <button class="uxnote-btn-sm" onclick="document.getElementById('reply-form-${a.id}').classList.remove('open')">Annuler</button>
              <button class="uxnote-btn-sm resolve" onclick="submitReply(${a.id})">Envoyer</button>
            </div>
          </div>` : ''}
          <div class="uxnote-annotation-actions">
            <button class="uxnote-btn-sm see-btn" onclick="document.uxnoteCloud.focusPin(${a.id})">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              Voir
            </button>
            ${!projectArchived ? `
            <button class="uxnote-btn-sm reply-btn" onclick="toggleReplyForm(${a.id})">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/></svg>
              Répondre
            </button>
            ${a.status!=='resolved'
              ?`<button class="uxnote-btn-sm resolve" onclick="document.uxnoteCloud.resolve(${a.id})">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                  Résoudre
                </button>`
              :`<button class="uxnote-btn-sm unresolve" onclick="document.uxnoteCloud.unresolve(${a.id})">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/></svg>
                  Réouvrir
                </button>`}
            ${isMine?`<button class="uxnote-btn-sm delete" onclick="document.uxnoteCloud.deleteMine(${a.id})">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
              </button>`:''}
            ` : ''}
          </div>
        </div>`;
    }).join('');
  }

  // Reproduit refreshMarkers() + applyElementHighlight() de l'original
  function renderPins() {
    // Nettoyer les pins existants
    pinElements.forEach(p => p.remove());
    pinElements = [];

    // Nettoyer les highlights sur les éléments
    document.querySelectorAll('.uxnote-annotated').forEach(el => {
      el.classList.remove('uxnote-annotated');
    });

    const markerLayer = document.getElementById('uxnote-marker-layer');
    if (!markerLayer) return;

    const sorted = getSortedAnnotations();
    sorted.forEach((a, i) => {
      // Retrouver l'élément via XPath
      const el   = a.xpath ? findNodeByXPath(a.xpath) : null;
      const rect = el ? getVisibleRect(el) : null;

      // Appliquer le highlight sur l'élément (comme applyElementHighlight)
      if (el && rect && !projectArchived) {
        el.classList.add('uxnote-annotated');
      }

      // Créer le pin dans le marker layer (position:absolute dans fixed layer)
      const pin = document.createElement('div');
      pin.className = 'uxnote-pin status-' + a.status + (a.author_token === userToken ? ' mine' : '');
      pin.innerHTML  = '<span class="uxnote-pin-number">' + a.id + '</span>';
      pin.title      = '#' + a.id + ' ' + a.author_name + ': ' + a.comment;
      markerLayer.appendChild(pin);
      pinElements.push(pin);

      if (!rect) {
        pin.style.display = 'none';
      } else {
        pin.style.display = '';
        // Positionner dans le marker layer fixed — rect est déjà en coordonnées viewport !
        pin.style.left = (rect.x + rect.width  + 4) + 'px';
        pin.style.top  = (rect.y               - 4) + 'px';
      }

      pin.addEventListener('click', () => {
        openPanel();
        // Scroller vers l'item dans le panel
        setTimeout(() => {
          const panelItem = document.querySelector('[data-id="' + a.id + '"]');
          if (panelItem) panelItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 150);
        // Scroller vers l'élément annoté sur la page
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    });
  }

  async function checkProjectStatus() {
    try {
      const res  = await fetch(`${API}?check_project=${encodeURIComponent(PROJECT_ID)}`);
      const data = await res.json();
      projectArchived = data.archived || false;
      const btn = document.getElementById('uxnote-toggle-btn');
      if (projectArchived) {
        btn.classList.add('archived');
        document.getElementById('uxnote-btn-label').textContent = 'Archivé';
      } else {
        btn.classList.remove('archived');
        document.getElementById('uxnote-btn-label').textContent = 'Annoter';
      }
    } catch(e) { projectArchived = false; }
  }

  async function loadIntervenants() {
    try {
      const res  = await fetch(`${API}?intervenants=1`);
      const data = await res.json();
      intervenants = (data.intervenants || []).filter(i => i.actif == 1);
      const sel = document.getElementById('uxnote-input-intervenant');
      if (sel) {
        const cur = sel.value;
        sel.innerHTML = '<option value="">— Assigner à un intervenant Équinoxes —</option>' +
          intervenants.map(i => `<option value="${i.id}" ${i.id==cur?'selected':''}>${i.prenom}${i.poste?' ('+i.poste+')':''}</option>`).join('');
      }
    } catch(e) {}
  }

  async function loadAnnotations() {
    try {
      const res  = await fetch(`${API}?project_id=${encodeURIComponent(PROJECT_ID)}&page_url=${encodeURIComponent(PAGE_URL)}`);
      const data = await res.json();
      annotations = data.annotations || [];
      renderPanel(); renderPins();
    } catch(e) { console.error('UX Note Cloud:', e); }
  }

  window.submitReply = async function(annotationId) {
    if (projectArchived) return;
    const ta      = document.getElementById(`reply-text-${annotationId}`);
    const comment = ta ? ta.value.trim() : '';
    if (!comment) { if (ta) ta.focus(); return; }
    if (!currentUser) { alert('Veuillez d\'abord créer une annotation pour vous identifier.'); return; }

    const btn = document.querySelector(`#reply-form-${annotationId} .resolve`);
    if (btn) { btn.disabled = true; btn.textContent = '...'; }

    const fd = new FormData();
    fd.append('action',        'reply');
    fd.append('annotation_id', annotationId);
    fd.append('author_name',   currentUser.name);
    fd.append('author_email',  currentUser.email || '');
    fd.append('author_token',  userToken);
    fd.append('comment',       comment);

    try {
      await fetch(API, { method:'POST', body:fd });
      if (ta) ta.value = '';
      const form = document.getElementById(`reply-form-${annotationId}`);
      if (form) form.classList.remove('open');
      await loadAnnotations();
    } catch(e) { console.error('Erreur réponse:', e); }
  };

  window.toggleReplyForm = function(id) {
    if (projectArchived) return;
    const form = document.getElementById(`reply-form-${id}`);
    if (form) {
      form.classList.toggle('open');
      if (form.classList.contains('open')) {
        const ta = document.getElementById(`reply-text-${id}`);
        if (ta) setTimeout(() => ta.focus(), 50);
      }
    }
  };

  function activateMode() {
    if (projectArchived) return;
    if (!authenticated) { openPasswordScreen(); return; }
    annotationMode = true;
    document.body.classList.add('uxnote-mode-active');
    document.getElementById('uxnote-cursor-hint').style.display = 'block';
    document.getElementById('uxnote-toggle-btn').classList.add('active');
    document.getElementById('uxnote-btn-label').textContent = 'Annuler';
    document.getElementById('uxnote-panel').classList.remove('open');
  }

  function deactivateMode() {
    annotationMode = false;
    document.body.classList.remove('uxnote-mode-active');
    document.getElementById('uxnote-cursor-hint').style.display = 'none';
    document.getElementById('uxnote-toggle-btn').classList.remove('active');
    document.getElementById('uxnote-btn-label').textContent = projectArchived ? 'Archivé' : 'Annoter';
    const outline = document.getElementById('uxnote-hover-outline');
    if (outline) outline.style.display = 'none';
  }

  function openPanel()  { document.getElementById('uxnote-panel').classList.add('open'); }
  function closeModal() { document.getElementById('uxnote-modal-overlay').classList.remove('open'); }

  function openPasswordScreen() {
    document.getElementById('uxnote-pwd-overlay').classList.add('open');
    setTimeout(() => document.getElementById('uxnote-pwd-input').focus(), 50);
  }

  function openModal() {
    document.getElementById('uxnote-input-text').value = '';
    document.getElementById('uxnote-file-selected').textContent = '';
    const fi = document.getElementById('uxnote-file-input');
    if (fi) fi.value = '';
    document.getElementById('uxnote-user-fields').style.display = currentUser ? 'none' : 'block';
    document.getElementById('uxnote-modal-overlay').classList.add('open');
    setTimeout(() => document.getElementById('uxnote-input-text').focus(), 50);
  }

  function bindEvents() {
    document.getElementById('uxnote-toggle-btn').addEventListener('click', () => {
      if (projectArchived) { openPanel(); return; }
      if (!authenticated) { openPasswordScreen(); return; }
      if (annotationMode) { deactivateMode(); openPanel(); } else { activateMode(); }
    });
    document.getElementById('uxnote-add-btn').addEventListener('click', () => {
      if (projectArchived) return;
      document.getElementById('uxnote-panel').classList.remove('open');
      activateMode();
    });
    document.getElementById('uxnote-close-panel').addEventListener('click', () => {
      document.getElementById('uxnote-panel').classList.remove('open');
    });
    // Hover outline en mode annotation (comme handleElementHover dans l'original)
    document.addEventListener('mousemove', (e) => {
      if (!annotationMode) return;
      const outline = document.getElementById('uxnote-hover-outline');
      if (!outline) return;
      const target = e.target;
      if (!target || target.closest('#uxnote-bar,#uxnote-panel,#uxnote-modal-overlay,.uxnote-pin,#uxnote-pwd-overlay,#uxnote-marker-layer')) {
        outline.style.display = 'none';
        return;
      }
      const rect = target.getBoundingClientRect();
      if (!rect.width || !rect.height) { outline.style.display = 'none'; return; }
      outline.style.display = 'block';
      outline.style.left    = rect.x + 'px';
      outline.style.top     = rect.y + 'px';
      outline.style.width   = rect.width  + 'px';
      outline.style.height  = rect.height + 'px';
    });

    document.addEventListener('click', (e) => {
      if (!annotationMode) return;
      // Cacher le hover outline au clic
      const outline = document.getElementById('uxnote-hover-outline');
      if (outline) outline.style.display = 'none';
      if (e.target.closest('#uxnote-bar,#uxnote-panel,#uxnote-modal-overlay,.uxnote-pin,#uxnote-pwd-overlay,#uxnote-marker-layer,#uxnote-hover-outline')) return;
      e.preventDefault(); e.stopPropagation();

      // Stocker uniquement le XPath de l'élément cliqué
      // Exactement comme l'original UXnote — pas de coordonnées, juste l'élément
      const target = e.target;
      pendingPos = { xpath: getXPath(target) };
      deactivateMode(); openModal();
    }, true);
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { deactivateMode(); closeModal(); }
    });
    document.getElementById('uxnote-modal-cancel').addEventListener('click', closeModal);
    document.getElementById('uxnote-modal-overlay').addEventListener('click', (e) => {
      if (e.target === e.currentTarget) closeModal();
    });
    document.getElementById('uxnote-file-input').addEventListener('change', (e) => {
      const file = e.target.files[0];
      const el   = document.getElementById('uxnote-file-selected');
      if (file) {
        if (file.size > 5*1024*1024) {
          el.textContent = '⚠️ Fichier trop volumineux (max 5 Mo)';
          el.style.color = '#e63946'; e.target.value = '';
        } else {
          el.textContent = '✓ ' + file.name; el.style.color = '#2ab54a';
        }
      }
    });
    document.getElementById('uxnote-modal-submit').addEventListener('click', async () => {
      const text = document.getElementById('uxnote-input-text').value.trim();
      if (!text) { document.getElementById('uxnote-input-text').focus(); return; }
      if (!currentUser) {
        const nameEl  = document.getElementById('uxnote-input-name');
        const emailEl = document.getElementById('uxnote-input-email');
        const name    = nameEl ? nameEl.value.trim() : '';
        if (!name) { if (nameEl) nameEl.focus(); return; }
        currentUser = { name, email: emailEl ? emailEl.value.trim() : '' };
        localStorage.setItem('uxnote_user', JSON.stringify(currentUser));
      }
      const btn = document.getElementById('uxnote-modal-submit');
      btn.disabled = true; btn.textContent = 'Envoi...';
      const fd = new FormData();
      fd.append('project_id',   PROJECT_ID);
      fd.append('page_url',     PAGE_URL);
      fd.append('author_name',  currentUser.name);
      fd.append('author_email', currentUser.email || '');
      fd.append('author_token', userToken);
      fd.append('comment',      text);
      // Uniquement le XPath — la position est recalculée via getBoundingClientRect
      fd.append('xpath',       pendingPos ? pendingPos.xpath : '');
      fd.append('pos_x',       0);
      fd.append('pos_y',       0);
      fd.append('rel_x',       0);
      fd.append('rel_y',       0);
      const selInterv = document.getElementById('uxnote-input-intervenant');
      fd.append('assigned_to', selInterv ? (selInterv.value || 0) : 0);
      const fileInput = document.getElementById('uxnote-file-input');
      if (fileInput && fileInput.files[0]) fd.append('file', fileInput.files[0]);
      await fetch(API, { method:'POST', body:fd });
      btn.disabled = false; btn.textContent = 'Envoyer';
      closeModal(); await loadAnnotations(); openPanel();
    });
    document.getElementById('uxnote-pwd-submit').addEventListener('click', checkPassword);
    document.getElementById('uxnote-pwd-input').addEventListener('keydown', (e) => {
      if (e.key === 'Enter') checkPassword();
    });
  }

  function checkPassword() {
    const val = document.getElementById('uxnote-pwd-input').value;
    const err = document.getElementById('uxnote-pwd-error');
    if (val === PASSWORD) {
      authenticated = true;
      document.getElementById('uxnote-pwd-overlay').classList.remove('open');
      err.style.display = 'none';
      loadAnnotations(); openPanel();
    } else {
      err.style.display = 'block';
      document.getElementById('uxnote-pwd-input').value = '';
      document.getElementById('uxnote-pwd-input').focus();
    }
  }

  document.uxnoteCloud = {
    focusPin: (id) => {
      // Scroller vers l'élément annoté sur la page (pas vers le pin fixed)
      const ann = annotations.find(a => a.id == id);
      if (ann && ann.xpath) {
        const el = findNodeByXPath(ann.xpath);
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'center' });
          // Flasher le cadre pour attirer l'attention
          el.style.outline = '3px solid #3ce65f';
          el.style.outlineOffset = '3px';
          setTimeout(() => {
            el.style.outline = '';
            el.style.outlineOffset = '';
            el.classList.add('uxnote-annotated');
          }, 1500);
          return;
        }
      }
      // Fallback : scroller vers l'item dans le panel
      const panelItem = document.querySelector('[data-id="' + id + '"]');
      if (panelItem) panelItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
    },
    resolve:   (id) => updateStatus(id, 'resolved'),
    unresolve: (id) => updateStatus(id, 'open'),
    deleteMine: async (id) => {
      if (projectArchived) return;
      if (!confirm('Supprimer votre annotation ?')) return;
      await fetch(`${API}?id=${id}&token=${encodeURIComponent(userToken)}`, { method:'DELETE' });
      await loadAnnotations();
    }
  };

  async function updateStatus(id, status) {
    if (projectArchived) return;
    await fetch(API, {
      method:'PATCH', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ id, status, actor: currentUser ? currentUser.name : 'Anonyme' })
    });
    await loadAnnotations();
  }

  // ─── XPath helpers — système exact de l'original UXnote ─────────────────
  function getXPath(node) {
    if (node === document.body) return '/html/body';
    const parts = [];
    while (node && node !== document) {
      let index = 1;
      let sibling = node.previousSibling;
      while (sibling) {
        if (sibling.nodeType === node.nodeType && sibling.nodeName === node.nodeName) index++;
        sibling = sibling.previousSibling;
      }
      const name = node.nodeType === 3 ? 'text()' : node.nodeName.toLowerCase();
      parts.unshift(name + '[' + index + ']');
      node = node.parentNode;
      if (!node || node.nodeType !== 1) break;
    }
    return '/' + parts.join('/');
  }

  function findNodeByXPath(xpath) {
    try {
      const result = document.evaluate(xpath, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null);
      return result.singleNodeValue;
    } catch(e) { return null; }
  }

  // Reproduit getVisibleRect() de l'original
  function getVisibleRect(el) {
    if (!el || !el.getBoundingClientRect) return null;
    let rect = el.getBoundingClientRect();
    if (!rect.width || !rect.height) return null;
    return rect;
  }

  // Reproduit positionMarker() de l'original exactement
  // Le marker est placé en position:absolute dans son offsetParent


  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function formatDate(ts) {
    return new Date(ts*1000).toLocaleDateString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
  }

  async function init() {
    createUI(); bindEvents();
    await checkProjectStatus();
    if (authenticated) {
      loadAnnotations();
      loadIntervenants();
    }

    // Refresh au resize et scroll (comme l'original)
    window.addEventListener('resize', renderPins);
    window.addEventListener('scroll', renderPins, { passive: true });

    setInterval(async () => {
      if (authenticated) {
        await checkProjectStatus();
        loadAnnotations();
      }
    }, 15000);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();

})();
