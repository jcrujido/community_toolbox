<?php
include 'db.php';
session_start();

$user_id = $_SESSION['user_id'];

echo "<h2>Debugging Borrower Name Issue</h2>";

// 1. Check a borrowed tool
$tool_query = mysqli_query($conn, "SELECT * FROM tools WHERE owner_id = $user_id AND status = 'borrowed' LIMIT 1");
$tool = mysqli_fetch_assoc($tool_query);

if (!$tool) {
    die("<h3>No borrowed tools found for this user. Please Approve a request first.</h3>");
}

echo "<b>Tool Found:</b> " . $tool['tool_name'] . "<br>";
echo "<b>Value inside 'borrowed_by' column:</b> '" . $tool['borrowed_by'] . "'<br>";

// 2. Check if this value exists in Users table
$b_val = $tool['borrowed_by'];
echo "<hr>";

// Check as Username
$u_check = mysqli_query($conn, "SELECT * FROM users WHERE username = '$b_val'");
if (mysqli_num_rows($u_check) > 0) {
    $u = mysqli_fetch_assoc($u_check);
    echo "✅ <b>MATCH FOUND by Username!</b><br>";
    echo "The borrower is: " . $u['first_name'] . " " . $u['last_name'];
} else {
    echo "❌ <b>NO MATCH found by Username.</b><br>";
}

echo "<br>---<br>";

// Check as ID
$id_check = mysqli_query($conn, "SELECT * FROM users WHERE id = '$b_val'");
if (mysqli_num_rows($id_check) > 0) {
    $u = mysqli_fetch_assoc($id_check);
    echo "✅ <b>MATCH FOUND by ID!</b><br>";
    echo "The borrower is: " . $u['first_name'] . " " . $u['last_name'];
} else {
    echo "❌ <b>NO MATCH found by ID.</b>";
}
?>
