<?php
/** api_chat.php - JSON chat endpoint (login required). GET = fetch new messages, POST = send then fetch. */
require_once __DIR__ . '/functions.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$user = current_user();
if (!$user) { http_response_code(401); echo json_encode(['error' => 'Please log in again.']); exit; }
$eid = (int)($_GET['enquiry_id'] ?? $_POST['enquiry_id'] ?? 0);
if (!enquiry_for_user($pdo, $eid, $user)) { http_response_code(403); echo json_encode(['error' => 'No access to this conversation.']); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $body = post('body');
    if ($body === '' || mb_strlen($body) > 1000) {
        http_response_code(422); echo json_encode(['error' => 'Message must be 1-1000 characters.']); exit;
    }
    $pdo->prepare('INSERT INTO messages (enquiry_id, sender, body) VALUES (:e, :s, :b)')
        ->execute([':e' => $eid, ':s' => $user['role'], ':b' => $body]);   // sender = logged-in role (cannot be forged)
}
$after = (int)($_GET['after'] ?? $_POST['after'] ?? 0);
$stmt = $pdo->prepare("SELECT message_id, sender, body, DATE_FORMAT(sent_at, '%d %b %H:%i') AS sent_at
                       FROM messages WHERE enquiry_id = :e AND message_id > :a ORDER BY message_id");
$stmt->execute([':e' => $eid, ':a' => $after]);
echo json_encode(['messages' => $stmt->fetchAll()]);
