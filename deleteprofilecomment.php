<?php
session_start();
require_once("db.php");

if (!isset($_SESSION['User_Name'])) {
    header("Location: login.php");
    exit;
}

$commentId   = (int)($_GET['ID'] ?? 0);
$profileUser = $_GET['user'] ?? '';

if ($commentId <= 0) die("Invalid comment.");

// Fetch the comment to check ownership
$stmt = $conn->prepare("
    SELECT tbl_Profile_Comments.Author_User_ID, tbl_Profile_Comments.Profile_User_ID,
           tbl_Users.User_Name AS Profile_User_Name
    FROM tbl_Profile_Comments
    INNER JOIN tbl_Users ON tbl_Profile_Comments.Profile_User_ID = tbl_Users.ID
    WHERE tbl_Profile_Comments.ID = ?
");
$stmt->bind_param("i", $commentId);
$stmt->execute();
$comment = $stmt->get_result()->fetch_assoc();

if (!$comment) die("Comment not found.");

$isAuthor       = ($comment['Author_User_ID'] === (int)$_SESSION['ID']);
$isProfileOwner = ($comment['Profile_User_Name'] === $_SESSION['User_Name']);
$isAdmin        = ($_SESSION['UserLevel'] === 'Admin');

if (!$isAuthor && !$isProfileOwner && !$isAdmin) {
    die("You do not have permission to delete this comment.");
}

$stmt = $conn->prepare("DELETE FROM tbl_Profile_Comments WHERE ID=?");
$stmt->bind_param("i", $commentId);
$stmt->execute();

$_SESSION['message'] = "Comment deleted.";
header("Location: Members/profile.php?user=" . urlencode($profileUser));
exit;