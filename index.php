<?php
$page = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <script src="assets/js/script.js" defer></script>
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>
  <?php include 'includes/header.php'; ?>
  <?php include 'includes/sidebar.php'; ?>

  <main class="main-content" id="mainContent">
    <?php
      if ($page == 'home') include 'home.php';
      elseif ($page == 'user_management') include 'user_management.php';
      elseif ($page == 'course_management') include 'course_management.php';
      elseif ($page == 'submissions_overview') include 'submissions_overview.php';
      elseif ($page == 'system_settings') include 'system_settings.php';
      else echo "<h2 style='color:#fff;padding:20px'>Page not found</h2>";
    ?>
  </main>
</body>
</html>
