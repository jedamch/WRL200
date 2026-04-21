<?php
session_start();
require_once("db.php");

if (isset($_SESSION["User_Name"])) {
    header("Location: index.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['login'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($loginInput === '' || $password === '') {
        $errors[] = 'Username/email and password are required.';
    } else {
        $stmt = $conn->prepare("SELECT ID, User_Name, Hashed_Password, UserLevel, Avatar, Is_Banned, Ban_Reason FROM tbl_Users WHERE Email_Address = ? OR User_Name = ?");
        $stmt->bind_param("ss", $loginInput, $loginInput);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['Hashed_Password'])) {
            if ($user['Is_Banned']) {
                $errors[] = "Your account has been banned. Reason: " . htmlspecialchars($user['Ban_Reason'] ?? 'No reason given.');
            } else {
                $_SESSION['ID']        = $user['ID'];
                $_SESSION['User_Name'] = $user['User_Name'];
                $_SESSION['UserLevel'] = $user['UserLevel'];
                $_SESSION['Avatar']    = $user['Avatar'] ?? 'default.png';

                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
                $stmt = $conn->prepare("UPDATE tbl_Users SET Last_IP=?, Last_Login=NOW() WHERE ID=?");
                $stmt->bind_param("si", $ip, $user['ID']);
                $stmt->execute();

                header("Location: index.php");
                exit;
            }
        } else {
            $errors[] = 'Invalid credentials.';
        }
    }
}

if (isset($_GET['banned'])): ?>
    <?php $errors[] = "Your account has been banned.";
endif;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Login </h2>

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

<div class="container">
    <h2>Login</h2>

    <?php if (!empty($errors)): ?>
        <div style="color:red;">
            <?php foreach ($errors as $e): ?>
                <p><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="message">
            <p><?= $_SESSION['message'] ?></p>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <form method="POST">
        <label>Username or Email Address:</label>
        <input type="text" name="login" required><br><br>

        <label>Password:</label>
        <input type="password" name="password" required><br><br>

        <input type="submit" value="Login">
    </form>

    <p>Don't have an account? <a href="createaccount.php">Register</a></p>
</div>
<a href="index.php">← Back</a>

</body>
</html>