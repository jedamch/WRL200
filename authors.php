<?php


include './db.php';
session_start();

if (empty($_GET['author'])) die("No author provided.");
$slug = $_GET['author'];

// Get author details
$stmt = $conn->prepare("
    SELECT ID, Author_Name, Bio, Year_Of_Birth, Year_Of_Death, Place_Of_Birth, Photograph
    FROM tbl_Authors
    WHERE Slug = ?
");
$stmt->bind_param("s", $slug);
$stmt->execute();
$author = $stmt->get_result()->fetch_assoc();

if (!$author) die("Author not found.");

// Get their books
$stmt = $conn->prepare("
    SELECT ID, Title, Cover, Year_Of_Release, Slug
    FROM tbl_Books
    WHERE Author_ID = ?
");
$stmt->bind_param("i", $author['ID']);
$stmt->execute();
$books = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($author['Author_Name']) ?> - DSTLib</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="topnavigationbar">
    <div class="nav-left">
        <a href="index.php">Home</a>
        <a href="topbooks.php">Books</a>
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

<div class="author-page">

    <div class="author-details">

        <?php if (!empty($author['Photograph'])): ?>
            <img src="Restricted/Authors/Photographs/<?= htmlspecialchars($author['Photograph']) ?>" 
                 alt="<?= htmlspecialchars($author['Author_Name']) ?>"
                 class="author-photo">
        <?php endif; ?>

        <div class="author-info">
            <h1><?= htmlspecialchars($author['Author_Name']) ?></h1>

            <?php if ($author['Year_Of_Birth']): ?>
                <p>
                    <strong>Born:</strong> <?= htmlspecialchars($author['Year_Of_Birth']) ?>
                    <?php if (!empty($author['Place_Of_Birth'])): ?>
                        — <?= htmlspecialchars($author['Place_Of_Birth']) ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>

            <?php if ($author['Year_Of_Death']): ?>
                <p><strong>Died:</strong> <?= htmlspecialchars($author['Year_Of_Death']) ?></p>
            <?php endif; ?>

            <?php if (!empty($author['Bio'])): ?>
                <p><?= nl2br(htmlspecialchars($author['Bio'])) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <hr>

    <h2>Books</h2>

    <div class="book-grid">
    <?php if ($books->num_rows > 0): ?>
        <?php while ($book = $books->fetch_assoc()): ?>
            <<a href="books.php?book=<?= htmlspecialchars($book['Slug']) ?>" class="book-card">
                <img src="Restricted/Books/Covers/<?= htmlspecialchars($book['Cover']) ?>"
                     alt="<?= htmlspecialchars($book['Title']) ?>">
            </a>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No books found for this author.</p>
    <?php endif; ?>
    </div>

</div>
<script src="/DSTLib/main.js"></script>
</body>
</html>