<?php
session_start();
require_once("db.php");

if (isset($_SESSION["User_Name"])) {
    header("Location: index.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = trim($_POST['txtname'] ?? '');
    $email         = trim($_POST['txtemail'] ?? '');
    $user_password = $_POST['txtpass'] ?? '';
    $confirmpass   = $_POST['txtcpass'] ?? '';

    if ($name === '' || $email === '' || $user_password === '' || $confirmpass === '') {
        $errors[] = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    } elseif ($user_password !== $confirmpass) {
        $errors[] = 'The passwords do not match.';
    } elseif (strlen($user_password) < 10) {
        $errors[] = 'Password must be at least 10 characters.';
    } elseif (strlen($user_password) > 100) {
        $errors[] = 'Password is too long.';
    }

    if (empty($errors)) {
        $password_hash = password_hash($user_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("SELECT ID FROM tbl_Users WHERE Email_Address = ? OR User_Name = ?");
        $stmt->bind_param("ss", $email, $name);
        $stmt->execute();

        if ($stmt->get_result()->fetch_assoc()) {
            $errors[] = 'Email is already registered or username is taken.';
        } else {
            $token = bin2hex(random_bytes(32));

           $stmt = $conn->prepare("INSERT INTO tbl_Users (User_Name, Email_Address, Hashed_Password, DateCreated, UserLevel) VALUES (?, ?, ?, NOW(), 'User')");
           $stmt->bind_param("sss", $name, $email, $password_hash);
           $stmt->execute();

           $_SESSION['message'] = "Account created successfully. You can now log in.";
           header("Location: login.php");
           exit;

            $_SESSION['message'] = "Registration successful!";
            header("Location: login.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Create an Account</h2>

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
    <h2>Register</h2>

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
        <label>Username:</label>
        <input type="text" name="txtname" required><br><br>
        
        <label>Email Address:</label>
        <input type="email" name="txtemail" required><br><br>

        <label>Password:</label>
        <input type="password" name="txtpass" required><br><br>

        <label>Confirm Password:</label>
        <input type="password" name="txtcpass" required><br><br>
        <p> Make sure that the password 10 characters or longer</p>

        <input type="submit" name="btnsubmit" value="Register">
    </form>

    <p>Already have an account? <a href="login.php">Login</a></p>
    <a href="index.php">← Back</a>
</div>

</body>
</html>

