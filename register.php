<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $error = "Semua field harus diisi!";
    } elseif ($password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = "Email sudah terdaftar!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $email, $hashed_password, $role])) {
                $success = "Registrasi berhasil! Silakan login.";
            } else {
                $error = "Terjadi kesalahan. Silakan coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Sistem Penjadwalan</title>
    <link rel="stylesheet" href="assets/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Daftar Akun Baru</h1>
                <p>Buat akun untuk mulai menggunakan sistem penjadwalan</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form" id="registerForm">
                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-user"></i>
                        Nama Lengkap
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                        placeholder="Masukkan nama lengkap Anda"
                        required
                        autocomplete="name"
                    >
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Alamat Email
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                        placeholder="Masukkan alamat email Anda"
                        required
                        autocomplete="email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="role">
                        <i class="fas fa-user-tag"></i>
                        Pilih Peran
                    </label>
                    <select id="role" name="role" required>
                        <option value="">Pilih Peran Anda</option>
                        <option value="guru" <?php echo isset($_POST['role']) && $_POST['role'] == 'guru' ? 'selected' : ''; ?>>
                            👨‍🏫 Guru
                        </option>
                        <option value="guru_pramuka" <?php echo isset($_POST['role']) && $_POST['role'] == 'guru_pramuka' ? 'selected' : ''; ?>>
                            🏕️ Guru Pramuka
                        </option>
                        <option value="guru_olahraga" <?php echo isset($_POST['role']) && $_POST['role'] == 'guru_olahraga' ? 'selected' : ''; ?>>
                            ⚽ Guru Olahraga
                        </option>
                        <option value="guru_silat" <?php echo isset($_POST['role']) && $_POST['role'] == 'guru_silat' ? 'selected' : ''; ?>>
                            🥋 Guru Silat
                        </option>
                        <option value="guru_marching_band" <?php echo isset($_POST['role']) && $_POST['role'] == 'guru_marching_band' ? 'selected' : ''; ?>>
                            🎺 Guru Marching Band
                        </option>
                        <option value="guru_paskibra" <?php echo isset($_POST['role']) && $_POST['role'] == 'guru_paskibra' ? 'selected' : ''; ?>>
                            🎖️ Guru Paskibra
                        </option>
                        <option value="siswa" <?php echo isset($_POST['role']) && $_POST['role'] == 'siswa' ? 'selected' : ''; ?>>
                            🎓 Siswa
                        </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Kata Sandi
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Masukkan kata sandi (minimal 6 karakter)"
                        required 
                        minlength="6"
                        autocomplete="new-password"
                    >
                    <button type="button" class="password-toggle" id="passwordToggle">
                        <i class="fas fa-eye"></i>
                    </button>
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span class="strength-text" id="strengthText">Kekuatan kata sandi</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i>
                        Konfirmasi Kata Sandi
                    </label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        placeholder="Konfirmasi kata sandi Anda"
                        required 
                        minlength="6"
                        autocomplete="new-password"
                    >
                    <button type="button" class="password-toggle" id="confirmPasswordToggle">
                        <i class="fas fa-eye"></i>
                    </button>
                    <div class="password-match" id="passwordMatch">
                        <i class="fas fa-check" id="matchIcon"></i>
                        <span id="matchText">Kata sandi cocok</span>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="terms-agreement">
                        <input type="checkbox" name="terms" id="terms" required>
                        Saya menyetujui <a href="#" class="terms-link">Syarat & Ketentuan</a> dan <a href="#" class="terms-link">Kebijakan Privasi</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" id="registerButton">
                    <i class="fas fa-user-plus"></i>
                    Daftar Akun Baru
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordInput = document.getElementById('password');
        const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
        
        confirmPasswordToggle.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        // Password strength indicator
        const passwordStrength = document.getElementById('passwordStrength');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let text = '';
            let color = '';
            let width = '0%';
            
            if (password.length > 0) {
                if (password.length >= 6) strength++;
                if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
                if (password.match(/\d/)) strength++;
                if (password.match(/[^a-zA-Z\d]/)) strength++;
                
                switch (strength) {
                    case 1:
                        text = 'Lemah';
                        color = '#ef4444';
                        width = '25%';
                        break;
                    case 2:
                        text = 'Cukup';
                        color = '#f59e0b';
                        width = '50%';
                        break;
                    case 3:
                        text = 'Baik';
                        color = '#10b981';
                        width = '75%';
                        break;
                    case 4:
                        text = 'Kuat';
                        color = '#059669';
                        width = '100%';
                        break;
                    default:
                        text = 'Kekuatan kata sandi';
                        color = '#6b7280';
                        width = '0%';
                }
            } else {
                text = 'Kekuatan kata sandi';
                color = '#6b7280';
                width = '0%';
            }
            
            strengthFill.style.width = width;
            strengthFill.style.backgroundColor = color;
            strengthText.textContent = text;
            strengthText.style.color = color;
        });

        // Password match validation
        const passwordMatch = document.getElementById('passwordMatch');
        const matchIcon = document.getElementById('matchIcon');
        const matchText = document.getElementById('matchText');
        
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword.length === 0) {
                passwordMatch.style.display = 'none';
                return;
            }
            
            passwordMatch.style.display = 'flex';
            
            if (password === confirmPassword && password.length >= 6) {
                matchIcon.className = 'fas fa-check';
                matchIcon.style.color = '#10b981';
                matchText.textContent = 'Kata sandi cocok';
                matchText.style.color = '#10b981';
            } else {
                matchIcon.className = 'fas fa-times';
                matchIcon.style.color = '#ef4444';
                matchText.textContent = 'Kata sandi tidak cocok';
                matchText.style.color = '#ef4444';
            }
        }
        
        passwordInput.addEventListener('input', checkPasswordMatch);
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);

        // Form submission handling
        const registerForm = document.getElementById('registerForm');
        const registerButton = document.getElementById('registerButton');
        const termsCheckbox = document.getElementById('terms');
        
        registerForm.addEventListener('submit', function(e) {
            if (!termsCheckbox.checked) {
                e.preventDefault();
                alert('Anda harus menyetujui Syarat & Ketentuan untuk melanjutkan.');
                return;
            }
            
            // Add loading state
            registerButton.classList.add('loading');
            registerButton.innerHTML = '<i class="fas fa-spinner"></i> Membuat Akun...';
            registerButton.disabled = true;
        });

        // Real-time validation
        const inputs = document.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '' && this.hasAttribute('required')) {
                    this.style.borderColor = '#ef4444';
                } else {
                    this.style.borderColor = '#10b981';
                }
            });
            
            input.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    this.style.borderColor = '#4f46e5';
                }
            });
        });

        // Role selection styling
        const roleSelect = document.getElementById('role');
        roleSelect.addEventListener('change', function() {
            if (this.value) {
                this.style.borderColor = '#10b981';
                this.style.background = "linear-gradient(45deg, #f0f9ff, #ffffff)";
            }
        });
    </script>
</body>
</html>