<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$message = "";

// Handle Update
if (isset($_POST['update_profile'])) {
    $fname = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lname = mysqli_real_escape_string($conn, $_POST['last_name']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    $sql = "UPDATE users SET first_name='$fname', last_name='$lname', contact_number='$contact', address='$address' WHERE id=$user_id";
    if (mysqli_query($conn, $sql)) {
        if (!empty($_POST['new_password'])) {
            $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password='$hashed' WHERE id=$user_id");
        }
        $message = "Profile updated successfully!";
    }
}

$res = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$user = mysqli_fetch_assoc($res);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile - Community Toolbox</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <nav><a href="index.php">← Back to Toolbox</a></nav>

    <div class="profile-card">
        <div class="profile-header">
            <div class="avatar-circle"><?= strtoupper(substr($user['first_name'], 0, 1)) ?></div>
            <h2><?= htmlspecialchars($user['first_name'] . " " . $user['last_name']) ?></h2>
            
            <?php if($user['is_verified']): ?>
                <span class="badge available">✓ Verified Member</span>
            <?php else: ?>
                <span class="badge borrowed" style="background:#f39c12; color:white;">Verification Pending</span>
            <?php endif; ?>
            
            <?php if($message) echo "<p style='color:green; margin-top:10px;'>$message</p>"; ?>
        </div>

        <!-- VIEW MODE -->
        <div id="viewMode">
            <div class="info-group">
                <label>Username</label>
                <p>@<?= htmlspecialchars($user['username']) ?></p>
            </div>
            <div class="info-group">
                <label>Contact Number</label>
                <p><?= htmlspecialchars($user['contact_number']) ?></p>
            </div>
            <div class="info-group">
                <label>Home Address</label>
                <p><?= nl2br(htmlspecialchars($user['address'])) ?></p>
            </div>
            <button onclick="toggleEdit()" style="width:100%; margin-top:20px;">Edit Profile</button>
        </div>

        <!-- EDIT MODE -->
        <form method="POST" id="editMode" class="edit-mode">
            <div style="display:flex; gap:10px;">
                <div style="flex:1;">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                </div>
                <div style="flex:1;">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                </div>
            </div>

            <label>Contact Number</label>
            <input type="text" name="contact" value="<?= htmlspecialchars($user['contact_number']) ?>" required>

            <label>Home Address</label>
            <textarea name="address" required><?= htmlspecialchars($user['address']) ?></textarea>

            <label>Change Password (leave blank to keep current)</label>
            <input type="password" name="new_password">

            <div class="profile-actions">
                <button type="submit" name="update_profile" style="flex:2;">Save Changes</button>
                <button type="button" onclick="toggleEdit()" class="btn-secondary" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleEdit() {
    var v = document.getElementById('viewMode');
    var e = document.getElementById('editMode');
    if (v.style.display === 'none') {
        v.style.display = 'block';
        e.style.display = 'none';
    } else {
        v.style.display = 'none';
        e.style.display = 'block';
    }
}
</script>
</body>
</html>
