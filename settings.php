<?php
session_start();
require_once 'config/database.php';

// Hanya admin yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

// Buat tabel settings jika belum ada
$pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE,
        setting_value VARCHAR(255),
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Insert default setting jika belum ada
$stmt = $pdo->prepare("
    INSERT IGNORE INTO settings (setting_key, setting_value, description) 
    VALUES ('booking_max_days', '14', 'Maksimal hari untuk pemesanan di depan')
");
$stmt->execute();

// Proses update setting
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $max_days = intval($_POST['max_days']);
    
    if ($max_days < 1) {
        $error = "Minimal booking harus 1 hari!";
    } elseif ($max_days > 365) {
        $error = "Maksimal booking tidak boleh lebih dari 1 tahun (365 hari)!";
    } else {
        $stmt = $pdo->prepare("
            UPDATE settings 
            SET setting_value = ? 
            WHERE setting_key = 'booking_max_days'
        ");
        
        if ($stmt->execute([$max_days])) {
            $success = "Pengaturan berhasil diperbarui! Maksimal pemesanan sekarang: $max_days hari";
        } else {
            $error = "Terjadi kesalahan saat menyimpan pengaturan.";
        }
    }
}

// Ambil setting saat ini
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'booking_max_days'");
$current_setting = $stmt->fetch();
$max_days = $current_setting ? $current_setting['setting_value'] : 14;

// Statistik sistem
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_rooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pending_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Sistem - Sistem Penjadwalan</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .settings-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .settings-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .settings-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .settings-form {
            max-width: 600px;
        }
        
        .setting-item {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 12px;
            border-left: 4px solid #4f46e5;
        }
        
        .setting-label {
            display: block;
            font-weight: 600;
            color: #1e3a8a;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .setting-description {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .input-with-unit {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .input-with-unit input {
            flex: 1;
            max-width: 200px;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 600;
            color: #1e3a8a;
            text-align: center;
        }
        
        .input-unit {
            font-weight: 600;
            color: #64748b;
            font-size: 1rem;
        }
        
        .current-value {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #dbeafe;
            border-radius: 8px;
            color: #1e40af;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .info-box i {
            color: #3b82f6;
            margin-right: 0.5rem;
        }
        
        .info-box ul {
            margin: 0.5rem 0 0 1.5rem;
            color: #1e40af;
        }
        
        .info-box li {
            margin-bottom: 0.3rem;
        }
    </style>
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
                    <li class="nav-item">
                        <a href="manage_bookings.php">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Kelola Booking</span>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a href="settings.php">
                            <i class="fas fa-cog"></i>
                            <span>Pengaturan Sistem</span>
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
                <h1>Pengaturan Sistem</h1>
                <div class="header-actions">
                    <span>Kelola konfigurasi sistem</span>
                </div>
            </header>
            
            <div class="content">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Statistik Sistem -->
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
                
                <!-- Pengaturan Booking -->
                <div class="settings-card">
                    <div class="settings-header">
                        <div class="settings-icon">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                        <div>
                            <h2 style="margin: 0; color: #1e3a8a;">Pengaturan Pemesanan</h2>
                            <p style="margin: 0; color: #64748b; font-size: 0.9rem;">Atur batasan pemesanan ruangan</p>
                        </div>
                    </div>
                    
                    <form method="POST" class="settings-form">
                        <div class="setting-item">
                            <label class="setting-label">
                                <i class="fas fa-hourglass-half"></i> Batas Maksimal Pemesanan
                            </label>
                            
                            <div class="setting-description">
                                Tentukan berapa hari ke depan user dapat melakukan pemesanan ruangan.
                                <br>
                                <span class="current-value">
                                    <i class="fas fa-info-circle"></i>
                                    Saat ini: <?php echo $max_days; ?> hari
                                </span>
                            </div>
                            
                            <div class="input-with-unit">
                                <input 
                                    type="number" 
                                    name="max_days" 
                                    id="max_days"
                                    value="<?php echo $max_days; ?>" 
                                    min="1" 
                                    max="365"
                                    required
                                >
                                <span class="input-unit">hari ke depan</span>
                            </div>
                            
                            <div class="info-box">
                                <i class="fas fa-lightbulb"></i>
                                <strong>Contoh:</strong>
                                <ul>
                                    <li><strong>7 hari</strong> = User hanya bisa booking maksimal 1 minggu ke depan</li>
                                    <li><strong>14 hari</strong> = User hanya bisa booking maksimal 2 minggu ke depan</li>
                                    <li><strong>30 hari</strong> = User hanya bisa booking maksimal 1 bulan ke depan</li>
                                </ul>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Pengaturan
                        </button>
                    </form>
                </div>
                
                <!-- Info Tambahan -->
                <div class="card">
                    <h2>
                        <i class="fas fa-info-circle"></i> Informasi Pengaturan
                    </h2>
                    <p style="color: #64748b; line-height: 1.8;">
                        Pengaturan ini akan mempengaruhi semua user (guru, guru lab, dan siswa) ketika melakukan pemesanan ruangan.
                        Perubahan akan langsung diterapkan setelah disimpan.
                    </p>
                    
                    <div style="margin-top: 1.5rem; padding: 1rem; background: #fef3c7; border-radius: 10px; border-left: 4px solid #f59e0b;">
                        <strong style="color: #d97706;">
                            <i class="fas fa-exclamation-triangle"></i> Catatan Penting:
                        </strong>
                        <p style="margin: 0.5rem 0 0 0; color: #92400e;">
                            Pastikan nilai yang diatur sesuai dengan kebijakan sekolah. 
                            Nilai yang terlalu besar dapat menyebabkan jadwal tidak terkelola dengan baik.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Preview live update
        document.getElementById('max_days').addEventListener('input', function() {
            const value = this.value;
            if (value) {
                const weeks = Math.floor(value / 7);
                const days = value % 7;
                let text = '';
                
                if (weeks > 0) {
                    text += weeks + ' minggu';
                }
                if (days > 0) {
                    if (text) text += ' ';
                    text += days + ' hari';
                }
                
                console.log('Preview: ' + text);
            }
        });
    </script>
</body>
</html>