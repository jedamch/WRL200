<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../requirerole.php';

requirerole(['Admin']);

if (empty($_GET['ID'])) die("No user ID provided.");
$userId = (int)$_GET['ID'];


if ($userId === (int)$_SESSION['ID']) {
    $_SESSION['message'] = "You cannot ban yourself.";
    header("Location: users.php");
    exit;
}

$reason = trim($_POST['reason'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("UPDATE tbl_Users SET Is_Banned=1, Ban_Reason=? WHERE ID=?");
    $stmt->bind_param("si", $reason, $userId);
    $stmt->execute();

    
    $_SESSION['message'] = "User banned successfully.";
    header("Location: users.php");
    exit;
}

// GET - show the ban form
$stmt = $conn->prepare("SELECT User_Name FROM tbl_Users WHERE ID=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) die("User not found.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ban User</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
      <div class="topnavigationbar">
    <div class="nav-left">
        <a href="../index.php">Home</a>
        <a href="../sitebooks.php">Books</a>
        <a href="../suggestbook.php">Suggest A Book</a>
        <a href="../lists.php">Lists</a>
        <a href="../about.php">About</a>
    </div>

    <div class="nav-right">
    <?php
    if (isset($_SESSION["User_Name"])) {
        $profilePic = $_SESSION['Avatar'] ?? 'default.png';
        echo "<a href='/DSTLib/Members/profile.php?user=" . $_SESSION['User_Name'] . "' class='profile-link'>";
        echo "<img src='/DSTLib/Members/Uploads/$profilePic' alt='Profile' class='profile-pic'>";
        echo "<span>" . htmlspecialchars($_SESSION['User_Name']) . "</span>";
        echo "</a>";
        echo "<a href='../logout.php'>Logout</a>";
        echo "<a href='/DSTLib/notifications.php' class='bell-icon' id='notifBell'>
        🔔 <span class='notif-count' id='notifCount' style='display:none;'></span>
      </a>";
    } else {
        echo "<a href='../login.php'>Log in</a>";
        echo "<a href='../createaccount.php'>Create Account</a>";
    }

    if (isset($_SESSION['UserLevel']) && $_SESSION['UserLevel'] === "Admin") {
        echo "<a href='dashboard.php'>Admin Dash</a>";
    }
    ?>
    </div>
</div>

<h2>Ban <?= htmlspecialchars($user['User_Name']) ?></h2>

<form method="POST" action="banuser.php?ID=<?= $userId ?>">
    <label>Reason (optional):</label><br>
    <input type="text" name="reason" style="width:400px;"><br><br>
    <input type="submit" value="Confirm Ban">
</form>

<a href="users.php">← Cancel</a>
<script src="/DSTLib/main.js"></script>
</body>
</html>