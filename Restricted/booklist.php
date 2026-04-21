<?php

session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../requirerole.php';

requirerole(['Admin']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book list</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <p>Book List</p>
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

    <?php 
    $sql = "SELECT tbl_Books.ID, tbl_Books.Title, tbl_Books.Synopsis, tbl_Books.Year_Of_Release, tbl_Books.Cover, tbl_Authors.Author_Name FROM tbl_Books
            INNER JOIN tbl_Authors ON tbl_Books.Author_ID = tbl_Authors.ID";

    $result = $conn->query($sql);     
    ?>

    <table class="styled-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Synopsis</th>
                <th>Year</th>
                <th>Author</th>
                <th>Cover</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['Title']) . '</td>';
                echo '<td>' . htmlspecialchars($row['Synopsis']) . '</td>';
                echo '<td>' . htmlspecialchars($row['Year_Of_Release']) . '</td>';
                echo '<td>' . htmlspecialchars($row['Author_Name']) . '</td>';
                echo '<td><img src="/DSTLib/Restricted/Books/Covers/' . htmlspecialchars($row['Cover']) . '" width="50" height="75" style="object-fit:cover;"></td>';

                echo '<td>
                <a href="editbooks.php?ID=' . $row['ID'] . '">Edit</a>
        |
        <a href="deletebook.php?ID=' . $row['ID'] . '" 
           onclick="return confirm(\'Are you sure you want to delete this book?\')">Delete</a>
      </td>';
echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6">No books found.</td></tr>';
        }
        ?>
        </tbody>
    </table>
<script src="/DSTLib/main.js"></script>
</body>
</html>