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
          <li><a href="<?= $navFile ?>"<?= $navFile === $active ? ' aria-current="page" class="active"' : '' ?>><?= $navLabel ?><?php if ($navFile === 'enquiries.php'): ?> <span class="nav-badge js-badge" hidden>0</span><?php endif; ?></a></li>
        <?php endforeach; ?>
        <?php if ($authUser): ?>
          <li><button type="button" id="notifBtn" class="notif-btn" aria-haspopup="true" aria-expanded="false" aria-controls="notifPanel" aria-label="Messages"><span aria-hidden="true">💬</span><span class="nav-badge js-badge" hidden>0</span></button></li>
          <li><a class="navuser<?= $active === 'profile.php' ? ' active' : '' ?>" href="profile.php" title="My profile"<?= $active === 'profile.php' ? ' aria-current="page"' : '' ?>><?= avatar_html($authUser['name'], $authUser['avatar'] ?? null, 'sm') ?><span><?= e($authUser['name']) ?></span></a></li>
          <li><form method="post" action="logout.php"><?= csrf_field() ?><button type="submit" class="linklike">Log out</button></form></li>
        <?php endif; ?>
      </ul>
    </nav>
    <?php if ($authUser): ?>
    <div id="notifPanel" class="notif-panel" hidden>
      <div class="notif-head"><strong>Messages</strong><span id="notifTime" class="hint">Checking...</span></div>
      <ul id="notifList" class="notif-list"></ul>
      <div class="notif-foot"><a href="enquiries.php">Open all chats</a><span class="hint">Refreshes every 5 seconds</span></div>
    </div>
    <?php endif; ?>
  </div>
</header>
<main id="main" class="wrap">
<?php if (!empty($_SESSION['flash'])): [$flashMsg, $flashType] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="alert <?= e($flashType) ?>" role="status"><?= e($flashMsg) ?></div>
<?php endif; ?>
