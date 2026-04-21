<?php

session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../requirerole.php';

requirerole(['Admin']);

$result = $conn->query("SELECT ID, Author_Name, Year_Of_Birth, Year_Of_Death, Place_Of_Birth FROM tbl_Authors ORDER BY Author_Name ASC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Author List</title>
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

<h2>Author List</h2>
<a href="addauthors.php">+ Add New Author</a><br><br>

<table class="styled-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Born</th>
            <th>Died</th>
            <th>Place of Birth</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['Author_Name']) ?></td>
                <td><?= htmlspecialchars($row['Year_Of_Birth']) ?></td>
                <td><?= htmlspecialchars($row['Year_Of_Death'] ?? '—') ?></td>
                <td><?= htmlspecialchars($row['Place_Of_Birth'] ?? '—') ?></td>
                <td>
                    <a href="editauthors.php?ID=<?= $row['ID'] ?>">Edit</a>
                    |
                    <a href="deleteauthors.php?ID=<?= $row['ID'] ?>"
                       onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($row['Author_Name']) ?>?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="5">No authors found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<br><a href="dashboard.php">← Back to dashboard</a>
<script src="/DSTLib/main.js"></script>
</body>
</html>