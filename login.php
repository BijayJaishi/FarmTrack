<?php
require_once __DIR__ . '/php/functions.php';
$pageTitle = 'Login'; $active = 'login.php';
if (current_user()) { redirect('index.php'); }
$errors = []; $email = ''; $role = 'farmer';
$next = $_GET['next'] ?? $_POST['next'] ?? '';
if (!is_string($next) || !preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9_=&%.-]*)?$/', $next)) { $next = ''; }   // only local pages (no open redirect)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = post('email'); $pw = raw('password'); $role = post('role');
    if (!in_array($role, ['farmer', 'buyer'], true)) { $role = 'farmer'; }

    if (time() >= ($_SESSION['lock_until'] ?? 0) && ($_SESSION['fails'] ?? 0) >= 5) { $_SESSION['fails'] = 0; }   // lock expired
    if (($_SESSION['fails'] ?? 0) >= 5) {
        $errors['form'] = 'Too many failed attempts. Please wait a minute and try again.';
    } else {
        [$table, $idCol, $nameCol] = $role === 'farmer' ? ['farmers', 'farmer_id', 'farmer_name'] : ['buyers', 'buyer_id', 'buyer_name'];
        $stmt = $pdo->prepare("SELECT $idCol AS id, $nameCol AS name, avatar, password_hash FROM $table WHERE email = :e");
        $stmt->execute([':e' => $email]);
        $row = $stmt->fetch();
        if ($row && password_verify($pw, $row['password_hash'])) {
            unset($_SESSION['fails'], $_SESSION['lock_until']);
            login_user((int)$row['id'], $role, $row['name'], $row['avatar'] ?? null);   // avatar shows in the menu straight after login
            flash('Welcome back, ' . $row['name'] . '.');
            redirect($next !== '' ? $next : ($role === 'farmer' ? 'dashboard.php' : 'market_board.php'));
        }
        $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
        if ($_SESSION['fails'] >= 5) { $_SESSION['lock_until'] = time() + 60; }
        $errors['form'] = 'Incorrect email or password.';       // same message for both cases (no account enumeration)
    }
}
require __DIR__ . '/php/header.php';
?>
<h1>Log in</h1>
<form method="post" class="card form" data-validate novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="next" value="<?= e($next) ?>">
  <?php if (!empty($errors['form'])): ?><p class="alert error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
  <fieldset class="radios"><legend>I am a</legend>
    <label><input type="radio" name="role" value="farmer"<?= $role === 'farmer' ? ' checked' : '' ?>> Farmer</label>
    <label><input type="radio" name="role" value="buyer"<?= $role === 'buyer' ? ' checked' : '' ?>> Buyer</label>
  </fieldset>
  <?php field('email', 'Email', 'email', $email, [], 'required autocomplete="username"');
        field('password', 'Password', 'password', '', [], 'required autocomplete="current-password"'); ?>
  <button class="btn" type="submit">Log in</button>
  <p>No account? <a href="register_farmer.php">Register as a farmer</a> or <a href="register_buyer.php">as a buyer</a>.</p>
</form>
<?php require __DIR__ . '/php/footer.php'; ?>