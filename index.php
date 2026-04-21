<?php 
include 'db.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DSTLib</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h2>Welcome to DSTLib</h2>

<div class="topnavigationbar">
    <div class="nav-left">
        <a href="index.php">Home</a>
        <a href="sitebooks.php">Books</a>
        <a href="suggestbook.php">Suggest A Book</a>
        <a href="lists.php">Lists</a>
        <a href="about.php">About</a>
    </div>

    <div class="nav-right">
    <?php
    if (isset($_SESSION["User_Name"])) {
        $profilePic = $_SESSION['Avatar'] ?? 'default.png';
        echo "<a href='Members/profile.php?user=" . $_SESSION['User_Name'] . "' class='profile-link'>";
        echo "<img src='Members/Uploads/$profilePic' alt='Profile' class='profile-pic'>";
        echo "<span>" . htmlspecialchars($_SESSION['User_Name']) . "</span>";
        echo "</a>";
        echo "<a href='logout.php'>Logout</a>";
        echo "<a href='/DSTLib/notifications.php' class='bell-icon' id='notifBell'>
        🔔 <span class='notif-count' id='notifCount' style='display:none;'></span>
      </a>";
    } else {
        echo "<a href='login.php'>Log in</a>";
        echo "<a href='createaccount.php'>Create Account</a>";
    }

    if (isset($_SESSION['UserLevel']) && $_SESSION['UserLevel'] === "Admin") {
        echo "<a href='Restricted/dashboard.php'>Admin Dash</a>";
    }
    ?>
    </div>
</div>

<!-- Search bar outside the nav -->
<div class="search-wrapper">
    <input type="text" id="searchInput" placeholder="Search books, authors, users..." autocomplete="off">
    <div id="searchResults" class="search-results"></div>
</div>

<p>Books released this year: </p>

<div class="book-grid">
<?php
$sql = "SELECT ID, Slug, Title, Cover FROM tbl_Books WHERE Year_Of_Release='2026'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<a href="books.php?book=' . htmlspecialchars($row['Slug']) . '" class="book-card">';
        echo '<img src="Restricted/Books/Covers/' . htmlspecialchars($row['Cover']) . '" alt="' . htmlspecialchars($row['Title']) . '">';
        echo '</a>';
    }
} else {
    echo '<p>No books found.</p>';
}
?>
</div>


<script src="/DSTLib/main.js"></script>
</body>
</html>

