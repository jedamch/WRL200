<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_GET['user'])) {
    die("No user specified.");
}

$userParam = $_GET['user'];

if (is_numeric($userParam)) {
    $stmt = $conn->prepare("SELECT * FROM tbl_Users WHERE ID = ?");
    $stmt->bind_param("i", $userParam);
} else {
    $stmt = $conn->prepare("SELECT * FROM tbl_Users WHERE User_Name = ?");
    $stmt->bind_param("s", $userParam);
}

$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) die("User not found.");

$profilePic    = $user['Avatar'] ?? 'default.png';
$profilePicUrl = "Uploads/$profilePic";
$isOwnProfile  = isset($_SESSION['User_Name']) && $_SESSION['User_Name'] === $user['User_Name'];

// Fetch profile comments
$stmt = $conn->prepare("
    SELECT tbl_Profile_Comments.ID, tbl_Profile_Comments.Comment_Body, tbl_Profile_Comments.Created_At,
           tbl_Users.User_Name, tbl_Users.Avatar
    FROM tbl_Profile_Comments
    INNER JOIN tbl_Users ON tbl_Profile_Comments.Author_User_ID = tbl_Users.ID
    WHERE tbl_Profile_Comments.Profile_User_ID = ?
    ORDER BY tbl_Profile_Comments.Created_At DESC
");
$stmt->bind_param("i", $user['ID']);
$stmt->execute();
$profileComments = $stmt->get_result();

// Fetch recent reviews
$stmt = $conn->prepare("
    SELECT tbl_Reviews.Rating, tbl_Reviews.Review_Body, tbl_Reviews.Created_At,
           tbl_Books.Title, tbl_Books.Slug, tbl_Books.Cover
    FROM tbl_Reviews
    INNER JOIN tbl_Books ON tbl_Reviews.Book_ID = tbl_Books.ID
    WHERE tbl_Reviews.User_ID = ?
    ORDER BY tbl_Reviews.Created_At DESC
    LIMIT 10
");
$stmt->bind_param("i", $user['ID']);
$stmt->execute();
$userReviews = $stmt->get_result();

// Fetch lists
$listQuery = "
    SELECT tbl_Lists.ID, tbl_Lists.Name, tbl_Lists.Slug, tbl_Lists.Is_Public,
           COUNT(tbl_List_Books.ID) AS Book_Count
    FROM tbl_Lists
    LEFT JOIN tbl_List_Books ON tbl_Lists.ID = tbl_List_Books.List_ID
    WHERE tbl_Lists.User_ID = ?
";
if (!$isOwnProfile) {
    $listQuery .= " AND tbl_Lists.Is_Public = 1";
}
$listQuery .= " GROUP BY tbl_Lists.ID ORDER BY tbl_Lists.Created_At DESC";

$stmt = $conn->prepare($listQuery);
$stmt->bind_param("i", $user['ID']);
$stmt->execute();
$userLists = $stmt->get_result();

// Get friend count
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total FROM tbl_Friends 
    WHERE (Requester_ID=? OR Recipient_ID=?) AND Status='accepted'
");
$stmt->bind_param("ii", $user['ID'], $user['ID']);
$stmt->execute();
$friendCount = $stmt->get_result()->fetch_assoc()['total'];

// Get friendship status if logged in
$friendStatus = null;
$friendRow    = null;
if (isset($_SESSION['ID']) && !$isOwnProfile) {
    $stmt = $conn->prepare("
        SELECT ID, Status, Requester_ID FROM tbl_Friends 
        WHERE (Requester_ID=? AND Recipient_ID=?) 
        OR (Requester_ID=? AND Recipient_ID=?)
    ");
    $stmt->bind_param("iiii", $_SESSION['ID'], $user['ID'], $user['ID'], $_SESSION['ID']);
    $stmt->execute();
    $friendRow = $stmt->get_result()->fetch_assoc();
    $friendStatus = $friendRow ? $friendRow['Status'] : null;
}

// Get pending requests for own profile
$pendingRequests = null;
if ($isOwnProfile) {
    $stmt = $conn->prepare("
        SELECT tbl_Friends.ID, tbl_Friends.Requester_ID, tbl_Users.User_Name, tbl_Users.Avatar
        FROM tbl_Friends
        INNER JOIN tbl_Users ON tbl_Friends.Requester_ID = tbl_Users.ID
        WHERE tbl_Friends.Recipient_ID=? AND tbl_Friends.Status='pending'
    ");
    $stmt->bind_param("i", $user['ID']);
    $stmt->execute();
    $pendingRequests = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($user['User_Name']) ?>'s Profile</title>
    <link rel="stylesheet" href="../profilestyle.css">
    <link rel="stylesheet" href="../style.css">
    <script>
    function toggleReportForm(id) {
        const el = document.getElementById(id);
        if (el) {
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }
    }
    </script>
    <script src="/DSTLib/main.js" defer></script>
</head>
<body>

<div class="topnavigationbar">
    <div class="nav-left">
        <a href="../index.php">Home</a>
        <a href="../books.php">Books</a>
        <a href="../suggestabook">Suggest A Book</a>
        <a href="../lists.php">Lists</a>
        <a href="../about.php">About</a>
    </div>

    <div class="nav-right">
    <?php
    if (isset($_SESSION["User_Name"])) {
        $navPic = $_SESSION['Avatar'] ?? 'default.png';
        echo "<a href='/DSTLib/Members/profile.php?user=" . $_SESSION['User_Name'] . "' class='profile-link'>";
        echo "<img src='../Members/Uploads/$navPic' alt='Profile' class='profile-pic'>";
        echo "<span>" . htmlspecialchars($_SESSION['User_Name']) . "</span>";
        echo "</a>";
        echo "<a href='/DSTLib/notifications.php' class='bell-icon' id='notifBell'>🔔<span class='notif-count' id='notifCount' style='display:none;'></span></a>";
        echo "<a href='../logout.php'>Logout</a>";
    } else {
        echo "<a href='../login.php'>Log in</a>";
        echo "<a href='../createaccount.php'>Create Account</a>";
    }

    if (isset($_SESSION['UserLevel']) && $_SESSION['UserLevel'] === "Admin") {
        echo "<a href='/DSTLib/Restricted/dashboard.php'>Admin Dash</a>";
    }
    ?>
    </div>
</div>

<div class="profile-container">
    <img src="<?= $profilePicUrl ?>" alt="Profile Picture">
    <h2><?= htmlspecialchars($user['User_Name']) ?></h2>

    <div class="profile-info">
        <p><strong>Date Joined:</strong> <?= date("F j, Y", strtotime($user['DateCreated'])) ?></p>
        <p><strong>Bio:</strong> <?= nl2br(htmlspecialchars($user['Bio'] ?? 'No bio yet.')) ?></p>
        <p><strong>Friends:</strong> <?= $friendCount ?></p>

        <?php if (!$isOwnProfile && isset($_SESSION['User_Name'])): ?>
            <?php $profileUrl = "/DSTLib/Members/profile.php?user=" . urlencode($user['User_Name']); ?>

            <?php if ($friendStatus === 'accepted'): ?>
                <form method="POST" action="/DSTLib/removefriend.php">
                    <input type="hidden" name="user_id" value="<?= $user['ID'] ?>">
                    <input type="hidden" name="redirect" value="<?= $profileUrl ?>">
                    <button type="submit" onclick="return confirm('Remove this friend?')">Remove Friend</button>
                </form>

            <?php elseif ($friendStatus === 'pending' && $friendRow['Requester_ID'] === (int)$_SESSION['ID']): ?>
                <p><em>Friend request sent.</em></p>

            <?php elseif ($friendStatus === 'pending' && $friendRow['Requester_ID'] === $user['ID']): ?>
                <a href="/DSTLib/friendresponse.php?action=accept&from=<?= $user['ID'] ?>&redirect=<?= urlencode($profileUrl) ?>">Accept</a>
                <a href="/DSTLib/friendresponse.php?action=reject&from=<?= $user['ID'] ?>&redirect=<?= urlencode($profileUrl) ?>" style="color:red;">Decline</a>

            <?php else: ?>
                <form method="POST" action="/DSTLib/friendrequest.php">
                    <input type="hidden" name="recipient_id" value="<?= $user['ID'] ?>">
                    <input type="hidden" name="redirect" value="<?= $profileUrl ?>">
                    <button type="submit">Add Friend</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($isOwnProfile && $pendingRequests && $pendingRequests->num_rows > 0): ?>
            <div class="pending-requests">
                <h3>Friend Requests</h3>
                <?php while ($req = $pendingRequests->fetch_assoc()): ?>
                    <div class="request-item">
                        <img src="Uploads/<?= htmlspecialchars($req['Avatar'] ?? 'default.png') ?>"
                             width="32" height="32" style="border-radius:50%;object-fit:cover;">
                        <a href="profile.php?user=<?= htmlspecialchars($req['User_Name']) ?>">
                            <?= htmlspecialchars($req['User_Name']) ?>
                        </a>
                        <a href="/DSTLib/friendresponse.php?action=accept&from=<?= $req['Requester_ID'] ?>&redirect=<?= urlencode("/DSTLib/Members/profile.php?user=" . $_SESSION['User_Name']) ?>">Accept</a>
                        <a href="/DSTLib/friendresponse.php?action=reject&from=<?= $req['Requester_ID'] ?>&redirect=<?= urlencode("/DSTLib/Members/profile.php?user=" . $_SESSION['User_Name']) ?>" style="color:red;">Decline</a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($isOwnProfile): ?>
        <a href="../editprofile.php" class="edit-btn">Edit Profile</a>
    <?php endif; ?>
</div>

<!-- Comments -->
<div class="profile-comments">
    <h2>Comments</h2>

    <?php if (isset($_SESSION['message'])): ?>
        <p style="color:green;"><?= htmlspecialchars($_SESSION['message']) ?></p>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['User_Name'])): ?>
        <form method="POST" action="../submitprofilecomment.php">
            <input type="hidden" name="profile_user_id" value="<?= $user['ID'] ?>">
            <input type="hidden" name="profile_username" value="<?= htmlspecialchars($user['User_Name']) ?>">
            <textarea name="comment_body" rows="3" placeholder="Leave a comment..." style="width:100%;"></textarea><br><br>
            <button type="submit">Post Comment</button>
        </form>
        <hr>
    <?php else: ?>
        <p><a href="../login.php">Log in</a> to leave a comment.</p>
        <hr>
    <?php endif; ?>

    <?php if ($profileComments->num_rows > 0): ?>
        <?php while ($pc = $profileComments->fetch_assoc()): ?>
            <div class="comment-item">
                <img src="Uploads/<?= htmlspecialchars($pc['Avatar'] ?? 'default.png') ?>"
                     width="32" height="32" style="border-radius:50%;object-fit:cover;">
                <div class="comment-content">
                    <a href="profile.php?user=<?= htmlspecialchars($pc['User_Name']) ?>">
                        <strong><?= htmlspecialchars($pc['User_Name']) ?></strong>
                    </a>
                    <span class="review-date"><?= date('d M Y', strtotime($pc['Created_At'])) ?></span>
                    <p><?= nl2br(htmlspecialchars($pc['Comment_Body'])) ?></p>

                    <div style="display:flex;gap:10px;align-items:center;margin-top:4px;">
                        <?php if (isset($_SESSION['User_Name']) &&
                                  ($_SESSION['User_Name'] === $pc['User_Name'] ||
                                   $_SESSION['User_Name'] === $user['User_Name'] ||
                                   $_SESSION['UserLevel'] === 'Admin')): ?>
                            <a href="../deleteprofilecomment.php?ID=<?= $pc['ID'] ?>&user=<?= htmlspecialchars($user['User_Name']) ?>"
                               style="font-size:0.75rem;color:red;"
                               onclick="return confirm('Delete this comment?')">Delete</a>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['User_Name']) && $_SESSION['User_Name'] !== $pc['User_Name']): ?>
                            <button onclick="toggleReportForm('profile_comment_<?= $pc['ID'] ?>')"
                                    style="font-size:0.75rem;background:none;border:none;color:#999;cursor:pointer;">
                                Report
                            </button>
                            <div id="profile_comment_<?= $pc['ID'] ?>" style="display:none;margin-top:6px;">
                                <form method="POST" action="../submitreport.php">
                                    <input type="hidden" name="comment_type" value="profile_comment">
                                    <input type="hidden" name="comment_id" value="<?= $pc['ID'] ?>">
                                    <input type="hidden" name="redirect" value="Members/profile.php?user=<?= htmlspecialchars($user['User_Name']) ?>">
                                    <textarea name="reason" rows="2" placeholder="Reason (optional)" style="width:100%;font-size:0.8rem;"></textarea>
                                    <button type="submit" style="font-size:0.8rem;">Submit Report</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No comments yet.</p>
    <?php endif; ?>
</div>

<!-- Recent Reviews -->
<div class="recent-reviews">
    <h2>Recent Reviews</h2>

    <?php if ($userReviews->num_rows > 0): ?>
        <?php while ($r = $userReviews->fetch_assoc()): ?>
            <div class="review-item">
                <div class="review-header">
                    <a href="../books.php?book=<?= htmlspecialchars($r['Slug']) ?>">
                        <img src="../Restricted/Books/Covers/<?= htmlspecialchars($r['Cover']) ?>"
                             width="40" height="60" style="object-fit:cover;">
                    </a>
                    <div>
                        <a href="../books.php?book=<?= htmlspecialchars($r['Slug']) ?>">
                            <?= htmlspecialchars($r['Title']) ?>
                        </a>
                        <span class="review-stars">
                            <?= $r['Rating'] === 0 ? '0 ☆☆☆☆' : str_repeat('★', $r['Rating']) . str_repeat('☆', 4 - $r['Rating']) ?>
                        </span>
                        <span class="review-date">
                            <?= date('d M Y', strtotime($r['Created_At'])) ?>
                        </span>
                    </div>
                </div>
                <?php if (!empty($r['Review_Body'])): ?>
                    <p class="review-body"><?= nl2br(htmlspecialchars($r['Review_Body'])) ?></p>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No reviews yet.</p>
    <?php endif; ?>
</div>

<!-- Lists -->
<div class="profile-lists">
    <h2>Lists</h2>

    <?php if ($isOwnProfile): ?>
        <a href="../createlists.php">+ Create New List</a><br><br>
    <?php endif; ?>

    <?php if ($userLists->num_rows > 0): ?>
        <?php while ($l = $userLists->fetch_assoc()): ?>
            <div class="list-card">
                <a href="/DSTLib/list.php?user=<?= urlencode($user['User_Name']) ?>&list=<?= urlencode($l['Slug']) ?>">
                    <?= htmlspecialchars($l['Name']) ?>
                </a>
                <span style="color:#999;font-size:0.8rem;">
                    — <?= $l['Book_Count'] ?> book<?= $l['Book_Count'] !== 1 ? 's' : '' ?>
                    <?= !$l['Is_Public'] ? ' 🔒' : '' ?>
                </span>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No lists yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
