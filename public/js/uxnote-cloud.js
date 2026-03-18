/**
 * UX Note Cloud - Script client
 * Inspiré de UX Note (MIT) — ninefortyonestudio
 * Modifié pour backend collaboratif PHP/SQLite
 * 
 * Usage : <script src="https://votre-serveur/js/uxnote-cloud.js" data-project-id="mon-site"></script>
 */
(function () {
  'use strict';

  // ─── Config ────────────────────────────────────────────────────────────────
  const currentScript = document.currentScript || (function () {
    const scripts = document.getElementsByTagName('script');
    return scripts[scripts.length - 1];
  })();

  const API_BASE = currentScript.src.replace(/\/js\/uxnote-cloud\.js.*/, '');
  const PROJECT_ID = currentScript.getAttribute('data-project-id') || window.location.hostname;
  const PAGE_URL = window.location.href;

  // ─── State ─────────────────────────────────────────────────────────────────
  let annotationMode = false;
  let currentUser = localStorage.getItem('uxnote_user') ? JSON.parse(localStorage.getItem('uxnote_user')) : null;
  let annotations = [];
  let pinElements = [];

  // ─── Styles ────────────────────────────────────────────────────────────────
  const style = document.createElement('style');
  style.textContent = `
    #uxnote-bar {
      position: fixed; bottom: 20px; right: 20px; z-index: 999999;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-size: 14px;
    }
    #uxnote-toggle-btn {
      background: #2563eb; color: #fff; border: none; border-radius: 50px;
      padding: 10px 18px; cursor: pointer; font-size: 14px; font-weight: 600;
      box-shadow: 0 4px 14px rgba(37,99,235,0.4); display: flex; align-items: center; gap: 8px;
      transition: background 0.2s;
    }
    #uxnote-toggle-btn:hover { background: #1d4ed8; }
    #uxnote-toggle-btn.active { background: #dc2626; }
    #uxnote-toggle-btn.active:hover { background: #b91c1c; }
    #uxnote-panel {
      position: fixed; top: 0; right: 0; width: 340px; height: 100vh;
      background: #fff; box-shadow: -4px 0 24px rgba(0,0,0,0.12);
      z-index: 999998; display: none; flex-direction: column;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-size: 14px; overflow: hidden;
    }
    #uxnote-panel.open { display: flex; }
    #uxnote-panel-header {
      background: #2563eb; color: #fff; padding: 16px 20px;
      display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;
    }
    #uxnote-panel-header h3 { margin: 0; font-size: 16px; }
    #uxnote-close-panel { background: none; border: none; color: #fff; cursor: pointer; font-size: 20px; }
    #uxnote-panel-body { flex: 1; overflow-y: auto; padding: 16px; }
    #uxnote-panel-footer { padding: 12px 16px; border-top: 1px solid #e5e7eb; flex-shrink: 0; }
    .uxnote-annotation-item {
      border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-bottom: 10px;
      background: #f9fafb; position: relative;
    }
    .uxnote-annotation-item.resolved { opacity: 0.5; border-color: #6ee7b7; background: #f0fdf4; }
    .uxnote-annotation-meta { font-size: 11px; color: #6b7280; margin-bottom: 6px; }
    .uxnote-annotation-text { color: #111827; line-height: 1.5; margin-bottom: 8px; }
    .uxnote-annotation-actions { display: flex; gap: 6px; flex-wrap: wrap; }
    .uxnote-btn-sm {
      padding: 4px 10px; border-radius: 4px; border: 1px solid #d1d5db;
      background: #fff; cursor: pointer; font-size: 12px; color: #374151;
      transition: background 0.15s;
    }
    .uxnote-btn-sm:hover { background: #f3f4f6; }
    .uxnote-btn-sm.resolve { border-color: #10b981; color: #10b981; }
    .uxnote-btn-sm.resolve:hover { background: #ecfdf5; }
    .uxnote-btn-sm.unresolve { border-color: #f59e0b; color: #f59e0b; }
    .uxnote-btn-sm.delete { border-color: #ef4444; color: #ef4444; }
    .uxnote-btn-sm.delete:hover { background: #fef2f2; }
    .uxnote-pin {
      position: absolute; width: 28px; height: 28px; border-radius: 50% 50% 50% 0;
      transform: rotate(-45deg); display: flex; align-items: center; justify-content: center;
      cursor: pointer; z-index: 99999; border: 2px solid #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2); transition: transform 0.15s;
    }
    .uxnote-pin:hover { transform: rotate(-45deg) scale(1.15); }
    .uxnote-pin-number {
      transform: rotate(45deg); color: #fff; font-size: 11px; font-weight: 700;
      font-family: -apple-system, sans-serif;
    }
    .uxnote-pin.status-open { background: #2563eb; }
    .uxnote-pin.status-resolved { background: #10b981; }
    #uxnote-cursor-hint {
      position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%);
      background: rgba(37,99,235,0.92); color: #fff; padding: 14px 24px;
      border-radius: 10px; font-size: 15px; font-weight: 600; z-index: 999997;
      pointer-events: none; display: none;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    #uxnote-modal-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 9999999; display: none;
      align-items: center; justify-content: center;
    }
    #uxnote-modal-overlay.open { display: flex; }
    #uxnote-modal {
      background: #fff; border-radius: 12px; padding: 24px; width: 360px;
      box-shadow: 0 20px 48px rgba(0,0,0,0.2);
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }
    #uxnote-modal h4 { margin: 0 0 16px; font-size: 16px; color: #111827; }
    #uxnote-modal input, #uxnote-modal textarea {
      width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #d1d5db;
      border-radius: 6px; font-size: 14px; margin-bottom: 12px; outline: none;
      font-family: inherit;
    }
    #uxnote-modal input:focus, #uxnote-modal textarea:focus { border-color: #2563eb; }
    #uxnote-modal textarea { resize: vertical; min-height: 90px; }
    #uxnote-modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
    .uxnote-modal-btn {
      padding: 8px 18px; border-radius: 6px; border: none; cursor: pointer;
      font-size: 14px; font-weight: 600;
    }
    .uxnote-modal-btn.cancel { background: #f3f4f6; color: #374151; }
    .uxnote-modal-btn.submit { background: #2563eb; color: #fff; }
    .uxnote-modal-btn.submit:hover { background: #1d4ed8; }
    .uxnote-empty { text-align: center; color: #9ca3af; padding: 32px 16px; }
    .uxnote-empty svg { margin-bottom: 12px; opacity: 0.4; }
    body.uxnote-mode-active { cursor: crosshair !important; }
    body.uxnote-mode-active * { cursor: crosshair !important; }
  `;
  document.head.appendChild(style);

  // ─── DOM ───────────────────────────────────────────────────────────────────
  function createBar() {
    const bar = document.createElement('div');
    bar.id = 'uxnote-bar';
    bar.innerHTML = `
      <button id="uxnote-toggle-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg>
        <span id="uxnote-btn-label">Annoter</span>
      </button>
    `;
    document.body.appendChild(bar);

    const panel = document.createElement('div');
    panel.id = 'uxnote-panel';
    panel.innerHTML = `
      <div id="uxnote-panel-header">
        <h3>💬 Annotations</h3>
        <button id="uxnote-close-panel">✕</button>
      </div>
      <div id="uxnote-panel-body">
        <div class="uxnote-empty">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          <p>Aucune annotation</p>
          <p style="font-size:12px">Cliquez sur "Annoter" puis<br>sur n'importe quelle zone de la page</p>
        </div>
      </div>
      <div id="uxnote-panel-footer">
        <button id="uxnote-open-mode-btn" style="width:100%;padding:9px;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px;font-weight:600;">
          + Ajouter une annotation
        </button>
      </div>
    `;
    document.body.appendChild(panel);

    const hint = document.createElement('div');
    hint.id = 'uxnote-cursor-hint';
    hint.textContent = '🖊 Cliquez sur la zone à annoter — Échap pour annuler';
    document.body.appendChild(hint);

    const modalOverlay = document.createElement('div');
    modalOverlay.id = 'uxnote-modal-overlay';
    modalOverlay.innerHTML = `
      <div id="uxnote-modal">
        <h4>📝 Nouvelle annotation</h4>
        ${!currentUser ? `
          <input id="uxnote-input-name" type="text" placeholder="Votre prénom / nom *" />
          <input id="uxnote-input-email" type="email" placeholder="Votre email (optionnel)" />
        ` : `<p style="color:#6b7280;font-size:13px;margin-bottom:12px">Connecté en tant que <strong>${currentUser.name}</strong></p>`}
        <textarea id="uxnote-input-text" placeholder="Décrivez votre annotation..."></textarea>
        <div id="uxnote-modal-actions">
          <button class="uxnote-modal-btn cancel" id="uxnote-modal-cancel">Annuler</button>
          <button class="uxnote-modal-btn submit" id="uxnote-modal-submit">Envoyer</button>
        </div>
      </div>
    `;
    document.body.appendChild(modalOverlay);
  }

  // ─── Annotation rendering ──────────────────────────────────────────────────
  function renderPanel() {
    const body = document.getElementById('uxnote-panel-body');
    if (!body) return;
    if (annotations.length === 0) {
      body.innerHTML = `
        <div class="uxnote-empty">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          <p>Aucune annotation</p>
          <p style="font-size:12px">Cliquez sur "+ Ajouter" puis<br>sur n'importe quelle zone de la page</p>
        </div>`;
      return;
    }
    body.innerHTML = annotations.map((a, i) => `
      <div class="uxnote-annotation-item ${a.status === 'resolved' ? 'resolved' : ''}" data-id="${a.id}">
        <div class="uxnote-annotation-meta">
          <strong>#${i + 1} ${escHtml(a.author_name)}</strong>
          ${a.author_email ? `&lt;${escHtml(a.author_email)}&gt;` : ''}
          · ${formatDate(a.created_at)}
          ${a.status === 'resolved' ? ' · <span style="color:#10b981">✓ Résolu</span>' : ''}
        </div>
        <div class="uxnote-annotation-text">${escHtml(a.comment)}</div>
        <div class="uxnote-annotation-actions">
          <button class="uxnote-btn-sm" onclick="document.uxnoteCloud.focusPin(${a.id})">📍 Voir</button>
          ${a.status !== 'resolved'
            ? `<button class="uxnote-btn-sm resolve" onclick="document.uxnoteCloud.resolve(${a.id})">✓ Résoudre</button>`
            : `<button class="uxnote-btn-sm unresolve" onclick="document.uxnoteCloud.unresolve(${a.id})">↩ Réouvrir</button>`}
          <button class="uxnote-btn-sm delete" onclick="document.uxnoteCloud.deleteAnnotation(${a.id})">🗑</button>
        </div>
      </div>
    `).join('');
  }

  function renderPins() {
    pinElements.forEach(p => p.remove());
    pinElements = [];
    annotations.forEach((a, i) => {
      const pin = document.createElement('div');
      pin.className = `uxnote-pin status-${a.status}`;
      pin.style.left = `${a.pos_x}px`;
      pin.style.top = `${a.pos_y + window.scrollY}px`;
      pin.innerHTML = `<span class="uxnote-pin-number">${i + 1}</span>`;
      pin.title = `#${i + 1} ${a.author_name}: ${a.comment}`;
      pin.addEventListener('click', () => {
        openPanel();
        setTimeout(() => {
          const el = document.querySelector(`[data-id="${a.id}"]`);
          if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
      });
      document.body.appendChild(pin);
      pinElements.push(pin);
    });
  }

  // ─── API calls ─────────────────────────────────────────────────────────────
  async function loadAnnotations() {
    try {
      const res = await fetch(`${API_BASE}/api/annotations.php?project_id=${encodeURIComponent(PROJECT_ID)}&page_url=${encodeURIComponent(PAGE_URL)}`);
      const data = await res.json();
      annotations = data.annotations || [];
      renderPanel();
      renderPins();
    } catch (e) { console.error('UX Note Cloud: erreur chargement', e); }
  }

  async function saveAnnotation(payload) {
    const res = await fetch(`${API_BASE}/api/annotations.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    return res.json();
  }

  async function updateStatus(id, status) {
    await fetch(`${API_BASE}/api/annotations.php`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, status })
    });
    await loadAnnotations();
  }

  async function deleteAnnotationById(id) {
    await fetch(`${API_BASE}/api/annotations.php?id=${id}`, { method: 'DELETE' });
    await loadAnnotations();
  }

  // ─── Mode annotation ───────────────────────────────────────────────────────
  let pendingPos = null;

  function activateMode() {
    annotationMode = true;
    document.body.classList.add('uxnote-mode-active');
    document.getElementById('uxnote-cursor-hint').style.display = 'block';
    const btn = document.getElementById('uxnote-toggle-btn');
    btn.classList.add('active');
    document.getElementById('uxnote-btn-label').textContent = 'Annuler';
    document.getElementById('uxnote-panel').classList.remove('open');
  }

  function deactivateMode() {
    annotationMode = false;
    document.body.classList.remove('uxnote-mode-active');
    document.getElementById('uxnote-cursor-hint').style.display = 'none';
    const btn = document.getElementById('uxnote-toggle-btn');
    btn.classList.remove('active');
    document.getElementById('uxnote-btn-label').textContent = 'Annoter';
  }

  function openPanel() {
    document.getElementById('uxnote-panel').classList.add('open');
  }

  function openModal() {
    // Rebuild modal pour reset champs
    const overlay = document.getElementById('uxnote-modal-overlay');
    overlay.querySelector('#uxnote-input-text').value = '';
    if (overlay.querySelector('#uxnote-input-name')) overlay.querySelector('#uxnote-input-name').value = '';
    overlay.classList.add('open');
    setTimeout(() => {
      const ta = overlay.querySelector('#uxnote-input-text');
      if (ta) ta.focus();
    }, 50);
  }

  function closeModal() {
    document.getElementById('uxnote-modal-overlay').classList.remove('open');
  }

  // ─── Event listeners ───────────────────────────────────────────────────────
  function bindEvents() {
    // Toggle bouton principal
    document.getElementById('uxnote-toggle-btn').addEventListener('click', () => {
      if (annotationMode) {
        deactivateMode();
        openPanel();
      } else {
        activateMode();
      }
    });

    // Bouton panel footer
    document.getElementById('uxnote-open-mode-btn').addEventListener('click', () => {
      document.getElementById('uxnote-panel').classList.remove('open');
      activateMode();
    });

    // Fermer panel
    document.getElementById('uxnote-close-panel').addEventListener('click', () => {
      document.getElementById('uxnote-panel').classList.remove('open');
    });

    // Clic sur la page en mode annotation
    document.addEventListener('click', (e) => {
      if (!annotationMode) return;
      if (e.target.closest('#uxnote-bar') || e.target.closest('#uxnote-panel') ||
          e.target.closest('#uxnote-modal-overlay') || e.target.closest('.uxnote-pin')) return;

      e.preventDefault();
      e.stopPropagation();
      pendingPos = { x: e.pageX, y: e.pageY - window.scrollY };
      deactivateMode();
      openModal();
    }, true);

    // Échap
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        if (annotationMode) deactivateMode();
        closeModal();
      }
    });

    // Annuler modal
    document.getElementById('uxnote-modal-cancel').addEventListener('click', closeModal);
    document.getElementById('uxnote-modal-overlay').addEventListener('click', (e) => {
      if (e.target === e.currentTarget) closeModal();
    });

    // Soumettre annotation
    document.getElementById('uxnote-modal-submit').addEventListener('click', async () => {
      const text = document.getElementById('uxnote-input-text').value.trim();
      if (!text) { document.getElementById('uxnote-input-text').focus(); return; }

      let author = currentUser;
      if (!author) {
        const nameEl = document.getElementById('uxnote-input-name');
        const emailEl = document.getElementById('uxnote-input-email');
        const name = nameEl ? nameEl.value.trim() : '';
        if (!name) { nameEl.focus(); return; }
        author = { name, email: emailEl ? emailEl.value.trim() : '' };
        localStorage.setItem('uxnote_user', JSON.stringify(author));
        currentUser = author;
      }

      const btn = document.getElementById('uxnote-modal-submit');
      btn.disabled = true;
      btn.textContent = 'Envoi...';

      await saveAnnotation({
        action: 'create',
        project_id: PROJECT_ID,
        page_url: PAGE_URL,
        author_name: author.name,
        author_email: author.email || '',
        comment: text,
        pos_x: pendingPos ? pendingPos.x : 0,
        pos_y: pendingPos ? pendingPos.y : 0
      });

      btn.disabled = false;
      btn.textContent = 'Envoyer';
      closeModal();
      await loadAnnotations();
      openPanel();
    });
  }

  // ─── API publique ──────────────────────────────────────────────────────────
  document.uxnoteCloud = {
    focusPin: (id) => {
      const idx = annotations.findIndex(a => a.id == id);
      if (idx === -1) return;
      const pin = pinElements[idx];
      if (pin) { pin.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    },
    resolve: (id) => updateStatus(id, 'resolved'),
    unresolve: (id) => updateStatus(id, 'open'),
    deleteAnnotation: async (id) => {
      if (confirm('Supprimer cette annotation ?')) await deleteAnnotationById(id);
    }
  };

  // ─── Utils ─────────────────────────────────────────────────────────────────
  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function formatDate(ts) {
    const d = new Date(ts * 1000);
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
  }

  // ─── Init ──────────────────────────────────────────────────────────────────
  function init() {
    createBar();
    bindEvents();
    loadAnnotations();
    // Polling toutes les 15s pour le temps réel
    setInterval(loadAnnotations, 15000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
