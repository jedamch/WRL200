<?php

include './db.php';
session_start();

if (empty($_GET['book'])) die("No book provided.");
$slug = $_GET['book'];

$stmt = $conn->prepare("
    SELECT tbl_Books.ID, tbl_Books.Title, tbl_Books.Synopsis, tbl_Books.Year_Of_Release, 
           tbl_Books.Cover, tbl_Books.Slug, tbl_Books.Author_ID,
           tbl_Authors.Author_Name, tbl_Authors.Slug AS Author_Slug
    FROM tbl_Books
    INNER JOIN tbl_Authors ON tbl_Books.Author_ID = tbl_Authors.ID
    WHERE tbl_Books.Slug = ?
");
$stmt->bind_param("s", $slug);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if (!$book) die("Book not found.");

// Fetch existing reviews
$stmt = $conn->prepare("
    SELECT tbl_Reviews.ID, tbl_Reviews.Rating, tbl_Reviews.Review_Body, tbl_Reviews.Created_At,
           tbl_Users.User_Name, tbl_Users.Avatar
    FROM tbl_Reviews
    INNER JOIN tbl_Users ON tbl_Reviews.User_ID = tbl_Users.ID
    WHERE tbl_Reviews.Book_ID = ?
    ORDER BY tbl_Reviews.Created_At DESC
");
$stmt->bind_param("i", $book['ID']);
$stmt->execute();
$reviews = $stmt->get_result();


$alreadyReviewed = false;
if (isset($_SESSION['ID'])) {
    $stmt = $conn->prepare("SELECT ID FROM tbl_Reviews WHERE User_ID = ? AND Book_ID = ?");
    $stmt->bind_param("ii", $_SESSION['ID'], $book['ID']);
    $stmt->execute();
    $alreadyReviewed = (bool)$stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($book['Title']) ?> - DSTLib</title>
    <link rel="stylesheet" href="style.css">
    <script>
    function toggleReportForm(id) {
        const el = document.getElementById(id);
        if (el) {
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }
    }
    </script>
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
        echo "<a href='logout.php'>Logout</a>";
        echo "<a href='/DSTLib/notifications.php' class='bell-icon' id='notifBell'>🔔<span class='notif-count' id='notifCount' style='display:none;'></span></a>";

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

<div class="book-page">

    <div class="book-details">
        <img src="Restricted/Books/Covers/<?= htmlspecialchars($book['Cover']) ?>"
             alt="<?= htmlspecialchars($book['Title']) ?>"
             class="book-page-cover">

        <div class="book-info">
            <h1><?= htmlspecialchars($book['Title']) ?></h1>
            <h3>
                <a href="authors.php?author=<?= htmlspecialchars($book['Author_Slug']) ?>">
                    <?= htmlspecialchars($book['Author_Name']) ?>
                </a>
            </h3>
            <p><strong>Year:</strong> <?= htmlspecialchars($book['Year_Of_Release']) ?></p>
                        <p><?= nl2br(htmlspecialchars($book['Synopsis'])) ?></p>
        </div> <!-- end book-info -->
    </div> <!-- end book-details -->

    <hr>

    <div class="reviews">
        <h2>Reviews</h2>

        <?php if (isset($_SESSION['message'])): ?>
            <p style="color:green;"><?= htmlspecialchars($_SESSION['message']) ?></p>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['User_Name']) && !$alreadyReviewed): ?>
            <form method="POST" action="submitreview.php">
                <input type="hidden" name="book_slug" value="<?= htmlspecialchars($book['Slug']) ?>">

                <label>Rating:</label><br>
                <div class="star-rating">
                    <?php for ($i = 0; $i <= 4; $i++): ?>
                        <label>
                            <input type="radio" name="rating" value="<?= $i ?>" <?= $i === 0 ? 'checked' : '' ?>>
                            <?= $i === 0 ? '0 ☆☆☆☆' : str_repeat('★', $i) . str_repeat('☆', 4 - $i) ?>
                        </label><br>
                    <?php endfor; ?>
                </div><br>

                <label>Review:</label><br>
                <textarea name="review_body" rows="4" style="width:100%;"></textarea><br><br>

                <input type="submit" value="Submit Review">
            </form>

        <?php elseif (!isset($_SESSION['User_Name'])): ?>
            <p><a href="login.php">Log in</a> to leave a review.</p>

        <?php else: ?>
            <p>You have already reviewed this book.</p>
        <?php endif; ?>

        <hr>

  <?php if ($reviews->num_rows > 0): ?>
    <?php while ($review = $reviews->fetch_assoc()): ?>
        <div class="review-item" id="review-<?= $review['ID'] ?>">
            <div class="review-header">
                <img src="Members/Uploads/<?= htmlspecialchars($review['Avatar'] ?? 'default.png') ?>"
                     width="36" height="36" style="border-radius:50%;object-fit:cover;">
                <a href="Members/profile.php?user=<?= htmlspecialchars($review['User_Name']) ?>">
                    <?= htmlspecialchars($review['User_Name']) ?>
                </a>
                <span class="review-stars">
                    <?= $review['Rating'] === 0 ? '0 ☆☆☆☆' : str_repeat('★', $review['Rating']) . str_repeat('☆', 4 - $review['Rating']) ?>
                </span>
                <span class="review-date">
                    <?= date('d M Y', strtotime($review['Created_At'])) ?>
                </span>
            </div>

            <?php if (!empty($review['Review_Body'])): ?>
                <p class="review-body"><?= nl2br(htmlspecialchars($review['Review_Body'])) ?></p>
            <?php endif; ?>

            <!-- Comments for this review -->
            <?php
            $cstmt = $conn->prepare("
                SELECT tbl_Review_Comments.ID, tbl_Review_Comments.Comment_Body, tbl_Review_Comments.Created_At,
                       tbl_Users.User_Name, tbl_Users.Avatar
                FROM tbl_Review_Comments
                INNER JOIN tbl_Users ON tbl_Review_Comments.User_ID = tbl_Users.ID
                WHERE tbl_Review_Comments.Review_ID = ?
                ORDER BY tbl_Review_Comments.Created_At ASC
            ");
            $cstmt->bind_param("i", $review['ID']);
            $cstmt->execute();
            $comments = $cstmt->get_result();
            ?>

   <div class="review-comments">
    <?php if ($comments->num_rows > 0): ?>
        <?php while ($comment = $comments->fetch_assoc()): ?>
            <div class="comment-item">
                <img src="Members/Uploads/<?= htmlspecialchars($comment['Avatar'] ?? 'default.png') ?>"
                     width="24" height="24" style="border-radius:50%;object-fit:cover;">
                <div class="comment-content">
                    <a href="Members/profile.php?user=<?= htmlspecialchars($comment['User_Name']) ?>">
                        <strong><?= htmlspecialchars($comment['User_Name']) ?></strong>
                    </a>
                    <span class="review-date"><?= date('d M Y', strtotime($comment['Created_At'])) ?></span>
                    <p><?= nl2br(htmlspecialchars($comment['Comment_Body'])) ?></p>

                    <?php if (isset($_SESSION['User_Name']) && $_SESSION['User_Name'] !== $comment['User_Name']): ?>
                        <button onclick="toggleReportForm('review_comment_<?= $comment['ID'] ?>')"
                                style="font-size:0.75rem;background:none;border:none;color:#999;cursor:pointer;">
                            Report
                        </button>
                        <div id="review_comment_<?= $comment['ID'] ?>" style="display:none;margin-top:6px;">
                            <form method="POST" action="submitreport.php">
                                <input type="hidden" name="comment_type" value="review_comment">
                                <input type="hidden" name="comment_id" value="<?= $comment['ID'] ?>">
                                <input type="hidden" name="redirect" value="books.php?book=<?= htmlspecialchars($book['Slug']) ?>">
                                <textarea name="reason" rows="2" placeholder="Reason (optional)" style="width:100%;font-size:0.8rem;"></textarea>
                                <button type="submit" style="font-size:0.8rem;">Submit Report</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?> <!-- ← this was missing -->
    <?php endif; ?>

    <?php if (isset($_SESSION['User_Name'])): ?>
        <form method="POST" action="submitcomment.php" class="comment-form">
            <input type="hidden" name="review_id" value="<?= $review['ID'] ?>">
            <input type="hidden" name="book_slug" value="<?= htmlspecialchars($book['Slug']) ?>">
            <textarea name="comment_body" rows="2" placeholder="Write a comment..."></textarea>
            <button type="submit">Post</button>
        </form>
    <?php else: ?>
        <p class="small"><a href="login.php">Log in</a> to comment.</p>
    <?php endif; ?>

</div> <!-- end review-comments -->

        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p>No reviews yet — be the first!</p>
<?php endif; ?>

    </div> <!-- end reviews -->

</div> <!-- end book-page -->

</body>
</html>