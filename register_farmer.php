<?php
require_once __DIR__ . '/php/functions.php';
$pageTitle = 'Farmer Sign-up'; $active = 'register_farmer.php';
if (current_user()) { redirect('index.php'); }
$errors = [];
$v = array_fill_keys(['farmer_name', 'farm_name', 'location', 'phone', 'email'], '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) { $v[$k] = post($k); }
    $pw = raw('password'); $pw2 = raw('confirm');

    foreach (['farmer_name' => 'Your name', 'farm_name' => 'Farm name', 'location' => 'Location'] as $k => $label) {
        if ($v[$k] === '' || mb_strlen($v[$k]) > 100) { $errors[$k] = "$label is required (max 100 characters)."; }
    }
    if (!valid_phone($v['phone'])) { $errors['phone'] = 'Enter a valid phone number (8-15 digits).'; }
    if (!valid_email($v['email'])) { $errors['email'] = 'Enter a valid email address.'; }
    if (!valid_password($pw)) { $errors['password'] = 'Password must be 8-72 characters.'; }
    elseif ($pw !== $pw2) { $errors['confirm'] = 'Passwords do not match.'; }
    if (!$errors) {
        $chk = $pdo->prepare('SELECT 1 FROM farmers WHERE email = :email');
        $chk->execute([':email' => $v['email']]);
        if ($chk->fetch()) { $errors['email'] = 'This email is already registered.'; }
    }
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO farmers (farm_name, farmer_name, location, phone, email, password_hash)
                               VALUES (:farm_name, :farmer_name, :location, :phone, :email, :hash)');
        $stmt->execute([':farm_name' => $v['farm_name'], ':farmer_name' => $v['farmer_name'], ':location' => $v['location'],
                        ':phone' => $v['phone'], ':email' => $v['email'], ':hash' => password_hash($pw, PASSWORD_DEFAULT)]);
        login_user((int)$pdo->lastInsertId(), 'farmer', $v['farmer_name']);
        flash('Account created and you are logged in. Add your first harvest.');
        redirect('add_harvest.php');
    }
}
require __DIR__ . '/php/header.php';
?>
<h1>Farmer Sign-up</h1>
<form method="post" class="card form" data-validate novalidate>
  <?= csrf_field() ?>
  <?php field('farmer_name', 'Your full name', 'text', $v['farmer_name'], $errors, 'required maxlength="100"');
        field('farm_name', 'Farm name', 'text', $v['farm_name'], $errors, 'required maxlength="100"');
        field('location', 'Farm location (town / state)', 'text', $v['location'], $errors, 'required maxlength="100"');
        field('phone', 'Phone', 'tel', $v['phone'], $errors, 'required data-phone');
        field('email', 'Email (used to log in)', 'email', $v['email'], $errors, 'required autocomplete="username"');
        field('password', 'Password (at least 8 characters)', 'password', '', $errors, 'required minlength="8" maxlength="72" autocomplete="new-password"');
        field('confirm', 'Confirm password', 'password', '', $errors, 'required data-match="password" autocomplete="new-password"'); ?>
  <button class="btn" type="submit">Create farmer account</button>
  <p>Already registered? <a href="login.php">Log in</a>.</p>
</form>
<?php require __DIR__ . '/php/footer.php'; ?>
