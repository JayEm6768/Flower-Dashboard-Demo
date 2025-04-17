<?php
include 'db.php';

// --- CONFIG ---
$rows_per_page = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $rows_per_page;

// --- DELETE SALE ---
if (isset($_GET['delete'])) {
    $sale_id = intval($_GET['delete']);
    $restore = $conn->query("SELECT product_id, quantity FROM sales WHERE id = $sale_id");
    if ($restore->num_rows > 0) {
        $row = $restore->fetch_assoc();
        $conn->query("UPDATE products SET stock = stock + {$row['quantity']} WHERE id = {$row['product_id']}");
        $conn->query("DELETE FROM sales WHERE id = $sale_id");
        echo "<p class='success'>✅ Sale deleted and stock restored.</p>";
    }
}

// --- UPDATE SALE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_sale'])) {
    $sale_id = intval($_POST['sale_id']);
    $new_quantity = intval($_POST['quantity']);
    $new_date = $_POST['sale_date'];

    $oldSale = $conn->query("SELECT product_id, quantity FROM sales WHERE id = $sale_id")->fetch_assoc();
    $product_id = $oldSale['product_id'];
    $old_quantity = $oldSale['quantity'];

    $stock_result = $conn->query("SELECT stock FROM products WHERE id = $product_id");
    $stock = $stock_result->fetch_assoc()['stock'];
    $diff = $new_quantity - $old_quantity;

    if ($stock < $diff) {
        echo "<p class='error'>❌ Not enough stock for this update.</p>";
    } else {
        $stmt = $conn->prepare("UPDATE sales SET quantity = ?, sale_date = ? WHERE id = ?");
        $stmt->bind_param("isi", $new_quantity, $new_date, $sale_id);
        $stmt->execute();
        $conn->query("UPDATE products SET stock = stock - $diff WHERE id = $product_id");
        echo "<p class='success'>✅ Sale updated successfully!</p>";
    }
}

// --- FETCH PAGINATED SALES ---
$sales = $conn->query("SELECT s.*, p.name FROM sales s 
                       JOIN products p ON s.product_id = p.id 
                       ORDER BY s.sale_date DESC 
                       LIMIT $offset, $rows_per_page");

// --- GET TOTAL FOR PAGINATION ---
$total_sales = $conn->query("SELECT COUNT(*) as total FROM sales")->fetch_assoc()['total'];
$total_pages = ceil($total_sales / $rows_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sales - Flower Shop</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 1000px;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }

        table th {
            background-color: #f8f9fa;
            color: #333;
        }

        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        table input[type="number"],
        table input[type="date"] {
            padding: 8px;
            width: 80px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        table input[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        table input[type="submit"]:hover {
            background-color: #0056b3;
        }

        a {
            text-decoration: none;
            color: #dc3545;
        }

        .pagination {
            text-align: center;
            margin-top: 20px;
        }

        .pagination a {
            margin: 0 5px;
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
        }

        .pagination a:hover {
            background-color: #0056b3;
        }

        .pagination a.active {
            font-weight: bold;
            background-color: #28a745;
        }

        .success, .error {
            text-align: center;
            font-size: 18px;
            margin-top: 20px;
        }

        .success {
            color: #28a745;
        }

        .error {
            color: #dc3545;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>🧾 Manage Sales Records</h2>

        <!-- Sales Table -->
        <table>
            <tr>
                <th>ID</th>
                <th>Flower</th>
                <th>Quantity</th>
                <th>Sale Date</th>
                <th>Actions</th>
            </tr>

            <?php while ($row = $sales->fetch_assoc()): ?>
            <tr>
                <form method="post">
                    <input type="hidden" name="sale_id" value="<?= $row['id'] ?>">
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><input type="number" name="quantity" value="<?= $row['quantity'] ?>" required></td>
                    <td><input type="date" name="sale_date" value="<?= $row['sale_date'] ?>" required></td>
                    <td>
                        <input type="submit" name="update_sale" value="Update">
                        <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this sale?')">Delete</a>
                    </td>
                </form>
            </tr>
            <?php endwhile; ?>
        </table>

        <!-- Pagination Links -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

</body>
</html>
