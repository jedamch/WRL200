<?php

session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../requirerole.php';

requirerole(['Admin']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../style.css">
  
</head>
<body>
  <div class="topnavigationbar">
    <div class="nav-left">
        <a href="../index.php">Home</a>
        <a href="../sitebooks.php">Books</a>
        <a href="../suggestbook.php">Suggest A Book</a>
        <a href="../lists.php">Lists</a>
        <a href="../about.php">About</a>
    </div>

    <div class="nav-right">
    <?php
    if (isset($_SESSION["User_Name"])) {
        $profilePic = $_SESSION['Avatar'] ?? 'default.png';
        echo "<a href='/DSTLib/Members/profile.php?user=" . $_SESSION['User_Name'] . "' class='profile-link'>";
        echo "<img src='/DSTLib/Members/Uploads/$profilePic' alt='Profile' class='profile-pic'>";
        echo "<span>" . htmlspecialchars($_SESSION['User_Name']) . "</span>";
        echo "</a>";
        echo "<a href='../logout.php'>Logout</a>";
        echo "<a href='/DSTLib/notifications.php' class='bell-icon' id='notifBell'>
        🔔 <span class='notif-count' id='notifCount' style='display:none;'></span>
      </a>";
    } else {
        echo "<a href='../login.php'>Log in</a>";
        echo "<a href='../createaccount.php'>Create Account</a>";
    }

    if (isset($_SESSION['UserLevel']) && $_SESSION['UserLevel'] === "Admin") {
        echo "<a href='dashboard.php'>Admin Dash</a>";
    }
    ?>
    </div>
</div>

  <header class="admin-header">
    <div class="admin-logo">Admin Panel</div>
    <div class="admin-header-tag">DSTLib</div>
  </header>

 <main class="admin-main">
    <p class="admin-page-title">Control Centre</p>
    <h1>Welcome back, <span><?= htmlspecialchars($_SESSION['User_Name'] ?? 'Admin') ?></span></h1>

    <!-- Books -->
    <p class="section-label">Books</p>
    <div class="cards">
      <a href="booklist.php" class="card">
        <div class="card-icon">📚</div>
        <div class="card-title">View All Books</div>
        <div class="card-desc">Browse, edit, and delete existing book records.</div>
        <div class="card-arrow">Open list</div>
      </a>
      <a href="addbooks.php" class="card">
        <div class="card-icon">➕</div>
        <div class="card-title">Add Book</div>
        <div class="card-desc">Add a new book to the database.</div>
        <div class="card-arrow">Add new</div>
      </a>
    </div>

    <!-- Authors -->
    <p class="section-label">Authors</p>
    <div class="cards">
      <a href="authorlist.php" class="card">
        <div class="card-icon">🖊️</div>
        <div class="card-title">View All Authors</div>
        <div class="card-desc">Browse, edit, and delete existing author records.</div>
        <div class="card-arrow">Open list</div>
      </a>
      <a href="addauthors.php" class="card">
        <div class="card-icon">➕</div>
        <div class="card-title">Add Author</div>
        <div class="card-desc">Add a new author to the database.</div>
        <div class="card-arrow">Add new</div>
      </a>
    </div>

    <!-- Users -->
    <p class="section-label">Users</p>
    <div class="cards">
      <a href="users.php" class="card">
        <div class="card-icon">👥</div>
        <div class="card-title">View All Users</div>
        <div class="card-desc">Browse and manage registered user accounts.</div>
        <div class="card-arrow">Not yet available</div>
      </a>
          <a href="reports.php" class="card">
        <div class="card-icon">🚩</div>
        <div class="card-title">Reports</div>
        <div class="card-desc">Review reported comments from users.</div>
        <div class="card-arrow">Review</div>
    </a>
        <a href="suggestions.php" class="card">
        <div class="card-icon">📬</div>
        <div class="card-title">Book Suggestions</div>
        <div class="card-desc">Review book submissions from users.</div>
        <div class="card-arrow">Review</div>
    </a>
    </div>




  </main>

  <footer class="admin-footer">
    <span class="footer-text">Admin Dashboard</span>
    <span class="footer-text" id="ts"></span>
  </footer>

  <script>
    const ts = document.getElementById('ts');
    const now = new Date();
    ts.textContent = now.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
  </script>
<script src="/DSTLib/main.js"></script>
<a href="index.php">← Back</a>
</body>
</html>