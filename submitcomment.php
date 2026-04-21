<?php
session_start();
require_once("db.php");

/* Making sure that the user is logged in. */
if (!isset($_SESSION['User_Name'])) {
    header("Location: login.php");
    exit;
}

/* Making sure that the link was access by a form */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$reviewId    = (int)($_POST['review_id'] ?? 0);
$commentBody = trim($_POST['comment_body'] ?? '');
$bookSlug    = $_POST['book_slug'] ?? '';
$userId      = $_SESSION['ID'];

if (empty($commentBody)) {
    $_SESSION['message'] = "Comment cannot be empty.";
    header("Location: books.php?book=" . urlencode($bookSlug));
    exit;
}

if ($reviewId <= 0) {
    die("Invalid review.");
}

$stmt = $conn->prepare("INSERT INTO tbl_Review_Comments (Review_ID, User_ID, Comment_Body) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $reviewId, $userId, $commentBody);
$stmt->execute();

$_SESSION['message'] = "Comment posted.";
header("Location: books.php?book=" . urlencode($bookSlug) . "#review-" . $reviewId);
exit;


$stmt = $conn->prepare("SELECT User_ID FROM tbl_Reviews WHERE ID=?");
$stmt->bind_param("i", $reviewId);
$stmt->execute();
$review = $stmt->get_result()->fetch_assoc();

/* Making sure that the comments trigger notifcations for the original commenter */
if ($review && $review['User_ID'] !== (int)$_SESSION['ID']) {
    $message = htmlspecialchars($_SESSION['User_Name']) . " commented on your review.";
    $link    = "/DSTLib/books.php?book=" . urlencode($bookSlug) . "#review-" . $reviewId;
    sendNotification($conn, $review['User_ID'], 'review_comment', $message, $link);
}