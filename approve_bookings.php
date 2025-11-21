<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin_ruangan') {
    header("Location: login.php");
    exit();
}

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

$pending_bookings = $pdo->query("
    SELECT b.*, u.name as user_name, r.name as room_name 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.status = 'pending'
    ORDER BY b.date, b.start_time
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persetujuan Booking - Sistem Penjadwalan</title>
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
                    <li class="nav-item active">
                        <a href="approve_bookings.php">
                            <i class="fas fa-check-circle"></i>
                            <span>Persetujuan Booking</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="all_bookings.php">
                            <i class="fas fa-calendar-check"></i>
                            <span>Semua Booking</span>
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
                <h1>Persetujuan Booking</h1>
            </header>
            
            <div class="content">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <h2>Booking Menunggu Persetujuan</h2>
                    <?php if (count($pending_bookings) > 0): ?>
                        <div class="booking-list">
                            <?php foreach ($pending_bookings as $booking): ?>
                                <div class="booking-item pending">
                                    <div class="booking-info">
                                        <h3><?php echo $booking['room_name']; ?></h3>
                                        <p>Oleh: <?php echo $booking['user_name']; ?></p>
                                        <p>Tanggal: <?php echo date('d M Y', strtotime($booking['date'])); ?></p>
                                        <p>Waktu: <?php echo date('H:i', strtotime($booking['start_time'])); ?> - <?php echo date('H:i', strtotime($booking['end_time'])); ?></p>
                                        <p>Tujuan: <?php echo $booking['purpose']; ?></p>
                                        <p>Diajukan: <?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?></p>
                                    </div>
                                    <div class="booking-actions">
                                        <a href="approve_booking.php?id=<?php echo $booking['id']; ?>&action=approve" class="btn btn-success">Setujui</a>
                                        <a href="approve_booking.php?id=<?php echo $booking['id']; ?>&action=reject" class="btn btn-danger">Tolak</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Tidak ada booking yang menunggu persetujuan.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>