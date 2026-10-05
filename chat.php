<?php
require_once __DIR__ . '/php/functions.php';
$user = require_login();
$pageTitle = 'Chat'; $active = 'enquiries.php';
$eid  = (int)($_GET['enquiry_id'] ?? 0);
$role = $user['role'];                            // sender role comes from the session, never from the browser

$enq = enquiry_for_user($pdo, $eid, $user);       // authorisation: only the two people in this enquiry
if (!$enq) { http_response_code(403); exit('You do not have access to this conversation.'); }

// Fallback for browsers without JavaScript: normal form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $body = post('body');
    if ($body === '' || mb_strlen($body) > 1000) { flash('Message must be 1-1000 characters.', 'error'); }
    else { $pdo->prepare('INSERT INTO messages (enquiry_id, sender, body) VALUES (:e, :s, :b)')->execute([':e' => $eid, ':s' => $role, ':b' => $body]); }
    redirect('chat.php?enquiry_id=' . $eid);
}
$msgs = $pdo->prepare("SELECT message_id, sender, body, DATE_FORMAT(sent_at, '%d %b %H:%i') AS sent_at FROM messages WHERE enquiry_id = :e ORDER BY message_id");
$msgs->execute([':e' => $eid]);
$msgs = $msgs->fetchAll();
$last = $msgs ? end($msgs)['message_id'] : 0;
$senderLabel = fn(string $s) => $s === $role ? 'You' : ucfirst($s);
$other = $role === 'farmer' ? $enq['business_name'] . ' (buyer)' : $enq['farm_name'] . ' (farm)';
require __DIR__ . '/php/header.php';
?>
<h1>Chat: <?= e($enq['crop_name']) ?></h1>
<p>You are chatting with <strong><?= e($other) ?></strong>. <a href="enquiries.php">Back to my chats</a></p>
<section id="chat" class="card" data-enquiry="<?= $eid ?>" data-role="<?= e($role) ?>" data-last="<?= (int)$last ?>">
  <div id="chatLog" class="chat-log" role="log" aria-live="polite" aria-label="Conversation">
    <div class="msg buyer<?= $role === 'buyer' ? ' mine' : '' ?>"><strong><?= $senderLabel('buyer') ?></strong><p><?= e($enq['message']) ?></p><small><?= e(substr($enq['enquiry_date'], 0, 16)) ?></small></div>
    <?php foreach ($msgs as $m): ?>
      <div class="msg <?= e($m['sender']) ?><?= $m['sender'] === $role ? ' mine' : '' ?>"><strong><?= $senderLabel($m['sender']) ?></strong><p><?= e($m['body']) ?></p><small><?= e($m['sent_at']) ?></small></div>
    <?php endforeach; ?>
  </div>
  <form method="post" class="chat-form">
    <?= csrf_field() ?>
    <input type="hidden" name="enquiry_id" value="<?= $eid ?>">
    <label for="body" class="sr-only">Your message</label>
    <textarea id="body" name="body" rows="2" maxlength="1000" required placeholder="Type a message..."></textarea>
    <button class="btn" type="submit">Send</button>
  </form>
  <p id="chatStatus" class="hint">New messages appear automatically.</p>
</section>
<?php require __DIR__ . '/php/footer.php'; ?>
