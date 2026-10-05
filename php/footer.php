</main>
<footer class="site-footer">
  <div class="wrap">FarmTrack &copy; <?= date('Y') ?> &mdash; MIT122 Interactive Web Design and Development</div>
</footer>
<script src="js/validate.js"></script>
<script src="js/live.js"></script>
<?php if (current_user()): ?>
<div id="toasts" class="toasts" role="status" aria-live="polite"></div>
<script src="js/notify.js"></script>
<?php endif; ?>
</body>
</html>
