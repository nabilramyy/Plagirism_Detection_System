<?php
include 'includes/db.php';

$filter = $_GET['filter'] ?? 'all';
if ($filter === 'students') {
  $stmt = $conn->prepare("SELECT id,name,email,role,status FROM users WHERE role='student' ORDER BY id DESC");
  $stmt->execute();
  $result = $stmt->get_result();
} elseif ($filter === 'instructors') {
  $stmt = $conn->prepare("SELECT id,name,email,role,status FROM users WHERE role='instructor' ORDER BY id DESC");
  $stmt->execute();
  $result = $stmt->get_result();
} elseif ($filter === 'admins') {
  $stmt = $conn->prepare("SELECT id,name,email,role,status FROM users WHERE role='admin' ORDER BY id DESC");
  $stmt->execute();
  $result = $stmt->get_result();
} else {
  $stmt = $conn->prepare("SELECT id,name,email,role,status FROM users ORDER BY id DESC");
  $stmt->execute();
  $result = $stmt->get_result();
}
?>
<section class="user-management">
  <h2>User Management 👥</h2>

<?php if (isset($_GET['success'])): ?>
  <div class="notice success">✅ User added successfully!</div>
<?php elseif (isset($_GET['error'])): ?>
  <div class="notice error">⚠️ Please fill in all fields.</div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
  <div class="notice success">🗑️ User deleted successfully.</div>
<?php elseif (isset($_GET['updated'])): ?>
  <div class="notice success">✅ User updated successfully.</div>
<?php endif; ?>


  <div class="filter-row">
    <a class="btn <?= $filter=='all' ? 'active' : '' ?>" href="index.php?page=user_management&filter=all">All</a>
    <a class="btn <?= $filter=='students' ? 'active' : '' ?>" href="index.php?page=user_management&filter=students">Students</a>
    <a class="btn <?= $filter=='instructors' ? 'active' : '' ?>" href="index.php?page=user_management&filter=instructors">Instructors</a>
    <a class="btn <?= $filter=='admins' ? 'active' : '' ?>" href="index.php?page=user_management&filter=admins">Admins</a>
  </div>

  <div class="add-user-form">
    <h3>Add New User ➕</h3>
    <form method="POST" action="add_user.php" class="add-form">
      <input type="text" name="name" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email Address" required>
      <select name="role" required>
        <option value="">Select Role</option>
        <option value="student">Student</option>
        <option value="instructor">Instructor</option>
        <option value="admin">Admin</option>
      </select>
      <button type="submit" class="btn primary">Add User</button>
    </form>
  </div>



  <div class="user-cards">
    <?php while ($row = $result->fetch_assoc()): ?>
      <div class="user-card" data-role="<?= htmlspecialchars($row['role']) ?>">
        <div class="user-top">
          <div class="avatar"><?= strtoupper(substr($row['name'],0,1)) ?></div>
          <div>
            <div class="user-name"><?= htmlspecialchars($row['name']) ?></div>
            <div class="user-email"><?= htmlspecialchars($row['email']) ?></div>
          </div>
        </div>

        <div class="user-info">
          <div><strong>Role:</strong> <?= htmlspecialchars(ucfirst($row['role'])) ?></div>
          <div><strong>Status:</strong> <span class="status <?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></span></div>
        </div>

      <div class="user-actions">
        <form method="post" action="user_actions.php" class="action-form">
          <input type="hidden" name="user_id" value="<?= (int)$row['id'] ?>">

          <!-- Ban / Unban -->
          <button type="submit" name="toggle_ban" class="btn small <?= $row['status']=='active' ? 'danger' : 'success' ?>">
            <?= $row['status']=='active' ? 'Ban' : 'Unban' ?>
          </button>

          <!-- Role change -->
          <select name="new_role" class="role-select" aria-label="Change role">
            <option value="">Change role...</option>
            <?php
              $roles = ['student','instructor','admin'];
              foreach ($roles as $r) {
                if ($r !== $row['role']) {
                  echo '<option value="'.htmlspecialchars($r).'">'.ucfirst($r).'</option>';
                }
              }
            ?>
          </select>
          <button type="submit" name="change_role" class="btn small">Apply</button>
        </form>

        <!-- Edit button -->
        <a href="edit_user.php?id=<?= (int)$row['id'] ?>" class="btn small">✏️ Edit</a>

        <!-- Delete button -->
        <form method="post" action="user_actions.php" onsubmit="return confirm('Are you sure you want to delete this user?');" style="display:inline;">
          <input type="hidden" name="user_id" value="<?= (int)$row['id'] ?>">
          <button type="submit" name="delete_user" class="btn small danger">🗑️ Delete</button>
        </form>
      </div>

      </div>
    <?php endwhile; ?>
  </div>
</section>
