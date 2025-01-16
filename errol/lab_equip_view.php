<?php
session_start();

// Ensure session security and timeout
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = bin2hex(random_bytes(16));
}

if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
} elseif (time() - $_SESSION['last_activity'] > 1800) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
$_SESSION['last_activity'] = time();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $con = mysqli_connect("localhost", "root", "", "lab equipment");

    if (!$con) {
        die(json_encode(['success' => false, 'error' => mysqli_connect_error()])); 
    }

    $query = $con->prepare("INSERT INTO `lab equipment` (NAME, TYPE, MODEL_NUMBER, PURCHASE_DATE, IMAGE) VALUES (?, ?, ?, ?, ?)");
    $name = $_POST['name'];
    $type = $_POST['type'];
    $model_number = $_POST['model_number'];
    $purchase_date = $_POST['purchase_date'];
    $stock_multiplier = $_POST['stock_multiplier'];

    if (!empty($_FILES['image']['name'])) {
        $target_dir = __DIR__ . "/images/";
        $image_name = basename($_FILES['image']['name']);
        $target_file = $target_dir . $image_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $relative_image_path = "images/" . $image_name;

            $insertedCount = 0;
            for ($i = 0; $i < $stock_multiplier; $i++) {
                $query->bind_param('sssss', $name, $type, $model_number, $purchase_date, $relative_image_path);
                if ($query->execute()) {
                    $insertedCount++;
                } else {
                    echo json_encode(['success' => false, 'error' => $query->error]);
                    break;
                }
            }

            // Store success data in the session
            $_SESSION['insert_success'] = true;
            $_SESSION['inserted_count'] = $insertedCount;
            $_SESSION['product_name'] = $name;

            // Redirect to the same page to show the success message
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to upload image.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No image uploaded.']);
    }

    $query->close();
    $con->close();
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Dashboard</title>
    <link rel="stylesheet" href="lab_equip_view.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="brandbox">
                <img class="brand" src="images/brandlogo.png" alt="Brand Logo">
            </div>
            <br>
            <div class="ulink">
                <ul>
                    <li><a href="lab_home.php" id="homeButton">Home</a></li>
                    <li><a href="#" id="viewButton" onclick="showSection('view')">View</a></li>
                    <li><a href="#" id="createButton" onclick="showSection('create')">Create</a></li>
                </ul>
            </div>
            <hr>
            <div class="filter_dropdown" id="filterDropdown">
                <label for="typeFilter">Type:</label>
                <select id="typeFilter" name="typeFilter">
                    <option value="all">All</option>
                    <?php
                    // Database connection
                    $con = mysqli_connect("localhost", "root", "", "lab equipment");
                    if (!$con) {
                        die('Could not connect: ' . mysqli_connect_error());
                    }

                    // Fetch unique equipment types
                    $typeQuery = "SELECT DISTINCT TYPE FROM `lab equipment` ORDER BY TYPE";
                    $typeResult = mysqli_query($con, $typeQuery);

                    if ($typeResult && mysqli_num_rows($typeResult) > 0) {
                        while ($row = mysqli_fetch_assoc($typeResult)) {
                            echo '<option value="' . htmlspecialchars($row['TYPE']) . '">' . htmlspecialchars($row['TYPE']) . '</option>';
                        }
                    }

                    mysqli_close($con);
                    ?>
                </select><br><br>

                <label for="minStock">Min Stock:</label>
                <input type="number" id="minStock" name="minStock" placeholder="Min Stock"><br>

                <label for="maxStock">Max Stock:</label>
                <input type="number" id="maxStock" name="maxStock" placeholder="Max Stock"><br>

                <label for="searchFilter">Search:</label>
                <input type="text" id="searchFilter" placeholder="Search by name"><br><br>

                <button id="filterButton" onclick="applyFilters()">Filter</button>
                <button id="resetButton" onclick="resetFilters()">Reset</button>
            </div>

        </div>

        <!-- Main Content -->
        <div class="main_content">
            <div class="header">
                <h3>Lab Equipment List</h3>

                <!-- View Products Section -->
                <div id="viewSection">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Model Number</th>
                                <th>Recent Purchase Date</th>
                                <th>Image</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="equipTable">
                            <!-- Data will be populated here by JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Create Product Section -->
                <div id="createSection" style="display: none;">
                    <h3>Add New Product</h3>
                    <div class="fcontainer">
                        <form action="" method="post" enctype="multipart/form-data">
                            <label for="name">Equipment Name:</label><br>
                            <input type="text" id="name" name="name" required><br><br>

                            <label for="type">Equipment Type:</label><br>
                            <input type="text" id="type" name="type" required><br><br>

                            <label for="model_number">Model Number:</label><br>
                            <input type="text" id="model_number" name="model_number" required><br><br>

                            <label for="purchase_date">Purchase Date:</label><br>
                            <input type="date" id="purchase_date" name="purchase_date" required><br><br>

                            <label for="image">Image File:</label><br>
                            <input type="file" id="image" name="image" accept="image/*" required><br><br>

                            <label for="stock_multiplier">Stock Multiplier:</label><br>
                            <input type="number" id="stock_multiplier" name="stock_multiplier" value="1" min="1" required><br><br>

                            <input type="submit" value="Submit">
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <script>
        <?php if (isset($_SESSION['insert_success']) && $_SESSION['insert_success'] === true): ?>
            <?php unset($_SESSION['insert_success']); ?>
        <?php endif; ?>

        const equipData = <?php
            
            $con = mysqli_connect("localhost", "root", "", "lab equipment");

            if (!$con) {
                die('Could not connect: ' . mysqli_connect_error());
            }

            $query = "SELECT 
                        ID,
                        NAME, 
                        TYPE, 
                        MODEL_NUMBER, 
                        MAX(PURCHASE_DATE) AS RECENT_PURCHASE_DATE, 
                        IMAGE, 
                        COUNT(*) AS STOCK
                      FROM `lab equipment`
                      GROUP BY NAME, TYPE, MODEL_NUMBER, IMAGE";
            $result = mysqli_query($con, $query);
            $equipData = [];
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $equipData[] = $row;
                }
            }

            echo json_encode($equipData);
            mysqli_close($con);
        ?>;

        function showSection(section) {
            const sections = ['view', 'create'];
            sections.forEach(id => {
                document.getElementById(id + 'Section').style.display = 'none';
            });

            document.getElementById(section + 'Section').style.display = 'block';
            document.getElementById('viewButton').classList.toggle('active', section === 'view');
            document.getElementById('createButton').classList.toggle('active', section === 'create');
        }

        showSection('view');

        function applyFilters() {
            const typeFilter = document.getElementById('typeFilter').value;
            const minStock = document.getElementById('minStock').value;
            const maxStock = document.getElementById('maxStock').value;
            const searchFilter = document.getElementById('searchFilter').value.toLowerCase();

            const filteredData = equipData.filter(item => {
                const meetsMinStock = !minStock || item.STOCK >= parseInt(minStock, 10);
                const meetsMaxStock = !maxStock || item.STOCK <= parseInt(maxStock, 10);

                return (typeFilter === 'all' || item.TYPE.toLowerCase() === typeFilter.toLowerCase())
                    && meetsMinStock
                    && meetsMaxStock
                    && item.NAME.toLowerCase().includes(searchFilter);
            });

            displayEquipment(filteredData);
        }


        function resetFilters() {
            document.getElementById('typeFilter').value = 'all';
            document.getElementById('minStock').value = '';
            document.getElementById('maxStock').value = '';
            document.getElementById('searchFilter').value = '';
            displayEquipment(equipData);
        }

        function displayEquipment(data) {
            const tableBody = document.getElementById('equipTable');
            tableBody.innerHTML = '';

            data.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.NAME}</td>
                    <td>${item.TYPE}</td>
                    <td>${item.MODEL_NUMBER}</td>
                    <td>${item.RECENT_PURCHASE_DATE}</td>
                    <td><img src="${item.IMAGE}" alt="${item.NAME}" width="50"></td>
                    <td>${item.STOCK}</td>
                    <td><a href="lab_equip_edit.php?edit_id=${item.ID}"><button class="edit">Edit</button></a>
                    <a href="lab_equip_delete.php?delete_id=${item.ID}"><button class="delete">Delete</button></a></td>
                `;
                tableBody.appendChild(row);
            });
        }

        displayEquipment(equipData);
        window.onload = function() {
            // Check if the success parameter is present in the session
            const insertedCount = <?php echo isset($_SESSION['inserted_count']) ? $_SESSION['inserted_count'] : 0; ?>;
            const productName = <?php echo json_encode(isset($_SESSION['product_name']) ? $_SESSION['product_name'] : ''); ?>;

            if (insertedCount > 0 && productName) {
                // Show the custom success alert with dynamic message
                const alertBox = document.getElementById('successAlert');
                alertBox.textContent = `Successfully Inserted ${insertedCount} ${productName}`;
                alertBox.classList.add('show');

                // Automatically hide the alert after 3 seconds
                setTimeout(function() {
                    alertBox.classList.remove('show');
                }, 3000);

                // Clear session data after displaying the message
                <?php unset($_SESSION['insert_success'], $_SESSION['inserted_count'], $_SESSION['product_name']); ?>
            }
        };


    </script>
    
    <div id="successAlert">Successfully Inserted</div>

</body>
</html>
