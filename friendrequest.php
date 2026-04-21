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

$recipientId = (int)($_POST['recipient_id'] ?? 0);
$requesterId = (int)$_SESSION['ID'];
$redirectTo  = $_POST['redirect'] ?? '/DSTLib/index.php';

if ($recipientId <= 0 || $recipientId === $requesterId) {
    header("Location: " . $redirectTo);
    exit;
}

/* Making sure that they are not already friends */
$stmt = $conn->prepare("
    SELECT ID, Status FROM tbl_Friends 
    WHERE (Requester_ID=? AND Recipient_ID=?) 
    OR (Requester_ID=? AND Recipient_ID=?)
");
$stmt->bind_param("iiii", $requesterId, $recipientId, $recipientId, $requesterId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    /* If rejexted, allow a resend */
    if ($existing['Status'] === 'rejected') {
        $stmt = $conn->prepare("UPDATE tbl_Friends SET Status='pending', Created_At=NOW() WHERE ID=?");
        $stmt->bind_param("i", $existing['ID']);
        $stmt->execute();
    }
    header("Location: " . $redirectTo);
    exit;
}


$stmt = $conn->prepare("INSERT INTO tbl_Friends (Requester_ID, Recipient_ID) VALUES (?, ?)");
$stmt->bind_param("ii", $requesterId, $recipientId);
$stmt->execute();

// Notify recipient
$requesterName = $_SESSION['User_Name'];
$message = htmlspecialchars($requesterName) . " sent you a friend request.";
$link    = "/DSTLib/Members/profile.php?user=" . urlencode($requesterName);
sendNotification($conn, $recipientId, 'friend_request', $message, $link);

header("Location: " . $redirectTo);
exit;