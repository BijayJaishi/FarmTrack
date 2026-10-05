<?php
/** header.php - shared header; navigation changes with login state and role. Expects $pageTitle and $active. */
$authUser = current_user();
$nav = ['index.php' => 'Home'];
if ($authUser && $authUser['role'] === 'farmer') {
    $nav += ['add_harvest.php' => 'Add Harvest', 'view_harvests.php' => 'My Harvests', 'dashboard.php' => 'Dashboard'];
}
$nav['market_board.php'] = 'Market Board';
if ($authUser) { $nav['enquiries.php'] = 'Chats'; }
else { $nav += ['login.php' => 'Login', 'register_farmer.php' => 'Farmer Sign-up', 'register_buyer.php' => 'Buyer Sign-up']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | FarmTrack</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<a class="skip" href="#main">Skip to main content</a>
<header class="site-header">
  <div class="wrap">
    <a class="brand" href="index.php">🌾 FarmTrack</a>
    <nav aria-label="Main navigation">
      <ul>
        <?php foreach ($nav as $navFile => $navLabel): ?>
          <li><a href="<?= $navFile ?>"<?= $navFile === $active ? ' aria-current="page" class="active"' : '' ?>><?= $navLabel ?></a></li>
        <?php endforeach; ?>
        <?php if ($authUser): ?>
          <li class="who"><?= e($authUser['name']) ?> (<?= e($authUser['role']) ?>)</li>
          <li><form method="post" action="logout.php"><?= csrf_field() ?><button type="submit" class="linklike">Log out</button></form></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>
<main id="main" class="wrap">
<?php if (!empty($_SESSION['flash'])): [$flashMsg, $flashType] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="alert <?= e($flashType) ?>" role="status"><?= e($flashMsg) ?></div>
<?php endif; ?>
