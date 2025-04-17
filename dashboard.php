<?php
include 'db.php'; // Database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌼 Flower Shop Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin: 30px 0;
            font-size: 36px;
        }

        .section {
            background: white;
            border: 1px solid #ccc;
            margin-bottom: 30px;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 1200px; /* Ensures sections don't get too wide */
            margin-left: auto;
            margin-right: auto;
            overflow: hidden;
        }

        .section h2 {
            color: #2c3e50;
            font-size: 24px;
            margin-top: 0;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: center;
        }

        .button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 16px;
        }

        .button:hover {
            background-color: #45a049;
        }

        /* Adjustments for smaller screens */
        @media (max-width: 1024px) {
            h1 {
                font-size: 28px;
            }

            .section {
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 24px;
            }

            .section {
                padding: 15px;
            }

            .button {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

    <h1>🌼 Flower Shop Dashboard</h1>

    <!-- Inventory Overview Section -->
    <div class="section">
        <h2>Inventory Overview</h2>
        <?php include 'inventory.php'; ?>
    </div>

    <!-- Sales Report Section -->
    <div class="section">
        <h2>Sales Report</h2>
        <?php include 'sales_report.php'; ?>
    </div>

    <!-- Manage Sales Section -->
    <div class="section">
        <h2>Manage Sales</h2>
        <?php include 'manage_sales.php'; ?>
    </div>

</body>
</html>
