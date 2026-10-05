/**
 * validate.js - client-side scripting for FarmTrack.
 * 1. Form validation before submission (mirrors PHP rules; PHP still re-validates).
 * 2. Instant filtering of the market board (no server round trip).
 * 3. Confirmation prompt for destructive actions.
 */
document.addEventListener('DOMContentLoaded', () => {
  const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const PHONE = /^[0-9+\s()-]{8,15}$/;

  function check(el) {
    const v = el.value.trim();
    if (el.required && v === '') return 'This field is required.';
    if (v === '') return '';
    if (el.dataset.match) { const o = el.form.querySelector('[name="' + el.dataset.match + '"]'); if (o && o.value !== el.value) return 'Passwords do not match.'; }
    if (el.type === 'email' && !EMAIL.test(v)) return 'Enter a valid email address.';
    if (el.hasAttribute('data-phone') && !PHONE.test(v)) return 'Enter a valid phone number (8-15 digits).';
    if (el.type === 'number' && (isNaN(v) || parseFloat(v) < parseFloat(el.min || '-Infinity'))) return 'Enter a valid number (minimum ' + el.min + ').';
    if (el.hasAttribute('data-nofuture') && new Date(v) > new Date()) return 'Date cannot be in the future.';
    if (el.minLength > 0 && v.length < el.minLength) return 'Enter at least ' + el.minLength + ' characters.';
    return '';
  }
  function show(el, msg) {
    const out = document.getElementById('err-' + el.id);
    if (out) out.textContent = msg;
    el.setAttribute('aria-invalid', msg ? 'true' : 'false');
  }

  document.querySelectorAll('form[data-validate]').forEach(form => {
    const fields = form.querySelectorAll('input:not([type=hidden]), select, textarea');
    fields.forEach(el => el.addEventListener('blur', () => show(el, check(el))));
    form.addEventListener('submit', ev => {
      let firstBad = null;
      fields.forEach(el => { const m = check(el); show(el, m); if (m && !firstBad) firstBad = el; });
      if (firstBad) { ev.preventDefault(); firstBad.focus(); }
    });
  });

  // Live market board filter
  const search = document.getElementById('boardSearch'), table = document.getElementById('boardTable');
  if (search && table) {
    const rows = table.querySelectorAll('tbody tr'), none = document.getElementById('noMatch');
    search.addEventListener('input', () => {
      const term = search.value.trim().toLowerCase(); let shown = 0;
      rows.forEach(r => { if (r.classList.contains('gone')) return; const hit = r.dataset.search.includes(term); r.hidden = !hit; if (hit) shown++; });
      if (none) none.hidden = shown !== 0;
    });
  }

  // Confirm before delete
  document.querySelectorAll('[data-confirm]').forEach(b =>
    b.addEventListener('click', ev => { if (!confirm(b.dataset.confirm)) ev.preventDefault(); }));
});
