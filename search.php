<?php
include './db.php';

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$like = "%" . $conn->real_escape_string($query) . "%";
$results = [];

// Books
$stmt = $conn->prepare("SELECT ID, Title, Slug, Cover FROM tbl_Books WHERE Title LIKE ? LIMIT 5");
$stmt->bind_param("s", $like);
$stmt->execute();
$books = $stmt->get_result();
while ($row = $books->fetch_assoc()) {
    $results[] = [
        'type'  => 'book',
        'label' => $row['Title'],
        'url'   => 'books.php?book=' . $row['Slug'],
        'cover' => 'Restricted/Books/Covers/' . $row['Cover']
    ];
}

// Authors
$stmt = $conn->prepare("SELECT ID, Author_Name, Slug FROM tbl_Authors WHERE Author_Name LIKE ? LIMIT 5");
$stmt->bind_param("s", $like);
$stmt->execute();
$authors = $stmt->get_result();
while ($row = $authors->fetch_assoc()) {
    $results[] = [
        'type'  => 'author',
        'label' => $row['Author_Name'],
        'url'   => 'authors.php?author=' . $row['Slug'],
    ];
}

// Users
$stmt = $conn->prepare("SELECT ID, User_Name, Avatar FROM tbl_Users WHERE User_Name LIKE ? LIMIT 5");
$stmt->bind_param("s", $like);
$stmt->execute();
$users = $stmt->get_result();
while ($row = $users->fetch_assoc()) {
    $results[] = [
        'type'   => 'user',
        'label'  => $row['User_Name'],
        'url'    => 'Members/profile.php?user=' . $row['User_Name'],
        'avatar' => 'Members/Uploads/' . ($row['Avatar'] ?? 'default.png')
    ];
}

header('Content-Type: application/json');
echo json_encode($results);