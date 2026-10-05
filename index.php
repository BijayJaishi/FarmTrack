<?php
require_once __DIR__ . '/php/functions.php';
$pageTitle = 'Home'; $active = 'index.php';
$stats = [
  'Registered farms'    => $pdo->query('SELECT COUNT(*) FROM farmers')->fetchColumn(),
  'Registered buyers'   => $pdo->query('SELECT COUNT(*) FROM buyers')->fetchColumn(),
  'Produce for sale'    => $pdo->query("SELECT COUNT(*) FROM harvests WHERE status='available'")->fetchColumn(),
  'Buyer enquiries'     => $pdo->query('SELECT COUNT(*) FROM enquiries')->fetchColumn(),
];
require __DIR__ . '/php/header.php';
?>
<section class="hero">
  <h1>FarmTrack</h1>
  <p>A web-based farm produce tracking and market access system for small and medium-scale farmers.</p>
  <p><?php if (!current_user()): ?><a class="btn" href="register_farmer.php">Farmer sign-up</a> <a class="btn" href="register_buyer.php">Buyer sign-up</a> <a class="btn secondary" href="login.php">Log in</a>
  <?php else: ?><a class="btn" href="market_board.php">Go to the market board</a><?php endif; ?></p>
</section>

<h2>System summary</h2>
<div class="stats">
  <?php foreach ($stats as $label => $n): ?>
    <div class="card stat"><strong><?= (int)$n ?></strong><span><?= e($label) ?></span></div>
  <?php endforeach; ?>
</div>

<h2>How it works</h2>
<div class="grid">
  <article class="card"><h3>1. Farmers</h3><p>Register a farm, record each harvest (crop, quantity, date, condition, price) and review past harvests with filters.</p></article>
  <article class="card"><h3>2. Market board</h3><p>Available produce is listed publicly so buyers can see stock and prices before visiting a market.</p></article>
  <article class="card"><h3>3. Buyers</h3><p>Buyers register once and send enquiries about listed produce directly to the farm.</p></article>
</div>
<?php require __DIR__ . '/php/footer.php'; ?>
