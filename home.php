<?php
include 'includes/db.php';

$totalUsers = 0; $totalCourses = 0; $totalSubmissions = 0; $uploadLimit = 50;

$res = $conn->query("SELECT COUNT(*) AS c FROM users");
if ($res && $row = $res->fetch_assoc()) $totalUsers = $row['c'];

$res = $conn->query("SELECT COUNT(*) AS c FROM courses");
if ($res && $row = $res->fetch_assoc()) $totalCourses = $row['c'];

$res = $conn->query("SELECT COUNT(*) AS c FROM submissions");
if ($res && $row = $res->fetch_assoc()) $totalSubmissions = $row['c'];

$res = $conn->query("SELECT upload_limit_mb FROM settings WHERE id = 1 LIMIT 1");
if ($res && $row = $res->fetch_assoc()) $uploadLimit = (int)$row['upload_limit_mb'];
?>
<section class="dashboard">
  <h2>Dashboard Overview</h2>

  <div class="stats-cards">
    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-users"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= (int)$totalUsers ?></div>
        <div class="stat-label">Total Users</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-file-alt"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= (int)$totalSubmissions ?></div>
        <div class="stat-label">Total Submissions</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-book-open"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= (int)$totalCourses ?></div>
        <div class="stat-label">Total Courses</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-hdd"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= (int)$uploadLimit ?> MB</div>
        <div class="stat-label">Max Upload Capacity</div>
      </div>
    </div>
  </div>
</section>
