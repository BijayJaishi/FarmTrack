<?php
require_once __DIR__ . "/php/functions.php";
$pageTitle = "Dashboard"; $active = "dashboard.php";
$user = require_login('farmer');
$fid = $user['id'];
$data = null;
if ($fid) {
    $q = fn(string $sql) => (function() use ($pdo, $sql, $fid) { $s = $pdo->prepare($sql); $s->execute([":f" => $fid]); return $s; })();
    $sum = $q("SELECT COUNT(*) AS n, COALESCE(SUM(quantity_kg),0) AS kg,
                      COALESCE(SUM(CASE WHEN status=\"available\" THEN quantity_kg*price_per_kg END),0) AS stock_value,
                      COALESCE(AVG(price_per_kg),0) AS avg_price
               FROM harvests WHERE farmer_id = :f")->fetch();
    $crops  = $q("SELECT crop_name, SUM(quantity_kg) AS kg, AVG(price_per_kg) AS avg_price FROM harvests WHERE farmer_id = :f GROUP BY crop_name ORDER BY kg DESC")->fetchAll();
    $months = $q("SELECT DATE_FORMAT(harvest_date, \"%Y-%m\") AS ym, SUM(quantity_kg) AS kg FROM harvests WHERE farmer_id = :f GROUP BY ym ORDER BY ym")->fetchAll();
    $prices = $q("SELECT ph.old_price, ph.new_price, ph.changed_at, h.crop_name FROM price_history ph JOIN harvests h ON h.harvest_id = ph.harvest_id
                  WHERE h.farmer_id = :f ORDER BY ph.changed_at DESC LIMIT 5")->fetchAll();
    $enq = $q("SELECT COUNT(*) FROM enquiries e JOIN harvests h ON h.harvest_id = e.harvest_id WHERE h.farmer_id = :f")->fetchColumn();
    $maxCrop  = max(array_column($crops, "kg") ?: [1]);
    $maxMonth = max(array_column($months, "kg") ?: [1]);
    $data = true;
}
require __DIR__ . "/php/header.php";
?>
<h1>Farm Dashboard &amp; Seasonal Analysis</h1>
<p>Signed in as <strong><?= e($user["name"]) ?></strong>.</p>
<?php if ($data): ?>
<div class="stats">
  <div class="card stat"><strong><?= (int)$sum["n"] ?></strong><span>Harvest records</span></div>
  <div class="card stat"><strong><?= number_format((float)$sum["kg"], 0) ?> kg</strong><span>Total harvested</span></div>
  <div class="card stat"><strong>$<?= number_format((float)$sum["stock_value"], 2) ?></strong><span>Value of unsold stock</span></div>
  <div class="card stat"><strong>$<?= number_format((float)$sum["avg_price"], 2) ?></strong><span>Average price / kg</span></div>
  <div class="card stat"><strong><?= (int)$enq ?></strong><span>Buyer enquiries</span></div>
</div>

<h2>Harvest by crop</h2>
<div class="table-wrap"><table><caption class="sr-only">Total kilograms per crop</caption>
  <thead><tr><th scope="col">Crop</th><th scope="col">Total kg</th><th scope="col">Avg price/kg</th><th scope="col">Share</th></tr></thead><tbody>
  <?php foreach ($crops as $c): ?>
    <tr><td><?= e($c["crop_name"]) ?></td><td><?= number_format((float)$c["kg"], 2) ?></td><td>$<?= number_format((float)$c["avg_price"], 2) ?></td>
      <td class="barcell"><div class="bar" style="width:<?= (int)round($c["kg"] / $maxCrop * 100) ?>%"></div></td></tr>
  <?php endforeach; ?></tbody></table></div>

<h2>Seasonal trend (kg per month)</h2>
<div class="table-wrap"><table><caption class="sr-only">Kilograms harvested per month</caption>
  <thead><tr><th scope="col">Month</th><th scope="col">Total kg</th><th scope="col">Volume</th></tr></thead><tbody>
  <?php foreach ($months as $mo): ?>
    <tr><td><?= e($mo["ym"]) ?></td><td><?= number_format((float)$mo["kg"], 2) ?></td>
      <td class="barcell"><div class="bar alt" style="width:<?= (int)round($mo["kg"] / $maxMonth * 100) ?>%"></div></td></tr>
  <?php endforeach; ?></tbody></table></div>

<h2>Recent price changes</h2>
<?php if ($prices): ?><ul><?php foreach ($prices as $p): ?>
  <li><?= e($p["crop_name"]) ?>: $<?= number_format((float)$p["old_price"], 2) ?> &rarr; $<?= number_format((float)$p["new_price"], 2) ?>
      <?= $p["new_price"] > $p["old_price"] ? "(up)" : "(down)" ?> on <?= e(substr($p["changed_at"], 0, 10)) ?></li>
<?php endforeach; ?></ul><?php else: ?><p>No price changes recorded yet.</p><?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . "/php/footer.php"; ?>
