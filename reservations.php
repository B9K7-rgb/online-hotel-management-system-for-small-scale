<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_reservation'])) {
        $customer_id = $_POST['customer_id'];
        $room_id = $_POST['room_id'];
        $check_in = $_POST['check_in'];
        $check_out = $_POST['check_out'];
        
        // Calculate total amount
        $room_query = "SELECT price FROM rooms WHERE id = ?";
        $room_stmt = $db->prepare($room_query);
        $room_stmt->execute([$room_id]);
        $room = $room_stmt->fetch(PDO::FETCH_ASSOC);
        
        $days = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
        $total_amount = $room['price'] * $days;
        
        $insert_query = "INSERT INTO reservations (customer_id, room_id, check_in, check_out, total_amount) 
                        VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute([$customer_id, $room_id, $check_in, $check_out, $total_amount]);
        
        // Update room status
        $update_room = "UPDATE rooms SET status = 'occupied' WHERE id = ?";
        $update_stmt = $db->prepare($update_room);
        $update_stmt->execute([$room_id]);
        
        $success = "Reservation added successfully!";
    }
}

// Get data for dropdowns
$customers_query = "SELECT * FROM customers ORDER BY full_name";
$customers_stmt = $db->prepare($customers_query);
$customers_stmt->execute();
$customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);

$available_rooms_query = "SELECT * FROM rooms WHERE status = 'available' ORDER BY room_number";
$available_rooms_stmt = $db->prepare($available_rooms_query);
$available_rooms_stmt->execute();
$available_rooms = $available_rooms_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all reservations
$reservations_query = "SELECT r.*, c.full_name as customer_name, rm.room_number, rm.room_type 
                      FROM reservations r 
                      JOIN customers c ON r.customer_id = c.id 
                      JOIN rooms rm ON r.room_id = rm.id 
                      ORDER BY r.reservation_date DESC";
$reservations_stmt = $db->prepare($reservations_query);
$reservations_stmt->execute();
$reservations = $reservations_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations - Hotel Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a href="dashboard.php" class="navbar-brand">OHMS</a>
                <ul class="navbar-nav">
                    <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                    <li><a href="reservations.php" class="nav-link active">Reservations</a></li>
                    <li><a href="rooms.php" class="nav-link">Rooms</a></li>
                    <li><a href="customers.php" class="nav-link">Customers</a></li>
                    <li><a href="billing.php" class="nav-link">Billing</a></li>
                    <li><a href="reports.php" class="nav-link">Reports</a></li>
                    <li><a href="logout.php" class="nav-link">Logout (<?php echo $_SESSION['full_name']; ?>)</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Reservation Management</h1>
            <p>All amounts in Kenyan Shillings (KES)</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Add Reservation Form -->
        <div class="card">
            <div class="card-header">
                <h2>Add New Reservation</h2>
            </div>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Customer</label>
                        <select name="customer_id" class="form-control" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>"><?php echo $customer['full_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Room</label>
                        <select name="room_id" class="form-control" required>
                            <option value="">Select Room</option>
                            <?php foreach ($available_rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>">
                                    <?php echo $room['room_number'] . ' - ' . $room['room_type'] . ' (KES ' . number_format($room['price'], 2) . '/night)'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Check-in Date</label>
                        <input type="date" name="check_in" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Check-out Date</label>
                        <input type="date" name="check_out" class="form-control" required>
                    </div>
                </div>
                <button type="submit" name="add_reservation" class="btn btn-primary">Create Reservation</button>
            </form>
        </div>

        <!-- Reservations List -->
        <div class="card">
            <div class="card-header">
                <h2>All Reservations</h2>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Room</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Amount (KES)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td>#<?php echo $reservation['id']; ?></td>
                                <td><?php echo $reservation['customer_name']; ?></td>
                                <td><?php echo $reservation['room_number'] . ' (' . $reservation['room_type'] . ')'; ?></td>
                                <td><?php echo $reservation['check_in']; ?></td>
                                <td><?php echo $reservation['check_out']; ?></td>
                                <td><strong>KES <?php echo number_format($reservation['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span style="padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: 600;
                                        background: <?php 
                                            switch($reservation['status']) {
                                                case 'confirmed': echo '#e3f2fd'; break;
                                                case 'checked-in': echo '#e8f5e8'; break;
                                                case 'checked-out': echo '#f3e5f5'; break;
                                                default: echo '#ffebee';
                                            }
                                        ?>; 
                                        color: <?php 
                                            switch($reservation['status']) {
                                                case 'confirmed': echo '#1976d2'; break;
                                                case 'checked-in': echo '#388e3c'; break;
                                                case 'checked-out': echo '#7b1fa2'; break;
                                                default: echo '#d32f2f';
                                            }
                                        ?>;">
                                        <?php echo $reservation['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($reservations)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #7f8c8d; padding: 2rem;">
                                    No reservations found. Create your first reservation above.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>