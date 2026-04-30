<?php
include 'db.php';
session_start();

// Auth check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$view = $_GET['view'] ?? 'community';

// Time Calculation Function
function get_time_diff($start_date) {
    if (!$start_date || $start_date == '0000-00-00 00:00:00') return "Just now";
    $start = new DateTime($start_date);
    $now = new DateTime();
    $diff = $now->diff($start);
    
    if ($diff->d > 0) return $diff->format('%a days, %h hrs');
    if ($diff->h > 0) return $diff->format('%h hrs, %i mins');
    return $diff->format('%i mins');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Community Toolbox</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Layout & Header */
        .header { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 2px solid #eee; }
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content { 
            display: none; position: absolute; right: 0; background: white; 
            box-shadow: 0 8px 16px rgba(0,0,0,0.2); z-index: 100; min-width: 160px; border-radius: 4px;
        }
        .dropdown:hover .dropdown-content { display: block; }
        .dropdown-content a { display: block; padding: 12px; border-bottom: 1px solid #eee; color: #333; text-decoration: none; }
        .dropdown-content a:hover { background: #f1f1f1; }

        /* Navigation */
        .nav-menu { margin: 20px 0; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .nav-group { display: flex; gap: 8px; align-items: center; border-right: 2px solid #ddd; padding-right: 15px; }
        .nav-group:last-child { border-right: none; }
        .nav-menu a { padding: 8px 15px; background: #ecf0f1; border-radius: 4px; color: #2c3e50; text-decoration: none; font-weight: 500; }
        .nav-menu a.active { background: #3498db; color: white; }

        /* Modals */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); }
        .modal-content { background: white; margin: 10% auto; padding: 25px; border-radius: 8px; width: 90%; max-width: 500px; position: relative; color: #333; text-align: left; }
        .close { position: absolute; right: 20px; top: 15px; font-size: 24px; cursor: pointer; color: #888; }
        textarea { width: 100%; height: 80px; margin-top: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: none; font-family: inherit; }
        
        /* Links & Table Styling */
        .view-details-link { text-decoration: underline; color: #27ae60; font-weight: bold; margin-right: 10px; cursor: pointer; border: none; background: none; padding: 0; font-size: inherit; }
        .view-details-link:hover { color: #219150; }
        .btn-delete { color: #e74c3c; font-weight: bold; text-decoration: none; }
        .btn-delete:hover { text-decoration: underline; }
        
        /* Status Badges */
        .badge.borrowed { background-color: #fce4e4; color: #c0392b; border: 1px solid #f9d2d2; padding: 2px 8px; border-radius: 12px; font-size: 0.85em; }
        .badge.available { background-color: #e8f5e9; color: #27ae60; border: 1px solid #c8e6c9; padding: 2px 8px; border-radius: 12px; font-size: 0.85em; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🛠️ Community Toolbox</h1>
        <div class="dropdown">
            <button>Hi, <?= htmlspecialchars($_SESSION['username']) ?> ▼</button>
            <div class="dropdown-content">
                <a href="profile.php">Profile Settings</a>
                <a href="logout.php" style="color: #e74c3c;">Logout</a>
            </div>
        </div>
    </div>

    <nav class="nav-menu">
        <div class="nav-group">
            <strong>Home:</strong>
            <a href="?view=my_tools" class="<?= $view=='my_tools'?'active':'' ?>">My Tools</a>
            <a href="?view=borrowed" class="<?= $view=='borrowed'?'active':'' ?>">Borrowed Tools</a>
        </div>
        <div class="nav-group">
            <strong>Toolbox:</strong>
            <a href="?view=community" class="<?= $view=='community'?'active':'' ?>">Community Tools</a>
        </div>
        <div class="nav-group">
            <strong>Requests:</strong>
            <a href="?view=requests" class="<?= $view=='requests'?'active':'' ?>">Borrow Requests</a>
        </div>
    </nav>

    <hr>

    <!-- MY TOOLS VIEW (Matches Image) -->
    <?php if ($view == 'my_tools'): ?>
        <h3>My Added Tools</h3>
        <form action="actions.php" method="POST" style="margin-bottom:20px; display:flex; gap:10px;">
            <input type="text" name="tool_name" placeholder="Tool name..." required style="margin:0; flex-grow:1;">
            <button type="submit" name="add_tool" style="background:#27ae60; color:white;">Add Tool</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>Tool Name</th>
                    <th>Status</th>
                    <th>Borrower</th>
                    <th>Date Borrowed</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // THE FIX: We join tools.borrowed_by (Username) with users.username
            $my_sql = "SELECT t.*, u.first_name, u.last_name, u.contact_number, u.address 
           FROM tools t 
           LEFT JOIN users u ON t.borrowed_by = u.username
           WHERE t.owner_id = $user_id";

                $res = mysqli_query($conn, $my_sql);
                
                while($row = mysqli_fetch_assoc($res)): 
                    $tid = $row['id'];
                    $modalId = "borrowerDetails" . $tid;
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['tool_name']) ?></td>
                    <td><span class="badge <?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                    <td>
                        <?php if($row['status'] == 'borrowed' && !empty($row['first_name'])): ?>
                            <?= htmlspecialchars($row['first_name'] . " " . $row['last_name']) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= ($row['status'] == 'borrowed' && $row['borrow_start_datetime']) ? date("M d", strtotime($row['borrow_start_datetime'])) : '-' ?></td>
                    <td>
                        <?php if($row['status'] == 'borrowed'): ?>
                            <button onclick="document.getElementById('<?= $modalId ?>').style.display='block'" class="view-details-link">View Details</button>
                            <!-- Modal remains as before -->
                            <div id="<?= $modalId ?>" class="modal">
                                <div class="modal-content">
                                    <span class="close" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
                                    <h3>Details: <?= htmlspecialchars($row['tool_name']) ?></h3>
                                    <p><strong>Borrower:</strong> <?= htmlspecialchars($row['first_name'] . " " . $row['last_name']) ?></p>
                                    <p><strong>Contact:</strong> <?= htmlspecialchars($row['contact_number']) ?></p>
                                    <p><strong>Address:</strong> <?= htmlspecialchars($row['address']) ?></p>
                                    <hr>
                                    <form action="actions.php" method="POST">
                                        <input type="hidden" name="tool_id" value="<?= $tid ?>">
                                        <label>Message to Borrower:</label>
                                        <textarea name="owner_msg"><?= htmlspecialchars($row['owner_message_to_borrower'] ?? '') ?></textarea>
                                        <button type="submit" name="update_borrower_msg" style="width:100%; margin-top:10px;">Save Message</button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                        <a href="actions.php?delete=<?= $tid ?>" class="btn-delete" onclick="return confirm('Delete this tool?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    <!-- COMMUNITY TOOLS VIEW -->
    <?php elseif ($view == 'community'): ?>
        <h3>Community Tools</h3>
        <form method="GET" class="search-container">
            <input type="hidden" name="view" value="community">
            <input type="text" name="search" placeholder="Search tools..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <select name="filter">
                <option value="all" <?= ($_GET['filter'] ?? '') == 'all' ? 'selected' : '' ?>>All Status</option>
                <option value="available" <?= ($_GET['filter'] ?? '') == 'available' ? 'selected' : '' ?>>Available</option>
                <option value="borrowed" <?= ($_GET['filter'] ?? '') == 'borrowed' ? 'selected' : '' ?>>Borrowed</option>
            </select>
            <button type="submit">Search</button>
        </form>

        <table>
            <tr><th>Tool Name</th><th>Owner</th><th>Status / Duration</th><th>Action</th></tr>
            <?php
            $search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
            $filter = $_GET['filter'] ?? 'all';
            $sql = "SELECT t.*, u.username FROM tools t JOIN users u ON t.owner_id = u.id WHERE t.owner_id != $user_id AND t.tool_name LIKE '%$search%'";
            if ($filter == 'available') $sql .= " AND t.status='available'";
            if ($filter == 'borrowed') $sql .= " AND t.status='borrowed'";
            
            $res = mysqli_query($conn, $sql);
            while($row = mysqli_fetch_assoc($res)): 
                $tid = $row['id'];
                $check = mysqli_query($conn, "SELECT status FROM borrow_requests WHERE tool_id=$tid AND borrower_id=$user_id AND status='pending'");
                $is_pending = mysqli_num_rows($check) > 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($row['tool_name']) ?></td>
                <td>@<?= htmlspecialchars($row['username']) ?></td>
                <td>
                    <span class="badge <?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span>
                    <?php if($row['status'] == 'borrowed'): ?>
                        <br><small>Out for: <?= get_time_diff($row['borrow_start_datetime']) ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($row['status'] == 'available' && !$is_pending): ?>
                        <button onclick="document.getElementById('reqModal<?= $tid ?>').style.display='block'">Borrow Request</button>
                        <div id="reqModal<?= $tid ?>" class="modal">
                            <div class="modal-content">
                                <span class="close" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
                                <h3>Request: <?= htmlspecialchars($row['tool_name']) ?></h3>
                                <form action="actions.php" method="POST">
                                    <input type="hidden" name="tool_id" value="<?= $tid ?>">
                                    <textarea name="borrower_note" placeholder="Note to owner..." required></textarea>
                                    <button type="submit" name="submit_request" style="width:100%; margin-top:10px;">Send Request</button>
                                </form>
                            </div>
                        </div>
                    <?php elseif($is_pending): ?>
                        <span style="color:orange;">Request Pending</span>
                    <?php elseif($row['borrowed_by'] == $_SESSION['username']): ?>
                        <a href="actions.php?return=<?= $tid ?>" class="btn-borrow">Return Tool</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

    <!-- BORROWED TOOLS VIEW -->
    <?php elseif ($view == 'borrowed'): ?>
        <h3>Tools I'm Borrowing</h3>
        <table>
            <tr><th>Tool Name</th><th>Duration</th><th>Owner's Message</th><th>Action</th></tr>
            <?php
            $b_res = mysqli_query($conn, "SELECT * FROM tools WHERE borrowed_by = '{$_SESSION['username']}'");
            while($row = mysqli_fetch_assoc($b_res)): ?>
            <tr>
                <td><?= htmlspecialchars($row['tool_name']) ?></td>
                <td><?= get_time_diff($row['borrow_start_datetime']) ?></td>
                <td style="background: #f9f9f9; padding: 10px; border-left: 3px solid #3498db;">
                    <small><?= $row['owner_message_to_borrower'] ?: 'No instructions.' ?></small>
                </td>
                <td><a href="actions.php?return=<?= $row['id'] ?>" class="btn-borrow">Return</a></td>
            </tr>
            <?php endwhile; ?>
        </table>

    <!-- REQUESTS VIEW -->
    <?php elseif ($view == 'requests'): ?>
        <h3>Incoming Requests</h3>
        <table>
            <tr><th>Tool</th><th>Borrower</th><th>Details</th><th>Action</th></tr>
            <?php
            $req_sql = "SELECT r.*, t.tool_name, u.first_name, u.last_name FROM borrow_requests r 
                        JOIN tools t ON r.tool_id = t.id 
                        JOIN users u ON r.borrower_id = u.id 
                        WHERE t.owner_id = $user_id AND r.status = 'pending'";
            $res = mysqli_query($conn, $req_sql);
            while($row = mysqli_fetch_assoc($res)): 
                $modalId = "reqDetail" . $row['id'];
            ?>
            <tr>
                <td><?= htmlspecialchars($row['tool_name']) ?></td>
                <td><?= htmlspecialchars($row['first_name'] . " " . $row['last_name']) ?></td>
                <td>
                    <button onclick="document.getElementById('<?= $modalId ?>').style.display='block'">Review</button>
                    <div id="<?= $modalId ?>" class="modal">
                        <div class="modal-content">
                            <span class="close" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
                            <h3>Request for <?= htmlspecialchars($row['tool_name']) ?></h3>
                            <p><strong>Note:</strong> "<?= htmlspecialchars($row['borrower_note']) ?>"</p>
                            <hr>
                            <form action="actions.php" method="POST">
                                <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                                <textarea name="owner_reply" placeholder="Reply/Instructions..."></textarea>
                                <div style="display:flex; gap:10px; margin-top:15px;">
                                    <button type="submit" name="action_approve" style="flex:1;">Approve</button>
                                    <button type="submit" name="action_reject" style="flex:1; background:#e74c3c; color:white;">Reject</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </td>
                <td>Pending</td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>

</div>

<script>
    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = 'none';
        }
    }
</script>
</body>
</html>
