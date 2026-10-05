<?php
require_once __DIR__ . '/php/functions.php';
$user = current_user();
$fid = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT farmer_id, farm_name, farmer_name, location, bio, avatar, created_at FROM farmers WHERE farmer_id = :id');
$stmt->execute([':id' => $fid]);
$f = $stmt->fetch();
if (!$f) { http_response_code(404); exit('Farm not found.'); }
$pageTitle = $f['farm_name']; $active = 'market_board.php';

$s = $pdo->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(status='available'),0) AS live,
                           COALESCE(SUM(CASE WHEN status='available' THEN quantity_kg END),0) AS kg FROM harvests WHERE farmer_id = :id");
$s->execute([':id' => $fid]); $st = $s->fetch();
$s = $pdo->prepare("SELECT * FROM harvests WHERE farmer_id = :id AND status = 'available' ORDER BY harvest_date DESC");
$s->execute([':id' => $fid]); $items = $s->fetchAll();
$mine = $user && $user['role'] === 'farmer' && $user['id'] === $fid;
require __DIR__ . '/php/header.php';
?>
<section class="profile-card">
  <div class="profile-cover"></div>
  <div class="profile-head">
    <?= avatar_html($f['farm_name'], $f['avatar'], 'xl') ?>
    <div class="profile-id">
      <h1><?= e($f['farm_name']) ?></h1>
      <p class="subtitle">📍 <?= e($f['location']) ?> &middot; run by <?= e($f['farmer_name']) ?></p>
      <p><span class="role-badge farmer">🌱 Farm</span> <span class="since">On FarmTrack since <?= e(date('F Y', strtotime($f['created_at']))) ?></span></p>
    </div>
    <?php if ($mine): ?><div class="profile-actions"><a class="btn secondary" href="profile.php">Edit my profile</a></div><?php endif; ?>
  </div>
</section>

<div class="stats">
  <div class="card stat"><strong><?= (int)$st['live'] ?></strong><span>Products for sale</span></div>
  <div class="card stat"><strong><?= number_format((float)$st['kg'], 0) ?> kg</strong><span>Currently in stock</span></div>
  <div class="card stat"><strong><?= (int)$st['n'] ?></strong><span>Harvests recorded</span></div>
</div>

<section class="card"><h2>About this farm</h2>
  <p class="bio"><?= trim($f['bio']) !== '' ? nl2br(e($f['bio'])) : '<em>This farm has not added a story yet.</em>' ?></p></section>

<h2>Available produce</h2>
<?php if ($items): ?>
<div class="grid">
  <?php foreach ($items as $h): ?>
    <article class="card listing">
      <h3><?= e($h['crop_name']) ?></h3>
      <p class="price">$<?= number_format((float)$h['price_per_kg'], 2) ?><small>/kg</small></p>
      <p><?= number_format((float)$h['quantity_kg'], 0) ?> kg available &middot; <?= e($h['condition']) ?></p>
      <p class="hint">Harvested <?= e($h['harvest_date']) ?></p>
      <?php if ($user && $user['role'] === 'buyer'): ?><a class="btn small" href="contact.php?harvest_id=<?= (int)$h['harvest_id'] ?>">Enquire<span class="sr-only"> about <?= e($h['crop_name']) ?></span></a>
      <?php elseif (!$user): ?><a class="btn small secondary" href="login.php?next=<?= urlencode('contact.php?harvest_id=' . (int)$h['harvest_id']) ?>">Log in to enquire</a><?php endif; ?>
    </article>
  <?php endforeach; ?>
</div>
<?php else: ?><p class="alert error">No produce is currently listed by this farm.</p><?php endif; ?>
<p><a href="market_board.php">&larr; Back to the market board</a></p>
<?php require __DIR__ . '/php/footer.php'; ?>
