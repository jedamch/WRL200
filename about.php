<?php
session_start();
require_once("db.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - DSTLib</title>
    <link rel="stylesheet" href="style.css">
    <script src="/DSTLib/main.js" defer></script>
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
        echo "<a href='/DSTLib/notifications.php' class='bell-icon' id='notifBell'>🔔<span class='notif-count' id='notifCount' style='display:none;'></span></a>";
        echo "<a href='logout.php'>Logout</a>";
    } else {
        echo "<a href='login.php'>Log in</a>";
        echo "<a href='createaccount.php'>Create Account</a>";
    }

    if (isset($_SESSION['UserLevel']) && $_SESSION['UserLevel'] === "Admin") {
        echo "<a href='/DSTLib/Restricted/dashboard.php'>Admin Dash</a>";
    }
    ?>
    </div>
</div>

<div class="about-page">

    <div class="about-hero">
        <h1>About DSTLib</h1>
        <p class="about-tagline">A community for readers to discover, review, and share books.</p>
    </div>

    <hr>

    <div class="about-section">
        <h2>What is DSTLib?</h2>
        <p>DSTLib is a community-driven digital library where readers can explore books, write reviews, and connect with other people who share a passion for reading. Whether you're looking for your next favourite novel or want to share your thoughts on something you've just finished, DSTLib is the place for it.</p>
    </div>

    <div class="about-section">
        <h2>What can you do?</h2>
        <p>As a member of DSTLib you can write reviews and rate books on a scale of 0 to 4 stars, comment on other people's reviews, build and share reading lists, follow other members and see what they're reading, suggest books to be added to the library, and customise your profile with a photo and bio.</p>
    </div>

    <div class="about-section">
        <h2>How do I get started?</h2>
        <p>
            <a href="createaccount.php">Create a free account</a> to get started. Once you're signed up you can start reviewing books straight away. If you can't find a book you're looking for, use the <a href="suggestbook.php">suggest a book</a> page to submit it and an admin will review your request.
        </p>
    </div>

    <div class="about-section">
        <h2>Community Guidelines</h2>
        <p>DSTLib is a welcoming space for all readers. We ask that all members are respectful in their reviews and comments. Harmful, offensive, or abusive content will be removed and may result in your account being suspended. If you see something that violates these guidelines, please use the report button on any comment to flag it for review.</p>
    </div>

    <hr>

    <div class="about-footer">
        <p>DSTLib is a student project. &copy; <?= date('Y') ?></p>
    </div>

</div>

</body>
</html>