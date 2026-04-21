<?php
session_start();
require_once("db.php");

if (!isset($_SESSION['User_Name'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['ID'];

// Mark all as read
$stmt = $conn->prepare("UPDATE tbl_Notifications SET Is_Read=1 WHERE User_ID=?");
$stmt->bind_param("i", $userId);
$stmt->execute();

// Fetch all notifications
$stmt = $conn->prepare("
    SELECT ID, Type, Message, Link, Is_Read, Created_At
    FROM tbl_Notifications
    WHERE User_ID = ?
    ORDER BY Created_At DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$notifications = $stmt->get_result();;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="topnavigationbar">
    <div class="nav-left">
        <a href="index.php">Home</a>
        <a href="books.php">Books</a>
        <a href="suggestbook.php">Suggest A Book</a>
        <a href="lists.php">Lists</a>
        <a href="about.php">About</a>
    </div>
    <div class="nav-right">
    <?php
    if (isset($_SESSION["User_Name"])) {
        $profilePic = $_SESSION['Avatar'] ?? 'default.png';
        echo "<a href='/DSTLib/Members/profile.php?user=" . $_SESSION['User_Name'] . "' class='profile-link'>";
        echo "<img src='Members/Uploads/$profilePic' alt='Profile' class='profile-pic'>";
        echo "<span>" . htmlspecialchars($_SESSION['User_Name']) . "</span>";
        echo "</a>";
        echo "<a href='logout.php'>Logout</a>";
    }
    ?>
    </div>
</div>

<div class="notifications-page">
    <h1>Notifications</h1>

    <?php if ($notifications->num_rows > 0): ?>
        <?php while ($n = $notifications->fetch_assoc()): ?>
            <a href="<?= htmlspecialchars($n['Link'] ?? '#') ?>" class="notification-item <?= $n['Is_Read'] ? 'read' : 'unread' ?>">
                <div class="notification-icon">
                    <?php
                    switch ($n['Type']) {
                        case 'review_comment': echo '💬'; break;
                        case 'profile_comment': echo '🗨️'; break;
                        case 'friend_request': echo '👤'; break;
                        case 'friend_accepted': echo '✅'; break;
                        default: echo '🔔'; break;
                    }
                    ?>
                </div>
                <div class="notification-content">
                    <p><?= htmlspecialchars($n['Message']) ?></p>
                    <span class="notification-date"><?= date('d M Y H:i', strtotime($n['Created_At'])) ?></span>
                </div>
                <?php if (!$n['Is_Read']): ?>
                    <div class="notification-dot"></div>
                <?php endif; ?>
            </a>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No notifications yet.</p>
    <?php endif; ?>
</div>

<script src="/DSTLib/main.js"></script>

</body>
</html>