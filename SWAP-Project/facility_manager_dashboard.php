<?php
session_start();
include('db_connection.php');

// Handle AJAX delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_delete'])) {
    $assignment_id = $_POST['assignment_id'];

    $delete_query = "DELETE FROM inventory_assignments WHERE assignment_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $assignment_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Record deleted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete the record."]);
    }
    exit();
}

// Logout functionality
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_unset();
    session_destroy();
    if (isset($_COOKIE['username'])) {
        setcookie('username', '', time() - 3600, '/');
    }
    if (isset($_COOKIE['role'])) {
        setcookie('role', '', time() - 3600, '/');
    }
    header("Location: login.php");
    exit();
}

// Check user role and redirect if not authorized
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'facility_manager') {
    header("Location: login.php");
    exit();
}

// Handle approve/reject, edit, delete, and create assignment actions

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $request_id = $_POST['assignment_id'];

        // Get request details
        $query = "SELECT ar.equipment_id, ar.student_id FROM assignment_requests ar WHERE ar.request_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();

        // Insert into inventory_assignments
        $insert_query = "INSERT INTO inventory_assignments (equipment_id, student_id, assigned_date, due_date) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY))";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("ii", $request['equipment_id'], $request['student_id']);
        $insert_stmt->execute();

        // Update the status of the request to approved
        $update_query = "UPDATE assignment_requests SET status = 'approved' WHERE request_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $request_id);
        $update_stmt->execute();

        $success_message = "Request approved and assignment created successfully.";
    } elseif (isset($_POST['reject'])) {
        $request_id = $_POST['assignment_id'];
        $update_query = "UPDATE assignment_requests SET status = 'rejected' WHERE request_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $error_message = "Request rejected.";
    } elseif (isset($_POST['edit'])) {
        $assignment_id = $_POST['assignment_id'];
        header("Location: edit_assignment.php?assignment_id=$assignment_id");
        exit();
    } elseif (isset($_POST['delete'])) {
        $assignment_id = $_POST['assignment_id'];
        $delete_query = "DELETE FROM inventory_assignments WHERE assignment_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $assignment_id);
        if ($stmt->execute()) {
            $success_message = "Record deleted successfully.";
        } else {
            $error_message = "Failed to delete the record.";
        }
    } elseif (isset($_POST['create_assignment'])) {
        // Handle create new assignment form submission
        $username = $_POST['username'];
        $equipment_id = $_POST['equipment_id'];
        $due_date = $_POST['due_date'];

        // Look up student ID based on username
        $query = "SELECT user_id FROM users WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();

        if ($student) {
            // Insert new assignment into the inventory_assignments table
            $insert_query = "INSERT INTO inventory_assignments (equipment_id, student_id, assigned_date, due_date) VALUES (?, ?, NOW(), ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iis", $equipment_id, $student['user_id'], $due_date);

            if ($insert_stmt->execute()) {
                $success_message = "New inventory assignment created successfully.";
            } else {
                $error_message = "Failed to create inventory assignment. Please try again.";
            }
        } else {
            $error_message = "Username not found.";
        }
    }

    // Redirect to prevent re-submission on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch pending requests
$requests = $conn->query("SELECT ar.request_id, le.equipment_name, u.first_name, ar.status 
                          FROM assignment_requests ar
                          JOIN lab_equipment le ON ar.equipment_id = le.equipment_id
                          JOIN users u ON ar.student_id = u.user_id
                          WHERE ar.status='pending'");

// Fetch available equipment for dropdown
$equipment_query = "SELECT equipment_id, equipment_name FROM lab_equipment";
$equipment_result = $conn->query($equipment_query);

// Search assignments by username
$search_results = null;
if (isset($_GET['student_search']) && !empty($_GET['student_search'])) {
    $student_search = $_GET['student_search'];
    $query = "SELECT ia.assignment_id, le.equipment_name, ia.assigned_date, ia.due_date, u.username
              FROM inventory_assignments ia
              JOIN lab_equipment le ON ia.equipment_id = le.equipment_id
              JOIN users u ON ia.student_id = u.user_id
              WHERE u.username LIKE ?";
    $stmt = $conn->prepare($query);
    $search_term = "%" . $student_search . "%";
    $stmt->bind_param("s", $search_term);
    $stmt->execute();
    $search_results = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Manager Dashboard</title>
    <link rel="stylesheet" href="/SWAP-Project/css/dashboard_styles.css">
</head>
<body class="facility-manager">
    <header class="header">
        <div class="logo">
            <h2>BioSphere</h2>
        </div>
        <div class="username-dropdown">
            <button class="dropdown-btn">
                <?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?>
                <span class="caret">›</span>
            </button>
            <div class="dropdown-menu">
                <a href="profile.php">Profile</a>
                <a href="?action=logout">Logout</a>
            </div>
        </div>
    </header>

    <div class="main-content">
        <div class="sidebar">
            <ul>
                <li><a href="#tab1" class="tab-link active">Requests</a></li>
                <li><a href="#tab2" class="tab-link">Assignments</a></li>
                <li><a href="#tab3" class="tab-link">Create Assignment</a></li>
            </ul>
        </div>
        <div class="container">
            <h1>Facility Manager Dashboard</h1>

            <div id="tab1" class="tab-content active">
                <h2>Pending Inventory Assignment Requests</h2>
                <?php if (isset($success_message)): ?>
                    <p class="success-message"><?= htmlspecialchars($success_message); ?></p>
                <?php endif; ?>
                <?php if (isset($error_message)): ?>
                    <p class="error-message"><?= htmlspecialchars($error_message); ?></p>
                <?php endif; ?>
                <?php if ($requests->num_rows > 0): ?>
                    <table>
                        <tr>
                            <th>Equipment Name</th>
                            <th>Student Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        <?php while ($request = $requests->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($request['equipment_name']); ?></td>
                                <td><?= htmlspecialchars($request['first_name']); ?></td>
                                <td><?= htmlspecialchars($request['status']); ?></td>
                                <td>
                                <button class="delete-button button_delete" data-assignment-id="<?= $result['assignment_id']; ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p>No pending requests.</p>
                <?php endif; ?>
            </div>

            <div id="tab2" class="tab-content">
                <h2>Search Inventory Assignments</h2>
                <form method="GET">
                    <input type="text" name="student_search" placeholder="Search by username" value="<?= isset($_GET['student_search']) ? htmlspecialchars($_GET['student_search']) : ''; ?>">
                    <button type="submit" class="button">Search</button>
                </form>

                <?php if ($search_results && $search_results->num_rows > 0): ?>
                    <table>
                        <tr>
                            <th>Assignment ID</th>
                            <th>Equipment Name</th>
                            <th>Assigned Date</th>
                            <th>Due Date</th>
                            <th>Student Username</th>
                            <th>Actions</th>
                        </tr>
                        <?php while ($result = $search_results->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($result['assignment_id']); ?></td>
                                <td><?= htmlspecialchars($result['equipment_name']); ?></td>
                                <td><?= htmlspecialchars($result['assigned_date']); ?></td>
                                <td><?= htmlspecialchars($result['due_date']); ?></td>
                                <td><?= htmlspecialchars($result['username']); ?></td>
                                <td>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="assignment_id" value="<?= $result['assignment_id']; ?>">
                                        <button type="submit" name="edit" class="button_edit">Edit</button>
                                    </form>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="assignment_id" value="<?= $result['assignment_id']; ?>">
                                        <button type="submit" name="delete" class="button_delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p>No assignments found for the username entered.</p>
                <?php endif; ?>
            </div>

            <div id="tab3" class="tab-content">
                <h2>Create New Inventory Assignment</h2>
                <form method="POST">
                    <label for="username">Username:</label>
                    <input type="text" name="username" required>

                    <label for="equipment_id">Equipment:</label>
                    <select name="equipment_id" required>
                        <option value="">Select Equipment</option>
                        <?php while ($equipment = $equipment_result->fetch_assoc()): ?>
                            <option value="<?= $equipment['equipment_id']; ?>"><?= htmlspecialchars($equipment['equipment_name']); ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label for="due_date">Due Date:</label>
                    <input type="date" name="due_date" required>

                    <button type="submit" name="create_assignment" class="button">Create Assignment</button>
                </form>

                <?php if (isset($success_message)): ?>
                    <p class="success-message"><?= htmlspecialchars($success_message); ?></p>
                <?php endif; ?>
                <?php if (isset($error_message)): ?>
                    <p class="error-message"><?= htmlspecialchars($error_message); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="/SWAP-Project/js/dashboard.js"></script>
</body>
</html>