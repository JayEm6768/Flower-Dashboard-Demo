<?php
include 'db.php';

$success = '';
$error = '';

// Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $sale_date = $_POST['sale_date'];

    if ($quantity <= 0) {
        $error = "❌ Quantity must be greater than 0.";
    } else {
        // Fetch current stock
        $result = $conn->query("SELECT quantity, name FROM product WHERE flower_id = $product_id");
        if ($result->num_rows === 0) {
            $error = "❌ Product ID does not exist.";
        } else {
            $product = $result->fetch_assoc();
            $current_stock = $product['quantity'];
            $product_name = htmlspecialchars($product['name']);

            if ($current_stock < $quantity) {
                $error = "❌ Not enough stock. Available: $current_stock.";
            } else {
                // Insert sale and update stock
                $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity, sale_date) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $product_id, $quantity, $sale_date);
                $stmt->execute();

                $conn->query("UPDATE product SET quantity = quantity - $quantity WHERE flower_id = $product_id");

                $remaining = $current_stock - $quantity;
                $success = "✅ Sale recorded for <strong>$product_name</strong>. Remaining stock: <strong>$remaining</strong>";
            }
        }
    }
}

// Fetch product list
$products = $conn->query("SELECT flower_id, name, quantity FROM product");

// Fetch sales history
$sales_history = $conn->query("
    SELECT s.id, p.name AS product_name, s.quantity, s.sale_date 
    FROM sales s 
    JOIN product p ON s.product_id = p.flower_id 
    ORDER BY s.sale_date DESC, s.id DESC 
    LIMIT 20
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Sale</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 40px;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        .message {
            text-align: center;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .success {
            color: #28a745;
        }

        .error {
            color: #dc3545;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 16px;
            margin-bottom: 5px;
            color: #333;
        }

        select, input[type="number"], input[type="date"] {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        input[type="submit"] {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
        }

        table th, table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }

        table th {
            background-color: #007bff;
            color: white;
        }

        .section-title {
            margin-top: 60px;
            margin-bottom: 10px;
            font-size: 20px;
            color: #333;
            text-align: center;
        }

        /* Add the Go Back button styling */
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
    <script>
        function confirmSale() {
            const productSelect = document.getElementById('product_id');
            const selectedProduct = productSelect.options[productSelect.selectedIndex].text;
            const quantity = document.getElementById('quantity').value;
            return confirm(`Are you sure you want to sell ${quantity} unit(s) of "${selectedProduct}"?`);
        }
    </script>
</head>
<body>

    <div class="container">
        <h2>💸 Record a Sale</h2>

        <?php if ($success): ?>
            <p class="message success"><?= $success ?></p>
        <?php elseif ($error): ?>
            <p class="message error"><?= $error ?></p>
        <?php endif; ?>

        <form method="post" onsubmit="return confirmSale()">
            <label for="product_id">Select Flower:</label>
            <select name="product_id" id="product_id" required>
                <option disabled selected>-- Select a flower --</option>
                <?php while($row = $products->fetch_assoc()): ?>
                    <option value="<?= $row['flower_id'] ?>">
                        <?= htmlspecialchars($row['name']) ?> (Stock: <?= $row['quantity'] ?>)
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="quantity">Quantity Sold:</label>
            <input type="number" name="quantity" id="quantity" min="1" required>

            <label for="sale_date">Sale Date:</label>
            <input type="date" name="sale_date" id="sale_date" required>

            <input type="submit" value="Record Sale">
        </form>

        <!-- Go Back Button -->
        <a href="dashboard.php" class="btn-back-dashboard">🔙 Go Back to Dashboard</a>

        <div class="section-title">📄 Recent Sales History</div>
        <table>
            <thead>
                <tr>
                    <th>Sale ID</th>
                    <th>Flower Name</th>
                    <th>Quantity</th>
                    <th>Sale Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while($sale = $sales_history->fetch_assoc()): ?>
                    <tr>
                        <td><?= $sale['id'] ?></td>
                        <td><?= htmlspecialchars($sale['product_name']) ?></td>
                        <td><?= $sale['quantity'] ?></td>
                        <td><?= $sale['sale_date'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
