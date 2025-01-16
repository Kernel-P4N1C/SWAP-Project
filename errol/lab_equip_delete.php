<?php
// Start the session
session_start();

// Check if a session ID is already set
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = bin2hex(random_bytes(16)); // Generate a secure session ID
}

// Optional: Set session timeout (e.g., 30 minutes)
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
} elseif (time() - $_SESSION['last_activity'] > 1800) {
    // Destroy session if idle for too long
    session_unset();
    session_destroy();
    header("Location: login.php"); // Redirect to login or another appropriate page
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Database connection
$con = mysqli_connect("localhost", "root", "", "lab equipment");

if (!$con) {
    die('Could not connect: ' . mysqli_connect_error());
}

// Fetch the 'delete_id' from the URL
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // Fetch the specific item details for the item being deleted
    $query = $con->prepare("SELECT * FROM `lab equipment` WHERE ID = ?");
    $query->bind_param("i", $delete_id);
    $query->execute();
    $result = $query->get_result();

    if ($result && $result->num_rows > 0) {
        $item = $result->fetch_assoc();
        $model_number = $item['MODEL_NUMBER']; // Get the model number of the item for fetching other items with the same model number
        $name = $item['NAME']; // Get the name of the item for fetching other items with the same name
    } else {
        echo "No item found with that ID.";
        exit();
    }
} else {
    echo "No ID provided.";
    exit();
}

// Fetch all items with the same name and model number (including the item to be deleted)
$all_items_query = $con->prepare("SELECT ID, NAME, TYPE, MODEL_NUMBER, PURCHASE_DATE, IMAGE FROM `lab equipment` WHERE NAME = ? AND MODEL_NUMBER = ?");
$all_items_query->bind_param("ss", $name, $model_number);
$all_items_query->execute();
$all_items_result = $all_items_query->get_result();

// Handle the delete form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_item_ids'])) {
    $delete_item_ids = $_POST['delete_item_ids']; // Array of IDs to delete

    // Loop through the selected IDs and delete them
    foreach ($delete_item_ids as $id) {
        $delete_query = $con->prepare("DELETE FROM `lab equipment` WHERE ID = ?");
        $delete_query->bind_param("i", $id);
        $delete_query->execute();
    }

    echo "Selected items deleted successfully!";
    // Redirect back to the view page after successful deletion
    header("Location: lab_equip_view.php");
    exit();
}

$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Lab Equipment</title>
    <link rel="stylesheet" href="lab_equip_delete.css">
</head>
<body>
    <h3>All Items with Name "<?php echo htmlspecialchars($item['NAME']); ?>" and Model Number "<?php echo htmlspecialchars($item['MODEL_NUMBER']); ?>"</h3>
    <form method="post" onsubmit="return confirm('Are you sure you want to delete the selected items?');">
        <table border="1">
            <tr>
                <th><input type="checkbox" id="select_all" onclick="selectAllItems()"></th>
                <th>Name</th>
                <th>Type</th>
                <th>Model Number</th>
                <th>Purchase Date</th>
                <th>Image</th>
            </tr>

            <?php while ($row = $all_items_result->fetch_assoc()) { ?>
                <tr>
                    <td><input type="checkbox" name="delete_item_ids[]" value="<?php echo $row['ID']; ?>"></td>
                    <td><?php echo htmlspecialchars($row['NAME']); ?></td>
                    <td><?php echo htmlspecialchars($row['TYPE']); ?></td>
                    <td><?php echo htmlspecialchars($row['MODEL_NUMBER']); ?></td>
                    <td><?php echo htmlspecialchars($row['PURCHASE_DATE']); ?></td>
                    <td><img src="<?php echo htmlspecialchars($row['IMAGE']); ?>" alt="Image" width="50"></td>
                </tr>
            <?php } ?>
        </table>

        <br>
        <button type="submit">Delete Selected</button>
    </form>

    <br>
    <a href="lab_equip_view.php">Back to Equipment List</a>

    <script>
        function selectAllItems() {
            var checkboxes = document.querySelectorAll('input[name="delete_item_ids[]"]');
            var selectAll = document.getElementById('select_all').checked;
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAll;
            });
        }
    </script>
</body>
</html>
