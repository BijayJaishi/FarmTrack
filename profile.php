<?php
require_once __DIR__ . '/php/functions.php';
$user = require_login();
$pageTitle = 'My Profile'; $active = 'profile.php';
$isFarmer = $user['role'] === 'farmer';
$table = $isFarmer ? 'farmers' : 'buyers';
$idCol = $isFarmer ? 'farmer_id' : 'buyer_id';
$nameCol = $isFarmer ? 'farmer_name' : 'buyer_name';
$avatarDir = __DIR__ . '/uploads/avatars';

$stmt = $pdo->prepare("SELECT * FROM $table WHERE $idCol = :id");
$stmt->execute([':id' => $user['id']]);
$p = $stmt->fetch();
if (!$p) { logout_user(); session_start(); flash('Account not found.', 'error'); redirect('login.php'); }

$fields = $isFarmer ? ['farmer_name', 'farm_name', 'location', 'phone', 'bio'] : ['buyer_name', 'business_name', 'phone', 'bio'];
$v = []; foreach ($fields as $f) { $v[$f] = (string)$p[$f]; }
$errors = [];

function delete_avatar_file(?string $file, string $dir): void {
    if ($file && preg_match('/^[a-f0-9]{16}\.(jpg|png|webp)$/', $file)) { @unlink($dir . '/' . $file); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');

    if ($action === 'details') {
        foreach ($fields as $f) { $v[$f] = post($f); }
        $required = ['farmer_name' => 'Your name', 'farm_name' => 'Farm name', 'location' => 'Location',
                     'buyer_name' => 'Your name', 'business_name' => 'Business name'];
        foreach ($required as $k => $label) {
            if (isset($v[$k]) && ($v[$k] === '' || mb_strlen($v[$k]) > 100)) { $errors[$k] = "$label is required (max 100 characters)."; }
        }
        if (!valid_phone($v['phone'])) { $errors['phone'] = 'Enter a valid phone number (8-15 digits).'; }
        if (mb_strlen($v['bio']) > 300) { $errors['bio'] = 'Keep your bio under 300 characters.'; }
        if (!$errors) {
            $set = implode(', ', array_map(fn($f) => "$f = :$f", $fields));          // column names come from the fixed list above
            $params = [':id' => $user['id']];
            foreach ($fields as $f) { $params[":$f"] = $v[$f]; }
            $pdo->prepare("UPDATE $table SET $set WHERE $idCol = :id")->execute($params);
            $_SESSION['user']['name'] = $v[$nameCol];
            flash('Profile updated.');
            redirect('profile.php');
        }
    } elseif ($action === 'avatar') {
        $f = $_FILES['avatar'] ?? null;
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) { flash('Choose an image first.', 'error'); }
        elseif ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024) { flash('Upload failed or the file is larger than 2 MB.', 'error'); }
        else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);              // real type, not the browser's claim
            $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
            if (!$ext || !@getimagesize($f['tmp_name'])) { flash('Please upload a JPG, PNG or WebP image.', 'error'); }
            else {
                if (!is_dir($avatarDir)) { @mkdir($avatarDir, 0755, true); }
                $name = bin2hex(random_bytes(8)) . '.' . $ext;                         // random name, we never trust the original
                if (!@move_uploaded_file($f['tmp_name'], "$avatarDir/$name")) {
                    flash('Could not save the photo. Make the uploads/avatars folder writable (see README).', 'error');
                } else {
                    delete_avatar_file($p['avatar'], $avatarDir);
                    $pdo->prepare("UPDATE $table SET avatar = :a WHERE $idCol = :id")->execute([':a' => $name, ':id' => $user['id']]);
                    $_SESSION['user']['avatar'] = $name;
                    flash('Profile photo updated.');
                }
            }
        }
        redirect('profile.php');
    } elseif ($action === 'remove_avatar') {
        delete_avatar_file($p['avatar'], $avatarDir);
        $pdo->prepare("UPDATE $table SET avatar = NULL WHERE $idCol = :id")->execute([':id' => $user['id']]);
        $_SESSION['user']['avatar'] = null;
        flash('Profile photo removed.');
        redirect('profile.php');
    } elseif ($action === 'password') {
        $cur = raw('current_password'); $new = raw('new_password'); $new2 = raw('confirm_password');
        if (!password_verify($cur, $p['password_hash'])) { $errors['current_password'] = 'Current password is incorrect.'; }
        elseif (!valid_password($new)) { $errors['new_password'] = 'New password must be 8-72 characters.'; }
        elseif ($new !== $new2) { $errors['confirm_password'] = 'Passwords do not match.'; }
        if (!$errors) {
            $pdo->prepare("UPDATE $table SET password_hash = :h WHERE $idCol = :id")
                ->execute([':h' => password_hash($new, PASSWORD_DEFAULT), ':id' => $user['id']]);
            session_regenerate_id(true);
            flash('Password changed.');
            redirect('profile.php');
        }
    }
}

// ----- statistics shown on the profile -----
if ($isFarmer) {
    $s = $pdo->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(quantity_kg),0) AS kg, COALESCE(SUM(status='available'),0) AS live FROM harvests WHERE farmer_id = :id");
    $s->execute([':id' => $user['id']]); $hs = $s->fetch();
    $s = $pdo->prepare("SELECT COUNT(*) FROM enquiries e JOIN harvests h ON h.harvest_id = e.harvest_id WHERE h.farmer_id = :id");
    $s->execute([':id' => $user['id']]); $enqCount = (int)$s->fetchColumn();
    $s = $pdo->prepare("SELECT * FROM harvests WHERE farmer_id = :id AND status = 'available' ORDER BY harvest_date DESC LIMIT 3");
    $s->execute([':id' => $user['id']]); $latest = $s->fetchAll();
    $stats = ['Harvest records' => (int)$hs['n'], 'Total harvested' => number_format((float)$hs['kg'], 0) . ' kg',
              'Live listings' => (int)$hs['live'], 'Enquiries received' => $enqCount];
    $hasActivity = (int)$hs['n'] > 0;
    $subtitle = $p['farm_name'] . ' &middot; ' . $p['location'];
} else {
    $s = $pdo->prepare('SELECT COUNT(*) FROM enquiries WHERE buyer_id = :id');
    $s->execute([':id' => $user['id']]); $enqCount = (int)$s->fetchColumn();
    $s = $pdo->prepare("SELECT COUNT(*) FROM messages m JOIN enquiries e ON e.enquiry_id = m.enquiry_id WHERE e.buyer_id = :id AND m.sender = 'buyer'");
    $s->execute([':id' => $user['id']]); $msgCount = (int)$s->fetchColumn();
    $stats = ['Enquiries sent' => $enqCount, 'Messages sent' => $msgCount, 'Member since' => date('M Y', strtotime($p['created_at']))];
    $hasActivity = $enqCount > 0;
    $subtitle = $p['business_name'];
}
$checks = $isFarmer
    ? ['Add a profile photo' => !empty($p['avatar']), 'Write a short farm story' => trim($p['bio']) !== '', 'Add your phone number' => $p['phone'] !== '',
       'Add your farm location' => $p['location'] !== '', 'List your first harvest' => $hasActivity]
    : ['Add a profile photo' => !empty($p['avatar']), 'Write a short introduction' => trim($p['bio']) !== '', 'Add your phone number' => $p['phone'] !== '',
       'Add your business name' => $p['business_name'] !== '', 'Send your first enquiry' => $hasActivity];
$pct = (int)round(count(array_filter($checks)) / count($checks) * 100);

require __DIR__ . '/php/header.php';
?>
<section class="profile-card">
  <div class="profile-cover"></div>
  <div class="profile-head">
    <?= avatar_html($p[$nameCol], $p['avatar'], 'xl') ?>
    <div class="profile-id">
      <h1><?= e($p[$nameCol]) ?></h1>
      <p class="subtitle"><?= $subtitle === '' ? '' : e(html_entity_decode($subtitle)) ?></p>
      <p><span class="role-badge <?= $isFarmer ? 'farmer' : 'buyer' ?>"><?= $isFarmer ? '🌱 Farmer' : '🛒 Buyer' ?></span>
         <span class="since">Member since <?= e(date('F Y', strtotime($p['created_at']))) ?></span></p>
    </div>
    <?php if ($isFarmer): ?><div class="profile-actions"><a class="btn secondary" href="farm.php?id=<?= (int)$user['id'] ?>">View public farm page</a></div><?php endif; ?>
  </div>
</section>

<div class="stats">
  <?php foreach ($stats as $label => $n): ?><div class="card stat"><strong><?= e((string)$n) ?></strong><span><?= e($label) ?></span></div><?php endforeach; ?>
</div>

<div class="profile-grid">
  <div class="col">
    <section class="card">
      <h2>About</h2>
      <p class="bio"><?= trim($p['bio']) !== '' ? nl2br(e($p['bio'])) : '<em>No bio yet. Tell people about yourself below.</em>' ?></p>
      <dl class="details">
        <dt>Email</dt><dd><?= e($p['email']) ?></dd>
        <dt>Phone</dt><dd><?= e($p['phone']) ?></dd>
        <?php if ($isFarmer): ?><dt>Location</dt><dd><?= e($p['location']) ?></dd><?php else: ?><dt>Business</dt><dd><?= e($p['business_name']) ?></dd><?php endif; ?>
      </dl>
    </section>

    <section class="card">
      <h2>Profile completeness: <?= $pct ?>%</h2>
      <div class="progress" role="progressbar" aria-label="Profile completeness" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $pct ?>"><div style="width:<?= $pct ?>%"></div></div>
      <ul class="checklist">
        <?php foreach ($checks as $label => $done): ?><li class="<?= $done ? 'done' : '' ?>"><span aria-hidden="true"><?= $done ? '✔' : '○' ?></span> <?= e($label) ?><?= $done ? '' : '' ?></li><?php endforeach; ?>
      </ul>
    </section>

    <?php if ($isFarmer && !empty($latest)): ?>
    <section class="card">
      <h2>Latest live listings</h2>
      <div class="mini-list">
        <?php foreach ($latest as $h): ?>
          <div class="mini"><strong><?= e($h['crop_name']) ?></strong><span><?= number_format((float)$h['quantity_kg'], 0) ?> kg &middot; $<?= number_format((float)$h['price_per_kg'], 2) ?>/kg</span></div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </div>

  <div class="col">
    <section class="card">
      <h2>Edit details</h2>
      <form method="post" data-validate novalidate>
        <?= csrf_field() ?><input type="hidden" name="action" value="details">
        <?php if ($isFarmer) {
            field('farmer_name', 'Your full name', 'text', $v['farmer_name'], $errors, 'required maxlength="100"');
            field('farm_name', 'Farm name', 'text', $v['farm_name'], $errors, 'required maxlength="100"');
            field('location', 'Farm location', 'text', $v['location'], $errors, 'required maxlength="100"');
        } else {
            field('buyer_name', 'Your full name', 'text', $v['buyer_name'], $errors, 'required maxlength="100"');
            field('business_name', 'Business name', 'text', $v['business_name'], $errors, 'required maxlength="100"');
        }
        field('phone', 'Phone', 'tel', $v['phone'], $errors, 'required data-phone'); ?>
        <div class="field"><label for="bio">Bio (max 300 characters)</label>
          <textarea id="bio" name="bio" rows="4" maxlength="300" aria-describedby="err-bio"><?= e($v['bio']) ?></textarea>
          <span class="error" id="err-bio" role="alert"><?= e($errors['bio'] ?? '') ?></span></div>
        <div class="field"><label for="email_ro">Email (your login, cannot be changed)</label>
          <input id="email_ro" type="email" value="<?= e($p['email']) ?>" disabled></div>
        <button class="btn" type="submit">Save changes</button>
      </form>
    </section>

    <section class="card">
      <h2>Profile photo</h2>
      <form method="post" enctype="multipart/form-data" class="photo-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="avatar">
        <div class="field"><label for="avatar">Choose a JPG, PNG or WebP image (max 2 MB)</label>
          <input id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required></div>
        <button class="btn" type="submit">Upload photo</button>
      </form>
      <?php if (!empty($p['avatar'])): ?>
        <form method="post" class="photo-form"><?= csrf_field() ?><input type="hidden" name="action" value="remove_avatar">
          <button class="btn small secondary" type="submit">Remove photo</button></form>
      <?php endif; ?>
    </section>

    <section class="card">
      <h2>Change password</h2>
      <form method="post" data-validate novalidate>
        <?= csrf_field() ?><input type="hidden" name="action" value="password">
        <?php field('current_password', 'Current password', 'password', '', $errors, 'required autocomplete="current-password"');
              field('new_password', 'New password (at least 8 characters)', 'password', '', $errors, 'required minlength="8" maxlength="72" autocomplete="new-password"');
              field('confirm_password', 'Confirm new password', 'password', '', $errors, 'required data-match="new_password" autocomplete="new-password"'); ?>
        <button class="btn" type="submit">Change password</button>
      </form>
    </section>
  </div>
</div>
<?php require __DIR__ . '/php/footer.php'; ?>
