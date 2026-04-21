<?php
session_start();
require_once("db.php");

if (!isset($_SESSION['User_Name'])) {
    header("Location: login.php");
    exit;
}

$errors  = [];
$success = "";
$userId  = $_SESSION['ID'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title'] ?? '');
    $author     = trim($_POST['author'] ?? '');
    $year       = trim($_POST['year'] ?? '');
    $reason     = trim($_POST['reason'] ?? '');

    if (empty($title)) $errors[] = "Title is required.";
    if (empty($author)) $errors[] = "Author is required.";

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO tbl_Book_Suggestions (User_ID, Title, Author, Year, Reason, Created_At) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issss", $userId, $title, $author, $year, $reason);
        $stmt->execute();
        $success = "Your suggestion has been submitted. Thank you!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suggest a Book</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="topnavigationbar">
    <div class="nav-left">
        <a href="index.php">Home</a>
        <a href="books.php">Books</a>
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
    } else {
        echo "<a href='login.php'>Log in</a>";
        echo "<a href='createaccount.php'>Create Account</a>";
    }

    if (isset($_SESSION['UserLevel']) && $_SESSION['UserLevel'] === 'Admin') {
        echo "<a href='Restricted/dashboard.php'>Admin Dash</a>";
    }
    ?>
    </div>
</div>

<div class="suggest-page">
    <h1>Suggest a Book</h1>
    <p>Think a book should be on DSTLib? Submit it here and an admin will review it.</p>

    <?php if (!empty($errors)): ?>
        <div style="color:red;">
            <?php foreach ($errors as $e): ?>
                <p><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color:green;"><?= $success ?></p>
    <?php endif; ?>

    <form method="POST" action="suggestbook.php">

        <label>Title: *</label><br>
        <input type="text" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"><br><br>

        <label>Author: *</label><br>
        <input type="text" name="author" value="<?= htmlspecialchars($_POST['author'] ?? '') ?>"><br><br>

        <label>Year of Publication:</label><br>
        <input type="text" name="year" value="<?= htmlspecialchars($_POST['year'] ?? '') ?>"><br><br>

        <label>Why should this book be added?</label><br>
        <textarea name="reason" rows="4" style="width:100%;"><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea><br><br>

        <input type="submit" value="Submit Suggestion">
    </form>
</div>

<script src="/DSTLib/main.js"></script>
</body>
</html>