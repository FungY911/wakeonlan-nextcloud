(() => {
  const root = document.getElementById('wol-app');
  if (!root) return;
  const statusUrl = root.dataset.statusUrl;
  const tokenEl = document.querySelector('meta[name="requesttoken"]');
  const requesttoken = tokenEl ? tokenEl.content : '';

  function applyStatus(list) {
    list.forEach(item => {
      const dot = document.querySelector('.wol-dot[data-id="' + item.id + '"]');
      if (!dot) return;
      dot.classList.toggle('unknown', false);
      dot.classList.toggle('online', !!item.online);
      dot.classList.toggle('offline', !item.online);
      dot.title = item.online ? 'Online' : 'Offline';
    });
  }

    function showMessage(text, isError = false, ttl = 5000) {
      if (!msgModal || !msgText) return alert(text);
      q('#wol-msg-title').textContent = isError ? 'Error' : 'Success';
      msgText.textContent = text;
      openModal(msgModal);
      if (ttl > 0) setTimeout(() => closeModal(msgModal), ttl);
    }

  async function poll() {
    try {
      const res = await fetch(statusUrl, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'requesttoken': OC.requestToken,   // ← add this
        },
        credentials: 'same-origin',
        cache: 'no-store',
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      if (data && Array.isArray(data.devices)) applyStatus(data.devices);
    } catch (e) {
      // console.warn('Status poll failed', e);
    }
  }

  poll();
  setInterval(poll, 15000);
})();

(function () {
  // ---------- helpers ----------
  function ocUrl(path) { return OC.generateUrl(path); }

  async function postUrl(url, formOrData) {
    let body;
    if (formOrData instanceof FormData) body = formOrData;
    else if (formOrData && typeof formOrData === 'object') {
      body = new FormData(); for (const [k,v] of Object.entries(formOrData)) body.set(k,v);
    } else body = new FormData();

    body.set('requesttoken', OC.requestToken);

    const res = await fetch(url, {
      method: 'POST',
      headers: { 'requesttoken': OC.requestToken, 'Accept': 'application/json, text/plain, */*' },
      credentials: 'same-origin',
      body
    });

    let data, text = '';
    const ct = res.headers.get('content-type') || '';
    if (ct.includes('application/json')) { try { data = await res.json(); } catch {}
    } else { try { text = await res.text(); } catch {} }

    if (!res.ok || (data && data.error)) {
      const msg = (data && data.error) ? data.error
                : text ? text.slice(0, 300)
                : `HTTP ${res.status}`;
      const err = new Error(msg);
      if (data && data.field) err.field = data.field;
      err.status = res.status;
      throw err;
    }
    return data || {};
  }


  function q(sel, root = document) { return root.querySelector(sel); }
  function qa(sel, root = document) { return Array.from(root.querySelectorAll(sel)); }

  // ---------- modal controls ----------
  function openModal(el) {
    el.classList.add('is-open');
    // focus first focusable element
    const f = el.querySelector('input,button,select,textarea');
    if (f) setTimeout(()=>f.focus(), 10);
  }
  function closeModal(el) { el.classList.remove('is-open'); }

  function wireModal(id) {
    const m = q(id);
    if (!m) return null;
    m.addEventListener('click', (e) => {
      if (e.target.matches('[data-close="true"], .modal__backdrop')) closeModal(m);
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && m.classList.contains('is-open')) closeModal(m);
    });
    return m;
  }

  // message modal (auto-close)
  const msgModal = wireModal('#wol-msg-modal');
  const msgText  = q('#wol-msg-text');
  function showMessage(text, isError = false, ttl = 0) {
    if (!msgModal || !msgText) return alert(text);
    q('#wol-msg-title').textContent = isError ? 'Error' : 'Success';
    msgText.textContent = text;
    openModal(msgModal);
    if (ttl > 0) setTimeout(() => closeModal(msgModal), ttl);
  }

  // ---------- ADD device modal ----------
  const addModal = wireModal('#wol-add-modal');
  const addBtn   = q('#wol-open-add');

  if (addBtn && addModal) addBtn.addEventListener('click', () => openModal(addModal));

  const form = document.getElementById('wol-add-form');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      // Before building payload, let the browser validate required/min/max, etc.
      if (!form.reportValidity()) return;

      const payload   = new URLSearchParams();
      const intFields = { port: { min: 1, max: 65535, fallback: 9 } };

      for (const [k, v] of new FormData(form).entries()) {
        let s = typeof v === 'string' ? v.trim() : '';

        // If it's a declared int field, validate & normalize it
        if (k in intFields) {
          if (s === '') s = String(intFields[k].fallback);          // default
          const n = Number(s);
          s = String(n); // send as string, server will parse as int
        }

        payload.append(k, s);
      }

      try {
        const res = await fetch(OC.generateUrl('/apps/wol/device/add'), {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'requesttoken': OC.requestToken
          },
          body: payload.toString(),
          credentials: 'same-origin'
        });
        const data = await res.json();
        if (!res.ok || !data.ok) {
          throw new Error((data && data.error) ? data.error : ('HTTP ' + res.status));
        }
        // success: simplest is a reload to show the new device
        window.location.reload();
      } catch (err) {
        console.error('Add failed', err);
        showMessage(err.message, true)
      }
    });
  }

  // ---------- device grid interactions ----------
  const grid = q('#wol-grid');

  function renderCard(d) {
    const wrap = document.createElement('div');
    wrap.className = 'device-card';
    wrap.setAttribute('data-id', d.id);

    wrap.innerHTML = `
      <div class="device-card__header">
        <div class="device-name" title="${escapeHtml(d.name || '')}">${escapeHtml(d.name || '')}</div>
      </div>
      <div class="device-meta">
        <div><span class="meta-key">MAC</span><span class="meta-val">${escapeHtml(d.mac)}</span></div>
        <div><span class="meta-key">Broadcast</span><span class="meta-val">${escapeHtml(d.broadcast)}</span></div>
        <div><span class="meta-key">Port</span><span class="meta-val">${d.port|0}</span></div>
      </div>
      <div class="device-actions">
        <button class="btn primary wake-btn" data-devicename="${escapeHtml(d.name || '')}">Wake</button>
        <button class="btn subtle del-btn">Delete</button>
      </div>
    `;
    return wrap;
  }

  function ensureGridExists() {
    if (!q('#wol-grid')) {
      const gridEl = document.createElement('div');
      gridEl.id = 'wol-grid';
      gridEl.className = 'devices-grid';
      q('#wol-app').appendChild(gridEl);
      // also remove empty state if present
      const empty = q('.wol-empty');
      if (empty) empty.remove();
    }
  }

  function findCard(el) { return el.closest('.device-card'); }

  // Wake
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.wake-btn');
    if (!btn) return;
    const card = findCard(btn);
    if (!card) return;
    const id = card.getAttribute('data-id');
    const name = btn.getAttribute('data-devicename') || '';
    btn.disabled = true;
    try {
      await postUrl(ocUrl('/apps/wol/device/' + id + '/wake'));
      showMessage(`Magic packet sent${name ? ' to ' + name : ''}.`);
    } catch (err) {
      showMessage('Wake failed: ' + err.message, true);
    } finally {
      btn.disabled = false;
    }
  });

  // Delete (simple confirm)
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.del-btn');
    if (!btn) return;
    const card = findCard(btn);
    if (!card) return;
    const id = card.getAttribute('data-id');
    const ok = confirm('Delete this device?');
    if (!ok) return;
    btn.disabled = true;
    try {
      await postUrl(ocUrl('/apps/wol/device/' + id + '/delete'));
      card.remove();
      showMessage('Device deleted.');
    } catch (err) {
      showMessage('Delete failed: ' + err.message, true);
    } finally {
      btn.disabled = false;
    }
  });

  // utils
  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
  }
})();
