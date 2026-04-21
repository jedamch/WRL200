<?php
session_start();
require_once("db.php");

if (empty($_GET['user']) || empty($_GET['list'])) die("Invalid list URL.");

$userName = $_GET['user'];
$listSlug = $_GET['list'];

// Get the user
$stmt = $conn->prepare("SELECT ID FROM tbl_Users WHERE User_Name = ?");
$stmt->bind_param("s", $userName);
$stmt->execute();
$listOwner = $stmt->get_result()->fetch_assoc();

if (!$listOwner) die("User not found.");

// Get the list
$stmt = $conn->prepare("
    SELECT tbl_Lists.*, tbl_Users.User_Name
    FROM tbl_Lists
    INNER JOIN tbl_Users ON tbl_Lists.User_ID = tbl_Users.ID
    WHERE tbl_Lists.User_ID = ? AND tbl_Lists.Slug = ?
");
$stmt->bind_param("is", $listOwner['ID'], $listSlug);
$stmt->execute();
$list = $stmt->get_result()->fetch_assoc();

if (!$list) die("List not found.");

$isOwner = isset($_SESSION['ID']) && $list['User_ID'] === (int)$_SESSION['ID'];

if (!$list['Is_Public'] && !$isOwner) die("This list is private.");

$listUrl = "/DSTLib/list.php?user=" . urlencode($userName) . "&list=" . urlencode($listSlug);

$errors  = [];
$success = "";

// Add book to list
if ($isOwner && isset($_POST['add_book'])) {
    $bookId = (int)$_POST['book_id'];

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tbl_List_Books WHERE List_ID = ?");
    $stmt->bind_param("i", $list['ID']);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['total'];

    if ($count >= 200) {
        $errors[] = "This list has reached the maximum of 200 books.";
    } elseif ($bookId <= 0) {
        $errors[] = "Please select a book.";
    } else {
        $stmt = $conn->prepare("INSERT IGNORE INTO tbl_List_Books (List_ID, Book_ID) VALUES (?, ?)");
        $stmt->bind_param("ii", $list['ID'], $bookId);
        $stmt->execute();

        if ($conn->affected_rows === 0) {
            $errors[] = "That book is already on this list.";
        } else {
            $success = "Book added.";
        }
    }
}


if ($isOwner && isset($_GET['remove'])) {
    $bookId = (int)$_GET['remove'];
    $stmt = $conn->prepare("DELETE FROM tbl_List_Books WHERE List_ID = ? AND Book_ID = ?");
    $stmt->bind_param("ii", $list['ID'], $bookId);
    $stmt->execute();
    header("Location: " . $listUrl);
    exit;
}

if ($isOwner && isset($_GET['toggle'])) {
    $newVal = $list['Is_Public'] ? 0 : 1;
    $stmt = $conn->prepare("UPDATE tbl_Lists SET Is_Public=? WHERE ID=?");
    $stmt->bind_param("ii", $newVal, $list['ID']);
    $stmt->execute();
    header("Location: " . $listUrl);
    exit;
}


if ($isOwner && isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM tbl_Lists WHERE ID=?");
    $stmt->bind_param("i", $list['ID']);
    $stmt->execute();
    header("Location: /DSTLib/Members/profile.php?user=" . urlencode($_SESSION['User_Name']));
    exit;
}

// Fetch books on list
$stmt = $conn->prepare("
    SELECT tbl_Books.ID, tbl_Books.Title, tbl_Books.Cover, tbl_Books.Slug,
           tbl_Authors.Author_Name
    FROM tbl_List_Books
    INNER JOIN tbl_Books ON tbl_List_Books.Book_ID = tbl_Books.ID
    INNER JOIN tbl_Authors ON tbl_Books.Author_ID = tbl_Authors.ID
    WHERE tbl_List_Books.List_ID = ?
    ORDER BY tbl_List_Books.Added_At ASC
");
$stmt->bind_param("i", $list['ID']);
$stmt->execute();
$books = $stmt->get_result();

$allBooks = null;
if ($isOwner) {
    $allBooks = $conn->query("SELECT ID, Title FROM tbl_Books ORDER BY Title ASC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($list['Name']) ?> - DSTLib</title>
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
    ?>
    </div>
</div>

<div class="list-page">

    <div class="list-header">
        <h1><?= htmlspecialchars($list['Name']) ?></h1>
        <p>By <a href="Members/profile.php?user=<?= htmlspecialchars($list['User_Name']) ?>">
            <?= htmlspecialchars($list['User_Name']) ?>
        </a></p>
        <?php if (!empty($list['Description'])): ?>
            <p><?= nl2br(htmlspecialchars($list['Description'])) ?></p>
        <?php endif; ?>
        <p><small><?= $list['Is_Public'] ? '🌐 Public' : '🔒 Private' ?></small></p>
    </div>

    <?php if ($isOwner): ?>
        <div class="list-actions">
            <a href="<?= $listUrl ?>&toggle=1">
                <?= $list['Is_Public'] ? 'Make Private' : 'Make Public' ?>
            </a>
            |
            <a href="<?= $listUrl ?>&delete=1"
               style="color:red;"
               onclick="return confirm('Are you sure you want to delete this list?')">Delete List</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div style="color:red;">
            <?php foreach ($errors as $e): echo "<p>" . htmlspecialchars($e) . "</p>"; endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color:green;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <?php if ($isOwner): ?>
        <form method="POST" action="<?= $listUrl ?>" style="margin:20px 0;">
            <select name="book_id">
                <option value="">-- Select a book --</option>
                <?php while ($b = $allBooks->fetch_assoc()): ?>
                    <option value="<?= $b['ID'] ?>"><?= htmlspecialchars($b['Title']) ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="add_book">Add to List</button>
        </form>
    <?php endif; ?>

    <div class="list-books">
        <?php if ($books->num_rows > 0): ?>
            <?php while ($book = $books->fetch_assoc()): ?>
                <div class="list-book-item">
                    <a href="books.php?book=<?= htmlspecialchars($book['Slug']) ?>">
                        <img src="Restricted/Books/Covers/<?= htmlspecialchars($book['Cover']) ?>"
                             width="60" height="90" style="object-fit:cover;">
                    </a>
                    <div class="list-book-info">
                        <a href="books.php?book=<?= htmlspecialchars($book['Slug']) ?>">
                            <?= htmlspecialchars($book['Title']) ?>
                        </a>
                        <span><?= htmlspecialchars($book['Author_Name']) ?></span>
                    </div>
                    <?php if ($isOwner): ?>
                        <a href="<?= $listUrl ?>&remove=<?= $book['ID'] ?>"
                           style="color:red;margin-left:auto;"
                           onclick="return confirm('Remove this book from the list?')">Remove</a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No books on this list yet.</p>
        <?php endif; ?>
    </div>

</div>
<script src="/DSTLib/main.js"></script>
</body>
</html>