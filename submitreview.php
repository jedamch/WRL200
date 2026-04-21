<?php
session_start();
require_once("db.php");

if (!isset($_SESSION['User_Name'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$bookSlug   = $_POST['book_slug'] ?? '';
$rating     = isset($_POST['rating']) ? (int)$_POST['rating'] : -1;
$reviewBody = trim($_POST['review_body'] ?? '');
$userId     = $_SESSION['ID'];

// Validate rating is between 0 and 4
if ($rating < 0 || $rating > 4) {
    $_SESSION['message'] = "Please select a star rating.";
    header("Location: books.php?book=" . urlencode($bookSlug));
    exit;
}

// Get book ID from slug
$stmt = $conn->prepare("SELECT ID FROM tbl_Books WHERE Slug = ?");
$stmt->bind_param("s", $bookSlug);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if (!$book) {
    die("Book not found.");
}

$bookId = $book['ID'];

/* Mitigating duplicate reviews */

$stmt = $conn->prepare("SELECT ID FROM tbl_Reviews WHERE User_ID = ? AND Book_ID = ?");
$stmt->bind_param("ii", $userId, $bookId);
$stmt->execute();

if ($stmt->get_result()->fetch_assoc()) {
    $_SESSION['message'] = "You have already reviewed this book.";
    header("Location: books.php?book=" . urlencode($bookSlug));
    exit;
}

$stmt = $conn->prepare("INSERT INTO tbl_Reviews (User_ID, Book_ID, Rating, Review_Body) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $userId, $bookId, $rating, $reviewBody);
$stmt->execute();

$_SESSION['message'] = "Review submitted.";
header("Location: books.php?book=" . urlencode($bookSlug));
exit;