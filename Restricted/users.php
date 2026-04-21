<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../requirerole.php';

requirerole(['Admin']);

$result = $conn->query("SELECT ID, User_Name, Email_Address, UserLevel, Is_Banned, Ban_Reason, Last_Login, Last_IP FROM tbl_Users ORDER BY DateCreated DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users</title>
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

<h2>Users</h2>

<?php if (isset($_SESSION['message'])): ?>
    <p style="color:green;"><?= $_SESSION['message'] ?></p>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<table class="styled-table">
    <thead>
        <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Level</th>
            <th>Last Login</th>
            <th>Last IP</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['User_Name']) ?></td>
            <td><?= htmlspecialchars($row['Email_Address']) ?></td>
            <td><?= htmlspecialchars($row['UserLevel']) ?></td>
            <td><?= $row['Last_Login'] ? date('d M Y H:i', strtotime($row['Last_Login'])) : '—' ?></td>
            <td><?= htmlspecialchars($row['Last_IP'] ?? '—') ?></td>
            <td>
                <?php if ($row['Is_Banned']): ?>
                    <span style="color:red;">Banned</span>
                    <?php if ($row['Ban_Reason']): ?>
                        <br><small><?= htmlspecialchars($row['Ban_Reason']) ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    <span style="color:green;">Active</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($row['ID'] !== (int)$_SESSION['ID'] && $row['UserLevel'] !== 'Admin'): ?>
                    <?php if ($row['Is_Banned']): ?>
                        <a href="unbanuser.php?ID=<?= $row['ID'] ?>">Unban</a>
                    <?php else: ?>
                        <a href="banuser.php?ID=<?= $row['ID'] ?>" style="color:red;">Ban</a>
                    <?php endif; ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<br><a href="dashboard.php">← Back to dashboard</a>
<script src="/DSTLib/main.js"></script>
</body>
</html>