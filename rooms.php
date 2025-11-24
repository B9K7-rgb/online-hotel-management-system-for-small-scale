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
    if (isset($_POST['add_room'])) {
        $room_number = $_POST['room_number'];
        $room_type = $_POST['room_type'];
        $price = $_POST['price'];
        $description = $_POST['description'];
        
        $insert_query = "INSERT INTO rooms (room_number, room_type, price, description) VALUES (?, ?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute([$room_number, $room_type, $price, $description]);
        
        $success = "Room added successfully!";
    }
}

// Get all rooms
$rooms_query = "SELECT * FROM rooms ORDER BY room_number";
$rooms_stmt = $db->prepare($rooms_query);
$rooms_stmt->execute();
$rooms = $rooms_stmt->fetchAll(PDO::FETCH_ASSOC);

// Count rooms by type
$room_stats_query = "SELECT room_type, COUNT(*) as count, AVG(price) as avg_price FROM rooms GROUP BY room_type";
$room_stats_stmt = $db->prepare($room_stats_query);
$room_stats_stmt->execute();
$room_stats = $room_stats_stmt->fetchAll(PDO::FETCH_ASSOC);

// Room type prices for reference
$room_prices = [
    'Standard' => 1500,
    'Deluxe' => 3000,
    'Executive' => 4500,
    'Family Suite' => 5500,
    'Presidential Suite' => 7500
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms - Hotel Management System</title>
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
                    <li><a href="rooms.php" class="nav-link active">Rooms</a></li>
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
            <h1>Room Management</h1>
            <p>Manage your 50-room hotel inventory - All prices in Kenyan Shillings (KES)</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Room Statistics -->
        <div class="stats-grid">
            <?php foreach ($room_stats as $stat): ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stat['count']; ?></div>
                <div class="stat-label"><?php echo $stat['room_type']; ?> Rooms</div>
                <div style="font-size: 0.9rem; color: #7f8c8d; margin-top: 0.5rem;">
                    KES <?php echo number_format($stat['avg_price'], 2); ?> avg/night
                </div>
            </div>
            <?php endforeach; ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($rooms); ?></div>
                <div class="stat-label">Total Rooms</div>
                <div style="font-size: 0.9rem; color: #7f8c8d; margin-top: 0.5rem;">
                    Hotel Capacity
                </div>
            </div>
        </div>

        <!-- Add Room Form -->
        <div class="card">
            <div class="card-header">
                <h2>Add New Room</h2>
            </div>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Room Number</label>
                        <input type="text" name="room_number" class="form-control" placeholder="e.g., 101, 201" required>
                    </div>
                    <div class="form-group">
                        <label>Room Type</label>
                        <select name="room_type" class="form-control" required>
                            <option value="Standard">Standard - KES 1,500/night</option>
                            <option value="Deluxe">Deluxe - KES 3,000/night</option>
                            <option value="Executive">Executive - KES 4,500/night</option>
                            <option value="Family Suite">Family Suite - KES 5,500/night</option>
                            <option value="Presidential Suite">Presidential Suite - KES 7,500/night</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Price per Night (KES)</label>
                        <input type="number" name="price" step="0.01" class="form-control" placeholder="Enter price in KES" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Room features, bed type, amenities..."></textarea>
                </div>
                <button type="submit" name="add_room" class="btn btn-primary">Add Room</button>
            </form>
        </div>

        <!-- Rooms List -->
        <div class="card">
            <div class="card-header">
                <h2>All Rooms (<?php echo count($rooms); ?> rooms)</h2>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Room Number</th>
                            <th>Type</th>
                            <th>Price (KES)</th>
                            <th>Status</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td><strong><?php echo $room['room_number']; ?></strong></td>
                                <td>
                                    <span style="padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; 
                                        background: 
                                        <?php 
                                            switch($room['room_type']) {
                                                case 'Standard': echo '#e3f2fd'; break;
                                                case 'Deluxe': echo '#f3e5f5'; break;
                                                case 'Executive': echo '#e8f5e8'; break;
                                                case 'Family Suite': echo '#fff3e0'; break;
                                                case 'Presidential Suite': echo '#ffebee'; break;
                                                default: echo '#f5f5f5';
                                            }
                                        ?>; 
                                        color: 
                                        <?php 
                                            switch($room['room_type']) {
                                                case 'Standard': echo '#1976d2'; break;
                                                case 'Deluxe': echo '#7b1fa2'; break;
                                                case 'Executive': echo '#388e3c'; break;
                                                case 'Family Suite': echo '#f57c00'; break;
                                                case 'Presidential Suite': echo '#d32f2f'; break;
                                                default: echo '#757575';
                                            }
                                        ?>;">
                                        <?php echo $room['room_type']; ?>
                                    </span>
                                </td>
                                <td><strong>KES <?php echo number_format($room['price'], 2); ?></strong></td>
                                <td>
                                    <span style="padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: 600;
                                        background: <?php 
                                            switch($room['status']) {
                                                case 'available': echo '#e8f5e8'; break;
                                                case 'occupied': echo '#ffebee'; break;
                                                default: echo '#fff3e0';
                                            }
                                        ?>; 
                                        color: <?php 
                                            switch($room['status']) {
                                                case 'available': echo '#388e3c'; break;
                                                case 'occupied': echo '#d32f2f'; break;
                                                default: echo '#f57c00';
                                            }
                                        ?>;">
                                        <?php echo $room['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo $room['description']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>