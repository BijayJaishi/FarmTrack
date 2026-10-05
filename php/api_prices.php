<?php
/** api_prices.php - JSON feed of current prices/stock. Polled by js/live.js (AJAX). */
require_once __DIR__ . "/functions.php";
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
$items = $pdo->query("SELECT harvest_id, price_per_kg, quantity_kg, status FROM harvests")->fetchAll();
echo json_encode(["items" => $items, "time" => date("H:i:s")]);
