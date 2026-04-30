<?php
include 'db.php';
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lname = mysqli_real_escape_string($conn, $_POST['last_name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // Check if user exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Username already taken!";
    } else {
        // File Upload Logic
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES["id_image"]["name"], PATHINFO_EXTENSION);
        $filename = time() . "_" . $username . "." . $file_ext;
        $target_file = $target_dir . $filename;

        if (move_uploaded_file($_FILES["id_image"]["tmp_name"], $target_file)) {
            $sql = "INSERT INTO users (first_name, last_name, username, password, contact_number, address, id_filename) 
                    VALUES ('$fname', '$lname', '$username', '$password', '$contact', '$address', '$filename')";
            
            if (mysqli_query($conn, $sql)) {
                $message = "Registration successful! ID submitted for verification. <a href='login.php'>Login here</a>";
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        } else {
            $error = "Failed to upload ID. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Join Community Toolbox</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container" style="max-width: 500px;">
    <h2>Create Account</h2>
    <?php if($error) echo "<p style='color:red;'>$error</p>"; ?>
    <?php if($message) echo "<p style='color:green;'>$message</p>"; ?>

    <form method="POST" enctype="multipart/form-data">
        <div style="display:flex; gap:10px;">
            <div style="flex:1;">
                <label>First Name</label>
                <input type="text" name="first_name" required>
            </div>
            <div style="flex:1;">
                <label>Last Name</label>
                <input type="text" name="last_name" required>
            </div>
        </div>

        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Contact Number</label>
        <input type="text" name="contact" required>

        <label>Home Address</label>
        <textarea name="address" required></textarea>

        <label>Upload Valid ID (Image only)</label>
        <input type="file" name="id_image" accept="image/*" required>

        <button type="submit" style="width:100%; margin-top:10px;">Register & Submit ID</button>
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>
</div>
</body>
</html>
