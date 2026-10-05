/**
 * notify.js - message notifications on every page (logged-in users).
 * Every 5 seconds it asks php/api_notifications.php for unread messages and then:
 *   1. updates the unread badge on the chat icon, the Chats link and the browser tab title
 *   2. refreshes the short message panel opened from the chat icon
 *   3. shows a pop-up (toast) when a NEW message arrives from the other person
 */
(() => {
  const btn = document.getElementById('notifBtn');
  if (!btn) return;
  const panel = document.getElementById('notifPanel'), list = document.getElementById('notifList');
  const timeEl = document.getElementById('notifTime'), toasts = document.getElementById('toasts');
  const badges = document.querySelectorAll('.js-badge');
  const baseTitle = document.title, KEY = 'ft_seen', INTERVAL = 5000;
  const chatEl = document.getElementById('chat');
  const openChat = chatEl ? Number(chatEl.dataset.enquiry) : null;   // do not pop up for the chat you are reading

  let seen = new Set(), first = true, timer = null;
  try { const s = sessionStorage.getItem(KEY); if (s !== null) { seen = new Set(JSON.parse(s)); first = false; } } catch (e) {}
  const save = () => { try { sessionStorage.setItem(KEY, JSON.stringify([...seen].slice(-200))); } catch (e) {} };

  function setBadge(n) {
    badges.forEach(b => { b.textContent = n > 99 ? '99+' : String(n); b.hidden = n === 0; });
    btn.setAttribute('aria-label', n ? 'Messages, ' + n + ' unread' : 'Messages');
    document.title = n ? '(' + n + ') ' + baseTitle : baseTitle;
  }
  function el(tag, cls, text) { const x = document.createElement(tag); if (cls) x.className = cls; if (text !== undefined) x.textContent = text; return x; }
  function avatar(html) { const s = el('span', 'notif-av'); s.innerHTML = html; return s; }   // html is built and escaped by the server

  /* ---------- short message panel ---------- */
  function renderPanel(data) {
    list.replaceChildren();
    if (!data.recent.length) { list.appendChild(el('li', 'notif-empty', 'No conversations yet.')); }
    data.recent.forEach(r => {
      const li = el('li'), a = el('a', 'notif-item' + (r.unread ? ' unread' : ''));
      a.href = 'chat.php?enquiry_id=' + r.enquiry_id;
      const body = el('div', 'notif-body'), top = el('div', 'notif-top');
      top.append(el('strong', '', r.who), el('small', '', r.time));
      body.append(top, el('small', 'notif-crop', r.crop), el('p', '', (r.mine ? 'You: ' : '') + r.text));
      a.append(avatar(r.avatar), body);
      if (r.unread) a.appendChild(el('span', 'nav-badge', String(r.unread)));
      li.appendChild(a); list.appendChild(li);
    });
    timeEl.textContent = 'Updated ' + data.time;
  }

  /* ---------- pop-up toasts ---------- */
  function toast(group) {
    const latest = group[0], t = el('div', 'toast');
    t.setAttribute('role', 'alert');
    const txt = el('div', 'toast-text');
    txt.append(el('strong', '', latest.who + ' (' + latest.crop + ')'), el('p', '', latest.text));
    if (group.length > 1) txt.appendChild(el('small', '', '+' + (group.length - 1) + ' more new message' + (group.length > 2 ? 's' : '')));
    const open = el('a', 'btn small', 'Open chat'); open.href = 'chat.php?enquiry_id=' + latest.enquiry_id;
    const close = el('button', 'toast-close', '×'); close.type = 'button'; close.setAttribute('aria-label', 'Dismiss notification');
    close.addEventListener('click', () => t.remove());
    t.append(avatar(latest.avatar), txt, open, close);
    while (toasts.children.length >= 3) toasts.firstChild.remove();
    toasts.appendChild(t);
    setTimeout(() => t.remove(), 8000);
  }

  async function poll() {
    try {
      const res = await fetch('php/api_notifications.php', { cache: 'no-store' });
      if (res.status === 401) { clearInterval(timer); setBadge(0); timeEl.textContent = 'Session expired'; return; }
      if (!res.ok) throw new Error();
      const data = await res.json();
      setBadge(data.unread);
      renderPanel(data);

      const fresh = data.items.filter(i => !seen.has(i.key));
      fresh.forEach(i => seen.add(i.key));
      if (!first && panel.hidden) {                                  // no pop-up while the panel is open (it already updates)
        const groups = {};
        fresh.filter(i => i.enquiry_id !== openChat).forEach(i => (groups[i.enquiry_id] = groups[i.enquiry_id] || []).push(i));
        Object.values(groups).forEach(toast);
      }
      first = false; save();
    } catch (e) { timeEl.textContent = 'Connection problem - retrying'; }
  }

  /* ---------- open / close the panel ---------- */
  function setOpen(open) {
    panel.hidden = !open; btn.setAttribute('aria-expanded', String(open));
    if (open) poll();
  }
  btn.addEventListener('click', ev => { ev.stopPropagation(); setOpen(panel.hidden); });
  document.addEventListener('click', ev => { if (!panel.hidden && !panel.contains(ev.target)) setOpen(false); });
  document.addEventListener('keydown', ev => { if (ev.key === 'Escape' && !panel.hidden) { setOpen(false); btn.focus(); } });
  document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

  poll();
  timer = setInterval(() => { if (!document.hidden) poll(); }, INTERVAL);
})();
