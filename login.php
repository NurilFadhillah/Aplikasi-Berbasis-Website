<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Email atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Penjadwalan</title>
    <link rel="stylesheet" href="assets/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Masuk ke Akun</h1>
                <p>Selamat datang kembali! Silakan masuk ke akun Anda</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form" id="loginForm">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Alamat Email
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="masukkan email Anda"
                        required
                        autocomplete="email"
                    >
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
                        placeholder="masukkan kata sandi Anda"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="password-toggle" id="passwordToggle">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" id="remember">
                        Ingat saya
                    </label>
                    <a href="forgot_password.php" class="forgot-link">
                        Lupa Kata Sandi?
                    </a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" id="loginButton">
                    <i class="fas fa-sign-in-alt"></i>
                    Masuk ke Sistem
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordInput = document.getElementById('password');
        
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        // Form submission handling
        const loginForm = document.getElementById('loginForm');
        const loginButton = document.getElementById('loginButton');
        
        loginForm.addEventListener('submit', function() {
            // Add loading state
            loginButton.classList.add('loading');
            loginButton.innerHTML = '<i class="fas fa-spinner"></i> Memproses...';
            loginButton.disabled = true;
        });

        // Remember me functionality
        const rememberCheckbox = document.getElementById('remember');
        const emailInput = document.getElementById('email');
        
        // Check if there's saved email
        const savedEmail = localStorage.getItem('rememberedEmail');
        if (savedEmail) {
            emailInput.value = savedEmail;
            rememberCheckbox.checked = true;
        }
        
        // Save email if remember me is checked
        rememberCheckbox.addEventListener('change', function() {
            if (this.checked && emailInput.value) {
                localStorage.setItem('rememberedEmail', emailInput.value);
            } else {
                localStorage.removeItem('rememberedEmail');
            }
        });

        // Auto-save email when typing
        emailInput.addEventListener('input', function() {
            if (rememberCheckbox.checked && this.value) {
                localStorage.setItem('rememberedEmail', this.value);
            }
        });
    </script>
</body>
</html>