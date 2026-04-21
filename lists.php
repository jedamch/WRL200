<?php
session_start();
require_once("db.php");

$stmt = $conn->prepare("
    SELECT tbl_Lists.ID, tbl_Lists.Name, tbl_Lists.Slug, tbl_Lists.Description, tbl_Lists.Created_At,
           tbl_Users.User_Name,
           COUNT(tbl_List_Books.ID) AS Book_Count
    FROM tbl_Lists
    LEFT JOIN tbl_Users ON tbl_Lists.User_ID = tbl_Users.ID
    LEFT JOIN tbl_List_Books ON tbl_Lists.ID = tbl_List_Books.List_ID
    WHERE tbl_Lists.Is_Public = 1
    GROUP BY tbl_Lists.ID
    ORDER BY tbl_Lists.Created_At DESC
");
$stmt->execute();
$lists = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lists</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

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
        echo "<a href='/DSTLib/Members/profile.php?user=" . $_SESSION['User_Name'] . "' class='profile-link'>";
        echo "<img src='Members/Uploads/$profilePic' alt='Profile' class='profile-pic'>";
        echo "<span>" . htmlspecialchars($_SESSION['User_Name']) . "</span>";
        echo "</a>";
        echo "<a href='logout.php'>Logout</a>";
        echo "<a href='createlists.php'>+ New List</a>";
    } else {
        echo "<a href='login.php'>Log in</a>";
        echo "<a href='createaccount.php'>Create Account</a>";
    }
    ?>
    </div>
</div>

<div class="lists-page">
    <h1>Public Lists</h1>

    <?php if ($lists->num_rows > 0): ?>
        <?php while ($l = $lists->fetch_assoc()): ?>
            <div class="list-card">
                <div>
                    <a href="list.php?user=<?= urlencode($l['User_Name']) ?>&list=<?= urlencode($l['Slug']) ?>">
                        <?= htmlspecialchars($l['Name']) ?>
                    </a>
                    <span> by <a href="Members/profile.php?user=<?= htmlspecialchars($l['User_Name']) ?>"><?= htmlspecialchars($l['User_Name']) ?></a></span>
                    <span style="color:#999;font-size:0.8rem;"> — <?= $l['Book_Count'] ?> book<?= $l['Book_Count'] !== 1 ? 's' : '' ?></span>
                </div>
                <?php if (!empty($l['Description'])): ?>
                    <p style="margin:4px 0 0;font-size:0.85rem;color:#555;"><?= htmlspecialchars($l['Description']) ?></p>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No public lists yet.</p>
    <?php endif; ?>
</div>

<script src="/DSTLib/main.js"></script>
</body>
</html>