<?php
include 'db.php'; // Database connection

// Query to fetch products from the database
$result = $conn->query("SELECT * FROM products ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flower Inventory</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 20px;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        a {
            background-color: #4CAF50;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 0;
            display: inline-block;
        }

        a:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<h2>🌼 Inventory List</h2>
<a href="add_product.php">➕ Add New Flower</a> <!-- Link to add new product page -->

<!-- Inventory Table -->
<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Flower Name</th>
        <th>Category</th>
        <th>Price (₱)</th>
        <th>Stock</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= !empty($row['category']) ? htmlspecialchars($row['category']) : 'N/A' ?></td> <!-- Handling empty category -->
        <td><?= number_format($row['price'], 2) ?></td>
        <td><?= $row['stock'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
