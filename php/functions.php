<?php
/** functions.php - shared helpers: escaping, CSRF, flash messages, form fields. */
require_once __DIR__ . '/config.php';
const SESSION_IDLE_SECONDS = 1800;   // auto logout after 30 minutes of inactivity
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');                       // reject unknown session IDs
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
    session_start();
}
if (isset($_SESSION['user'])) {
    if (time() - ($_SESSION['last_active'] ?? 0) > SESSION_IDLE_SECONDS) {
        unset($_SESSION['user']);
        $_SESSION['flash'] = ['Your session expired. Please log in again.', 'error'];
    } else { $_SESSION['last_active'] = time(); }
}

/** Escape output (prevents XSS). */
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Trimmed POST value. */
function post(string $key): string { $v = $_POST[$key] ?? ''; return is_string($v) ? trim($v) : ''; }
/** Raw POST value (used for passwords, which must not be trimmed). */
function raw(string $key): string { $v = $_POST[$key] ?? ''; return is_string($v) ? $v : ''; }

/** CSRF protection: one token per session, checked on every POST. */
function csrf_field(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Invalid form submission (CSRF check failed). Please go back and try again.');
    }
}

/** Flash messages (shown once after a redirect). */
function flash(string $msg, string $type = 'success'): void { $_SESSION['flash'] = [$msg, $type]; }
function redirect(string $url): never { header('Location: ' . $url); exit; }

/** Shared validation rules (mirrored client-side in js/validate.js). */
function valid_email(string $v): bool { return (bool)filter_var($v, FILTER_VALIDATE_EMAIL); }
function valid_phone(string $v): bool { return (bool)preg_match('/^[0-9+\s()-]{8,15}$/', $v); }

/** Accessible text input with its label and error message. */
function field(string $id, string $label, string $type, string $value, array $errors, string $attrs = ''): void {
    $err = $errors[$id] ?? '';
    echo '<div class="field"><label for="', $id, '">', e($label), '</label>',
         '<input type="', $type, '" id="', $id, '" name="', $id, '" value="', e($value), '" ', $attrs,
         ($err ? ' aria-invalid="true"' : ''), ' aria-describedby="err-', $id, '">',
         '<span class="error" id="err-', $id, '" role="alert">', e($err), '</span></div>';
}

/** Accessible select box. $options = [value => label]. */
function select_field(string $id, string $label, array $options, string $selected, array $errors, string $placeholder = 'Select...'): void {
    $err = $errors[$id] ?? '';
    echo '<div class="field"><label for="', $id, '">', e($label), '</label>',
         '<select id="', $id, '" name="', $id, '" required aria-describedby="err-', $id, '">',
         '<option value="">', e($placeholder), '</option>';
    foreach ($options as $val => $text) {
        echo '<option value="', e((string)$val), '"', ((string)$val === $selected ? ' selected' : ''), '>', e($text), '</option>';
    }
    echo '</select><span class="error" id="err-', $id, '" role="alert">', e($err), '</span></div>';
}

/* ---------- Authentication and sessions ---------- */
function valid_password(string $p): bool { return strlen($p) >= 8 && strlen($p) <= 72; }   // 72 = bcrypt limit

function current_user(): ?array { return $_SESSION['user'] ?? null; }   // ['id', 'role', 'name']

function login_user(int $id, string $role, string $name): void {
    session_regenerate_id(true);                       // prevents session fixation
    $_SESSION['user'] = ['id' => $id, 'role' => $role, 'name' => $name];
    $_SESSION['last_active'] = time();
    unset($_SESSION['csrf']);                          // fresh CSRF token for the new session
}

function logout_user(): void {
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    session_destroy();
}

/** Page guard: must be logged in (optionally as a specific role). Returns the user. */
function require_login(?string $role = null): array {
    $u = current_user();
    if (!$u) {
        $next = basename($_SERVER['SCRIPT_NAME']) . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');
        flash('Please log in to continue.', 'error');
        redirect('login.php?next=' . urlencode($next));
    }
    if ($role !== null && $u['role'] !== $role) { http_response_code(403); exit('Access denied: this page is for ' . $role . ' accounts.'); }
    return $u;
}

/** Authorisation: returns the enquiry only if it belongs to this farmer (via harvest) or this buyer. */
function enquiry_for_user(PDO $pdo, int $eid, array $u) {
    $col = $u['role'] === 'farmer' ? 'h.farmer_id' : 'e.buyer_id';    // fixed column names, value is bound
    $s = $pdo->prepare("SELECT e.*, h.crop_name, f.farm_name, b.business_name FROM enquiries e
        JOIN harvests h ON h.harvest_id = e.harvest_id JOIN farmers f ON f.farmer_id = h.farmer_id
        JOIN buyers b ON b.buyer_id = e.buyer_id WHERE e.enquiry_id = :id AND $col = :uid");
    $s->execute([':id' => $eid, ':uid' => $u['id']]);
    return $s->fetch();
}
