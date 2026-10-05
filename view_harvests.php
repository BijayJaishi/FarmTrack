<?php
require_once __DIR__ . '/php/functions.php';
$user = require_login('farmer');
$fid = $user['id'];                               // always the logged-in farmer, never taken from the URL or form
$pageTitle = 'My Harvests'; $active = 'view_harvests.php';

// UPDATE / DELETE actions (POST + CSRF; every query is limited to this farmer's own rows)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $hid = (int)post('harvest_id');
    $action = post('action');
    if ($action === 'toggle') {
        $pdo->prepare("UPDATE harvests SET status = IF(status='available','sold','available') WHERE harvest_id = :id AND farmer_id = :fid")
            ->execute([':id' => $hid, ':fid' => $fid]);
        flash('Harvest status updated.');
    } elseif ($action === 'price') {
        $np = post('new_price');
        $cur = $pdo->prepare('SELECT price_per_kg FROM harvests WHERE harvest_id = :id AND farmer_id = :fid');
        $cur->execute([':id' => $hid, ':fid' => $fid]);
        $old = $cur->fetchColumn();
        if (!is_numeric($np) || (float)$np < 0 || $old === false) { flash('Enter a valid price.', 'error'); }
        elseif ((float)$old !== (float)$np) {
            $pdo->prepare('UPDATE harvests SET price_per_kg = :p WHERE harvest_id = :id AND farmer_id = :fid')->execute([':p' => $np, ':id' => $hid, ':fid' => $fid]);
            $pdo->prepare('INSERT INTO price_history (harvest_id, old_price, new_price) VALUES (:id, :o, :n)')->execute([':id' => $hid, ':o' => $old, ':n' => $np]);
            flash('Price updated. Buyers on the market board see it live.');
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM harvests WHERE harvest_id = :id AND farmer_id = :fid')->execute([':id' => $hid, ':fid' => $fid]);
        flash('Harvest deleted.');
    }
    redirect('view_harvests.php');
}

// READ with filters (GET)
$crop = trim($_GET['crop'] ?? ''); $from = trim($_GET['from'] ?? ''); $to = trim($_GET['to'] ?? '');
$sql = 'SELECT * FROM harvests WHERE farmer_id = :fid'; $params = [':fid' => $fid];
if ($crop !== '') { $sql .= ' AND crop_name LIKE :crop'; $params[':crop'] = '%' . $crop . '%'; }
if ($from !== '') { $sql .= ' AND harvest_date >= :from'; $params[':from'] = $from; }
if ($to   !== '') { $sql .= ' AND harvest_date <= :to';   $params[':to']   = $to; }
$stmt = $pdo->prepare($sql . ' ORDER BY harvest_date DESC');
$stmt->execute($params);
$rows = $stmt->fetchAll();
$totalKg = array_sum(array_column($rows, 'quantity_kg'));
require __DIR__ . '/php/header.php';
?>
<h1>My Harvest History</h1>
<form method="get" class="card filters">
  <div class="field"><label for="crop">Crop</label><input type="text" id="crop" name="crop" value="<?= e($crop) ?>"></div>
  <div class="field"><label for="from">From date</label><input type="date" id="from" name="from" value="<?= e($from) ?>"></div>
  <div class="field"><label for="to">To date</label><input type="date" id="to" name="to" value="<?= e($to) ?>"></div>
  <button class="btn" type="submit">Filter</button>
</form>

<?php if ($rows): ?>
  <p><strong><?= count($rows) ?></strong> record(s) &middot; total <strong><?= number_format($totalKg, 2) ?> kg</strong></p>
  <div class="table-wrap">
  <table>
    <caption class="sr-only">Harvest records</caption>
    <thead><tr><th scope="col">Crop</th><th scope="col">Quantity (kg)</th><th scope="col">Date</th><th scope="col">Condition</th>
      <th scope="col">Price/kg</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): $hid = (int)$r['harvest_id']; ?>
      <tr>
        <td><?= e($r['crop_name']) ?></td>
        <td><?= number_format((float)$r['quantity_kg'], 2) ?></td>
        <td><?= e($r['harvest_date']) ?></td>
        <td><?= e($r['condition']) ?></td>
        <td>$<?= number_format((float)$r['price_per_kg'], 2) ?></td>
        <td><span class="badge <?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
        <td class="actions">
          <form method="post" class="price-form"><?= csrf_field() ?>
            <input type="hidden" name="harvest_id" value="<?= $hid ?>">
            <label class="sr-only" for="p<?= $hid ?>">New price for <?= e($r['crop_name']) ?></label>
            <input id="p<?= $hid ?>" type="number" name="new_price" min="0" step="0.01" value="<?= e($r['price_per_kg']) ?>" required>
            <button class="btn small" name="action" value="price">Update price</button>
          </form>
          <form method="post"><?= csrf_field() ?>
            <input type="hidden" name="harvest_id" value="<?= $hid ?>">
            <button class="btn small secondary" name="action" value="toggle">Mark <?= $r['status'] === 'available' ? 'sold' : 'available' ?></button>
            <button class="btn small danger" name="action" value="delete" data-confirm="Delete this harvest record?">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
<?php else: ?>
  <p class="alert error">No harvest records found. <a href="add_harvest.php">Add your first harvest.</a></p>
<?php endif; ?>
<?php require __DIR__ . '/php/footer.php'; ?>
