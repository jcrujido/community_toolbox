<?php
include 'db.php';
session_start();

// Redirect to login if the session is not active
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- 1. ADD NEW TOOL ---
if (isset($_POST['add_tool'])) {
    $name = mysqli_real_escape_string($conn, $_POST['tool_name']);
    mysqli_query($conn, "INSERT INTO tools (tool_name, owner_id, status) VALUES ('$name', '$user_id', 'available')");
    header("Location: index.php?view=my_tools");
    exit();
}

// --- 2. SUBMIT BORROW REQUEST (With Note) ---
if (isset($_POST['submit_request'])) {
    $tid = (int)$_POST['tool_id'];
    $note = mysqli_real_escape_string($conn, $_POST['borrower_note']);
    
    // Check for existing pending requests to prevent duplicates
    $check = mysqli_query($conn, "SELECT id FROM borrow_requests WHERE tool_id=$tid AND borrower_id=$user_id AND status='pending'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO borrow_requests (tool_id, borrower_id, borrower_note, status) 
                            VALUES ($tid, $user_id, '$note', 'pending')");
    }
    header("Location: index.php?view=community");
    exit();
}

// --- 3. APPROVE REQUEST (The Fix for Borrower Name) ---
if (isset($_POST['action_approve'])) {
    $rid = (int)$_POST['request_id'];
    $reply = mysqli_real_escape_string($conn, $_POST['owner_reply']);
    
    // Get the USERNAME of the borrower
    $req_res = mysqli_query($conn, "SELECT r.*, u.username FROM borrow_requests r JOIN users u ON r.borrower_id = u.id WHERE r.id=$rid");
    $req = mysqli_fetch_assoc($req_res);
    
    if ($req) {
        $tid = $req['tool_id'];
        $b_username = $req['username']; // This gets the text name

        // SAVE THE USERNAME STRING
        mysqli_query($conn, "UPDATE tools SET status='borrowed', borrowed_by='$b_username', borrow_start_datetime=NOW() WHERE id=$tid");
        
        mysqli_query($conn, "UPDATE borrow_requests SET status='approved', owner_reply='$reply' WHERE id=$rid");
    }
    header("Location: index.php?view=requests");
    exit();
}



// --- 4. REJECT REQUEST ---
if (isset($_POST['action_reject'])) {
    $rid = (int)$_POST['request_id'];
    mysqli_query($conn, "UPDATE borrow_requests SET status='rejected' WHERE id=$rid");
    header("Location: index.php?view=requests");
    exit();
}

// --- 5. UPDATE MESSAGE TO BORROWER (From My Tools) ---
if (isset($_POST['update_borrower_msg'])) {
    $tid = (int)$_POST['tool_id'];
    $msg = mysqli_real_escape_string($conn, $_POST['owner_msg']);
    mysqli_query($conn, "UPDATE tools SET owner_message_to_borrower = '$msg' WHERE id = $tid AND owner_id = $user_id");
    header("Location: index.php?view=my_tools");
    exit();
}

// --- 6. RETURN TOOL ---
if (isset($_GET['return'])) {
    $tid = (int)$_GET['return'];
    // Reset tool status and clear borrower info
    mysqli_query($conn, "UPDATE tools SET status='available', borrowed_by=NULL, borrow_start_datetime=NULL, owner_message_to_borrower=NULL WHERE id=$tid");
    // Clean up approved request record
    mysqli_query($conn, "DELETE FROM borrow_requests WHERE tool_id=$tid AND status='approved'");
    header("Location: index.php?view=borrowed");
    exit();
}

// --- 7. DELETE TOOL ---
if (isset($_GET['delete'])) {
    $tid = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM tools WHERE id=$tid AND owner_id=$user_id");
    header("Location: index.php?view=my_tools");
    exit();
}
?>
