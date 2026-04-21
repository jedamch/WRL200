<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['ID'])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = "";



$stmt = $conn->prepare("SELECT User_Name, Hashed_Password, Bio, Avatar FROM tbl_Users WHERE ID = ?");
$stmt->bind_param("i", $_SESSION['ID']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();


if (!empty($_POST['cropped_image'])) {
    $data = $_POST['cropped_image'];

  
    $data = explode(",", $data)[1];
    $data = base64_decode($data);

    $newNamePic = "profile_" . $_SESSION['User_Name'] . "_" . time() . ".png";
    $uploadDir = "Members/Uploads/";

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    file_put_contents($uploadDir . $newNamePic, $data);

    if (!empty($user['Avatar']) && $user['Avatar'] !== "default.png") {
        $oldPath = $uploadDir . $user['Avatar'];
        if (file_exists($oldPath)) unlink($oldPath);
    }

    $stmt = $conn->prepare("UPDATE tbl_Users SET Avatar=? WHERE ID=?");
    $stmt->bind_param("si", $newNamePic, $_SESSION['ID']);
    $stmt->execute();

    $_SESSION['Avatar'] = $newNamePic;
    $success .= "Profile photo updated.<br>";
}




if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($_POST['cropped_image'])) {

    $newName = trim($_POST["username"]);
    $newPassword = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];
    $bio = $_POST["bio"] ?? "";

    
    if ($newName !== $user['User_Name']) {
        $stmt = $conn->prepare("UPDATE tbl_Users SET User_Name=? WHERE ID=?");
        $stmt->bind_param("si", $newName, $_SESSION['ID']);
        $stmt->execute();
        $_SESSION['User_Name'] = $newName;
        $success .= "Username updated.<br>";
    }

   
    if (!empty($newPassword)) {
        if ($newPassword === $confirmPassword) {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE tbl_Users SET Hashed_Password=? WHERE ID=?");
            $stmt->bind_param("si", $hash, $_SESSION['ID']);
            $stmt->execute();
            $success .= "Password updated.<br>";
        } else {
            $errors[] = "Passwords do not match.";
        }
    }

    
    if ($bio !== $user['Bio']) {
        $stmt = $conn->prepare("UPDATE tbl_Users SET Bio=? WHERE ID=?");
        $stmt->bind_param("si", $bio, $_SESSION['ID']);
        $stmt->execute();
        $success .= "Bio updated.<br>";
    }
}




?>
<!DOCTYPE html>
<html lang="en">
    
    <link rel="stylesheet" href="style.css">
<head>
 <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>   




</head>


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

<?php
if ($errors) {
    echo '<div style="color:red;">';
    foreach ($errors as $e) echo "<p>$e</p>";
    echo "</div>";
}
if ($success) {
    echo '<div style="color:green;">' . $success . '</div>';
}
?>


<form method="POST" enctype="multipart/form-data">
    <label>Username:</label><br>
    <input type="text" name="username" value="<?= htmlspecialchars($user['User_Name']); ?>"><br><br>

    <label>New Password:</label><br>
    <input type="password" name="password"><br>

    <label>Confirm Password:</label><br>
    <input type="password" name="confirm_password"><br><br>

    <label>Bio:</label><br>
    <textarea name="bio" rows="4"><?= htmlspecialchars($user['Bio']); ?></textarea><br><br>

    <label>Profile Picture:</label><br>
    <img src="uploads/<?= htmlspecialchars($user['Avatar']); ?>" width="120" height="120" style="border-radius:50%;object-fit:cover;"><br><br>

    <input type="file" id="uploadImage" accept="image/*"><br><br>

    <input type="submit" value="Save Changes">
</form>



<div style="width: 300px; margin-top: 20px;">
    <img id="imageToCrop" style="max-width:100%; display:none;">
</div>

<button id="cropBtn" style="display:none;">Crop & Upload</button>


<form id="cropForm" method="POST">
    <input type="hidden" name="cropped_image" id="croppedImageInput">
</form>


<script src="editprofile.js"></script>
<script src="/DSTLib/main.js"></script>

</body>

</html>
