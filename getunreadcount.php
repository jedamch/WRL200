<?php
session_start();
require_once("db.php");

if (!isset($_SESSION['ID'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tbl_Notifications WHERE User_ID=? AND Is_Read=0");
$stmt->bind_param("i", $_SESSION['ID']);
$stmt->execute();
$count = $stmt->get_result()->fetch_assoc()['total'];

header('Content-Type: application/json');
echo json_encode(['count' => (int)$count]);