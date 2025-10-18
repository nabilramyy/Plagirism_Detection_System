<?php
include 'includes/db.php';

$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $limit = (int)($_POST['upload_limit'] ?? 50);
  if ($limit < 1) $limit = 1;
  $stmt = $conn->prepare("INSERT INTO settings (id, upload_limit_mb) VALUES (1, ?) ON DUPLICATE KEY UPDATE upload_limit_mb = ?");
  $stmt->bind_param("ii", $limit, $limit);
  $stmt->execute();
  $stmt->close();
  $feedback = "Settings saved.";
}

$res = $conn->query("SELECT upload_limit_mb FROM settings WHERE id=1 LIMIT 1");
$upload_limit = 50;
if ($res && $r = $res->fetch_assoc()) $upload_limit = (int)$r['upload_limit_mb'];
?>
<section class="settings">
  <h2>System Settings ⚙️</h2>

  <?php if ($feedback): ?>
    <div class="notice"><?= htmlspecialchars($feedback) ?></div>
  <?php endif; ?>

  <form method="post" class="settings-form">
    <label>Max upload file size (MB)</label>
    <input type="number" name="upload_limit" min="1" max="1000" value="<?= $upload_limit ?>">

    <button type="submit" class="btn primary">Save</button>
  </form>
</section>
