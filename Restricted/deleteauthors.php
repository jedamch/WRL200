<?php

session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../requirerole.php';

requirerole(['Admin']);

if (empty($_GET['ID'])) die("No author ID provided.");
$authorId = (int) $_GET['ID'];

$stmt = $conn->prepare("DELETE FROM tbl_Authors WHERE ID=?");
$stmt->bind_param("i", $authorId);
$stmt->execute();

header("Location: authorlist.php");
exit;