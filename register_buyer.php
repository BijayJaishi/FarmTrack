<?php
require_once __DIR__ . '/php/functions.php';
$pageTitle = 'Buyer Sign-up'; $active = 'register_buyer.php';
if (current_user()) { redirect('index.php'); }
$errors = [];
$v = array_fill_keys(['buyer_name', 'business_name', 'phone', 'email'], '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) { $v[$k] = post($k); }
    $pw = raw('password'); $pw2 = raw('confirm');
    foreach (['buyer_name' => 'Your name', 'business_name' => 'Business name'] as $k => $label) {
        if ($v[$k] === '' || mb_strlen($v[$k]) > 100) { $errors[$k] = "$label is required (max 100 characters)."; }
    }
    if (!valid_phone($v['phone'])) { $errors['phone'] = 'Enter a valid phone number (8-15 digits).'; }
    if (!valid_email($v['email'])) { $errors['email'] = 'Enter a valid email address.'; }
    if (!valid_password($pw)) { $errors['password'] = 'Password must be 8-72 characters.'; }
    elseif ($pw !== $pw2) { $errors['confirm'] = 'Passwords do not match.'; }
    if (!$errors) {
        $chk = $pdo->prepare('SELECT 1 FROM buyers WHERE email = :email');
        $chk->execute([':email' => $v['email']]);
        if ($chk->fetch()) { $errors['email'] = 'This email is already registered.'; }
    }
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO buyers (buyer_name, business_name, phone, email, password_hash)
                               VALUES (:buyer_name, :business_name, :phone, :email, :hash)');
        $stmt->execute([':buyer_name' => $v['buyer_name'], ':business_name' => $v['business_name'], ':phone' => $v['phone'],
                        ':email' => $v['email'], ':hash' => password_hash($pw, PASSWORD_DEFAULT)]);
        login_user((int)$pdo->lastInsertId(), 'buyer', $v['buyer_name']);
        flash('Account created and you are logged in. Browse the market board.');
        redirect('market_board.php');
    }
}
require __DIR__ . '/php/header.php';
?>
<h1>Buyer Sign-up</h1>
<form method="post" class="card form" data-validate novalidate>
  <?= csrf_field() ?>
  <?php field('buyer_name', 'Your full name', 'text', $v['buyer_name'], $errors, 'required maxlength="100"');
        field('business_name', 'Business name', 'text', $v['business_name'], $errors, 'required maxlength="100"');
        field('phone', 'Phone', 'tel', $v['phone'], $errors, 'required data-phone');
        field('email', 'Email (used to log in)', 'email', $v['email'], $errors, 'required autocomplete="username"');
        field('password', 'Password (at least 8 characters)', 'password', '', $errors, 'required minlength="8" maxlength="72" autocomplete="new-password"');
        field('confirm', 'Confirm password', 'password', '', $errors, 'required data-match="password" autocomplete="new-password"'); ?>
  <button class="btn" type="submit">Create buyer account</button>
  <p>Already registered? <a href="login.php">Log in</a>.</p>
</form>
<?php require __DIR__ . '/php/footer.php'; ?>
