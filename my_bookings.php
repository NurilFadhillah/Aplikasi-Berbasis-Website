<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$stmt = $pdo->prepare("
    SELECT b.*, r.name as room_name, r.type as room_type 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.user_id = ? 
    ORDER BY b.date DESC, b.start_time DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Saya - Sistem Penjadwalan</title>
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
                    <li class="nav-item">
                        <a href="booking.php">
                            <i class="fas fa-plus-circle"></i>
                            <span>Buat Booking</span>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a href="my_bookings.php">
                            <i class="fas fa-list"></i>
                            <span>Booking Saya</span>
                        </a>
                    </li>
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
                <h1>Booking Saya</h1>
                <div class="header-actions">
                    <a href="booking.php" class="btn btn-primary">Buat Booking Baru</a>
                </div>
            </header>
            
            <div class="content">
                <div class="card">
                    <h2>Daftar Booking</h2>
                    <?php if (count($bookings) > 0): ?>
                        <div class="booking-list">
                            <?php foreach ($bookings as $booking): ?>
                                <div class="booking-item <?php echo $booking['status']; ?>">
                                    <div class="booking-info">
                                        <h3><?php echo $booking['room_name']; ?> (<?php echo ucfirst($booking['room_type']); ?>)</h3>
                                        <p>Tanggal: <?php echo date('d M Y', strtotime($booking['date'])); ?></p>
                                        <p>Waktu: <?php echo date('H:i', strtotime($booking['start_time'])); ?> - <?php echo date('H:i', strtotime($booking['end_time'])); ?></p>
                                        <p>Tujuan: <?php echo $booking['purpose']; ?></p>
                                        <p>Status: 
                                            <span class="status-badge <?php echo $booking['status']; ?>">
                                                <?php 
                                                    if ($booking['status'] == 'pending') echo 'Menunggu';
                                                    elseif ($booking['status'] == 'approved') echo 'Disetujui';
                                                    else echo 'Ditolak';
                                                ?>
                                            </span>
                                        </p>
                                        <p>Dibuat: <?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Belum ada booking.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
</body>
</html>