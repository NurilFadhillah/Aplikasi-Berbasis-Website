<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Penjadwalan Ruangan Sekolah</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <h1>Sistem Penjadwalan Ruangan</h1>
                    <p>SMK BINA BANGSA</p>
                </div>
            </div>
        </header>
        
        <section class="hero">
            <div class="hero-content">
                <h2>Kelola Penjadwalan Ruangan dengan Efisien</h2>
                <p>Sistem digital terintegrasi untuk mengatur penggunaan ruang rapat, laboratorium, dan kelas di institusi pendidikan Anda dengan mudah dan terorganisir.</p>
                <div class="hero-buttons">
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        Masuk ke Sistem
                    </a>
                    <a href="register.php" class="btn btn-secondary">
                        <i class="fas fa-user-plus"></i>
                        Daftar Akun Baru
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-illustration">
                    <div class="illustration-content">
                        <i class="fas fa-school"></i>
                        <h3>Platform Digital Pendidikan</h3>
                        <p>Mendukung proses belajar mengajar yang lebih terstruktur dan efisien</p>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="stats">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Pengguna Aktif</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">50+</div>
                    <div class="stat-label">Ruangan Tersedia</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">1000+</div>
                    <div class="stat-label">Booking/Bulan</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">99%</div>
                    <div class="stat-label">Kepuasan Pengguna</div>
                </div>
            </div>
        </section>
        
        <section class="features">
            <h2>Fitur Unggulan</h2>
            <p class="features-subtitle">Platform lengkap dengan segala yang Anda butuhkan untuk manajemen ruangan yang optimal</p>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>Penjadwalan Intuitif</h3>
                    <p>Antarmuka yang mudah digunakan untuk menjadwalkan ruangan dengan drag & drop dan konfirmasi real-time</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3>Multi-Level Akses</h3>
                    <p>Sistem role-based dengan akses berbeda untuk admin, guru, siswa, dan staff dengan keamanan terjamin</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Notifikasi Real-Time</h3>
                    <p>Notifikasi otomatis untuk persetujuan booking, pengingat jadwal, dan update status secara real-time</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Analitik & Laporan</h3>
                    <p>Dashboard analitik lengkap dengan statistik penggunaan ruangan dan laporan periodik yang detail</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Responsive Design</h3>
                    <p>Akses sistem dari berbagai perangkat dengan tampilan yang optimal di desktop, tablet, dan smartphone</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Keamanan Data</h3>
                    <p>Protokol keamanan tingkat tinggi dengan enkripsi data dan backup otomatis untuk melindungi informasi</p>
                </div>
            </div>
        </section>
        
        <footer>
            <div class="footer-content">
                <div class="footer-logo">Sistem Penjadwalan Ruangan</div>
                <p>Platform digital terpercaya untuk manajemen ruangan institusi pendidikan</p>
                <div class="footer-links">
                    <a href="#"><i class="fas fa-info-circle"></i> Tentang Kami</a>
                    <a href="#"><i class="fas fa-envelope"></i> Kontak</a>
                    <a href="#"><i class="fas fa-shield-alt"></i> Kebijakan Privasi</a>
                    <a href="#"><i class="fas fa-file-alt"></i> Syarat & Ketentuan</a>
                </div>
                <div class="copyright">
                    &copy; 2024 Sistem Penjadwalan Ruangan Sekolah. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
</body>
</html>