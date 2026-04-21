<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../requirerole.php';

requirerole(['Admin']);

// Handle resolve/dismiss
if (isset($_GET['action']) && isset($_GET['ID'])) {
    $action   = $_GET['action'];
    $reportId = (int)$_GET['ID'];

    if (in_array($action, ['resolved', 'dismissed'])) {
        $stmt = $conn->prepare("UPDATE tbl_Reports SET Status=? WHERE ID=?");
        $stmt->bind_param("si", $action, $reportId);
        $stmt->execute();
    }

    header("Location: reports.php");
    exit;
}

$status = $_GET['status'] ?? 'pending';
if (!in_array($status, ['pending', 'resolved', 'dismissed'])) $status = 'pending';

$stmt = $conn->prepare("
    SELECT tbl_Reports.*, tbl_Users.User_Name AS Reporter_Name
    FROM tbl_Reports
    INNER JOIN tbl_Users ON tbl_Reports.Reporter_ID = tbl_Users.ID
    WHERE tbl_Reports.Status = ?
    ORDER BY tbl_Reports.Created_At DESC
");
$stmt->bind_param("s", $status);
$stmt->execute();
$reports = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<h2>Reports</h2>
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

<?php if (isset($_SESSION['report_message'])): ?>
    <p style="color:green;"><?= htmlspecialchars($_SESSION['report_message']) ?></p>
    <?php unset($_SESSION['report_message']); ?>
<?php endif; ?>

<p>
    <a href="?status=pending" <?= $status === 'pending' ? 'style="font-weight:bold;"' : '' ?>>Pending</a> |
    <a href="?status=resolved" <?= $status === 'resolved' ? 'style="font-weight:bold;"' : '' ?>>Resolved</a> |
    <a href="?status=dismissed" <?= $status === 'dismissed' ? 'style="font-weight:bold;"' : '' ?>>Dismissed</a>
</p>

<table class="styled-table">
    <thead>
        <tr>
            <th>Type</th>
            <th>Comment ID</th>
            <th>Comment</th>
            <th>Reported By</th>
            <th>Reason</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($reports->num_rows > 0): ?>
        <?php while ($r = $reports->fetch_assoc()): ?>
            <?php
            // Fetch the actual comment content
            $commentText = '—';
            if ($r['Comment_Type'] === 'review_comment') {
                $cs = $conn->prepare("SELECT Comment_Body, tbl_Users.User_Name FROM tbl_Review_Comments INNER JOIN tbl_Users ON tbl_Review_Comments.User_ID = tbl_Users.ID WHERE tbl_Review_Comments.ID=?");
            } else {
                $cs = $conn->prepare("SELECT Comment_Body, tbl_Users.User_Name FROM tbl_Profile_Comments INNER JOIN tbl_Users ON tbl_Profile_Comments.Author_User_ID = tbl_Users.ID WHERE tbl_Profile_Comments.ID=?");
            }
            $cs->bind_param("i", $r['Comment_ID']);
            $cs->execute();
            $commentRow = $cs->get_result()->fetch_assoc();
            if ($commentRow) {
                $commentText = '"' . htmlspecialchars(substr($commentRow['Comment_Body'], 0, 80)) . (strlen($commentRow['Comment_Body']) > 80 ? '...' : '') . '" — ' . htmlspecialchars($commentRow['User_Name']);
            }
            ?>
            <tr>
                <td><?= str_replace('_', ' ', $r['Comment_Type']) ?></td>
                <td><?= $r['Comment_ID'] ?></td>
                <td style="font-size:0.85rem;"><?= $commentText ?></td>
                <td>
                    <a href="../Members/profile.php?user=<?= htmlspecialchars($r['Reporter_Name']) ?>">
                        <?= htmlspecialchars($r['Reporter_Name']) ?>
                    </a>
                </td>
                <td><?= nl2br(htmlspecialchars($r['Reason'] ?? '—')) ?></td>
                <td><?= date('d M Y H:i', strtotime($r['Created_At'])) ?></td>
                <td>
                    <?php if ($r['Status'] === 'pending'): ?>
                        <a href="?action=resolved&ID=<?= $r['ID'] ?>" style="color:green;">Resolve</a>
                        |
                        <a href="?action=dismissed&ID=<?= $r['ID'] ?>" style="color:#999;">Dismiss</a>
                    <?php else: ?>
                        <a href="?action=pending&ID=<?= $r['ID'] ?>">Reopen</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="7">No <?= $status ?> reports.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<br><a href="dashboard.php">← Back to dashboard</a>

</body>
</html>