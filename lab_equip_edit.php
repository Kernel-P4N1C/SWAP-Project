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

// Fetch the 'edit_id' from the URL
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];

    // Fetch the specific item details for the item being edited
    $query = $con->prepare("SELECT * FROM `lab equipment` WHERE ID = ?");
    $query->bind_param("i", $edit_id);
    $query->execute();
    $result = $query->get_result();

    if ($result && $result->num_rows > 0) {
        $item = $result->fetch_assoc();
        $name = $item['NAME']; // Get the name for fetching other items with the same name and model number
        $model_number = $item['MODEL_NUMBER']; // Get the model number for fetching other items with the same name and model number
    } else {
        echo "No item found with that ID.";
        exit();
    }

    // Fetch all items with the same name and model number (including the item to be edited)
    $all_items_query = $con->prepare("SELECT ID, NAME, TYPE, MODEL_NUMBER, PURCHASE_DATE, IMAGE FROM `lab equipment` WHERE NAME = ? AND MODEL_NUMBER = ?");
    $all_items_query->bind_param("ss", $name, $model_number); // Use both name and model number to filter
    $all_items_query->execute();
    $all_items_result = $all_items_query->get_result();
} else {
    echo "No ID provided.";
    exit();
}

// Handle the edit form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Loop through all items and update them
    $update_success = true;
    foreach ($_POST['edit_item_ids'] as $id) {
        // Fetch the new details for each item
        $new_name = $_POST['name_' . $id];
        $new_type = $_POST['type_' . $id];
        $new_model_number = $_POST['model_number_' . $id];
        $new_purchase_date = $_POST['purchase_date_' . $id];
        $new_image = $_POST['image_url_' . $id]; // Get the image URL

        // Update the database with the edited details
        $update_query = $con->prepare("UPDATE `lab equipment` SET NAME = ?, TYPE = ?, MODEL_NUMBER = ?, PURCHASE_DATE = ?, IMAGE = ? WHERE ID = ?");
        $update_query->bind_param("sssssi", $new_name, $new_type, $new_model_number, $new_purchase_date, $new_image, $id);
        if (!$update_query->execute()) {
            $update_success = false;
        }
    }

    if ($update_success) {
        echo "Items updated successfully!";
        // Redirect back to the view page after successful update
        header("Location: lab_equip_view.php");
        exit();
    } else {
        echo "An error occurred while updating the items.";
    }
}

$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lab Equipment</title>
    <link rel="stylesheet" href="lab_equip_edit.css?v=1">
</head>
<body>
    <h3>Edit Items with Name "<?php echo htmlspecialchars($item['NAME']); ?>" and Model Number "<?php echo htmlspecialchars($item['MODEL_NUMBER']); ?>"</h3>
    <form method="post" onsubmit="return confirm('Are you sure you want to update these items?');">
        <table border="1">
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Model Number</th>
                <th>Purchase Date</th>
                <th>Image URL</th>
            </tr>
            <?php while ($row = $all_items_result->fetch_assoc()) { ?>
                <tr>
                    <td><input type="text" name="name_<?php echo $row['ID']; ?>" value="<?php echo htmlspecialchars($row['NAME']); ?>"></td>
                    <td><input type="text" name="type_<?php echo $row['ID']; ?>" value="<?php echo htmlspecialchars($row['TYPE']); ?>"></td>
                    <td><input type="text" name="model_number_<?php echo $row['ID']; ?>" value="<?php echo htmlspecialchars($row['MODEL_NUMBER']); ?>"></td>
                    <td><input type="date" name="purchase_date_<?php echo $row['ID']; ?>" value="<?php echo htmlspecialchars($row['PURCHASE_DATE']); ?>"></td>
                    <td>
                        <input type="text" name="image_url_<?php echo $row['ID']; ?>" value="<?php echo htmlspecialchars($row['IMAGE']); ?>">
                    </td>
                    <input type="hidden" name="edit_item_ids[]" value="<?php echo $row['ID']; ?>"> <!-- Add hidden input to track selected items -->
                </tr>
            <?php } ?>
        </table>

        <br>
        <button type="submit">Update Selected Items</button>
    </form>

    <br>
    <a href="lab_equip_view.php">Back to Equipment List</a>
</body>
</html>
