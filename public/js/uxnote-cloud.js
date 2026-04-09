/**
 * UX Note Cloud v2 — Script client
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

  const C = {
    primary: '#222339', accent: '#3ce65f', slate: '#757686',
    danger: '#e63946', success: '#2dc653', light: '#f4f5f7', white: '#ffffff',
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
    #uxnote-panel {
      position:fixed; top:0; left:0; width:360px; height:100vh;
      background:${C.white}; box-shadow:4px 0 28px ${C.shadow};
      z-index:999998; display:none; flex-direction:column;
      font-family:'Montserrat',sans-serif; font-size:14px; overflow:hidden;
    }
    #uxnote-panel.open { display:flex; }
    #uxnote-panel-header {
      background:${C.primary}; color:${C.white}; padding:18px 20px;
      display:flex; justify-content:space-between; align-items:center;
      flex-shrink:0; border-bottom:3px solid ${C.accent};
    }
    #uxnote-panel-header h3 { font-family:'Raleway',sans-serif; font-size:16px; font-weight:700; display:flex; align-items:center; gap:8px; }
    #uxnote-panel-header h3::before { content:''; display:inline-block; width:8px; height:8px; background:${C.accent}; border-radius:50%; }
    #uxnote-close-panel { background:none; border:none; color:${C.white}; cursor:pointer; font-size:20px; opacity:0.7; }
    #uxnote-close-panel:hover { opacity:1; }
    #uxnote-panel-body { flex:1; overflow-y:auto; padding:16px; background:${C.light}; }
    #uxnote-panel-footer { padding:12px 16px; border-top:1px solid #e2e4ef; background:${C.white}; flex-shrink:0; }
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
    }
    .uxnote-replies { margin-top:10px; padding-top:10px; border-top:1px dashed #e2e4ef; }
    .uxnote-reply-item { background:${C.light}; border-radius:7px; padding:8px 10px; margin-bottom:6px; font-size:12px; }
    .uxnote-reply-meta { color:${C.slate}; margin-bottom:3px; font-size:11px; }
    .uxnote-reply-text { color:${C.primary}; }
    .uxnote-annotation-actions { display:flex; gap:6px; flex-wrap:wrap; margin-top:8px; }
    .uxnote-btn-sm {
      padding:4px 10px; border-radius:5px; border:1px solid #e2e4ef;
      background:${C.white}; cursor:pointer; font-size:11px; color:#475569;
      transition:all 0.15s; font-family:'Montserrat',sans-serif; font-weight:500;
    }
    .uxnote-btn-sm:hover { background:${C.light}; }
    .uxnote-btn-sm.resolve { border-color:${C.accent}; color:#2ab54a; }
    .uxnote-btn-sm.reply-btn { border-color:${C.primary}; color:${C.primary}; }
    .uxnote-btn-sm.delete { border-color:${C.danger}; color:${C.danger}; }
    .uxnote-reply-form { margin-top:8px; display:none; }
    .uxnote-reply-form.open { display:block; }
    .uxnote-reply-form textarea {
      width:100%; padding:8px; border:1px solid #e2e4ef; border-radius:6px;
      font-size:12px; font-family:'Montserrat',sans-serif; resize:vertical; min-height:60px; margin-bottom:6px;
    }
    .uxnote-reply-form textarea:focus { outline:none; border-color:${C.accent}; }
    .uxnote-reply-actions { display:flex; gap:6px; justify-content:flex-end; }
    .uxnote-pin {
      position:absolute; width:28px; height:28px; border-radius:50% 50% 50% 0;
      transform:rotate(-45deg); display:flex; align-items:center; justify-content:center;
      cursor:pointer; z-index:99999; border:2px solid ${C.white};
      box-shadow:0 2px 8px ${C.shadow}; transition:transform 0.15s;
    }
    .uxnote-pin:hover { transform:rotate(-45deg) scale(1.15); }
    .uxnote-pin-number { transform:rotate(45deg); color:${C.white}; font-size:11px; font-weight:700; font-family:'Montserrat',sans-serif; }
    .uxnote-pin.status-open { background:${C.primary}; }
    .uxnote-pin.status-open.mine { background:#9b5de5; }
    .uxnote-pin.status-resolved { background:${C.accent}; }
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
    #uxnote-file-selected { font-size:11px; color:#2ab54a; font-weight:600; margin-bottom:8px; }
    #uxnote-modal-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:4px; }
    .uxnote-modal-btn {
      padding:9px 18px; border-radius:8px; border:none; cursor:pointer;
      font-size:13px; font-weight:600; font-family:'Montserrat',sans-serif; transition:all 0.15s;
    }
    .uxnote-modal-btn.cancel { background:${C.light}; color:${C.slate}; }
    .uxnote-modal-btn.submit { background:${C.primary}; color:${C.white}; border-left:3px solid ${C.accent}; }
    .uxnote-modal-btn.submit:hover { background:#2d2f4a; }
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
      letter-spacing:0.1em; font-family:'Montserrat',sans-serif;
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
    #uxnote-add-btn {
      width:100%; padding:10px; background:${C.primary}; color:${C.white};
      border:none; border-radius:8px; cursor:pointer; font-size:13px; font-weight:600;
      font-family:'Montserrat',sans-serif; border-left:3px solid ${C.accent}; transition:background 0.15s;
    }
    #uxnote-add-btn:hover { background:#2d2f4a; }
  `;
  document.head.appendChild(style);

  function createUI() {
    const bar = document.createElement('div');
    bar.id = 'uxnote-bar';
    bar.innerHTML = `
      <button id="uxnote-toggle-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg>
        <span id="uxnote-btn-label">Annoter</span>
      </button>`;
    document.body.appendChild(bar);

    const panel = document.createElement('div');
    panel.id = 'uxnote-panel';
    panel.innerHTML = `
      <div id="uxnote-panel-header">
        <h3>Annotations</h3>
        <button id="uxnote-close-panel" title="Fermer">✕</button>
      </div>
      <div id="uxnote-panel-body">
        <div class="uxnote-empty"><p>Aucune annotation sur cette page.</p></div>
      </div>
      <div id="uxnote-panel-footer">
        <button id="uxnote-add-btn">+ Ajouter une annotation</button>
      </div>`;
    document.body.appendChild(panel);

    const hint = document.createElement('div');
    hint.id = 'uxnote-cursor-hint';
    hint.textContent = 'Cliquez sur la zone à annoter — Échap pour annuler';
    document.body.appendChild(hint);

    const modalOv = document.createElement('div');
    modalOv.id = 'uxnote-modal-overlay';
    modalOv.innerHTML = `
      <div id="uxnote-modal">
        <h4>Nouvelle annotation</h4>
        <div id="uxnote-user-fields">
          <input id="uxnote-input-name" type="text" placeholder="Votre prénom / nom *" />
          <input id="uxnote-input-email" type="email" placeholder="Votre email (optionnel)" />
        </div>
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

  function renderPanel() {
    const body = document.getElementById('uxnote-panel-body');
    if (!body) return;
    if (annotations.length === 0) {
      body.innerHTML = `<div class="uxnote-empty"><p>Aucune annotation sur cette page.<br>Cliquez sur "Ajouter" pour commencer.</p></div>`;
      return;
    }
    body.innerHTML = annotations.map((a, i) => {
      const isMine  = a.author_token === userToken;
      const replies = (a.replies || []).map(r => `
        <div class="uxnote-reply-item">
          <div class="uxnote-reply-meta"><strong>${escHtml(r.author_name)}</strong> · ${formatDate(r.created_at)}</div>
          <div class="uxnote-reply-text">${escHtml(r.comment)}</div>
        </div>`).join('');
      return `
        <div class="uxnote-annotation-item ${a.status==='resolved'?'resolved':''} ${isMine?'mine':''}" data-id="${a.id}">
          <div class="uxnote-annotation-meta">
            <strong>#${i+1} ${escHtml(a.author_name)}</strong>
            ${isMine?' <span style="color:#9b5de5;font-weight:700">(moi)</span>':''}
            · ${formatDate(a.created_at)}
            ${a.status==='resolved'?' · <span style="color:#2ab54a">✓ Résolu</span>':''}
          </div>
          <div class="uxnote-annotation-text">${escHtml(a.comment)}</div>
          ${a.file_name?`<div class="uxnote-file-attach">📎 ${escHtml(a.file_name)}</div>`:''}
          ${replies?`<div class="uxnote-replies">${replies}</div>`:''}
          <div class="uxnote-reply-form" id="reply-form-${a.id}">
            <textarea placeholder="Votre réponse..."></textarea>
            <div class="uxnote-reply-actions">
              <button class="uxnote-btn-sm" onclick="document.getElementById('reply-form-${a.id}').classList.remove('open')">Annuler</button>
              <button class="uxnote-btn-sm resolve" onclick="submitReply(${a.id})">Envoyer</button>
            </div>
          </div>
          <div class="uxnote-annotation-actions">
            <button class="uxnote-btn-sm" onclick="document.uxnoteCloud.focusPin(${a.id})">📍 Voir</button>
            <button class="uxnote-btn-sm reply-btn" onclick="toggleReplyForm(${a.id})">↩ Répondre</button>
            ${a.status!=='resolved'
              ?`<button class="uxnote-btn-sm resolve" onclick="document.uxnoteCloud.resolve(${a.id})">✓ Résoudre</button>`
              :`<button class="uxnote-btn-sm" onclick="document.uxnoteCloud.unresolve(${a.id})">↩ Réouvrir</button>`}
            ${isMine?`<button class="uxnote-btn-sm delete" onclick="document.uxnoteCloud.deleteMine(${a.id})">🗑</button>`:''}
          </div>
        </div>`;
    }).join('');
  }

  function renderPins() {
    pinElements.forEach(p => p.remove());
    pinElements = [];
    annotations.forEach((a, i) => {
      const pin = document.createElement('div');
      pin.className = `uxnote-pin status-${a.status}${a.author_token===userToken?' mine':''}`;
      pin.style.left = a.pos_x + 'px';
      pin.style.top  = (a.pos_y + window.scrollY) + 'px';
      pin.innerHTML  = `<span class="uxnote-pin-number">${i+1}</span>`;
      pin.title      = `#${i+1} ${a.author_name}: ${a.comment}`;
      pin.addEventListener('click', () => {
        openPanel();
        setTimeout(() => {
          const el = document.querySelector(`[data-id="${a.id}"]`);
          if (el) el.scrollIntoView({behavior:'smooth', block:'center'});
        }, 100);
      });
      document.body.appendChild(pin);
      pinElements.push(pin);
    });
  }

  async function loadAnnotations() {
    try {
      const res  = await fetch(`${API}?project_id=${encodeURIComponent(PROJECT_ID)}&page_url=${encodeURIComponent(PAGE_URL)}`);
      const data = await res.json();
      annotations = data.annotations || [];
      renderPanel(); renderPins();
    } catch(e) { console.error('UX Note Cloud:', e); }
  }

  async function submitReply(annotationId) {
    const form    = document.getElementById(`reply-form-${annotationId}`);
    const ta      = form ? form.querySelector('textarea') : null;
    const comment = ta ? ta.value.trim() : '';
    if (!comment) return;
    if (!currentUser) { alert('Veuillez d\'abord créer une annotation pour vous identifier.'); return; }
    const fd = new FormData();
    fd.append('action', 'reply');
    fd.append('annotation_id', annotationId);
    fd.append('author_name',  currentUser.name);
    fd.append('author_email', currentUser.email || '');
    fd.append('author_token', userToken);
    fd.append('comment', comment);
    await fetch(API, {method:'POST', body:fd});
    if (ta) ta.value = '';
    if (form) form.classList.remove('open');
    await loadAnnotations();
  }

  function toggleReplyForm(id) {
    const form = document.getElementById(`reply-form-${id}`);
    if (form) form.classList.toggle('open');
  }

  function activateMode() {
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
    document.getElementById('uxnote-btn-label').textContent = 'Annoter';
  }

  function openPanel() { document.getElementById('uxnote-panel').classList.add('open'); }

  function openPasswordScreen() {
    document.getElementById('uxnote-pwd-overlay').classList.add('open');
    setTimeout(() => document.getElementById('uxnote-pwd-input').focus(), 50);
  }

  function openModal() {
    const overlay = document.getElementById('uxnote-modal-overlay');
    overlay.querySelector('#uxnote-input-text').value = '';
    document.getElementById('uxnote-file-selected').textContent = '';
    const fi = document.getElementById('uxnote-file-input');
    if (fi) fi.value = '';
    document.getElementById('uxnote-user-fields').style.display = currentUser ? 'none' : 'block';
    overlay.classList.add('open');
    setTimeout(() => overlay.querySelector('#uxnote-input-text').focus(), 50);
  }

  function closeModal() { document.getElementById('uxnote-modal-overlay').classList.remove('open'); }

  function bindEvents() {
    document.getElementById('uxnote-toggle-btn').addEventListener('click', () => {
      if (!authenticated) { openPasswordScreen(); return; }
      if (annotationMode) { deactivateMode(); openPanel(); } else { activateMode(); }
    });
    document.getElementById('uxnote-add-btn').addEventListener('click', () => {
      document.getElementById('uxnote-panel').classList.remove('open');
      activateMode();
    });
    document.getElementById('uxnote-close-panel').addEventListener('click', () => {
      document.getElementById('uxnote-panel').classList.remove('open');
    });
    document.addEventListener('click', (e) => {
      if (!annotationMode) return;
      if (e.target.closest('#uxnote-bar,#uxnote-panel,#uxnote-modal-overlay,.uxnote-pin,#uxnote-pwd-overlay')) return;
      e.preventDefault(); e.stopPropagation();
      pendingPos = {x: e.pageX, y: e.pageY - window.scrollY};
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
        currentUser = {name, email: emailEl ? emailEl.value.trim() : ''};
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
      fd.append('pos_x',        pendingPos ? pendingPos.x : 0);
      fd.append('pos_y',        pendingPos ? pendingPos.y : 0);
      const fileInput = document.getElementById('uxnote-file-input');
      if (fileInput && fileInput.files[0]) fd.append('file', fileInput.files[0]);
      await fetch(API, {method:'POST', body:fd});
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
      err.style.display = 'none'; openPanel();
    } else {
      err.style.display = 'block';
      document.getElementById('uxnote-pwd-input').value = '';
      document.getElementById('uxnote-pwd-input').focus();
    }
  }

  document.uxnoteCloud = {
    focusPin: (id) => {
      const idx = annotations.findIndex(a => a.id == id);
      if (idx !== -1 && pinElements[idx]) pinElements[idx].scrollIntoView({behavior:'smooth', block:'center'});
    },
    resolve:   (id) => updateStatus(id, 'resolved'),
    unresolve: (id) => updateStatus(id, 'open'),
    deleteMine: async (id) => {
      if (!confirm('Supprimer votre annotation ?')) return;
      await fetch(`${API}?id=${id}&token=${encodeURIComponent(userToken)}`, {method:'DELETE'});
      await loadAnnotations();
    }
  };

  async function updateStatus(id, status) {
    await fetch(API, {
      method:'PATCH', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({id, status, actor: currentUser ? currentUser.name : 'Anonyme'})
    });
    await loadAnnotations();
  }

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function formatDate(ts) {
    return new Date(ts*1000).toLocaleDateString('fr-FR',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'});
  }

  function init() {
    createUI(); bindEvents();
    if (authenticated) loadAnnotations();
    setInterval(() => { if (authenticated) loadAnnotations(); }, 15000);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
