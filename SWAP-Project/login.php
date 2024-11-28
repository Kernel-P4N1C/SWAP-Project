<?php
session_start();

// Include the database connection
include('db_connection.php');

// Ensure connection is successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Process login form submission
if (isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Prepare SQL query to fetch user data based on entered username
    $stmt = $conn->prepare("SELECT user_id, username, password_hash, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $user); // "s" indicates the parameter is a string
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($db_user_id, $db_username, $db_password, $db_role);
    
    // If user found, validate password
    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        if (password_verify($pass, $db_password)) { // Check if the password is correct
            $_SESSION['user_id'] = $db_user_id; // Save user_id in the session
            $_SESSION['username'] = $db_username;
            $_SESSION['role'] = $db_role;

            // Redirect to the appropriate dashboard based on the user's role
            if ($db_role === 'student') {
                header("Location: student_dashboard.php");
                exit();
            } elseif ($db_role === 'admin') {
                header("Location: admin_dashboard.php");
                exit();
            } elseif ($db_role === 'lab_technician') {
                header("Location: technician_dashboard.php");
                exit();
            } elseif ($db_role === 'facility_manager') {
                header("Location: facility_manager_dashboard.php");
                exit();
            } else {
                header("Location: login.php?error=unauthorized");
                exit();
            }
            exit();

        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Invalid username.";
    }

    $stmt->close();
}

// Process logout action
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BioSphere</title>
    <link rel="stylesheet" href="/SWAP-Project/css/login_styles.css">
</head>
<body>
    <div class="container">
        <?php if (!isset($_SESSION['username'])): ?>
            <!-- Login Form -->
            <h2>Login</h2>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <button type="submit" name="login">Login</button>
            </form>
        <?php else: ?>
            <!-- Dashboard -->
            <h2>Dashboard</h2>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            <?php
                switch ($_SESSION['role']) {
                    case 'admin':
                        echo "<p>You are logged in as an Admin.</p>";
                        break;
                    case 'facility_manager':
                        echo "<p>You are logged in as a Facility Manager.</p>";
                        break;
                    case 'lab_technician':
                        echo "<p>You are logged in as a Lab Technician.</p>";
                        break;
                    case 'student':
                        echo "<p>You are logged in as a Student.</p>";
                        break;
                    default:
                        echo "<p>Role not recognized.</p>";
                }
            ?>
        <?php endif; ?>
    </div>
    <a href="/SWAP-Project/lab_home.php" class="back-to-home">← Go back to site</a>
</body>
</html>
