/**
 * live.js - AJAX features (fetch API + polling).
 * 1. Market board: live price/stock updates and a live cost calculator.
 * 2. Chat: sends and receives messages without reloading the page.
 * Data arrives as JSON from php/api_prices.php and php/api_chat.php.
 */
document.addEventListener("DOMContentLoaded", () => {
  const money = n => "$" + Number(n).toFixed(2);

  /* ---------- 1. Live prices + cost calculator ---------- */
  const table = document.getElementById("boardTable");
  if (table) {
    const rows = () => table.querySelectorAll("tbody tr[data-id]");
    function calc(tr) {
      const inp = tr.querySelector(".order-qty"), out = tr.querySelector(".est-cost");
      const q = parseFloat(inp.value), max = parseFloat(tr.dataset.qty);
      if (!inp.value || isNaN(q) || q <= 0) out.textContent = "-";
      else if (q > max) out.textContent = "Only " + max + " kg available";
      else out.textContent = money(q * parseFloat(tr.dataset.price));
    }
    rows().forEach(tr => tr.querySelector(".order-qty").addEventListener("input", () => calc(tr)));

    const status = document.getElementById("liveStatus"), banner = document.getElementById("newStock");
    async function refresh() {
      if (document.hidden) return;
      try {
        const res = await fetch("php/api_prices.php", { cache: "no-store" });
        if (!res.ok) throw new Error("bad response");
        const data = await res.json();
        const map = new Map(data.items.map(i => [String(i.harvest_id), i]));
        rows().forEach(tr => {
          const it = map.get(tr.dataset.id);
          if (!it || it.status !== "available") { tr.classList.add("gone"); return; }
          const oldPrice = parseFloat(tr.dataset.price), newPrice = parseFloat(it.price_per_kg);
          if (oldPrice !== newPrice) {
            const cell = tr.querySelector(".price-cell");
            cell.textContent = money(newPrice) + (newPrice > oldPrice ? " \u25B2" : " \u25BC");
            cell.classList.add(newPrice > oldPrice ? "up" : "down");
            tr.dataset.price = newPrice;
          }
          tr.dataset.qty = it.quantity_kg;
          tr.querySelector(".qty-cell").textContent = Number(it.quantity_kg).toFixed(2);
          calc(tr);
        });
        const known = new Set([...rows()].map(r => r.dataset.id));
        if (data.items.some(i => i.status === "available" && !known.has(String(i.harvest_id)))) banner.hidden = false;
        status.textContent = "Live prices updated at " + data.time;
      } catch (err) { status.textContent = "Live updates paused (connection problem)."; }
    }
    setInterval(refresh, 8000);
  }

  /* ---------- 2. Chat ---------- */
  const chat = document.getElementById("chat");
  if (chat) {
    const log = document.getElementById("chatLog"), form = chat.querySelector("form");
    const note = document.getElementById("chatStatus"), me = chat.dataset.role;
    let last = parseInt(chat.dataset.last, 10) || 0;
    log.scrollTop = log.scrollHeight;

    function add(m) {
      const d = document.createElement("div");
      d.className = "msg " + m.sender + (m.sender === me ? " mine" : "");
      const who = document.createElement("strong"), p = document.createElement("p"), t = document.createElement("small");
      who.textContent = m.sender === me ? "You" : m.sender.charAt(0).toUpperCase() + m.sender.slice(1);
      p.textContent = m.body;            // textContent (not innerHTML) -> no XSS
      t.textContent = m.sent_at;
      d.append(who, p, t); log.appendChild(d);
      last = Math.max(last, parseInt(m.message_id, 10));
    }
    function show(list) {
      const atBottom = log.scrollHeight - log.scrollTop - log.clientHeight < 80;
      list.forEach(add);
      if (list.length && atBottom) log.scrollTop = log.scrollHeight;
    }
    async function poll() {
      try {
        const res = await fetch("php/api_chat.php?enquiry_id=" + chat.dataset.enquiry + "&after=" + last, { cache: "no-store" });
        if (res.status === 401) { note.textContent = "Session expired - please log in again."; return; }
        if (!res.ok) throw new Error();
        show((await res.json()).messages); note.textContent = "New messages appear automatically.";
      } catch (e) { note.textContent = "Reconnecting..."; }
    }
    setInterval(() => { if (!document.hidden) poll(); }, 3000);

    form.addEventListener("submit", async ev => {
      ev.preventDefault();
      const box = form.elements.body;
      if (!box.value.trim()) { box.focus(); return; }
      const fd = new FormData(form); fd.append("after", last);
      try {
        const res = await fetch("php/api_chat.php", { method: "POST", body: fd });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || "Could not send");
        show(data.messages); box.value = ""; box.focus();
      } catch (e) { note.textContent = e.message; }
    });
    form.elements.body.addEventListener("keydown", ev => {
      if (ev.key === "Enter" && !ev.shiftKey) { ev.preventDefault(); form.requestSubmit(); }
    });
  }
});
