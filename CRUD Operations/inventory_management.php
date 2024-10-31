<?php
include 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Assignments</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        table, th, td { border: 1px solid black; padding: 8px; text-align: left; }
        form { display: inline; }
    </style>
</head>
<body>
    <h1>Inventory Management - Assignments</h1>

    <!-- Create Assignment Form -->
    <h2>Create Assignment</h2>
    <form method="POST" action="">
        <label for="student_name">Student Name:</label>
        <input type="text" name="student_name" required>
        <label for="equipment_name">Equipment Name:</label>
        <input type="text" name="equipment_name" required>
        <label for="assignment_date">Assignment Date:</label>
        <input type="date" name="assignment_date" required>
        <button type="submit" name="create">Create</button>
    </form>

    <?php
    // Handle Create
    if (isset($_POST['create'])) {
        $student_name = $_POST['student_name'];
        $equipment_name = $_POST['equipment_name'];
        $assignment_date = $_POST['assignment_date'];

        // Get student_id
        $sql = "SELECT id FROM students WHERE name='$student_name'";
        $result = $conn->query($sql);
        $student_id = ($result->num_rows > 0) ? $result->fetch_assoc()['id'] : null;

        // Get equipment_id
        $sql = "SELECT id FROM lab_equipment WHERE name='$equipment_name'";
        $result = $conn->query($sql);
        $equipment_id = ($result->num_rows > 0) ? $result->fetch_assoc()['id'] : null;

        if ($student_id && $equipment_id) {
            $sql = "INSERT INTO inventory_assignments (student_id, equipment_id, assignment_date) VALUES ('$student_id', '$equipment_id', '$assignment_date')";
            if ($conn->query($sql) === TRUE) {
                echo "New assignment created successfully!";
            } else {
                echo "Error: " . $conn->error;
            }
        } else {
            echo "Error: Student or equipment not found.";
        }
    }

    // Handle Delete
    if (isset($_POST['delete'])) {
        $id = $_POST['id'];
        $sql = "DELETE FROM inventory_assignments WHERE id='$id'";
        if ($conn->query($sql) === TRUE) {
            echo "Assignment deleted successfully!";
        } else {
            echo "Error: " . $conn->error;
        }
    }

    // Handle Update
    if (isset($_POST['update'])) {
        $id = $_POST['id'];
        $student_name = $_POST['student_name'];
        $equipment_name = $_POST['equipment_name'];
        $assignment_date = $_POST['assignment_date'];

        // Get student_id
        $sql = "SELECT id FROM students WHERE name='$student_name'";
        $result = $conn->query($sql);
        $student_id = ($result->num_rows > 0) ? $result->fetch_assoc()['id'] : null;

        // Get equipment_id
        $sql = "SELECT id FROM lab_equipment WHERE name='$equipment_name'";
        $result = $conn->query($sql);
        $equipment_id = ($result->num_rows > 0) ? $result->fetch_assoc()['id'] : null;

        if ($student_id && $equipment_id) {
            $sql = "UPDATE inventory_assignments SET student_id='$student_id', equipment_id='$equipment_id', assignment_date='$assignment_date' WHERE id='$id'";
            if ($conn->query($sql) === TRUE) {
                echo "Assignment updated successfully!";
            } else {
                echo "Error: " . $conn->error;
            }
        } else {
            echo "Error: Student or equipment not found.";
        }
    }

    // Display Assignments
    echo "<h2>View All Assignments</h2>";
    $sql = "SELECT a.id, s.name AS student_name, e.name AS equipment_name, a.assignment_date
            FROM inventory_assignments a
            JOIN students s ON a.student_id = s.id
            JOIN lab_equipment e ON a.equipment_id = e.id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table><tr><th>ID</th><th>Student</th><th>Equipment</th><th>Assignment Date</th><th>Actions</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . $row["id"] . "</td>
                    <td>" . $row["student_name"] . "</td>
                    <td>" . $row["equipment_name"] . "</td>
                    <td>" . $row["assignment_date"] . "</td>
                    <td>
                        <form method='POST' style='display:inline'>
                            <input type='hidden' name='id' value='" . $row["id"] . "'>
                            <button type='submit' name='delete'>Delete</button>
                        </form>
                        <button onclick='openUpdateForm(" . $row["id"] . ", \"" . addslashes($row["student_name"]) . "\", \"" . addslashes($row["equipment_name"]) . "\", \"" . $row["assignment_date"] . "\")'>Update</button>
                    </td>
                </tr>";
        }
        echo "</table>";
    } else {
        echo "No assignments found.";
    }

    // Close database connection
    $conn->close();
    ?>

    <!-- Update Assignment Form -->
    <div id="updateForm" style="display:none;">
        <h2>Update Assignment</h2>
        <form method="POST" action="" id="assignmentUpdate">
            <input type="hidden" name="id" id="update_id">
            <label for="update_student_name">Student Name:</label>
            <input type="text" name="student_name" id="update_student_name" required>
            <label for="update_equipment_name">Equipment Name:</label>
            <input type="text" name="equipment_name" id="update_equipment_name" required>
            <label for="update_assignment_date">Assignment Date:</label>
            <input type="date" name="assignment_date" id="update_assignment_date" required>
            <button type="submit" name="update">Update</button>
            <button type="button" onclick="closeUpdateForm()">Cancel</button>
        </form>
    </div>

    <script>
        function openUpdateForm(id, student_name, equipment_name, assignment_date) {
            document.getElementById('update_id').value = id;
            document.getElementById('update_student_name').value = student_name;
            document.getElementById('update_equipment_name').value = equipment_name;
            document.getElementById('update_assignment_date').value = assignment_date;
            document.getElementById('updateForm').style.display = 'block'; // Show the form
        }

        function closeUpdateForm() {
            document.getElementById('updateForm').style.display = 'none';
        }
    </script>
</body>
</html>
