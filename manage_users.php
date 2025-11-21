<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

// Buat tabel user_roles jika belum ada (untuk multiple roles)
$pdo->exec("
    CREATE TABLE IF NOT EXISTS user_roles (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        role VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_role (user_id, role),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");

// Proses tambah user baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Semua field harus diisi!";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = "Email sudah terdaftar!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, '')");
            
            if ($stmt->execute([$name, $email, $hashed_password])) {
                $success = "User berhasil ditambahkan! Silakan atur role untuk user ini.";
            } else {
                $error = "Terjadi kesalahan. Silakan coba lagi.";
            }
        }
    }
}

// Proses tambah role ke user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_role'])) {
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    
    if (empty($role)) {
        $error = "Role harus dipilih!";
    } else {
        try {
            // Cek apakah role sudah ada
            $stmt = $pdo->prepare("SELECT id FROM user_roles WHERE user_id = ? AND role = ?");
            $stmt->execute([$user_id, $role]);
            
            if ($stmt->fetch()) {
                $error = "Role ini sudah ditambahkan untuk user tersebut!";
            } else {
                // Tambah role ke tabel user_roles
                $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, ?)");
                $stmt->execute([$user_id, $role]);
                
                // Update role utama di tabel users jika masih kosong
                $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if (empty($user['role'])) {
                    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $stmt->execute([$role, $user_id]);
                }
                
                $success = "Role berhasil ditambahkan!";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan saat menambahkan role.";
        }
    }
}

// Proses hapus role
if (isset($_GET['delete_role'])) {
    $user_id = $_GET['user_id'];
    $role = $_GET['role'];
    
    try {
        // Hapus dari user_roles
        $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ? AND role = ?");
        $stmt->execute([$user_id, $role]);
        
        // Ambil semua role yang tersisa
        $stmt = $pdo->prepare("SELECT role FROM user_roles WHERE user_id = ? ORDER BY created_at ASC LIMIT 1");
        $stmt->execute([$user_id]);
        $remaining_role = $stmt->fetch();
        
        // Update role utama di users
        if ($remaining_role) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$remaining_role['role'], $user_id]);
        } else {
            // Jika tidak ada role tersisa, kosongkan
            $stmt = $pdo->prepare("UPDATE users SET role = '' WHERE id = ?");
            $stmt->execute([$user_id]);
        }
        
        $success = "Role berhasil dihapus!";
    } catch (PDOException $e) {
        $error = "Terjadi kesalahan saat menghapus role.";
    }
}

// Hapus user
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    if ($user_id == $_SESSION['user_id']) {
        $error = "Tidak dapat menghapus akun sendiri!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $success = "User berhasil dihapus!";
        } else {
            $error = "Terjadi kesalahan saat menghapus user.";
        }
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

// Ambil semua role untuk setiap user
$user_roles = [];
foreach ($users as $user) {
    $stmt = $pdo->prepare("SELECT role FROM user_roles WHERE user_id = ? ORDER BY created_at ASC");
    $stmt->execute([$user['id']]);
    $user_roles[$user['id']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Sistem Penjadwalan</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .user-form {
            max-width: 100%;
        }
        
        .role-management {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 2px dashed #cbd5e1;
        }
        
        .role-management h3 {
            color: #1e3a8a;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .search-user {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 40px 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #4f46e5;
        }
        
        .search-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        
        .user-results {
            max-height: 500px;
            overflow-y: auto;
            margin-top: 1rem;
        }
        
        .user-result-item {
            padding: 1.5rem;
            background: white;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .user-result-item:hover {
            border-color: #4f46e5;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
        }
        
        .user-result-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .user-result-info {
            flex: 1;
        }
        
        .user-result-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
        }
        
        .user-result-email {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .current-roles {
            margin-bottom: 1rem;
        }
        
        .current-roles-label {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .roles-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .role-badge-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            color: white;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
        }
        
        .role-badge-item .remove-role {
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.2s ease;
        }
        
        .role-badge-item .remove-role:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        
        .no-roles {
            color: #94a3b8;
            font-style: italic;
            font-size: 0.9rem;
        }
        
        .add-role-form {
            display: flex;
            gap: 0.75rem;
            align-items: end;
        }
        
        .add-role-form select {
            flex: 1;
            padding: 10px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95rem;
            background: white;
            transition: all 0.3s ease;
        }
        
        .add-role-form select:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .btn-add-role {
            padding: 10px 20px;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .btn-add-role:hover {
            background: linear-gradient(135deg, #4338ca, #4f46e5);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        
        .no-results {
            text-align: center;
            padding: 2rem;
            color: #64748b;
            font-style: italic;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .users-table th,
        .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .users-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #334155;
        }
        
        .user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar.small {
            width: 35px;
            height: 35px;
            font-size: 0.9rem;
        }
        
        .role-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
            display: inline-block;
            margin: 2px;
        }
        
        .role-badge.admin { background: #4f46e5; }
        .role-badge.kepala_sekolah { background: #f59e0b; }
        .role-badge.admin_ruangan { background: #10b981; }
        .role-badge.guru_lab { background: #8b5cf6; }
        .role-badge.guru { background: #64748b; }
        .role-badge.guru_pramuka,
        .role-badge.guru_olahraga,
        .role-badge.guru_silat,
        .role-badge.guru_marching_band,
        .role-badge.guru_paskibra { background: #06b6d4; }
        .role-badge.siswa { background: #ec4899; }
        .role-badge.empty { background: #94a3b8; }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        
        .text-muted {
            color: #94a3b8;
            font-style: italic;
        }
        
        .role-form-group {
            margin-bottom: 0.75rem;
        }
        
        .role-form-group label {
            display: block;
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 0.25rem;
            font-weight: 500;
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
                <h1>Kelola Pengguna</h1>
                <div class="header-actions">
                    <span>Total: <?php echo count($users); ?> pengguna</span>
                </div>
            </header>
            
            <div class="content">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Form Tambah User -->
                <div class="card">
                    <h2>Tambah Pengguna Baru</h2>
                    <form method="POST" class="user-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Nama Lengkap</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required minlength="6">
                            <small>Password minimal 6 karakter</small>
                        </div>
                        
                        <button type="submit" name="add_user" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Tambah Pengguna
                        </button>
                    </form>
                </div>
                
                <!-- Atur Role User -->
                <div class="card">
                    <h2>Atur Role Pengguna (Multi-Role Support)</h2>
                    <div class="role-management">
                        <h3>
                            <i class="fas fa-user-tag"></i>
                            Kelola Role Pengguna
                        </h3>
                        
                        <div class="search-user">
                            <input 
                                type="text" 
                                id="searchUser" 
                                class="search-input" 
                                placeholder="Ketik nama atau email pengguna..."
                            >
                            <i class="fas fa-search search-icon"></i>
                        </div>
                        
                        <div class="user-results" id="userResults">
                            <?php
                            $users_for_role = $pdo->query("SELECT * FROM users ORDER BY name ASC")->fetchAll();
                            
                            if (count($users_for_role) > 0):
                            ?>
                                <?php foreach ($users_for_role as $user): ?>
                                    <div class="user-result-item" data-name="<?php echo strtolower($user['name']); ?>" data-email="<?php echo strtolower($user['email']); ?>">
                                        <div class="user-result-header">
                                            <div class="user-result-info">
                                                <div class="user-result-name">
                                                    <?php echo htmlspecialchars($user['name']); ?>
                                                </div>
                                                <div class="user-result-email">
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Current Roles -->
                                        <div class="current-roles">
                                            <div class="current-roles-label">
                                                <i class="fas fa-tags"></i> Role saat ini:
                                            </div>
                                            <div class="roles-list">
                                                <?php if (!empty($user_roles[$user['id']])): ?>
                                                    <?php foreach ($user_roles[$user['id']] as $role): ?>
                                                        <div class="role-badge-item">
                                                            <span><?php echo ucfirst(str_replace('_', ' ', $role)); ?></span>
                                                            <span class="remove-role" onclick="confirmDeleteRole(<?php echo $user['id']; ?>, '<?php echo $role; ?>', '<?php echo htmlspecialchars($user['name']); ?>')">
                                                                <i class="fas fa-times"></i>
                                                            </span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="no-roles">Belum ada role</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Add Role Form -->
                                        <form method="POST" class="add-role-form">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            
                                            <div class="role-form-group" style="flex: 1;">
                                                <label>Tambah Role Baru</label>
                                                <select name="role" required>
                                                    <option value="">Pilih Role</option>
                                                    <option value="admin">Admin</option>
                                                    <option value="kepala_sekolah">Kepala Sekolah</option>
                                                    <option value="admin_ruangan">Admin Ruangan</option>
                                                    <option value="guru_lab">Guru Lab</option>
                                                    <option value="guru">Guru</option>
                                                    <option value="guru_pramuka">Guru Pramuka</option>
                                                    <option value="guru_olahraga">Guru Olahraga</option>
                                                    <option value="guru_silat">Guru Silat</option>
                                                    <option value="guru_marching_band">Guru Marching Band</option>
                                                    <option value="guru_paskibra">Guru Paskibra</option>
                                                    <option value="siswa">Siswa</option>
                                                </select>
                                            </div>
                                            
                                            <button type="submit" name="add_role" class="btn-add-role">
                                                <i class="fas fa-plus"></i> Tambah
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-results">Belum ada pengguna terdaftar</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Daftar User -->
                <div class="card">
                    <h2>Daftar Pengguna</h2>
                    <div class="table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar small">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <?php echo htmlspecialchars($user['name']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php if (!empty($user_roles[$user['id']])): ?>
                                                <?php foreach ($user_roles[$user['id']] as $role): ?>
                                                    <span class="role-badge <?php echo $role; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $role)); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="role-badge empty">Belum diatur</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="manage_users.php?delete=<?php echo $user['id']; ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Yakin ingin menghapus user ini?')">
                                                    Hapus
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Akun Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Live search functionality
        const searchInput = document.getElementById('searchUser');
        const userItems = document.querySelectorAll('.user-result-item');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            userItems.forEach(item => {
                const name = item.getAttribute('data-name');
                const email = item.getAttribute('data-email');
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
            
            const visibleItems = Array.from(userItems).filter(item => item.style.display !== 'none');
            
            if (visibleItems.length === 0 && searchTerm !== '') {
                if (!document.getElementById('noResultsMessage')) {
                    const noResults = document.createElement('div');
                    noResults.id = 'noResultsMessage';
                    noResults.className = 'no-results';
                    noResults.textContent = 'Tidak ada pengguna yang ditemukan';
                    document.getElementById('userResults').appendChild(noResults);
                }
            } else {
                const noResultsMsg = document.getElementById('noResultsMessage');
                if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            }
        });
        
        // Confirm delete role
        function confirmDeleteRole(userId, role, userName) {
            const roleName = role.replace(/_/g, ' ');
            if (confirm(`Apakah Anda yakin ingin menghapus role "${roleName}" dari ${userName}?`)) {
                window.location.href = `manage_users.php?delete_role=1&user_id=${userId}&role=${role}`;
            }
        }
        
        // Highlight on hover
        document.querySelectorAll('.remove-role').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.closest('.role-badge-item').style.opacity = '0.8';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.closest('.role-badge-item').style.opacity = '1';
            });
        });
    </script>
</body>
</html>