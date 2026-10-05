<?php
require_once __DIR__ . '/php/functions.php';
$user = require_login('farmer');                 // only logged-in farmers; harvest is saved under THEIR farm
$pageTitle = 'Add Harvest'; $active = 'add_harvest.php';
$errors = [];
$conditions = ['Excellent', 'Good', 'Fair', 'Sell Quickly'];
$v = ['crop_name' => '', 'quantity_kg' => '', 'harvest_date' => date('Y-m-d'), 'condition' => 'Good', 'price_per_kg' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) { $v[$k] = post($k); }
    if ($v['crop_name'] === '' || mb_strlen($v['crop_name']) > 80) { $errors['crop_name'] = 'Crop name is required (max 80 characters).'; }
    if (!is_numeric($v['quantity_kg']) || (float)$v['quantity_kg'] <= 0) { $errors['quantity_kg'] = 'Quantity must be a number greater than 0.'; }
    $d = DateTime::createFromFormat('Y-m-d', $v['harvest_date']);
    if (!$d || $d->format('Y-m-d') !== $v['harvest_date'] || $d > new DateTime('today')) { $errors['harvest_date'] = 'Enter a valid date that is not in the future.'; }
    if (!in_array($v['condition'], $conditions, true)) { $errors['condition'] = 'Select a condition.'; }
    if (!is_numeric($v['price_per_kg']) || (float)$v['price_per_kg'] < 0) { $errors['price_per_kg'] = 'Price must be 0 or more.'; }
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO harvests (farmer_id, crop_name, quantity_kg, harvest_date, `condition`, price_per_kg)
                               VALUES (:fid, :crop, :qty, :date, :cond, :price)');
        $stmt->execute([':fid' => $user['id'], ':crop' => $v['crop_name'], ':qty' => $v['quantity_kg'],
                        ':date' => $v['harvest_date'], ':cond' => $v['condition'], ':price' => $v['price_per_kg']]);
        flash('Harvest recorded and listed on the market board.');
        redirect('view_harvests.php');
    }
}
require __DIR__ . '/php/header.php';
?>
<h1>Add Harvest</h1>
<form method="post" class="card form" data-validate novalidate>
  <?= csrf_field() ?>
  <?php field('crop_name', 'Crop name (e.g. Tomatoes)', 'text', $v['crop_name'], $errors, 'required maxlength="80"');
        field('quantity_kg', 'Quantity harvested (kg)', 'number', $v['quantity_kg'], $errors, 'required min="0.01" step="0.01"');
        field('harvest_date', 'Harvest date', 'date', $v['harvest_date'], $errors, 'required data-nofuture max="' . date('Y-m-d') . '"');
        select_field('condition', 'Condition of produce', array_combine($conditions, $conditions), $v['condition'], $errors);
        field('price_per_kg', 'Asking price ($ per kg)', 'number', $v['price_per_kg'], $errors, 'required min="0" step="0.01"'); ?>
  <button class="btn" type="submit">Save harvest</button>
</form>
<?php require __DIR__ . '/php/footer.php'; ?>
