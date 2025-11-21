<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

if (isset($_GET['delete'])) {
    $booking_id = $_GET['delete'];
    
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
    if ($stmt->execute([$booking_id])) {
        $success = "Booking berhasil dihapus!";
    } else {
        $error = "Terjadi kesalahan saat menghapus booking.";
    }
}

if (isset($_GET['update_status'])) {
    $booking_id = $_GET['update_status'];
    $status = $_GET['status'];
    
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $booking_id])) {
        $success = "Status booking berhasil diupdate!";
    } else {
        $error = "Terjadi kesalahan saat mengupdate status.";
    }
}

$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_room = isset($_GET['room']) ? $_GET['room'] : '';

$query = "
    SELECT b.*, u.name as user_name, u.email as user_email, 
           r.name as room_name, r.type as room_type 
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

$query .= " ORDER BY b.date DESC, b.start_time DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$rooms = $pdo->query("SELECT DISTINCT type FROM rooms")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Booking - Sistem Penjadwalan</title>
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
                    <li class="nav-item active">
                        <a href="manage_bookings.php">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Kelola Booking</span>
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
                <h1>Kelola Booking</h1>
                <div class="header-actions">
                    <span>Total: <?php echo count($bookings); ?> booking</span>
                </div>
            </header>
            
            <div class="content">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <h2>Filter Booking</h2>
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                                    <option value="approved" <?php echo $filter_status == 'approved' ? 'selected' : ''; ?>>Disetujui</option>
                                    <option value="rejected" <?php echo $filter_status == 'rejected' ? 'selected' : ''; ?>>Ditolak</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="room">Tipe Ruangan</label>
                                <select id="room" name="room">
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
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="manage_bookings.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="card">
                    <h2>Daftar Booking</h2>
                    <?php if (count($bookings) > 0): ?>
                        <div class="table-container">
                            <table class="bookings-table">
                                <thead>
                                    <tr>
                                        <th>Ruangan</th>
                                        <th>Pemesan</th>
                                        <th>Tanggal & Waktu</th>
                                        <th>Tujuan</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($booking['room_name']); ?></strong>
                                                <br>
                                                <small><?php echo ucfirst($booking['room_type']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($booking['user_name']); ?>
                                                <br>
                                                <small><?php echo htmlspecialchars($booking['user_email']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo date('d M Y', strtotime($booking['date'])); ?>
                                                <br>
                                                <small><?php echo date('H:i', strtotime($booking['start_time'])); ?> - <?php echo date('H:i', strtotime($booking['end_time'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="purpose-text">
                                                    <?php echo htmlspecialchars($booking['purpose']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $booking['status']; ?>">
                                                    <?php 
                                                        if ($booking['status'] == 'pending') echo 'Menunggu';
                                                        elseif ($booking['status'] == 'approved') echo 'Disetujui';
                                                        else echo 'Ditolak';
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($booking['status'] == 'pending'): ?>
                                                        <a href="manage_bookings.php?update_status=<?php echo $booking['id']; ?>&status=approved" 
                                                           class="btn btn-success btn-sm">Setujui</a>
                                                        <a href="manage_bookings.php?update_status=<?php echo $booking['id']; ?>&status=rejected" 
                                                           class="btn btn-danger btn-sm">Tolak</a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="manage_bookings.php?delete=<?php echo $booking['id']; ?>" 
                                                       class="btn btn-danger btn-sm"
                                                       onclick="return confirm('Yakin ingin menghapus booking ini?')">
                                                        Hapus
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>Tidak ada booking yang ditemukan.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .filter-form {
            max-width: 100%;
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 1rem;
        }
        
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .bookings-table th,
        .bookings-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .bookings-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #334155;
        }
        
        .bookings-table td {
            vertical-align: top;
        }
        
        .purpose-text {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
            text-align: center;
        }
    </style>
</body>
</html>