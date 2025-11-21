<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_room'])) {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $description = trim($_POST['description']);
    $capacity = $_POST['capacity'];
    $facilities = trim($_POST['facilities']);
    
    if (empty($name) || empty($type)) {
        $error = "Nama dan tipe ruangan harus diisi!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO rooms (name, type, description, capacity, facilities) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$name, $type, $description, $capacity, $facilities])) {
            $success = "Ruangan berhasil ditambahkan!";
        } else {
            $error = "Terjadi kesalahan. Silakan coba lagi.";
        }
    }
}

if (isset($_GET['delete'])) {
    $room_id = $_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ?");
    $stmt->execute([$room_id]);
    $booking_count = $stmt->fetchColumn();
    
    if ($booking_count > 0) {
        $error = "Tidak dapat menghapus ruangan yang sudah memiliki booking!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
        if ($stmt->execute([$room_id])) {
            $success = "Ruangan berhasil dihapus!";
        } else {
            $error = "Terjadi kesalahan saat menghapus ruangan.";
        }
    }
}

$rooms = $pdo->query("SELECT * FROM rooms ORDER BY type, name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Ruangan - Sistem Penjadwalan</title>
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
                    <li class="nav-item active">
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
                <h1>Kelola Ruangan</h1>
                <div class="header-actions">
                    <span>Total: <?php echo count($rooms); ?> ruangan</span>
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
                    <h2>Tambah Ruangan Baru</h2>
                    <form method="POST" class="room-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Nama Ruangan</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            
                           <div class="form-group">
                                <label for="type">Tipe Ruangan</label>
                                <select id="type" name="type" required>
                                    <option value="">Pilih Tipe</option>
                                    <option value="rapat">Ruang Rapat</option>
                                    <option value="laboratorium">Laboratorium</option>
                                    <option value="lapangan">Lapangan</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="capacity">Kapasitas</label>
                                <input type="number" id="capacity" name="capacity" min="1" value="20">
                            </div>
                            
                            <div class="form-group">
                                <label for="facilities">Fasilitas</label>
                                <input type="text" id="facilities" name="facilities" placeholder="Proyektor, AC, Papan Tulis">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea id="description" name="description" rows="3" placeholder="Deskripsi ruangan..."></textarea>
                        </div>
                        
                        <button type="submit" name="add_room" class="btn btn-primary">Tambah Ruangan</button>
                    </form>
                </div>
                
                <div class="card">
                    <h2>Daftar Ruangan</h2>
                    <div class="rooms-grid">
                        <?php foreach ($rooms as $room): ?>
                            <div class="room-card">
                                <div class="room-header">
                                    <h3><?php echo htmlspecialchars($room['name']); ?></h3>
                                    <span class="room-type <?php echo $room['type']; ?>">
                                        <?php echo ucfirst($room['type']); ?>
                                    </span>
                                </div>
                                
                                <div class="room-details">
                                    <?php if ($room['description']): ?>
                                        <p class="room-description"><?php echo htmlspecialchars($room['description']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="room-info">
                                        <div class="info-item">
                                            <i class="fas fa-users"></i>
                                            <span>Kapasitas: <?php echo $room['capacity']; ?> orang</span>
                                        </div>
                                        
                                        <?php if ($room['facilities']): ?>
                                            <div class="info-item">
                                                <i class="fas fa-tools"></i>
                                                <span>Fasilitas: <?php echo htmlspecialchars($room['facilities']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="room-actions">
                                    <a href="manage_rooms.php?delete=<?php echo $room['id']; ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Yakin ingin menghapus ruangan ini?')">
                                        Hapus
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .room-form {
            max-width: 100%;
        }
        
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .room-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
        }
        
        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .room-header h3 {
            margin: 0;
            color: #334155;
        }
        
        .room-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
        }
        
        .room-type.rapat { background: #4f46e5; }
        .room-type.laboratorium { background: #10b981; }
        .room-type.kelas { background: #f59e0b; }
        
        .room-description {
            color: #64748b;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .room-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #64748b;
        }
        
        .info-item i {
            width: 16px;
            color: #94a3b8;
        }
        
        .room-actions {
            border-top: 1px solid #e2e8f0;
            padding-top: 1rem;
            text-align: right;
        }
    </style>
</body>
</html>