<?php
session_start();
include('db_connection.php');

// Logout functionality
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    // Unset session and destroy
    session_unset();
    session_destroy();

    // Delete cookies if they exist (optional)
    if (isset($_COOKIE['username'])) {
        setcookie('username', '', time() - 3600, '/');
    }

    if (isset($_COOKIE['role'])) {
        setcookie('role', '', time() - 3600, '/');
    }

    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Check user role and redirect if not authorized
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

// Handle inventory request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_inventory'])) {
    $equipment_id = $_POST['equipment_id'];
    $stmt = $conn->prepare("INSERT INTO assignment_requests (equipment_id, student_id, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("ii", $equipment_id, $_SESSION['user_id']);
    $stmt->execute();

    // Check if the request was successfully inserted
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "Inventory request submitted successfully.";
    } else {
        $_SESSION['error_message'] = "Error submitting request. Please try again.";
    }

    // Redirect after submission to prevent form re-submission on refresh
    header("Location: /SWAP-Project/student_dashboard.php");
    exit(); // Ensure that no further code is executed after redirect
}

// Fetch current inventory assignments for the student
$query = "
    SELECT ia.assignment_id, le.equipment_name, ia.assigned_date, ia.due_date, ia.status 
    FROM inventory_assignments ia
    JOIN lab_equipment le ON ia.equipment_id = le.equipment_id
    WHERE ia.student_id = ? AND ia.status = 'assigned'";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$assignments = $result->fetch_all(MYSQLI_ASSOC);

// Fetch current inventory requests
$request_query = "
    SELECT ar.request_id, le.equipment_name, ar.status 
    FROM assignment_requests ar
    JOIN lab_equipment le ON ar.equipment_id = le.equipment_id
    WHERE ar.student_id = ?";

$request_stmt = $conn->prepare($request_query);
$request_stmt->bind_param("i", $_SESSION['user_id']);
$request_stmt->execute();
$request_result = $request_stmt->get_result();
$requests = $request_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="/SWAP-Project/css/dashboard_styles.css">
</head>
<body>
    <!-- Header -->
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
                <a href="?action=logout">Logout</a> <!-- Logout link -->
            </div>
        </div>
    </header>


    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Sidebar -->
        <div class="sidebar">
            <ul>
                <li><a href="#tab1" class="tab-link active">Assignments</a></li>
                <li><a href="#tab2" class="tab-link">Requests</a></li>
                <li><a href="#tab3" class="tab-link">New Request</a></li>
            </ul>
        </div>

        <!-- Content Container -->
        <div class="container">
            <h1>Student Dashboard</h1>

            <!-- Display Success or Error Message -->
            <?php
            if (isset($_SESSION['success_message'])) {
                echo "<p class='success-message'>" . htmlspecialchars($_SESSION['success_message']) . "</p>";
                unset($_SESSION['success_message']); // Clear the success message after displaying
            }

            if (isset($_SESSION['error_message'])) {
                echo "<p class='error-message'>" . htmlspecialchars($_SESSION['error_message']) . "</p>";
                unset($_SESSION['error_message']); // Clear the error message after displaying
            }
            ?>

            <!-- Tab 1: Current Assignments -->
            <div id="tab1" class="tab-content active">
                <h2>Your Current Inventory Assignments</h2>
                <?php if (!empty($assignments)): ?>
                    <table>
                        <tr>
                            <th>Equipment Name</th>
                            <th>Assigned Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td><?= htmlspecialchars($assignment['equipment_name']); ?></td>
                                <td><?= htmlspecialchars($assignment['assigned_date']); ?></td>
                                <td><?= htmlspecialchars($assignment['due_date']); ?></td>
                                <td><?= htmlspecialchars($assignment['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p>You have no current inventory assignments.</p>
                <?php endif; ?>
            </div>

            <!-- Tab 2: Inventory Requests -->
            <div id="tab2" class="tab-content">
                <h2>Your Inventory Requests</h2>
                <?php if (!empty($requests)): ?>
                    <table>
                        <tr>
                            <th>Equipment Name</th>
                            <th>Status</th>
                        </tr>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?= htmlspecialchars($request['equipment_name']); ?></td>
                                <td><?= htmlspecialchars($request['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p>You have no pending or previous requests.</p>
                <?php endif; ?>
            </div>

            <!-- Tab 3: Request New Inventory -->
            <div id="tab3" class="tab-content">
                <h2>Request New Inventory Assignment</h2>
                <form method="POST">
                    <label for="equipment_id">Select Equipment:</label>
                    <select name="equipment_id" id="equipment_id" required>
                        <?php
                        $equipmentQuery = $conn->query("SELECT equipment_id, equipment_name FROM lab_equipment WHERE status = 'available'");
                        while ($equipment = $equipmentQuery->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($equipment['equipment_id']); ?>">
                                <?= htmlspecialchars($equipment['equipment_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" name="request_inventory" class="button">Request Inventory</button>
                </form>
            </div>
        </div>
    </div>
    <!-- JavaScript for Dropdown Button -->
    <script src="/SWAP-Project/js/dashboard.js"></script>
</body>
</html>
