<?php
include 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Equipment Management</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        table, th, td { border: 1px solid black; padding: 8px; text-align: left; }
        form { display: inline; }
    </style>
</head>
<body>
    <h1>Lab Equipment Management</h1>

    <!-- Create Equipment Form -->
    <h2>Create New Equipment</h2>
    <form method="POST" action="">
        <label for="name">Equipment Name:</label>
        <input type="text" name="name" required>
        <label for="type">Type:</label>
        <input type="text" name="type" required>
        <label for="model_number">Model Number:</label>
        <input type="text" name="model_number" required>
        <label for="purchase_date">Purchase Date:</label>
        <input type="date" name="purchase_date" required>
        <button type="submit" name="create">Create</button>
    </form>

    <?php
    // Handle Create
    if (isset($_POST['create'])) {
        $name = $_POST['name'];
        $type = $_POST['type'];
        $model_number = $_POST['model_number'];
        $purchase_date = $_POST['purchase_date'];

        $sql = "INSERT INTO lab_equipment (name, type, model_number, purchase_date) VALUES ('$name', '$type', '$model_number', '$purchase_date')";
        if ($conn->query($sql) === TRUE) {
            echo "New equipment added successfully!";
        } else {
            echo "Error: " . $conn->error;
        }
    }

    // Handle Update
    if (isset($_POST['update'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $type = $_POST['type'];
        $model_number = $_POST['model_number'];
        $purchase_date = $_POST['purchase_date'];

        $sql = "UPDATE lab_equipment SET name='$name', type='$type', model_number='$model_number', purchase_date='$purchase_date' WHERE id='$id'";
        if ($conn->query($sql) === TRUE) {
            echo "Equipment updated successfully!";
        } else {
            echo "Error: " . $conn->error;
        }
    }

    // Handle Delete
    if (isset($_POST['delete'])) {
        $id = $_POST['id'];
        $sql = "DELETE FROM lab_equipment WHERE id='$id'";
        if ($conn->query($sql) === TRUE) {
            echo "Equipment deleted successfully!";
        } else {
            echo "Error: " . $conn->error;
        }
    }

    // Display Equipment
    echo "<h2>View All Lab Equipment</h2>";
    $sql = "SELECT * FROM lab_equipment";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table><tr><th>ID</th><th>Equipment Name</th><th>Type</th><th>Model Number</th><th>Purchase Date</th><th>Actions</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . $row["id"] . "</td>
                    <td>" . $row["name"] . "</td>
                    <td>" . $row["type"] . "</td>
                    <td>" . $row["model_number"] . "</td>
                    <td>" . $row["purchase_date"] . "</td>
                    <td>
                        <form method='POST' style='display:inline'>
                            <input type='hidden' name='id' value='" . $row["id"] . "'>
                            <button type='submit' name='delete'>Delete</button>
                        </form>
                        <button onclick='openUpdateForm(" . $row["id"] . ", \"" . addslashes($row["name"]) . "\", \"" . addslashes($row["type"]) . "\", \"" . addslashes($row["model_number"]) . "\", \"" . $row["purchase_date"] . "\")'>Update</button>
                    </td>
                </tr>";
        }
        echo "</table>";
    } else {
        echo "No equipment found.";
    }

    // Close database connection
    $conn->close();
    ?>

    <!-- Update Equipment Form (Hidden by default) -->
    <div id="updateForm" style="display:none;">
        <h2>Update Equipment</h2>
        <form id="equipmentUpdate" method="POST" action="">
            <input type="hidden" name="id" id="update_id" required>
            <label for="update_name">Equipment Name:</label>
            <input type="text" name="name" id="update_name" required>
            <label for="update_type">Type:</label>
            <input type="text" name="type" id="update_type" required>
            <label for="update_model_number">Model Number:</label>
            <input type="text" name="model_number" id="update_model_number" required>
            <label for="update_purchase_date">Purchase Date:</label>
            <input type="date" name="purchase_date" id="update_purchase_date" required>
            <button type="submit" name="update">Update</button>
            <button type="button" onclick="closeUpdateForm()">Cancel</button>
        </form>
    </div>

    <script>
        function openUpdateForm(id, name, type, modelNumber, purchaseDate) {
            document.getElementById('update_id').value = id;
            document.getElementById('update_name').value = name;
            document.getElementById('update_type').value = type;
            document.getElementById('update_model_number').value = modelNumber;
            document.getElementById('update_purchase_date').value = purchaseDate;
            document.getElementById('updateForm').style.display = 'block';
        }

        function closeUpdateForm() {
            document.getElementById('updateForm').style.display = 'none';
        }
    </script>
</body>
</html>
