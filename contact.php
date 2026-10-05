<?php
require_once __DIR__ . '/php/functions.php';
$user = require_login('buyer');                   // only logged-in buyers can send enquiries
$pageTitle = 'Enquire'; $active = 'market_board.php';
$errors = [];
$items = $pdo->query("SELECT h.harvest_id, CONCAT(h.crop_name, ' - ', f.farm_name, ' ($', FORMAT(h.price_per_kg,2), '/kg)') AS label
                      FROM harvests h JOIN farmers f ON f.farmer_id = h.farmer_id
                      WHERE h.status = 'available' ORDER BY h.crop_name")->fetchAll(PDO::FETCH_KEY_PAIR);
$v = ['harvest_id' => (string)($_GET['harvest_id'] ?? ''), 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) { $v[$k] = post($k); }
    if (!isset($items[(int)$v['harvest_id']])) { $errors['harvest_id'] = 'Select an available produce item.'; }
    if (mb_strlen($v['message']) < 10 || mb_strlen($v['message']) > 1000) { $errors['message'] = 'Message must be 10-1000 characters.'; }
    if (!$errors) {
        $pdo->prepare('INSERT INTO enquiries (buyer_id, harvest_id, message) VALUES (:b, :h, :m)')
            ->execute([':b' => $user['id'], ':h' => (int)$v['harvest_id'], ':m' => $v['message']]);
        flash('Enquiry sent. The farmer can now reply in this chat.');
        redirect('chat.php?enquiry_id=' . $pdo->lastInsertId());
    }
}
require __DIR__ . '/php/header.php';
?>
<h1>Send an Enquiry</h1>
<form method="post" class="card form" data-validate novalidate>
  <?= csrf_field() ?>
  <?php select_field('harvest_id', 'Produce item', $items, $v['harvest_id'], $errors, 'Select produce'); ?>
  <div class="field"><label for="message">Message</label>
    <textarea id="message" name="message" rows="5" required minlength="10" maxlength="1000" aria-describedby="err-message"><?= e($v['message']) ?></textarea>
    <span class="error" id="err-message" role="alert"><?= e($errors['message'] ?? '') ?></span></div>
  <button class="btn" type="submit">Send enquiry</button>
</form>
<?php require __DIR__ . '/php/footer.php'; ?>
