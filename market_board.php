<?php
require_once __DIR__ . "/php/functions.php";
$user = current_user();
$pageTitle = "Market Board"; $active = "market_board.php";
$sortOptions = ["newest" => "h.harvest_date DESC", "price" => "h.price_per_kg ASC", "crop" => "h.crop_name ASC"];
$sort = array_key_exists($_GET["sort"] ?? "", $sortOptions) ? $_GET["sort"] : "newest";   // whitelist for ORDER BY
$q = trim($_GET["q"] ?? "");

$sql = "SELECT h.*, f.farm_name, f.location FROM harvests h JOIN farmers f ON f.farmer_id = h.farmer_id WHERE h.status = \"available\"";
$params = [];
if ($q !== "") { $sql .= " AND h.crop_name LIKE :q"; $params[":q"] = "%" . $q . "%"; }
$stmt = $pdo->prepare($sql . " ORDER BY " . $sortOptions[$sort]);
$stmt->execute($params);
$rows = $stmt->fetchAll();
require __DIR__ . "/php/header.php";
?>
<h1>Market Board</h1>
<p>Produce currently available. Prices update automatically - use the calculator to see your cost.</p>
<form method="get" class="card filters">
  <div class="field"><label for="boardSearch">Search crop (filters instantly)</label>
    <input type="search" id="boardSearch" name="q" value="<?= e($q) ?>"></div>
  <div class="field"><label for="sort">Sort by</label>
    <select id="sort" name="sort">
      <option value="newest"<?= $sort === "newest" ? " selected" : "" ?>>Newest harvest</option>
      <option value="price"<?= $sort === "price" ? " selected" : "" ?>>Lowest price</option>
      <option value="crop"<?= $sort === "crop" ? " selected" : "" ?>>Crop name</option>
    </select></div>
  <button class="btn" type="submit">Apply</button>
</form>
<p class="hint"><span class="live-dot" aria-hidden="true"></span> <span id="liveStatus">Live prices on.</span></p>
<p id="newStock" class="alert success" hidden>The produce list has changed. <a href="">Refresh to see new listings.</a></p>

<?php if ($rows): ?>
<div class="table-wrap">
<table id="boardTable">
  <caption class="sr-only">Produce available for sale with live prices</caption>
  <thead><tr><th scope="col">Crop</th><th scope="col">Farm</th><th scope="col">Location</th><th scope="col">Available (kg)</th>
    <th scope="col">Condition</th><th scope="col">Price/kg (live)</th><th scope="col">Order (kg)</th><th scope="col">Your cost</th><th scope="col">Enquire</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): $id = (int)$r["harvest_id"]; ?>
    <tr data-id="<?= $id ?>" data-price="<?= e($r["price_per_kg"]) ?>" data-qty="<?= e($r["quantity_kg"]) ?>"
        data-search="<?= e(strtolower($r["crop_name"] . " " . $r["farm_name"] . " " . $r["location"])) ?>">
      <td><?= e($r["crop_name"]) ?></td><td><a href="farm.php?id=<?= (int)$r["farmer_id"] ?>"><?= e($r["farm_name"]) ?></a></td><td><?= e($r["location"]) ?></td>
      <td class="qty-cell"><?= number_format((float)$r["quantity_kg"], 2) ?></td><td><?= e($r["condition"]) ?></td>
      <td class="price-cell">$<?= number_format((float)$r["price_per_kg"], 2) ?></td>
      <td><label class="sr-only" for="oq<?= $id ?>">Kilograms of <?= e($r["crop_name"]) ?> to order</label>
          <input class="order-qty" id="oq<?= $id ?>" type="number" min="0" step="0.5" placeholder="kg"></td>
      <td class="est-cost">-</td>
      <td><?php if ($user && $user["role"] === "buyer"): ?><a class="btn small" href="contact.php?harvest_id=<?= $id ?>">Enquire<span class="sr-only"> about <?= e($r["crop_name"]) ?></span></a>
        <?php elseif (!$user): ?><a href="login.php?next=<?= urlencode("contact.php?harvest_id=$id") ?>">Log in to enquire</a>
        <?php else: ?>-<?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<p id="noMatch" class="alert error" hidden>No produce matches your search.</p>
<?php else: ?>
  <p class="alert error">No produce is currently available.</p>
<?php endif; ?>
<?php require __DIR__ . "/php/footer.php"; ?>
