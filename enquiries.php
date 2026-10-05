<?php
require_once __DIR__ . '/php/functions.php';
$user = require_login();                          // farmers and buyers each see only their own conversations
$pageTitle = 'Chats'; $active = 'enquiries.php';
$col = $user['role'] === 'farmer' ? 'h.farmer_id' : 'e.buyer_id';   // fixed column names, value is bound
$stmt = $pdo->prepare("SELECT e.enquiry_id, e.enquiry_date, h.crop_name, f.farm_name, b.business_name,
            (SELECT COUNT(*) FROM messages m WHERE m.enquiry_id = e.enquiry_id) AS replies
        FROM enquiries e
        JOIN harvests h ON h.harvest_id = e.harvest_id
        JOIN farmers f  ON f.farmer_id  = h.farmer_id
        JOIN buyers b   ON b.buyer_id   = e.buyer_id
        WHERE $col = :id ORDER BY e.enquiry_date DESC");
$stmt->execute([':id' => $user['id']]);
$rows = $stmt->fetchAll();
require __DIR__ . '/php/header.php';
?>
<h1>My Enquiries &amp; Chats</h1>
<?php if ($rows): ?>
<div class="table-wrap"><table>
  <caption class="sr-only">Your enquiries</caption>
  <thead><tr><th scope="col">Date</th><th scope="col">Produce</th><th scope="col">Farm</th><th scope="col">Buyer</th><th scope="col">Replies</th><th scope="col">Chat</th></tr></thead>
  <tbody><?php foreach ($rows as $r): ?>
    <tr><td><?= e(substr($r['enquiry_date'], 0, 10)) ?></td><td><?= e($r['crop_name']) ?></td><td><?= e($r['farm_name']) ?></td>
      <td><?= e($r['business_name']) ?></td><td><?= (int)$r['replies'] ?></td>
      <td><a class="btn small" href="chat.php?enquiry_id=<?= (int)$r['enquiry_id'] ?>">Open chat<span class="sr-only"> about <?= e($r['crop_name']) ?></span></a></td></tr>
  <?php endforeach; ?></tbody></table></div>
<?php elseif ($user['role'] === 'buyer'): ?>
  <p class="alert error">No enquiries yet. <a href="market_board.php">Browse the market board</a> and press Enquire.</p>
<?php else: ?>
  <p class="alert error">No buyer has sent an enquiry yet.</p>
<?php endif; ?>
<?php require __DIR__ . '/php/footer.php'; ?>
