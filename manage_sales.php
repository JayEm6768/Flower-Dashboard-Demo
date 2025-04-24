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
        $conn->query("UPDATE product SET stock = stock + {$row['quantity']} WHERE flower_id = {$row['product_id']}");
        $conn->query("DELETE FROM sales WHERE id = $sale_id");
        echo "<p class='success'>✅ Sale deleted and stock restored.</p>";
    }
}

// --- UPDATE SALE ---
$update_success = false; // Flag to check if the sale was updated successfully
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_sale'])) {
    $sale_id = intval($_POST['sale_id']);
    $new_quantity = intval($_POST['quantity']);
    $new_date = $_POST['sale_date'];

    $oldSale = $conn->query("SELECT product_id, quantity FROM sales WHERE id = $sale_id")->fetch_assoc();
    $product_id = $oldSale['product_id'];
    $old_quantity = $oldSale['quantity'];

    $stock_result = $conn->query("SELECT quantity FROM product WHERE flower_id = $product_id");
    $stock = $stock_result->fetch_assoc()['quantity'];
    $diff = $new_quantity - $old_quantity;

    if ($stock < $diff) {
        echo "<p class='error'>❌ Not enough stock for this update.</p>";
    } else {
        $stmt = $conn->prepare("UPDATE sales SET quantity = ?, sale_date = ? WHERE id = ?");
        $stmt->bind_param("isi", $new_quantity, $new_date, $sale_id);
        $stmt->execute();
        $conn->query("UPDATE product SET quantity = quantity - $diff WHERE flower_id = $product_id");
        $update_success = true; // Set the flag to true if update is successful
    }
}

// --- FETCH PAGINATED SALES ---
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'sale_date';  // Default sort by sale_date
$order_dir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'DESC';  // Default descending order

$sales = $conn->query("SELECT s.*, p.name FROM sales s 
                       JOIN product p ON s.product_id = p.flower_id 
                       ORDER BY $order_by $order_dir
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
            cursor: pointer;
        }

        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        table input[type="number"],
        table input[type="date"] {
            padding: 8px;
            width: 100px;
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

        /* Success/Failure Pop-up Notification */
        .popup {
            display: none;
            background-color: #28a745;
            color: white;
            text-align: center;
            padding: 15px;
            border-radius: 4px;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            animation: popupAnimation 0.5s ease-in-out;
        }

        .error-popup {
            background-color: #dc3545;
        }

        @keyframes popupAnimation {
            0% {
                opacity: 0;
                top: 0;
            }
            100% {
                opacity: 1;
                top: 20px;
            }
        }

        /* Success/Failure Popup Fade-out */
        .popup.hide {
            animation: fadeOut 2s forwards;
        }

        @keyframes fadeOut {
            0% {
                opacity: 1;
            }
            100% {
                opacity: 0;
                display: none;
            }
        }

        /* Go Back Button */
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
        <h2>🧾 Manage Sales Records</h2>

        <!-- Go Back to Dashboard Button -->
        <a href="dashboard.php" class="btn-back-dashboard">Go Back to Dashboard</a>

        <!-- Sales Table -->
        <table>
            <tr>
                <th><a href="?order_by=id&order_dir=<?= $order_dir == 'ASC' ? 'DESC' : 'ASC' ?>">ID</a></th>
                <th><a href="?order_by=name&order_dir=<?= $order_dir == 'ASC' ? 'DESC' : 'ASC' ?>">Flower</a></th>
                <th><a href="?order_by=quantity&order_dir=<?= $order_dir == 'ASC' ? 'DESC' : 'ASC' ?>">Quantity</a></th>
                <th><a href="?order_by=sale_date&order_dir=<?= $order_dir == 'ASC' ? 'DESC' : 'ASC' ?>">Sale Date</a></th>
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

    <!-- Success/Failure Pop-up Notification -->
    <div id="popup" class="popup <?= $update_success ? '' : 'hide' ?>">
        ✅ Sale updated successfully!
    </div>
    

    <script>
        // Automatically hide the success popup after 3 seconds
        window.onload = function() {
            if (document.getElementById('popup') && document.getElementById('popup').classList.contains('popup')) {
                setTimeout(function() {
                    document.getElementById('popup').classList.add('hide');
                }, 3000); // Popup will fade out after 3 seconds
            }
        }
    </script>

</body>
</html>
