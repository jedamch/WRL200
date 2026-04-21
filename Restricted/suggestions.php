<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../requirerole.php';

requirerole(['Admin']);

// Handle approve/reject
if (isset($_GET['action']) && isset($_GET['ID'])) {
    $action = $_GET['action'];
    $suggId = (int)$_GET['ID'];

    if (in_array($action, ['approved', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE tbl_Book_Suggestions SET Status=? WHERE ID=?");
        $stmt->bind_param("si", $action, $suggId);
        $stmt->execute();
    }

    header("Location: suggestions.php");
    exit;
}

$status = $_GET['status'] ?? 'pending';
if (!in_array($status, ['pending', 'approved', 'rejected'])) $status = 'pending';

$stmt = $conn->prepare("
    SELECT tbl_Book_Suggestions.*, tbl_Users.User_Name
    FROM tbl_Book_Suggestions
    INNER JOIN tbl_Users ON tbl_Book_Suggestions.User_ID = tbl_Users.ID
    WHERE tbl_Book_Suggestions.Status = ?
    ORDER BY tbl_Book_Suggestions.Created_At DESC
");
$stmt->bind_param("s", $status);
$stmt->execute();
$suggestions = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Suggestions</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <h2>Welcome to DSTLib</h2>

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

<h2>Book Suggestions</h2>

<p>
    <a href="?status=pending" <?= $status === 'pending' ? 'style="font-weight:bold;"' : '' ?>>Pending</a> |
    <a href="?status=approved" <?= $status === 'approved' ? 'style="font-weight:bold;"' : '' ?>>Approved</a> |
    <a href="?status=rejected" <?= $status === 'rejected' ? 'style="font-weight:bold;"' : '' ?>>Rejected</a>
</p>

<table class="styled-table">
    <thead>
        <tr>
            <th>Title</th>
            <th>Author</th>
            <th>Year</th>
            <th>Reason</th>
            <th>Submitted By</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($suggestions->num_rows > 0): ?>
        <?php while ($s = $suggestions->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($s['Title']) ?></td>
                <td><?= htmlspecialchars($s['Author']) ?></td>
                <td><?= htmlspecialchars($s['Year'] ?? '—') ?></td>
                <td><?= nl2br(htmlspecialchars($s['Reason'] ?? '—')) ?></td>
                <td>
                    <a href="../Members/profile.php?user=<?= htmlspecialchars($s['User_Name']) ?>">
                        <?= htmlspecialchars($s['User_Name']) ?>
                    </a>
                </td>
                <td><?= date('d M Y', strtotime($s['Created_At'])) ?></td>
                <td>
                    <?php if ($s['Status'] === 'pending'): ?>
                        <a href="?action=approved&ID=<?= $s['ID'] ?>" style="color:green;">Approve</a>
                        |
                        <a href="?action=rejected&ID=<?= $s['ID'] ?>" style="color:red;">Reject</a>
                    <?php elseif ($s['Status'] === 'approved'): ?>
                        <a href="?action=rejected&ID=<?= $s['ID'] ?>" style="color:red;">Reject</a>
                    <?php else: ?>
                        <a href="?action=approved&ID=<?= $s['ID'] ?>" style="color:green;">Approve</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="7">No <?= $status ?> suggestions.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<br><a href="dashboard.php">← Back to dashboard</a>

</body>
</html>