<?php
session_start();
require_once("db.php");

if (!isset($_SESSION['User_Name'])) {
    header("Location: login.php");
    exit;
}

$errors  = [];
$success = "";
$userId  = $_SESSION['ID'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isPublic    = isset($_POST['is_public']) ? 1 : 0;

    if (empty($name)) {
        $errors[] = "List name is required.";
    } elseif (strlen($name) > 255) {
        $errors[] = "List name is too long.";
    } else {
        
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tbl_Lists WHERE User_ID = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['total'];

        if ($count >= 100) {
            $errors[] = "You have reached the maximum of 100 lists.";
        } else {
            $slug = makeSlug($name);

            $stmt = $conn->prepare("INSERT INTO tbl_Lists (User_ID, Name, Slug, Description, Is_Public) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $userId, $name, $slug, $description, $isPublic);
            $stmt->execute();

            $userName = $_SESSION['User_Name'];
            header("Location: /DSTLib/list.php?user=" . urlencode($_SESSION['User_Name']) . "&list=" . urlencode($slug));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create List</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Make a list</h2>

<div class="topnavigationbar">
    <div class="nav-left">
        <a href="index.php">Home</a>
        <a href="sitebooks.php">Books</a>
        <a href="suggestbook.php">Suggest A Book</a>
        <a href="lists.php">Lists</a>
        <a href="about.php">About</a>
    </div>

    <div class="nav-right">
    <?php
    if (isset($_SESSION["User_Name"])) {
        $profilePic = $_SESSION['Avatar'] ?? 'default.png';
        echo "<a href='Members/profile.php?user=" . $_SESSION['User_Name'] . "' class='profile-link'>";
        echo "<img src='Members/Uploads/$profilePic' alt='Profile' class='profile-pic'>";
        echo "<span>" . htmlspecialchars($_SESSION['User_Name']) . "</span>";
        echo "</a>";
        echo "<a href='logout.php'>Logout</a>";
        echo "<a href='/DSTLib/notifications.php' class='bell-icon' id='notifBell'>
        🔔 <span class='notif-count' id='notifCount' style='display:none;'></span>
      </a>";
    } else {
        echo "<a href='login.php'>Log in</a>";
        echo "<a href='createaccount.php'>Create Account</a>";
    }

    if (isset($_SESSION['UserLevel']) && $_SESSION['UserLevel'] === "Admin") {
        echo "<a href='Restricted/dashboard.php'>Admin Dash</a>";
    }
    ?>
    </div>
</div>

<h2>Create a New List</h2>

<?php if (!empty($errors)): ?>
    <div style="color:red;">
        <?php foreach ($errors as $e): ?>
            <p><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" action="createlists.php">
    <label>List Name:</label><br>
    <input type="text" name="name" maxlength="255"><br><br>

    <label>Description (optional):</label><br>
    <textarea name="description" rows="3" style="width:100%;"></textarea><br><br>

    <label>
        <input type="checkbox" name="is_public" checked>
        Make this list public
    </label><br><br>

    <input type="submit" value="Create List">
</form>

<a href="index.php">← Back</a>
<script src="/DSTLib/main.js"></script>
</body>
</html>