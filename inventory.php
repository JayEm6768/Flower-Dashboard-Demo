<?php
include 'db.php';

// Initialize variables to avoid undefined variable warnings
$success = '';
$error = '';

// Handle column sorting
$sort_column = 'name'; // Default column to sort by
$sort_order = 'ASC'; // Default sort order (ascending)

// Handle sorting
if (isset($_GET['sort_by']) && in_array($_GET['sort_by'], ['flower_id', 'name', 'price', 'quantity', 'size', 'color', 'available'])) {
    $sort_column = $_GET['sort_by'];
}

if (isset($_GET['sort_order']) && in_array($_GET['sort_order'], ['ASC', 'DESC'])) {
    $sort_order = $_GET['sort_order'];
}

// Handle delete request
if (isset($_GET['delete'])) {
    $flower_id = intval($_GET['delete']);
    
    $check = $conn->query("SELECT * FROM product WHERE flower_id = $flower_id");
    if ($check->num_rows > 0) {
        $conn->query("DELETE FROM product WHERE flower_id = $flower_id");
        $success = "✅ Flower deleted successfully.";
    } else {
        $error = "❌ Flower not found.";
    }
}

// Fetch sorted data
$query = "SELECT * FROM product ORDER BY $sort_column $sort_order";
$result = $conn->query($query);

// Toggle sort order for each column
$toggle_order = ($sort_order === 'ASC') ? 'DESC' : 'ASC';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flower Inventory Dashboard</title>
    <style>
        :root {
            --primary: #4CAF50;
            --primary-hover: #45a049;
            --secondary: #007bff;
            --secondary-hover: #0056b3;
            --error-color: #dc3545;
            --success-color: #28a745;
            --background: #f4f6f9;
            --card-bg: #ffffff;
            --text-color: #333;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--background);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            background: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: var(--primary);
            margin-bottom: 30px;
            font-size: 26px;
        }

        .actions {
            text-align: right;
            margin-bottom: 20px;
        }

        .actions a {
            background-color: var(--primary);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            font-size: 16px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }

        .actions a:hover {
            background-color: var(--primary-hover);
        }

        .message {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .success { color: var(--success-color); }
        .error { color: var(--error-color); }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background-color: var(--primary);
            color: white;
            cursor: pointer;
        }

        td {
            background-color: #fafafa;
        }

        .status-yes {
            color: var(--success-color);
            font-weight: bold;
        }

        .status-no {
            color: var(--error-color);
            font-weight: bold;
        }

        .delete-btn {
            color: var(--error-color);
            text-decoration: none;
            font-weight: bold;
        }

        .delete-btn:hover {
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            font-size: 14px;
            color: #777;
            margin-top: 30px;
        }

        /* Add the button styling here */
        .btn-back-dashboard {
            display: inline-block;
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            font-size: 10px;
            text-decoration: none;
            border-radius: 3px;
            margin-top: 10px;
            text-align: center;
        }

        .btn-back-dashboard:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>🌼 Flower Inventory Dashboard</h2>

    <div class="actions">
        <a href="add_product.php">➕ Add New Flower</a>
    </div>

    <?php if ($success): ?>
        <p class="message success"><?= $success ?></p>
    <?php elseif ($error): ?>
        <p class="message error"><?= $error ?></p>
    <?php endif; ?>

    <!-- Go Back Button -->
    <a href="dashboard.php" class="btn-back-dashboard">🔙 Go Back to Dashboard</a>

    <table>
        <thead>
            <tr>
                <th><a href="?sort_by=flower_id&sort_order=<?= $toggle_order ?>">ID</a></th>
                <th><a href="?sort_by=name&sort_order=<?= $toggle_order ?>">Flower Name</a></th>
                <th><a href="?sort_by=price&sort_order=<?= $toggle_order ?>">Price (₱)</a></th>
                <th><a href="?sort_by=quantity&sort_order=<?= $toggle_order ?>">Stock</a></th>
                <th><a href="?sort_by=size&sort_order=<?= $toggle_order ?>">Size</a></th>
                <th><a href="?sort_by=color&sort_order=<?= $toggle_order ?>">Color</a></th>
                <th><a href="?sort_by=available&sort_order=<?= $toggle_order ?>">Available</a></th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['flower_id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= number_format($row['price'], 2) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= htmlspecialchars($row['size']) ?></td>
                <td><?= htmlspecialchars($row['color']) ?></td>
                <td class="<?= $row['available'] ? 'status-yes' : 'status-no' ?>">
                    <?= $row['available'] ? 'Yes' : 'No' ?>
                </td>
                <td>
                    <a class="delete-btn" href="?delete=<?= $row['flower_id'] ?>" onclick="return confirm('Are you sure you want to delete this flower?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="footer">
        © <?= date("Y") ?> Flower Shop Inventory System
    </div>
</div>

</body>
</html>
