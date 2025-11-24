<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get statistics
$rooms_query = "SELECT COUNT(*) as total_rooms, 
                       SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_rooms 
                FROM rooms";
$rooms_stmt = $db->prepare($rooms_query);
$rooms_stmt->execute();
$rooms_stats = $rooms_stmt->fetch(PDO::FETCH_ASSOC);

$reservations_query = "SELECT COUNT(*) as total_reservations FROM reservations";
$reservations_stmt = $db->prepare($reservations_query);
$reservations_stmt->execute();
$reservations_stats = $reservations_stmt->fetch(PDO::FETCH_ASSOC);

$revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as total_revenue FROM reservations WHERE status = 'checked-out'";
$revenue_stmt = $db->prepare($revenue_query);
$revenue_stmt->execute();
$revenue_stats = $revenue_stmt->fetch(PDO::FETCH_ASSOC);

// Today's revenue
$today_revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as today_revenue FROM reservations WHERE DATE(reservation_date) = CURDATE()";
$today_revenue_stmt = $db->prepare($today_revenue_query);
$today_revenue_stmt->execute();
$today_revenue_stats = $today_revenue_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hotel Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a href="dashboard.php" class="navbar-brand">OHMS</a>
                <ul class="navbar-nav">
                    <li><a href="dashboard.php" class="nav-link active">Dashboard</a></li>
                    <li><a href="reservations.php" class="nav-link">Reservations</a></li>
                    <li><a href="rooms.php" class="nav-link">Rooms</a></li>
                    <li><a href="customers.php" class="nav-link">Customers</a></li>
                    <li><a href="billing.php" class="nav-link">Billing</a></li>
                    <li><a href="reports.php" class="nav-link">Reports</a></li>
                    <li><a href="logout.php" class="nav-link">Logout (<?php echo $_SESSION['full_name']; ?>)</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="dashboard-header">
            <h1>Welcome to OHMS</h1>
            <p>Online Hotel Management System - Kenyan Shillings (KES)</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $rooms_stats['total_rooms']; ?></div>
                <div class="stat-label">Total Rooms</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $rooms_stats['occupied_rooms']; ?></div>
                <div class="stat-label">Occupied Rooms</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $reservations_stats['total_reservations']; ?></div>
                <div class="stat-label">Total Reservations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">KES <?php echo number_format($revenue_stats['total_revenue'], 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">KES <?php echo number_format($today_revenue_stats['today_revenue'], 2); ?></div>
                <div class="stat-label">Today's Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $rooms_stats['total_rooms'] - $rooms_stats['occupied_rooms']; ?></div>
                <div class="stat-label">Available Rooms</div>
            </div>
        </div>

        <!-- Recent Reservations -->
        <div class="card">
            <div class="card-header">
                <h2>Recent Reservations</h2>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Reservation ID</th>
                            <th>Customer</th>
                            <th>Room</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Amount (KES)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_query = "SELECT r.*, c.full_name as customer_name, rm.room_number 
                                       FROM reservations r 
                                       JOIN customers c ON r.customer_id = c.id 
                                       JOIN rooms rm ON r.room_id = rm.id 
                                       ORDER BY r.reservation_date DESC LIMIT 5";
                        $recent_stmt = $db->prepare($recent_query);
                        $recent_stmt->execute();
                        
                        while ($row = $recent_stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>
                                <td>#{$row['id']}</td>
                                <td>{$row['customer_name']}</td>
                                <td>{$row['room_number']}</td>
                                <td>{$row['check_in']}</td>
                                <td>{$row['check_out']}</td>
                                <td>KES " . number_format($row['total_amount'], 2) . "</td>
                                <td><span style='padding: 0.25rem 0.5rem; border-radius: 15px; background: #e3f2fd; color: #1976d2; font-size: 0.8rem;'>{$row['status']}</span></td>
                            </tr>";
                        }
                        
                        if ($recent_stmt->rowCount() == 0) {
                            echo "<tr><td colspan='7' style='text-align: center; color: #7f8c8d;'>No reservations found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h2>Quick Actions</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="reservations.php" class="btn btn-primary">New Reservation</a>
                <a href="customers.php" class="btn btn-success">Add Customer</a>
                <a href="rooms.php" class="btn btn-info">Manage Rooms</a>
                <a href="reports.php" class="btn btn-warning">View Reports</a>
            </div>
        </div>
    </div>
</body>
</html>