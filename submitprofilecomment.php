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

$profileUserId = (int)($_POST['profile_user_id'] ?? 0);
$commentBody   = trim($_POST['comment_body'] ?? '');
$profileUser   = $_POST['profile_username'] ?? '';
$authorId      = $_SESSION['ID'];

if (empty($commentBody)) {
    $_SESSION['message'] = "Comment cannot be empty.";
    header("Location: Members/profile.php?user=" . urlencode($profileUser));
    exit;
}

if ($profileUserId <= 0) {
    die("Invalid profile.");
}

$stmt = $conn->prepare("INSERT INTO tbl_Profile_Comments (Profile_User_ID, Author_User_ID, Comment_Body) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $profileUserId, $authorId, $commentBody);
$stmt->execute();

$_SESSION['message'] = "Comment posted.";
header("Location: Members/profile.php?user=" . urlencode($profileUser));
exit;


if ($profileUserId !== (int)$_SESSION['ID']) {
    $message = htmlspecialchars($_SESSION['User_Name']) . " left a comment on your profile.";
    $link    = "/DSTLib/Members/profile.php?user=" . urlencode($profileUser);
    sendNotification($conn, $profileUserId, 'profile_comment', $message, $link);
}