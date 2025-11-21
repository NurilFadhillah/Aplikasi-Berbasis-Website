<?php
session_start();
require_once 'config/database.php';

// SEMUA role yang login bisa melihat semua booking (termasuk guru, guru_lab, siswa)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['user_role'];

// Get filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_room = isset($_GET['room']) ? $_GET['room'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

$query = "
    SELECT b.*, u.name as user_name, u.email as user_email, r.name as room_name, r.type as room_type 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.id 
    WHERE 1=1
";

$params = [];

if ($filter_status) {
    $query .= " AND b.status = ?";
    $params[] = $filter_status;
}

if ($filter_room) {
    $query .= " AND r.type = ?";
    $params[] = $filter_room;
}

if ($filter_date) {
    $query .= " AND b.date = ?";
    $params[] = $filter_date;
}

$query .= " ORDER BY b.date DESC, b.start_time DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$all_bookings = $stmt->fetchAll();

// Get rooms for filter
$rooms = $pdo->query("SELECT DISTINCT type FROM rooms")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Booking - Sistem Penjadwalan</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Penjadwalan</h2>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <h3><?php echo $_SESSION['user_name']; ?></h3>
                    <p><?php echo ucfirst(str_replace('_', ' ', $_SESSION['user_role'])); ?></p>
                </div>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item">
                        <a href="dashboard.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <?php if (in_array($user_role, ['guru_lab', 'guru', 'siswa'])): ?>
                        <li class="nav-item">
                            <a href="booking.php">
                                <i class="fas fa-plus-circle"></i>
                                <span>Buat Booking</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="my_bookings.php">
                                <i class="fas fa-list"></i>
                                <span>Booking Saya</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item active">
                        <a href="all_bookings.php">
                            <i class="fas fa-calendar-check"></i>
                            <span>Semua Booking</span>
                        </a>
                    </li>
                    
                    <?php if ($user_role == 'admin_ruangan'): ?>
                        <li class="nav-item">
                            <a href="approve_bookings.php">
                                <i class="fas fa-check-circle"></i>
                                <span>Persetujuan Booking</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($user_role == 'admin'): ?>
                        <li class="nav-item">
                            <a href="manage_users.php">
                                <i class="fas fa-users"></i>
                                <span>Kelola Pengguna</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="manage_rooms.php">
                                <i class="fas fa-door-open"></i>
                                <span>Kelola Ruangan</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="manage_bookings.php">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Kelola Booking</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Keluar</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <header class="main-header">
                <h1>Semua Booking</h1>
                <div class="header-actions">
                    <span>Total: <?php echo count($all_bookings); ?> booking</span>
                </div>
            </header>
            
            <div class="content">
                <div class="card">
                    <h2>Daftar Semua Booking</h2>
                    
                    <!-- Filter Options -->
                    <div class="filter-section">
                        <form method="GET" class="filter-form">
                            <div class="filter-row">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select id="status" name="status" class="filter-select">
                                        <option value="">Semua Status</option>
                                        <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                                        <option value="approved" <?php echo $filter_status == 'approved' ? 'selected' : ''; ?>>Disetujui</option>
                                        <option value="rejected" <?php echo $filter_status == 'rejected' ? 'selected' : ''; ?>>Ditolak</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="room">Tipe Ruangan</label>
                                    <select id="room" name="room" class="filter-select">
                                        <option value="">Semua Ruangan</option>
                                        <?php foreach ($rooms as $room): ?>
                                            <option value="<?php echo $room['type']; ?>" 
                                                    <?php echo $filter_room == $room['type'] ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($room['type']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="date">Tanggal</label>
                                    <input type="date" id="date" name="date" class="filter-input" 
                                           value="<?php echo $filter_date; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="all_bookings.php" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <?php if (count($all_bookings) > 0): ?>
                        <div class="booking-list">
                            <?php foreach ($all_bookings as $booking): ?>
                                <div class="booking-item <?php echo $booking['status']; ?>">
                                    <div class="booking-info">
                                        <h3><?php echo htmlspecialchars($booking['room_name']); ?> (<?php echo ucfirst($booking['room_type']); ?>)</h3>
                                        <p><strong>Pemesan:</strong> <?php echo htmlspecialchars($booking['user_name']); ?> (<?php echo htmlspecialchars($booking['user_email']); ?>)</p>
                                        <p><strong>Tanggal:</strong> <?php echo date('d M Y', strtotime($booking['date'])); ?></p>
                                        <p><strong>Waktu:</strong> <?php echo date('H:i', strtotime($booking['start_time'])); ?> - <?php echo date('H:i', strtotime($booking['end_time'])); ?></p>
                                        <p><strong>Tujuan:</strong> <?php echo htmlspecialchars($booking['purpose']); ?></p>
                                        <p><strong>Status:</strong> 
                                            <span class="status-badge <?php echo $booking['status']; ?>">
                                                <?php 
                                                    if ($booking['status'] == 'pending') echo 'Menunggu';
                                                    elseif ($booking['status'] == 'approved') echo 'Disetujui';
                                                    else echo 'Ditolak';
                                                ?>
                                            </span>
                                        </p>
                                        <p><strong>Dibuat:</strong> <?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?></p>
                                    </div>
                                    
                                    <?php if (($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'admin_ruangan') && $booking['status'] == 'pending'): ?>
                                        <div class="booking-actions">
                                            <a href="approve_booking.php?id=<?php echo $booking['id']; ?>&action=approve" class="btn btn-success">Setujui</a>
                                            <a href="approve_booking.php?id=<?php echo $booking['id']; ?>&action=reject" class="btn btn-danger">Tolak</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Tidak ada booking yang ditemukan.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>