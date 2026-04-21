<?php

session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../requirerole.php';

requirerole(['Admin']);

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title    = trim($_POST["booktitle"] ?? "");
    $slug = strtolower(str_replace(' ', '-', $title));
    $synopsis = trim($_POST["booksynopsis"] ?? "");
    $year     = trim($_POST["bookyear"] ?? "");
    $authorid = trim($_POST["authorid"] ?? "");

    if (empty($title) || empty($authorid)) {
        $error = "Title and Author ID are required.";
    } else {

        $cover = "default.png";

        $stmt = $conn->prepare("INSERT INTO tbl_Books (Title, Slug, Synopsis, Year_Of_Release, Author_ID, Cover) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiss", $title, $slug, $synopsis, $year, $authorid, $cover);
        $stmt->execute();

        $newBookId = $conn->insert_id;

   
        if (!empty($_POST['bookcover'])) {
            $data = explode(",", $_POST['bookcover'])[1];
            $data = base64_decode($data);

            $newbookcover = "cover_" . $newBookId . "_" . time() . ".png";
            $uploadDir = __DIR__ . "/Books/Covers/";

            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            file_put_contents($uploadDir . $newbookcover, $data);

            $stmt = $conn->prepare("UPDATE tbl_Books SET Cover=? WHERE ID=?");
            $stmt->bind_param("si", $newbookcover, $newBookId);
            $stmt->execute();
        }

        $success = "Book added successfully.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book</title>
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

<h2>Add New Book</h2>

<form method="POST" action="addbooks.php">
    <input type="hidden" name="bookcover" id="croppedImageInput">

    <label>Title:</label><br>
    <input type="text" name="booktitle"><br><br>

    <label>Synopsis:</label><br>
    <textarea name="booksynopsis" rows="4"></textarea><br><br>

    <label>Year of Release:</label><br>
    <input type="text" name="bookyear"><br><br>

 <label>Author:</label><br>
<select name="authorid">
    <option value="">-- Select an Author --</option>
    <?php
    $authors = $conn->query("SELECT ID, Author_Name FROM tbl_Authors ORDER BY Author_Name ASC");
    while ($a = $authors->fetch_assoc()) {
        echo '<option value="' . $a['ID'] . '">' . htmlspecialchars($a['Author_Name']) . '</option>';
    }
    ?>
</select><br><br>

    <label>Cover:</label><br>
    <input type="file" id="uploadImage" accept="image/*"><br><br>

    <div style="width:300px; margin-top:20px;">
        <img id="imageToCrop" style="max-width:100%; display:none;">
    </div>
    <button type="button" id="cropBtn" style="display:none;">Crop & Set Cover</button><br><br>

    <input type="submit" value="Add Book">
</form>

<a href="dashboard.php">← Back to dashboard</a>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
<script src="../editprofile.js"></script>
<script src="/DSTLib/main.js"></script>
</body>
</html>