<?php
session_start();
require_once("db.php");

if (!isset($_SESSION['User_Name'])) {
    header("Location: login.php");
    exit;
}

$action      = $_GET['action'] ?? '';
$requesterId = (int)($_GET['from'] ?? 0);
$recipientId = (int)$_SESSION['ID'];
$redirectTo  = $_GET['redirect'] ?? '/DSTLib/Members/profile.php?user=' . urlencode($_SESSION['User_Name']);

if (!in_array($action, ['accept', 'reject']) || $requesterId <= 0) {
    header("Location: " . $redirectTo);
    exit;
}

$stmt = $conn->prepare("
    SELECT ID FROM tbl_Friends 
    WHERE Requester_ID=? AND Recipient_ID=? AND Status='pending'
");
$stmt->bind_param("ii", $requesterId, $recipientId);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();

if (!$request) {
    header("Location: " . $redirectTo);
    exit;
}

if ($action === 'accept') {
    $stmt = $conn->prepare("UPDATE tbl_Friends SET Status='accepted' WHERE ID=?");
    $stmt->bind_param("i", $request['ID']);
    $stmt->execute();

    /* Acceptance */
    $message = htmlspecialchars($_SESSION['User_Name']) . " accepted your friend request.";
    $link    = "/DSTLib/Members/profile.php?user=" . urlencode($_SESSION['User_Name']);
    sendNotification($conn, $requesterId, 'friend_accepted', $message, $link);

} elseif ($action === 'reject') {
    /* Rejection */
    $stmt = $conn->prepare("UPDATE tbl_Friends SET Status='rejected' WHERE ID=?");
    $stmt->bind_param("i", $request['ID']);
    $stmt->execute();
}

header("Location: " . $redirectTo);
exit;