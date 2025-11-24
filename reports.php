<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get report data
$revenue_report = "SELECT 
    COUNT(*) as total_reservations,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_revenue,
    MIN(total_amount) as min_revenue,
    MAX(total_amount) as max_revenue
    FROM reservations 
    WHERE status = 'checked-out'";
$revenue_stmt = $db->prepare($revenue_report);
$revenue_stmt->execute();
$revenue_data = $revenue_stmt->fetch(PDO::FETCH_ASSOC);

// Room occupancy
$occupancy_report = "SELECT 
    room_type,
    COUNT(*) as total_rooms,
    SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_rooms,
    ROUND((SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as occupancy_rate
    FROM rooms 
    GROUP BY room_type";
$occupancy_stmt = $db->prepare($occupancy_report);
$occupancy_stmt->execute();
$occupancy_data = $occupancy_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Hotel Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a href="dashboard.php" class="navbar-brand">OHMS</a>
                <ul class="navbar-nav">
                    <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                    <li><a href="reservations.php" class="nav-link">Reservations</a></li>
                    <li><a href="rooms.php" class="nav-link">Rooms</a></li>
                    <li><a href="customers.php" class="nav-link">Customers</a></li>
                    <li><a href="billing.php" class="nav-link">Billing</a></li>
                    <li><a href="reports.php" class="nav-link active">Reports</a></li>
                    <li><a href="logout.php" class="nav-link">Logout (<?php echo $_SESSION['full_name']; ?>)</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Reports & Analytics</h1>
            <p>Comprehensive hotel performance reports - All amounts in Kenyan Shillings (KES)</p>
        </div>

        <!-- Revenue Summary -->
        <div class="card">
            <div class="card-header">
                <h2>Revenue Summary</h2>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $revenue_data['total_reservations'] ?: 0; ?></div>
                    <div class="stat-label">Total Reservations</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">KES <?php echo number_format($revenue_data['total_revenue'] ?: 0, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">KES <?php echo number_format($revenue_data['avg_revenue'] ?: 0, 2); ?></div>
                    <div class="stat-label">Average per Booking</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">KES <?php echo number_format($revenue_data['max_revenue'] ?: 0, 2); ?></div>
                    <div class="stat-label">Highest Booking</div>
                </div>
            </div>
        </div>

        <!-- Room Occupancy Report -->
        <div class="card">
            <div class="card-header">
                <h2>Room Occupancy by Type</h2>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Room Type</th>
                            <th>Total Rooms</th>
                            <th>Occupied</th>
                            <th>Available</th>
                            <th>Occupancy Rate</th>
                            <th>Revenue Potential (KES)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($occupancy_data as $occupancy): ?>
                            <tr>
                                <td><strong><?php echo $occupancy['room_type']; ?></strong></td>
                                <td><?php echo $occupancy['total_rooms']; ?></td>
                                <td><?php echo $occupancy['occupied_rooms']; ?></td>
                                <td><?php echo $occupancy['total_rooms'] - $occupancy['occupied_rooms']; ?></td>
                                <td>
                                    <span style="padding: 0.25rem 0.5rem; border-radius: 10px; font-size: 0.8rem; font-weight: 600;
                                        background: <?php echo $occupancy['occupancy_rate'] > 70 ? '#e8f5e8' : ($occupancy['occupancy_rate'] > 40 ? '#fff3e0' : '#ffebee'); ?>;
                                        color: <?php echo $occupancy['occupancy_rate'] > 70 ? '#388e3c' : ($occupancy['occupancy_rate'] > 40 ? '#f57c00' : '#d32f2f'); ?>;">
                                        <?php echo $occupancy['occupancy_rate']; ?>%
                                    </span>
                                </td>
                                <td>
                                    <strong>
                                        <?php 
                                        $revenue_potential = 0;
                                        switch($occupancy['room_type']) {
                                            case 'Standard': $revenue_potential = 1500 * $occupancy['total_rooms']; break;
                                            case 'Deluxe': $revenue_potential = 3000 * $occupancy['total_rooms']; break;
                                            case 'Executive': $revenue_potential = 4500 * $occupancy['total_rooms']; break;
                                            case 'Family Suite': $revenue_potential = 5500 * $occupancy['total_rooms']; break;
                                            case 'Presidential Suite': $revenue_potential = 7500 * $occupancy['total_rooms']; break;
                                        }
                                        echo 'KES ' . number_format($revenue_potential, 2);
                                        ?>
                                    </strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="card">
            <div class="card-header">
                <h2>Financial Summary</h2>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Current Month Revenue</label>
                    <input type="text" class="form-control" value="KES 0.00" readonly>
                </div>
                <div class="form-group">
                    <label>Last Month Revenue</label>
                    <input type="text" class="form-control" value="KES 0.00" readonly>
                </div>
                <div class="form-group">
                    <label>Year-to-Date Revenue</label>
                    <input type="text" class="form-control" value="KES 0.00" readonly>
                </div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="card">
            <div class="card-header">
                <h2>Export Reports</h2>
            </div>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button class="btn btn-primary">Export Revenue Report (PDF)</button>
                <button class="btn btn-success">Export Occupancy Report (Excel)</button>
                <button class="btn btn-info">Export Financial Summary (CSV)</button>
            </div>
        </div>
    </div>
</body>
</html>