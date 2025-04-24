<?php
include 'db.php';

// Revenue - format with commas for thousands
$result = $conn->query("SELECT SUM(s.quantity * p.price) AS revenue FROM sales s JOIN product p ON s.product_id = p.flower_id");
$revenueData = $result->fetch_assoc();
$totalRevenue = isset($revenueData['revenue']) ? number_format($revenueData['revenue'], 0, '.', ',') : '0';

// Product Count
$result = $conn->query("SELECT COUNT(*) AS total FROM product");
$productsData = $result->fetch_assoc();
$totalProducts = isset($productsData['total']) ? number_format($productsData['total'], 0, '.', ',') : '0';

// Sales Count
$result = $conn->query("SELECT COUNT(*) AS total FROM sales");
$salesData = $result->fetch_assoc();
$totalSales = isset($salesData['total']) ? number_format($salesData['total'], 0, '.', ',') : '0';

// Top by Quantity
$topQty = [];
$qtyLabels = [];
$qtyData = [];
$result = $conn->query("SELECT p.name, SUM(s.quantity) AS total FROM sales s JOIN product p ON s.product_id = p.flower_id GROUP BY s.product_id ORDER BY total DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $topQty[] = $row;
    $qtyLabels[] = $row['name'];
    $qtyData[] = $row['total'];
}

// Top by Revenue
$topRevenue = [];
$revLabels = [];
$revData = [];
$result = $conn->query("SELECT p.name, SUM(s.quantity * p.price) AS revenue FROM sales s JOIN product p ON s.product_id = p.flower_id GROUP BY s.product_id ORDER BY revenue DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $topRevenue[] = $row;
    $revLabels[] = $row['name'];
    $revData[] = round($row['revenue'], 2);
}

// Monthly Quantity
$monthLabels = [];
$monthQuantities = [];
$monthRevenues = [];

$result = $conn->query("
    SELECT DATE_FORMAT(s.sale_date, '%b %Y') AS month_label, 
           YEAR(s.sale_date) AS year, 
           MONTH(s.sale_date) AS month_number, 
           SUM(s.quantity) AS qty,
           SUM(s.quantity * p.price) AS revenue
    FROM sales s
    JOIN product p ON s.product_id = p.flower_id
    GROUP BY year, month_number
    ORDER BY year ASC, month_number ASC
");

while ($row = $result->fetch_assoc()) {
    $monthLabels[] = $row['month_label'];
    $monthQuantities[] = $row['qty'];
    $monthRevenues[] = round($row['revenue'], 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌸 Dashboard - Ar's Flowers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
        :root {
            --primary: #8e44ad;
            --secondary: #3498db;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --dark: #2c3e50;
            --light: #f8f9fa;
            --sidebar: #34495e;
            --sidebar-hover: #2c3e50;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            color: #333;
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: var(--sidebar);
            color: white;
            padding: 1.5rem;
            position: fixed;
            height: 100vh;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            color: white;
            font-size: 1.5rem;
            margin-left: 10px;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1rem;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: var(--sidebar-hover);
            color: white;
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            width: calc(100% - 250px);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            color: var(--dark);
            font-size: 1.8rem;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
        }
        
        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        /* Cards */
        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.5rem;
        }
        
        .card-icon.revenue { background-color: rgba(142, 68, 173, 0.2); color: var(--primary); }
        .card-icon.products { background-color: rgba(46, 204, 113, 0.2); color: var(--success); }
        .card-icon.sales { background-color: rgba(52, 152, 219, 0.2); color: var(--secondary); }
        
        .card-title {
            font-size: 1rem;
            color: #777;
            margin-bottom: 0.25rem;
        }
        
        .card-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .card-footer {
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #777;
        }
        
        /* Charts */
        .chart-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.3rem;
            color: var(--dark);
        }
        
        .chart-toggle {
            display: flex;
            gap: 0.5rem;
        }
        
        .chart-toggle button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            background-color: #f0f0f0;
            color: #555;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.85rem;
        }
        
        .chart-toggle button:hover, .chart-toggle button.active {
            background-color: var(--primary);
            color: white;
        }
        
        .chart-container {
            position: relative;
            height: 350px;
            margin-top: 1rem;
        }
        
        .pie-charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .pie-chart {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        
        .pie-chart-container {
            position: relative;
            height: 400px;  /* Increased height for better visibility */
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                padding: 1rem 0.5rem;
            }
            
            .sidebar-header h2, .sidebar-menu span {
                display: none;
            }
            
            .sidebar-header {
                justify-content: center;
            }
            
            .sidebar-menu a {
                justify-content: center;
                padding: 0.75rem 0;
            }
            
            .sidebar-menu i {
                margin-right: 0;
                font-size: 1.3rem;
            }
            
            .main-content {
                margin-left: 80px;
                width: calc(100% - 80px);
            }
        }
        
        @media (max-width: 768px) {
            .pie-charts {
                grid-template-columns: 1fr;
            }
            .pie-chart-container {
                height: 350px;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-seedling" style="font-size: 1.8rem;"></i>
        <h2>Ar's Flowers</h2>
    </div>
    
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li><a href="add_product.php"><i class="fas fa-plus-circle"></i> <span>Add Product</span></a></li>
        <li><a href="inventory.php"><i class="fas fa-boxes"></i> <span>Inventory</span></a></li>
        <li><a href="record_sale.php"><i class="fas fa-cash-register"></i> <span>Record Sale</span></a></li>
        <li><a href="sales_report.php"><i class="fas fa-chart-line"></i> <span>Sales Report</span></a></li>
        <li><a href="sales_report.php"><i class="fas fa-reorder"></i> <span>Orders</span></a></li>
        <li><a href="sales_report.php"><i class="fas fa-complaint"></i> <span>Complaints</span></a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <h1><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h1>
        <div class="user-profile">
            <img src="https://ui-avatars.com/api/?name=Admin&background=8e44ad&color=fff" alt="User">
            <span>Admin</span>
        </div>
    </div>
    
    <!-- Key Metrics Cards -->
    <div class="card-container">
        <div class="card">
            <div class="card-header">
                <div class="card-icon revenue">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div>
                    <div class="card-title">Total Revenue</div>
                    <div class="card-value" id="revenue">₱0</div>
                </div>
            </div>
            <div class="card-footer">
                <i class="fas fa-arrow-up text-success"></i> <span id="revenue-change">0%</span> from last period
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <div class="card-icon products">
                    <i class="fas fa-leaf"></i>
                </div>
                <div>
                    <div class="card-title">Total Products</div>
                    <div class="card-value" id="products">0</div>
                </div>
            </div>
            <div class="card-footer">
                <i class="fas fa-arrow-up text-success"></i> <span id="products-change">0%</span> from last month
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <div class="card-icon sales">
                    <i class="fas fa-receipt"></i>
                </div>
                <div>
                    <div class="card-title">Total Sales</div>
                    <div class="card-value" id="sales">0</div>
                </div>
            </div>
            <div class="card-footer">
                <i class="fas fa-arrow-up text-success"></i> <span id="sales-change">0%</span> from last month
            </div>
        </div>
    </div>
    
    <!-- Monthly Sales Chart -->
    <div class="chart-section">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-chart-line"></i> Monthly Sales</h2>
            <div class="chart-toggle">
                <button class="active" onclick="showChart('quantity')">Quantity</button>
                <button onclick="showChart('revenue')">Revenue</button>
                <button onclick="showChart('both')">Compare</button>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="salesChart"></canvas>
        </div>
    </div>
    
    <!-- Pie Charts -->
    <div class="pie-charts">
        <div class="pie-chart">
            <h2 class="section-title"><i class="fas fa-trophy"></i> Top Products by Quantity Sold</h2>
            <div class="pie-chart-container">
                <canvas id="pieQtyChart"></canvas>
            </div>
        </div>
        
        <div class="pie-chart">
            <h2 class="section-title"><i class="fas fa-coins"></i> Top Products by Revenue</h2>
            <div class="pie-chart-container">
                <canvas id="pieRevChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    // Register the ChartDataLabels plugin
    Chart.register(ChartDataLabels);

    // Debug output to verify data
    console.log('Revenue:', '<?= $totalRevenue ?>');
    console.log('Products:', '<?= $totalProducts ?>');
    console.log('Sales:', '<?= $totalSales ?>');

    // Set values directly without animation
    document.getElementById('revenue').textContent = '₱' + '<?= $totalRevenue ?>';
    document.getElementById('products').textContent = '<?= $totalProducts ?>';
    document.getElementById('sales').textContent = '<?= $totalSales ?>';
    
    // Remove growth percentage displays
    document.getElementById('revenue-change').textContent = '';
    document.getElementById('products-change').textContent = '';
    document.getElementById('sales-change').textContent = '';

    // Chart data
    const labels = <?= json_encode($monthLabels) ?>;
    const qtyData = <?= json_encode($monthQuantities) ?>;
    const revData = <?= json_encode($monthRevenues) ?>;

    console.log('Monthly Data:', {labels, qtyData, revData});

    // Sales Chart Configuration
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    let salesChart;

    function showChart(type) {
        // Update active button
        document.querySelectorAll('.chart-toggle button').forEach(btn => {
            btn.classList.remove('active');
        });
       // event.target.classList.add('active');
        
        if (salesChart) salesChart.destroy();

        const datasets = {
            quantity: [{
                label: 'Quantity Sold',
                data: qtyData,
                borderColor: '#2ecc71',
                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }],
            revenue: [{
                label: 'Revenue Generated (₱)',
                data: revData,
                borderColor: '#8e44ad',
                backgroundColor: 'rgba(142, 68, 173, 0.1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }],
            both: [
                {
                    label: 'Quantity Sold',
                    data: qtyData,
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    yAxisID: 'y'
                },
                {
                    label: 'Revenue Generated (₱)',
                    data: revData,
                    borderColor: '#8e44ad',
                    backgroundColor: 'rgba(142, 68, 173, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ]
        };

        const options = {
            maintainAspectRatio: false,
            responsive: true,
            interaction: {
                mode: 'nearest',
                intersect: false
            },
            plugins: {
                tooltip: {
                    enabled: true,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label.includes('Revenue')) {
                                return label + ': ₱' + context.parsed.y.toLocaleString();
                            }
                            return label + ': ' + context.parsed.y.toLocaleString();
                        }
                    }
                },
                legend: {
                    display: true,
                    position: 'top',
                }
            },
            elements: {
                point: {
                    radius: 4,
                    hoverRadius: 6
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { 
                        precision: 0,
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        };

        if (type === 'both') {
            options.scales = {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Quantity'
                    },
                    ticks: { 
                        precision: 0,
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Revenue (₱)'
                    },
                    grid: { drawOnChartArea: false },
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            };
        } else if (type === 'revenue') {
            options.scales.y.ticks = {
                callback: function(value) {
                    return '₱' + value.toLocaleString();
                }
            };
        }

        salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets[type]
            },
            options: options
        });
    }

    // Initialize with quantity view
    showChart('quantity');

    // Pie Chart Quantity
    const pieQtyCtx = document.getElementById('pieQtyChart');
    
    if (pieQtyCtx) {
        new Chart(pieQtyCtx.getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($qtyLabels) ?>,
                datasets: [{
                    data: <?= json_encode($qtyData) ?>,
                    backgroundColor: [
                        'rgba(231, 76, 60, 0.8)',
                        'rgba(241, 196, 15, 0.8)',
                        'rgba(46, 204, 113, 0.8)',
                        'rgba(52, 152, 219, 0.8)',
                        'rgba(155, 89, 182, 0.8)'
                    ],
                    borderColor: 'rgba(255,255,255,0.8)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw.toLocaleString()} units`;
                            }
                        }
                    },
                    datalabels: {
                        display: false
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    } else {
        console.error('pieQtyChart element not found');
    }

    // Pie Chart Revenue
    const pieRevCtx = document.getElementById('pieRevChart');
    if (pieRevCtx) {
        new Chart(pieRevCtx.getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($revLabels) ?>,
                datasets: [{
                    data: <?= json_encode($revData) ?>,
                    backgroundColor: [
                        'rgba(142, 68, 173, 0.8)',
                        'rgba(41, 128, 185, 0.8)',
                        'rgba(39, 174, 96, 0.8)',
                        'rgba(243, 156, 18, 0.8)',
                        'rgba(211, 84, 0, 0.8)'
                    ],
                    borderColor: 'rgba(255,255,255,0.8)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ₱${context.raw.toLocaleString()}`;
                            }
                        }
                    },
                    datalabels: {
                        formatter: (value, ctx) => {
                            let sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            return (value / sum * 100).toFixed(1) + '%';
                        },
                        color: '#fff',
                        font: {
                            weight: 'bold',
                            size: 12
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    } else {
        console.error('pieRevChart element not found');
    }
</script>
</body>
</html>