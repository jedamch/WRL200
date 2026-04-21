<?php

session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../requirerole.php';

requirerole(['Admin']);
$success = "";

if (empty($_GET['ID'])) die("No book ID provided.");
$bookId = (int) $_GET['ID'];

$stmt = $conn->prepare("SELECT Title, Synopsis, Year_Of_Release, Author_ID, Cover FROM tbl_Books WHERE ID = ?");
$stmt->bind_param("i", $bookId);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if (!empty($_POST['bookcover'])) {
    $data = explode(",", $_POST['bookcover'])[1];
    $data = base64_decode($data);

    $newbookcover = "cover_" . $bookId . "_" . time() . ".png";
    $uploadDir = __DIR__ . "/Books/Covers/";

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    file_put_contents($uploadDir . $newbookcover, $data);

    if (!empty($book['Cover']) && $book['Cover'] !== "default.png") {
        $oldPath = $uploadDir . $book['Cover'];
        if (file_exists($oldPath)) unlink($oldPath);
    }

    $stmt = $conn->prepare("UPDATE tbl_Books SET Cover=? WHERE ID=?");
    $stmt->bind_param("si", $newbookcover, $bookId);
    $stmt->execute();

    $book['Cover'] = $newbookcover;
    $success .= "Cover updated.<br>";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($_POST['bookcover'])) {
    $newtitle   = trim($_POST["booktitle"] ?? "");
    $newsynopsis = $_POST["booksynopsis"] ?? "";
    $newyear    = $_POST["bookyear"] ?? "";

    if ($newtitle !== $book['Title']) {
        $stmt = $conn->prepare("UPDATE tbl_Books SET Title=? WHERE ID=?");
        $stmt->bind_param("si", $newtitle, $bookId);
        $stmt->execute();
        $book['Title'] = $newtitle;
        $success .= "Title updated.<br>";
    }

    if ($newsynopsis !== $book['Synopsis']) {
        $stmt = $conn->prepare("UPDATE tbl_Books SET Synopsis=? WHERE ID=?");
        $stmt->bind_param("si", $newsynopsis, $bookId);
        $stmt->execute();
        $success .= "Synopsis updated.<br>";
    }

    if ($newyear !== $book['Year_Of_Release']) {
        $stmt = $conn->prepare("UPDATE tbl_Books SET Year_Of_Release=? WHERE ID=?");
        $stmt->bind_param("si", $newyear, $bookId);
        $stmt->execute();
        $success .= "Year of release updated.<br>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book dashboard</title> 
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

<?php if ($success): ?>
    <p style="color:green;"><?= $success ?></p>
<?php endif; ?>

<!-- Single form for everything -->
<form method="POST" action="editbooks.php?ID=<?= $bookId ?>">
    <input type="hidden" name="bookcover" id="croppedImageInput">

    <label>Book Title:</label><br>
    <input type="text" name="booktitle" value="<?= htmlspecialchars($book['Title']) ?>"><br><br>

    <label>Synopsis:</label><br>
    <textarea name="booksynopsis" rows="4"><?= htmlspecialchars($book['Synopsis']) ?></textarea><br><br>

    <label>Year of Release:</label><br>
    <input type="text" name="bookyear" value="<?= htmlspecialchars($book['Year_Of_Release']) ?>"><br><br>

 <label>Author:</label><br>
<select name="authorid">
    <option value="">-- Select an Author --</option>
    <?php
    $authors = $conn->query("SELECT ID, Author_Name FROM tbl_Authors ORDER BY Author_Name ASC");
    while ($a = $authors->fetch_assoc()) {
        $selected = ($a['ID'] == $book['Author_ID']) ? 'selected' : '';
        echo '<option value="' . $a['ID'] . '" ' . $selected . '>' . htmlspecialchars($a['Author_Name']) . '</option>';
    }
    ?>
</select><br><br>

    <label>Cover:</label><br>
<img src="Books/Covers/<?= htmlspecialchars($book['Cover']) ?>" 
     width="80" height="120"
     style="object-fit:contain;">

    <input type="file" id="uploadImage" accept="image/*"><br><br>

    <div style="width:1000px; margin-top:200px;">
        <img id="imageToCrop" style="max-width:100%; display:none;">
    </div>
    <button type="button" id="cropBtn" style="display:none;">Crop & Set Cover</button><br><br>

    <input type="submit" value="Save Changes">
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
<script src="../editprofile.js"></script>
<script src="/DSTLib/main.js"></script>
</body>
</html>