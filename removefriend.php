<?php
session_start();
require_once("db.php");

if (!isset($_SESSION['User_Name'])) {
    header("Location: login.php");
    exit;
}

$otherUserId = (int)($_POST['user_id'] ?? 0);
$userId      = (int)$_SESSION['ID'];
$redirectTo  = $_POST['redirect'] ?? '/DSTLib/index.php';

if ($otherUserId <= 0) {
    header("Location: " . $redirectTo);
    exit;
}

$stmt = $conn->prepare("
    DELETE FROM tbl_Friends 
    WHERE (Requester_ID=? AND Recipient_ID=?) 
    OR (Requester_ID=? AND Recipient_ID=?)
");
$stmt->bind_param("iiii", $userId, $otherUserId, $otherUserId, $userId);
$stmt->execute();

header("Location: " . $redirectTo);
exit;