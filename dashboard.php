<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

if ($user_role == 'admin') {
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_rooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
    $total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    $pending_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
} elseif ($user_role == 'kepala_sekolah') {
    $total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    $approved_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'approved'")->fetchColumn();
    $room_usage = $pdo->query("
        SELECT r.name, COUNT(b.id) as usage_count 
        FROM rooms r 
        LEFT JOIN bookings b ON r.id = b.room_id AND b.status = 'approved'
        GROUP BY r.id
    ")->fetchAll();
} elseif ($user_role == 'admin_ruangan') {
    $pending_bookings = $pdo->query("
        SELECT b.*, u.name as user_name, r.name as room_name 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN rooms r ON b.room_id = r.id 
        WHERE b.status = 'pending'
        ORDER BY b.date, b.start_time
    ")->fetchAll();
} else {
    // Untuk guru, guru_lab, guru ekstrakurikuler, dan siswa
    $my_bookings = $pdo->prepare("
        SELECT b.*, r.name as room_name, r.type as room_type 
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.id 
        WHERE b.user_id = ? 
        ORDER BY b.date DESC, b.start_time DESC 
        LIMIT 5
    ");
    $my_bookings->execute([$user_id]);
    $my_bookings = $my_bookings->fetchAll();
    
    $available_rooms = $pdo->prepare("
        SELECT r.* 
        FROM rooms r 
        WHERE (r.type != 'laboratorium' OR ? NOT IN ('guru', 'siswa'))
        ORDER BY r.type, r.name
    ");
    $available_rooms->execute([$user_role]);
    $available_rooms = $available_rooms->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Penjadwalan</title>
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
                    <h3><?php echo $user_name; ?></h3>
                    <p><?php echo ucfirst(str_replace('_', ' ', $user_role)); ?></p>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item active">
                        <a href="dashboard.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
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
                        <li class="nav-item">
                            <a href="settings.php">
                                <i class="fas fa-cog"></i>
                                <span>Pengaturan Sistem</span>
                            </a>
                        </li>
                    <?php elseif ($user_role == 'kepala_sekolah'): ?>
                        <li class="nav-item">
                            <a href="reports.php">
                                <i class="fas fa-chart-bar"></i>
                                <span>Laporan & Statistik</span>
                            </a>
                        </li>
                    <?php elseif ($user_role == 'admin_ruangan'): ?>
                        <li class="nav-item">
                            <a href="approve_bookings.php">
                                <i class="fas fa-check-circle"></i>
                                <span>Persetujuan Booking</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
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
                    
                    <!-- SEMUA ROLE BISA LIHAT SEMUA BOOKING -->
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
                <h1>Dashboard</h1>
                <div class="header-actions">
                    <span>Selamat datang, <?php echo $user_name; ?></span>
                </div>
            </header>
            
            <div class="content">
                <?php if ($user_role == 'admin'): ?>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon users">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $total_users; ?></h3>
                                <p>Total Pengguna</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon rooms">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $total_rooms; ?></h3>
                                <p>Total Ruangan</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon bookings">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $total_bookings; ?></h3>
                                <p>Total Booking</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $pending_bookings; ?></h3>
                                <p>Menunggu Persetujuan</p>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($user_role == 'kepala_sekolah'): ?>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon bookings">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $total_bookings; ?></h3>
                                <p>Total Booking</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon approved">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $approved_bookings; ?></h3>
                                <p>Booking Disetujui</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h2>Penggunaan Ruangan</h2>
                        <div class="room-usage">
                            <?php foreach ($room_usage as $room): ?>
                                <div class="usage-item">
                                    <span class="room-name"><?php echo $room['name']; ?></span>
                                    <span class="usage-count"><?php echo $room['usage_count']; ?> kali digunakan</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                <?php elseif ($user_role == 'admin_ruangan'): ?>
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
                    
                <?php else: ?>
                    <!-- Dashboard untuk Guru, Guru Lab, dan Siswa -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon rooms">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo count($available_rooms); ?></h3>
                                <p>Ruangan Tersedia</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon bookings">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo count($my_bookings); ?></h3>
                                <p>Booking Saya</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="content-grid">
                        <div class="card">
                            <h2>Booking Terbaru</h2>
                            <?php if (count($my_bookings) > 0): ?>
                                <div class="booking-list">
                                    <?php foreach ($my_bookings as $booking): ?>
                                        <div class="booking-item <?php echo $booking['status']; ?>">
                                            <h3><?php echo $booking['room_name']; ?> (<?php echo ucfirst($booking['room_type']); ?>)</h3>
                                            <p>Tanggal: <?php echo date('d M Y', strtotime($booking['date'])); ?></p>
                                            <p>Waktu: <?php echo date('H:i', strtotime($booking['start_time'])); ?> - <?php echo date('H:i', strtotime($booking['end_time'])); ?></p>
                                            <p>Status: 
                                                <span class="status-badge <?php echo $booking['status']; ?>">
                                                    <?php 
                                                        if ($booking['status'] == 'pending') echo 'Menunggu';
                                                        elseif ($booking['status'] == 'approved') echo 'Disetujui';
                                                        else echo 'Ditolak';
                                                    ?>
                                                </span>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p>Belum ada booking.</p>
                            <?php endif; ?>
                            <div class="card-footer">
                                <a href="my_bookings.php" class="btn btn-primary">Lihat Semua Booking</a>
                            </div>
                        </div>
                        
                        <div class="card">
                            <h2>Buat Booking Baru</h2>
                            <p>Jadwalkan penggunaan ruangan dengan mudah.</p>
                            <div class="card-footer">
                                <a href="booking.php" class="btn btn-primary">Buat Booking</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Mobile menu toggle
        const mobileMenuToggle = document.createElement('button');
        mobileMenuToggle.className = 'mobile-menu-toggle';
        mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.appendChild(mobileMenuToggle);

        mobileMenuToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !toggle.contains(event.target) && 
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });

        // Update mobile menu icon based on sidebar state
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    const icon = mobileMenuToggle.querySelector('i');
                    if (document.querySelector('.sidebar').classList.contains('active')) {
                        icon.className = 'fas fa-times';
                    } else {
                        icon.className = 'fas fa-bars';
                    }
                }
            });
        });

        observer.observe(document.querySelector('.sidebar'), {
            attributes: true,
            attributeFilter: ['class']
        });
    </script>
</body>
</html>