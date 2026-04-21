<?php

$servername = "localhost";
$username = "ohmodmin";
$DBpassword = "test1234";
$db = "DSTLib";

$conn = mysqli_connect($servername,$username,$DBpassword,$db);
if(!$conn)
{
	die("Connection Failed: ".mysqli_connect_error());
}

function makeSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

function sendNotification($conn, $userId, $type, $message, $link = null) {
    $stmt = $conn->prepare("INSERT INTO tbl_Notifications (User_ID, Type, Message, Link) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $type, $message, $link);
    $stmt->execute();
}

?>