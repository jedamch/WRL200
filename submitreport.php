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

$commentType = $_POST['comment_type'] ?? '';
$commentId   = (int)($_POST['comment_id'] ?? 0);
$reason      = trim($_POST['reason'] ?? '');
$redirectTo  = $_POST['redirect'] ?? '/DSTLib/index.php';
$reporterId  = (int)$_SESSION['ID'];

if (!in_array($commentType, ['review_comment', 'profile_comment']) || $commentId <= 0) {
    header("Location: " . $redirectTo);
    exit;
}


$stmt = $conn->prepare("
    SELECT ID FROM tbl_Reports 
    WHERE Reporter_ID=? AND Comment_Type=? AND Comment_ID=?
");
$stmt->bind_param("isi", $reporterId, $commentType, $commentId);
$stmt->execute();

if ($stmt->get_result()->fetch_assoc()) {
    $_SESSION['report_message'] = "You have already reported this comment.";
    header("Location: " . $redirectTo);
    exit;
}


$stmt = $conn->prepare("INSERT INTO tbl_Reports (Reporter_ID, Comment_Type, Comment_ID, Reason) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isis", $reporterId, $commentType, $commentId, $reason);
$stmt->execute();


$admins = $conn->query("SELECT ID FROM tbl_Users WHERE UserLevel='Admin'");
while ($admin = $admins->fetch_assoc()) {
    if ($admin['ID'] !== $reporterId) {
        $message = htmlspecialchars($_SESSION['User_Name']) . " reported a " . str_replace('_', ' ', $commentType) . ".";
        sendNotification($conn, $admin['ID'], 'report', $message, "/DSTLib/Restricted/reports.php");
    }
}

$_SESSION['report_message'] = "Report submitted. Thank you.";
header("Location: " . $redirectTo);
exit;