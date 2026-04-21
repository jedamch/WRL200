<?php

session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../requirerole.php';

requirerole(['Admin']);

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name          = trim($_POST["authorname"] ?? "");
    $slug          = strtolower(str_replace(' ', '-', $name));
    $year_of_birth = trim($_POST["authoryob"] ?? "");
    $year_of_death = trim($_POST["authoryod"] ?? "");
    $bio           = trim($_POST["authorbio"] ?? "");
    $place_of_birth = trim($_POST["authorpob"] ?? "");

    if (empty($name) || empty($year_of_birth)) {
        $error = "Author's name and year of birth are required.";
    } else {

        $photograph = "default.png";

        $stmt = $conn->prepare("INSERT INTO tbl_Authors (Author_Name, Slug, Year_Of_Birth, Year_Of_Death, Bio, Place_Of_Birth, Photograph) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisss", $name, $slug, $year_of_birth, $year_of_death, $bio, $place_of_birth, $photograph);
        $stmt->execute();

        $newAuthorId = $conn->insert_id;

        // Handle photograph upload
        if (!empty($_POST['photograph'])) {
            $data = explode(",", $_POST['photograph'])[1];
            $data = base64_decode($data);

            $newauthorphotograph = "photo_" . $newAuthorId . "_" . time() . ".png";
            $uploadDir = __DIR__ . "/Authors/Photographs/";

            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            file_put_contents($uploadDir . $newauthorphotograph, $data);

            $stmt = $conn->prepare("UPDATE tbl_Authors SET Photograph=? WHERE ID=?");
            $stmt->bind_param("si", $newauthorphotograph, $newAuthorId); // fixed variable names
            $stmt->execute();
        }

        $success = "Author added successfully.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Author</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
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

<?php if ($success): ?>
    <p style="color:green;"><?= $success ?></p>
<?php endif; ?>

<?php if ($error): ?>
    <p style="color:red;"><?= $error ?></p>
<?php endif; ?>

<h2>Add New Author</h2>

<form method="POST" action="addauthors.php">
    <input type="hidden" name="photograph" id="croppedImageInput">

    <label>Name:</label><br>
    <input type="text" name="authorname"><br><br>

    <label>Year of Birth:</label><br>
    <input type="text" name="authoryob"><br><br>

    <label>Year of Death:</label><br>
    <input type="text" name="authoryod"><br><br>

    <label>Place of Birth:</label><br>
    <input type="text" name="authorpob"><br><br>

    <label>Bio:</label><br>
    <textarea name="authorbio" rows="4"></textarea><br><br>

    <label>Photograph:</label><br>
    <input type="file" id="uploadImage" accept="image/*"><br><br>

    <div style="width:300px; margin-top:20px;">
        <img id="imageToCrop" style="max-width:100%; display:none;">
    </div>
    <button type="button" id="cropBtn" style="display:none;">Crop & Set Photo</button><br><br>

    <input type="submit" value="Add Author">
</form>

<a href="dashboard.php">← Back to dashboard</a>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
<script src="../editprofile.js"></script>
<script src="/DSTLib/main.js"></script>
</body>
</html>
