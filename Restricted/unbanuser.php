<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../requirerole.php';

requirerole(['Admin']);

if (empty($_GET['ID'])) die("No user ID provided.");
$userId = (int)$_GET['ID'];

$stmt = $conn->prepare("UPDATE tbl_Users SET Is_Banned=0, Ban_Reason=NULL WHERE ID=?");
$stmt->bind_param("i", $userId);
$stmt->execute();

$_SESSION['message'] = "User unbanned.";
header("Location: users.php");
exit;
