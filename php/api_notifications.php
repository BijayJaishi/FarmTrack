<?php
/** api_notifications.php - JSON feed for the unread badge, pop-up toasts and the message panel. Polled every 5 s by js/notify.js. */
define('NO_ACTIVITY_TOUCH', true);
require_once __DIR__ . '/functions.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$user = current_user();
if (!$user) { http_response_code(401); echo json_encode(['error' => 'Not logged in']); exit; }

$short = fn(string $t) => mb_strimwidth(preg_replace('/\s+/', ' ', $t), 0, 90, '…');
$unread = unread_items($pdo, $user);
$perChat = [];
foreach ($unread as $i) { $perChat[$i['enquiry_id']] = ($perChat[$i['enquiry_id']] ?? 0) + 1; }

$items = array_map(fn($i) => [
    'key' => $i['k'], 'enquiry_id' => (int)$i['enquiry_id'], 'who' => $i['who'], 'crop' => $i['crop_name'],
    'avatar' => avatar_html($i['who'], $i['av'], 'sm'),            // server-built, already escaped
    'text' => $short($i['body']), 'time' => date('H:i', strtotime($i['ts'])),
], $unread);

$recent = array_map(function ($r) use ($user, $perChat, $short) {
    $farmer = $user['role'] === 'farmer';
    $who = $farmer ? $r['business_name'] : $r['farm_name'];
    return [
        'enquiry_id' => (int)$r['enquiry_id'], 'who' => $who, 'crop' => $r['crop_name'],
        'avatar' => avatar_html($who, $farmer ? $r['bav'] : $r['fav'], 'sm'),
        'text' => $short($r['last_body']), 'mine' => $r['last_sender'] === $user['role'],
        'time' => date('d M H:i', strtotime($r['last_at'])), 'unread' => $perChat[$r['enquiry_id']] ?? 0,
    ];
}, recent_conversations($pdo, $user));

echo json_encode(['unread' => count($unread), 'items' => $items, 'recent' => $recent, 'time' => date('H:i:s')]);
