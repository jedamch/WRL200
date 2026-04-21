<?php

session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../requirerole.php';

requirerole(['Admin']);
$success = "";

if (empty($_GET['ID'])) die("No author ID provided.");
$authorId = (int) $_GET['ID'];

$stmt = $conn->prepare("SELECT ID, Author_Name, Slug, Year_Of_Birth, Year_Of_Death, Bio, Place_Of_Birth, Photograph FROM tbl_Authors WHERE ID = ?");
$stmt->bind_param("i", $authorId);
$stmt->execute();
$author = $stmt->get_result()->fetch_assoc();

if (!$author) die("Author not found.");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    
    if (!empty($_POST['photograph'])) {
        $data = explode(",", $_POST['photograph'])[1];
        $data = base64_decode($data);

        $newphotograph = "photo_" . $authorId . "_" . time() . ".png";
        $uploadDir = __DIR__ . "/Authors/Photographs/";

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        file_put_contents($uploadDir . $newphotograph, $data);

        if (!empty($author['Photograph']) && $author['Photograph'] !== "default.png") {
            $oldPath = $uploadDir . $author['Photograph'];
            if (file_exists($oldPath)) unlink($oldPath);
        }

        $stmt = $conn->prepare("UPDATE tbl_Authors SET Photograph=? WHERE ID=?");
        $stmt->bind_param("si", $newphotograph, $authorId);
        $stmt->execute();

        $author['Photograph'] = $newphotograph;
        $success .= "Photograph updated.<br>";
    }

    // Handle text fields
    $newname         = trim($_POST["authorname"] ?? "");
    $newslug         = strtolower(str_replace(' ', '-', $newname));
    $newyob          = trim($_POST["authoryob"] ?? "");
    $newyod          = trim($_POST["authoryod"] ?? "");
    $newbio          = trim($_POST["authorbio"] ?? "");
    $newpob          = trim($_POST["authorpob"] ?? "");

    if ($newname !== $author['Author_Name']) {
        $stmt = $conn->prepare("UPDATE tbl_Authors SET Author_Name=?, Slug=? WHERE ID=?");
        $stmt->bind_param("ssi", $newname, $newslug, $authorId);
        $stmt->execute();
        $author['Author_Name'] = $newname;
        $author['Slug'] = $newslug;
        $success .= "Name updated.<br>";
    }

    if ($newyob !== (string)$author['Year_Of_Birth']) {
        $stmt = $conn->prepare("UPDATE tbl_Authors SET Year_Of_Birth=? WHERE ID=?");
        $stmt->bind_param("si", $newyob, $authorId);
        $stmt->execute();
        $success .= "Year of birth updated.<br>";
    }

    if ($newyod !== (string)$author['Year_Of_Death']) {
        $stmt = $conn->prepare("UPDATE tbl_Authors SET Year_Of_Death=? WHERE ID=?");
        $stmt->bind_param("si", $newyod, $authorId);
        $stmt->execute();
        $success .= "Year of death updated.<br>";
    }

    if ($newbio !== $author['Bio']) {
        $stmt = $conn->prepare("UPDATE tbl_Authors SET Bio=? WHERE ID=?");
        $stmt->bind_param("si", $newbio, $authorId);
        $stmt->execute();
        $success .= "Bio updated.<br>";
    }

    if ($newpob !== $author['Place_Of_Birth']) {
        $stmt = $conn->prepare("UPDATE tbl_Authors SET Place_Of_Birth=? WHERE ID=?");
        $stmt->bind_param("si", $newpob, $authorId);
        $stmt->execute();
        $success .= "Place of birth updated.<br>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Author</title>
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

<h2>Edit Author — <?= htmlspecialchars($author['Author_Name']) ?></h2>

<form method="POST" action="editauthors.php?ID=<?= $authorId ?>">
    <input type="hidden" name="photograph" id="croppedImageInput">

    <label>Name:</label><br>
    <input type="text" name="authorname" value="<?= htmlspecialchars($author['Author_Name']) ?>"><br><br>

    <label>Year of Birth:</label><br>
    <input type="text" name="authoryob" value="<?= htmlspecialchars($author['Year_Of_Birth']) ?>"><br><br>

    <label>Year of Death:</label><br>
    <input type="text" name="authoryod" value="<?= htmlspecialchars($author['Year_Of_Death'] ?? '') ?>"><br><br>

    <label>Place of Birth:</label><br>
    <input type="text" name="authorpob" value="<?= htmlspecialchars($author['Place_Of_Birth'] ?? '') ?>"><br><br>

    <label>Bio:</label><br>
    <textarea name="authorbio" rows="6"><?= htmlspecialchars($author['Bio'] ?? '') ?></textarea><br><br>

    <label>Photograph:</label><br>
    <?php if (!empty($author['Photograph']) && $author['Photograph'] !== "default.png"): ?>
        <img src="Authors/Photographs/<?= htmlspecialchars($author['Photograph']) ?>" 
             width="120" height="150" style="object-fit:cover;"><br><br>
    <?php endif; ?>

    <input type="file" id="uploadImage" accept="image/*"><br><br>

    <div style="width:300px; margin-top:20px;">
        <img id="imageToCrop" style="max-width:100%; display:none;">
    </div>
    <button type="button" id="cropBtn" style="display:none;">Crop & Set Photo</button><br><br>

    <input type="submit" value="Save Changes">
</form>

<a href="authorlist.php">← Back to author list</a>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
<script src="../editprofile.js"></script>
<script src="/DSTLib/main.js"></script>
</body>
</html>