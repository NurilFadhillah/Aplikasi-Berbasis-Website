<?php
session_start();
require_once 'config/database.php';

// Role yang bisa booking: guru, guru ekstrakurikuler, guru_lab, dan siswa
$allowed_roles = ['guru', 'guru_lab', 'guru_pramuka', 'guru_olahraga', 'guru_silat', 'guru_marching_band', 'guru_paskibra', 'siswa'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$success = '';
$error = '';

// Ambil setting maksimal hari booking dari database
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'booking_max_days'");
$setting = $stmt->fetch();
$max_booking_days = $setting ? intval($setting['setting_value']) : 14;

// Hitung tanggal maksimal
$max_date = date('Y-m-d', strtotime("+{$max_booking_days} days"));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_id = $_POST['room_id'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $purpose = $_POST['purpose'];
    
    // Validasi: Tanggal tidak boleh lebih dari maksimal hari yang ditentukan
    $selected_date = strtotime($date);
    $today = strtotime(date('Y-m-d'));
    $max_date_timestamp = strtotime($max_date);
    
    if ($selected_date < $today) {
        $error = "Tanggal booking tidak boleh di masa lampau!";
    } elseif ($selected_date > $max_date_timestamp) {
        $error = "Pemesanan maksimal {$max_booking_days} hari dari hari ini!";
    } else {
        // VALIDASI BARU: Cek apakah user sudah booking di jam yang sama pada tanggal yang sama
        $stmt = $pdo->prepare("
            SELECT r.name as room_name
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            WHERE b.user_id = ? AND b.date = ? AND b.status IN ('pending', 'approved')
            AND ((b.start_time <= ? AND b.end_time > ?) 
                OR (b.start_time < ? AND b.end_time >= ?) 
                OR (b.start_time >= ? AND b.end_time <= ?))
        ");
        $stmt->execute([
            $user_id, $date, 
            $start_time, $start_time, 
            $end_time, $end_time, 
            $start_time, $end_time
        ]);
        $user_existing_booking = $stmt->fetch();
        
        if ($user_existing_booking) {
            $error = "Anda sudah memiliki booking di jam yang sama pada tanggal ini! (Ruangan: " . htmlspecialchars($user_existing_booking['room_name']) . "). Satu pengguna tidak bisa booking di jam yang sama walaupun ruangan berbeda.";
        } else {
            // Cek apakah ruangan sudah dibooking
            $stmt = $pdo->prepare("
                SELECT * FROM bookings 
                WHERE room_id = ? AND date = ? AND status = 'approved'
                AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))
            ");
            $stmt->execute([$room_id, $date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time]);
            $existing_booking = $stmt->fetch();
            
            if ($existing_booking) {
                $error = "Ruangan sudah dibooking pada jam tersebut!";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO bookings (user_id, room_id, date, start_time, end_time, purpose) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                if ($stmt->execute([$user_id, $room_id, $date, $start_time, $end_time, $purpose])) {
                    $success = "Booking berhasil diajukan! Menunggu persetujuan admin.";
                } else {
                    $error = "Terjadi kesalahan. Silakan coba lagi.";
                }
            }
        }
    }
}

// Siswa, guru, dan guru ekstrakurikuler tidak bisa booking laboratorium
$restricted_roles = ['siswa', 'guru', 'guru_pramuka', 'guru_olahraga', 'guru_silat', 'guru_marching_band', 'guru_paskibra'];
if (in_array($user_role, $restricted_roles)) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE type != 'laboratorium' ORDER BY type, name");
} else {
    // Guru lab bisa booking semua ruangan
    $stmt = $pdo->prepare("SELECT * FROM rooms ORDER BY type, name");
}
$stmt->execute();
$rooms = $stmt->fetchAll();

$time_slots = [];
for ($hour = 7; $hour <= 17; $hour++) {
    $time_slots[] = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Booking - Sistem Penjadwalan</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .time-availability {
            margin-top: 1.5rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        
        .time-availability h3 {
            color: #1e3a8a;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
            margin-top: 1rem;
        }
        
        .time-slot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            border-radius: 8px;
            background: white;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .time-slot:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .time-slot .time {
            font-weight: 600;
            color: #334155;
            font-size: 0.9rem;
            flex: 1;
        }
        
        .time-slot .status {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }
        
        .time-slot .status.available {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .time-slot .status.approved {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #ef4444;
        }
        
        .time-slot .status.pending {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #f59e0b;
        }
        
        .time-slot .status.user-booked {
            background: #ddd6fe;
            color: #7c3aed;
            border: 1px solid #a78bfa;
        }
        
        .time-slot.booked {
            background: #fef2f2;
            border-color: #fecaca;
        }
        
        .time-slot.pending-approval {
            background: #fffbeb;
            border-color: #fde68a;
        }
        
        .time-slot.user-has-booking {
            background: #f5f3ff;
            border-color: #ddd6fe;
        }
        
        .booked-info {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.25rem;
            font-style: italic;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 1rem;
            color: #64748b;
        }
        
        .loading-spinner.active {
            display: block;
        }
        
        .no-availability {
            text-align: center;
            padding: 2rem;
            color: #64748b;
            font-style: italic;
        }
        
        .date-limit-info {
            background: linear-gradient(135deg, #dbeafe, #e0f2fe);
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .date-limit-info i {
            color: #2563eb;
            font-size: 1.2rem;
        }
        
        .date-limit-info span {
            color: #1e40af;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .warning-box i {
            color: #f59e0b;
            margin-right: 0.5rem;
        }
        
        .warning-box strong {
            color: #92400e;
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
                    <li class="nav-item active">
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
                <h1>Buat Booking Ruangan</h1>
                <div class="header-actions">
                    <a href="my_bookings.php" class="btn btn-secondary">Lihat Booking Saya</a>
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
                    <h2>Form Booking Ruangan</h2>
                    
                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Penting:</strong> Anda tidak dapat membuat booking di jam yang sama pada tanggal yang sama, walaupun untuk ruangan yang berbeda. Pastikan jadwal Anda tidak bentrok!
                    </div>
                    
                    <form method="POST" class="booking-form">
                        <div class="form-group">
                            <label for="room_id">Pilih Ruangan</label>
                            <select id="room_id" name="room_id" required>
                                <option value="">-- Pilih Ruangan --</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>">
                                        <?php echo $room['name']; ?> (<?php echo ucfirst($room['type']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (in_array($user_role, $restricted_roles)): ?>
                                <small><em>Note: <?php echo ucfirst(str_replace('_', ' ', $user_role)); ?> tidak dapat membooking laboratorium</em></small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="date">Tanggal</label>
                            <input type="date" id="date" name="date" 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   max="<?php echo $max_date; ?>" 
                                   required>
                            
                            <div class="date-limit-info">
                                <i class="fas fa-calendar-check"></i>
                                <span>
                                    <strong>Batas Pemesanan:</strong> Maksimal <strong><?php echo $max_booking_days; ?> hari</strong> ke depan 
                                    (sampai tanggal <strong><?php echo date('d M Y', strtotime($max_date)); ?></strong>)
                                </span>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_time">Jam Mulai</label>
                                <select id="start_time" name="start_time" required>
                                    <option value="">-- Pilih Jam --</option>
                                    <?php foreach ($time_slots as $time): ?>
                                        <option value="<?php echo $time; ?>"><?php echo $time; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="end_time">Jam Selesai</label>
                                <select id="end_time" name="end_time" required>
                                    <option value="">-- Pilih Jam --</option>
                                    <?php foreach ($time_slots as $time): ?>
                                        <option value="<?php echo $time; ?>"><?php echo $time; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="purpose">Keperluan</label>
                            <textarea id="purpose" name="purpose" rows="4" placeholder="Jelaskan keperluan penggunaan ruangan..." required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Ajukan Booking</button>
                    </form>
                </div>
                
                <!-- Informasi Ketersediaan Waktu -->
                <div class="card">
                    <div class="time-availability">
                        <h3><i class="fas fa-clock"></i> Ketersediaan Waktu Ruangan</h3>
                        <p style="color: #64748b; margin-bottom: 1rem;">Pilih ruangan dan tanggal untuk melihat ketersediaan waktu</p>
                        
                        <div class="loading-spinner" id="loadingSpinner">
                            <i class="fas fa-spinner fa-spin"></i> Memuat ketersediaan...
                        </div>
                        
                        <div class="no-availability" id="noAvailability">
                            Silakan pilih ruangan dan tanggal terlebih dahulu
                        </div>
                        
                        <div class="time-slots-grid" id="timeSlotsGrid" style="display: none;">
                            <!-- Time slots akan dimuat melalui JavaScript -->
                        </div>
                        
                        <div class="booking-legend" style="margin-top: 1rem; padding: 1rem; background: white; border-radius: 8px;">
                            <h4 style="margin-bottom: 0.5rem; color: #334155; font-size: 0.9rem;">Keterangan:</h4>
                            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="width: 20px; height: 20px; background: #d1fae5; border-radius: 4px; display: inline-block; border: 1px solid #10b981;"></span>
                                    <span style="font-size: 0.85rem; color: #64748b;">Tersedia</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="width: 20px; height: 20px; background: #fee2e2; border-radius: 4px; display: inline-block; border: 1px solid #ef4444;"></span>
                                    <span style="font-size: 0.85rem; color: #64748b;">Sudah Dibooking</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="width: 20px; height: 20px; background: #fef3c7; border-radius: 4px; display: inline-block; border: 1px solid #f59e0b;"></span>
                                    <span style="font-size: 0.85rem; color: #64748b;">Menunggu Persetujuan</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="width: 20px; height: 20px; background: #ddd6fe; border-radius: 4px; display: inline-block; border: 1px solid #a78bfa;"></span>
                                    <span style="font-size: 0.85rem; color: #64748b;">Anda Sudah Booking</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const maxBookingDays = <?php echo $max_booking_days; ?>;
        const currentUserId = <?php echo $user_id; ?>;
        
        document.getElementById('start_time').addEventListener('change', function() {
            const startTime = this.value;
            const endTimeSelect = document.getElementById('end_time');
            
            if (startTime) {
                Array.from(endTimeSelect.options).forEach(option => {
                    if (option.value && option.value <= startTime) {
                        option.disabled = true;
                    } else {
                        option.disabled = false;
                    }
                });
                
                if (endTimeSelect.value && endTimeSelect.value <= startTime) {
                    endTimeSelect.value = '';
                }
            }
        });
        
        // Function untuk cek ketersediaan waktu
        function checkAvailability() {
            const roomId = document.getElementById('room_id').value;
            const date = document.getElementById('date').value;
            
            if (!roomId || !date) {
                document.getElementById('timeSlotsGrid').style.display = 'none';
                document.getElementById('noAvailability').style.display = 'block';
                document.getElementById('loadingSpinner').classList.remove('active');
                return;
            }
            
            document.getElementById('loadingSpinner').classList.add('active');
            document.getElementById('noAvailability').style.display = 'none';
            document.getElementById('timeSlotsGrid').style.display = 'none';
            
            // AJAX request untuk cek ketersediaan dengan informasi user
            fetch(`check_availability.php?room_id=${roomId}&date=${date}&user_id=${currentUserId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loadingSpinner').classList.remove('active');
                    
                    if (data.slots && data.slots.length > 0) {
                        const grid = document.getElementById('timeSlotsGrid');
                        grid.innerHTML = '';
                        
                        data.slots.forEach(slot => {
                            const slotDiv = document.createElement('div');
                            
                            let statusClass = 'available';
                            let statusText = 'Tersedia';
                            let slotClass = '';
                            
                            // Cek jika user sudah booking di jam ini
                            if (slot.user_has_booking) {
                                statusClass = 'user-booked';
                                statusText = 'Anda Sudah Booking';
                                slotClass = 'user-has-booking';
                            } else if (slot.status === 'approved') {
                                statusClass = 'approved';
                                statusText = 'Dibooking';
                                slotClass = 'booked';
                            } else if (slot.status === 'pending') {
                                statusClass = 'pending';
                                statusText = 'Pending';
                                slotClass = 'pending-approval';
                            }
                            
                            slotDiv.className = 'time-slot ' + slotClass;
                            
                            let bookedInfo = '';
                            if (slot.user_has_booking) {
                                bookedInfo = `<div class="booked-info">Di ruangan: ${slot.user_booking_room}</div>`;
                            } else if (slot.booked_by) {
                                bookedInfo = `<div class="booked-info">Dibooking oleh: ${slot.booked_by}</div>`;
                            }
                            
                            slotDiv.innerHTML = `
                                <div style="flex: 1;">
                                    <span class="time">${slot.time}</span>
                                    ${bookedInfo}
                                </div>
                                <span class="status ${statusClass}">${statusText}</span>
                            `;
                            
                            grid.appendChild(slotDiv);
                        });
                        
                        grid.style.display = 'grid';
                    } else {
                        document.getElementById('noAvailability').innerHTML = 'Tidak ada data ketersediaan';
                        document.getElementById('noAvailability').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('loadingSpinner').classList.remove('active');
                    document.getElementById('noAvailability').innerHTML = 'Terjadi kesalahan saat memuat data';
                    document.getElementById('noAvailability').style.display = 'block';
                });
        }
        
        // Event listeners
        document.getElementById('room_id').addEventListener('change', checkAvailability);
        document.getElementById('date').addEventListener('change', function() {
            // Validasi tanggal maksimal sesuai setting database
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            const maxDate = new Date();
            maxDate.setDate(maxDate.getDate() + maxBookingDays);
            maxDate.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                alert('Tanggal booking tidak boleh di masa lampau!');
                this.value = '';
                return;
            }
            
            if (selectedDate > maxDate) {
                alert(`Pemesanan maksimal ${maxBookingDays} hari dari hari ini!`);
                this.value = '';
                return;
            }
            
            checkAvailability();
        });
    </script>
</body>
</html>