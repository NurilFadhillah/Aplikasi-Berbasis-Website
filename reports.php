<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'kepala_sekolah') {
    header("Location: login.php");
    exit();
}

$total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$approved_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'approved'")->fetchColumn();
$pending_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$rejected_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'rejected'")->fetchColumn();

$room_usage = $pdo->query("
    SELECT r.name, r.type, 
           COUNT(b.id) as total_bookings,
           COUNT(CASE WHEN b.status = 'approved' THEN 1 END) as approved_bookings,
           COUNT(CASE WHEN b.status = 'pending' THEN 1 END) as pending_bookings
    FROM rooms r 
    LEFT JOIN bookings b ON r.id = b.room_id
    GROUP BY r.id
    ORDER BY total_bookings DESC
")->fetchAll();

$monthly_stats = $pdo->query("
    SELECT 
        DATE_FORMAT(date, '%Y-%m') as month,
        COUNT(*) as total_bookings,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_bookings
    FROM bookings 
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date, '%Y-%m')
    ORDER BY month DESC
")->fetchAll();

$user_stats = $pdo->query("
    SELECT u.name, u.role,
           COUNT(b.id) as total_bookings,
           COUNT(CASE WHEN b.status = 'approved' THEN 1 END) as approved_bookings
    FROM users u 
    LEFT JOIN bookings b ON u.id = b.user_id
    WHERE u.role IN ('guru', 'guru_lab', 'guru_pramuka', 'guru_olahraga', 'guru_silat', 'guru_marching_band', 'guru_paskibra', 'siswa')
    GROUP BY u.id
    HAVING total_bookings > 0
    ORDER BY total_bookings DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan & Statistik - Sistem Penjadwalan</title>
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
                        <a href="reports.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Laporan & Statistik</span>
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
                <h1>Laporan & Statistik</h1>
                <div class="header-actions">
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Cetak Laporan
                    </button>
                </div>
            </header>
            
            <div class="content">
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
                            <p>Disetujui</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pending_bookings; ?></h3>
                            <p>Menunggu</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon rejected">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $rejected_bookings; ?></h3>
                            <p>Ditolak</p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <h2>Statistik Penggunaan Ruangan</h2>
                    <div class="table-container">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Ruangan</th>
                                    <th>Tipe</th>
                                    <th>Total Booking</th>
                                    <th>Disetujui</th>
                                    <th>Menunggu</th>
                                    <th>Tingkat Penggunaan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($room_usage as $room): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($room['name']); ?></td>
                                        <td>
                                            <span class="room-type-badge <?php echo $room['type']; ?>">
                                                <?php echo ucfirst($room['type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $room['total_bookings']; ?></td>
                                        <td><?php echo $room['approved_bookings']; ?></td>
                                        <td><?php echo $room['pending_bookings']; ?></td>
                                        <td>
                                            <div class="usage-bar">
                                                <div class="usage-fill" style="width: <?php echo min(100, ($room['total_bookings'] / max(1, $total_bookings)) * 100); ?>%"></div>
                                                <span class="usage-text">
                                                    <?php echo number_format(($room['total_bookings'] / max(1, $total_bookings)) * 100, 1); ?>%
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="content-grid">
                    <div class="card">
                        <h2>Statistik 6 Bulan Terakhir</h2>
                        <div class="monthly-stats">
                            <?php foreach ($monthly_stats as $stat): ?>
                                <div class="month-item">
                                    <div class="month-header">
                                        <h4><?php echo date('M Y', strtotime($stat['month'] . '-01')); ?></h4>
                                        <span class="total"><?php echo $stat['total_bookings']; ?> booking</span>
                                    </div>
                                    <div class="month-details">
                                        <div class="detail-item">
                                            <span class="label">Disetujui:</span>
                                            <span class="value"><?php echo $stat['approved_bookings']; ?></span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo ($stat['approved_bookings'] / max(1, $stat['total_bookings'])) * 100; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h2>Statistik Pengguna</h2>
                        <div class="user-stats">
                            <?php foreach ($user_stats as $user): ?>
                                <div class="user-item">
                                    <div class="user-header">
                                        <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                                        <span class="role"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></span>
                                    </div>
                                    <div class="user-details">
                                        <div class="detail-item">
                                            <span class="label">Total Booking:</span>
                                            <span class="value"><?php echo $user['total_bookings']; ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="label">Disetujui:</span>
                                            <span class="value"><?php echo $user['approved_bookings']; ?></span>
                                        </div>
                                        <div class="success-rate">
                                            Tingkat keberhasilan: 
                                            <strong><?php echo number_format(($user['approved_bookings'] / max(1, $user['total_bookings'])) * 100, 1); ?>%</strong>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .stats-table th,
        .stats-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .stats-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #334155;
        }
        
        .room-type-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
        }
        
        .room-type-badge.rapat { background: #4f46e5; }
        .room-type-badge.laboratorium { background: #10b981; }
        .room-type-badge.kelas { background: #f59e0b; }
        
        .usage-bar {
            position: relative;
            background: #e2e8f0;
            border-radius: 10px;
            height: 20px;
            min-width: 100px;
        }
        
        .usage-fill {
            background: #10b981;
            border-radius: 10px;
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .usage-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 0.8rem;
            font-weight: 500;
            color: #334155;
        }
        
        .monthly-stats,
        .user-stats {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .month-item,
        .user-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1rem;
            border-left: 4px solid #4f46e5;
        }
        
        .month-header,
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .month-header h4,
        .user-header h4 {
            margin: 0;
            color: #334155;
        }
        
        .total {
            background: #4f46e5;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .role {
            background: #64748b;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .label {
            color: #64748b;
        }
        
        .value {
            font-weight: 500;
            color: #334155;
        }
        
        .progress-bar {
            background: #e2e8f0;
            border-radius: 10px;
            height: 8px;
            margin-top: 0.5rem;
        }
        
        .progress-fill {
            background: #10b981;
            border-radius: 10px;
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .success-rate {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.9rem;
            color: #64748b;
        }
    </style>
</body>
</html>