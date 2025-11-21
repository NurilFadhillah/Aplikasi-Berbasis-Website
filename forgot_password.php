<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';
$step = isset($_GET['step']) ? $_GET['step'] : 'request';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step === 'request') {
        $email = trim($_POST['email']);
        
        if (empty($email)) {
            $error = "Email harus diisi!";
        } else {
            $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $reset_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
                if ($stmt->execute([$reset_token, $expires_at, $email])) {
                    header("Location: forgot_password.php?step=reset&token=" . $reset_token);
                    exit();
                } else {
                    $error = "Terjadi kesalahan. Silakan coba lagi.";
                }
            } else {
                $error = "Email tidak terdaftar!";
            }
        }
    } elseif ($step === 'reset') {
        $token = $_POST['token'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($new_password) || empty($confirm_password)) {
            $error = "Semua field harus diisi!";
        } elseif ($new_password !== $confirm_password) {
            $error = "Password baru dan konfirmasi password tidak cocok!";
        } elseif (strlen($new_password) < 6) {
            $error = "Password minimal 6 karakter!";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if ($user) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
                
                if ($stmt->execute([$hashed_password, $token])) {
                    $success = "Password berhasil direset! Silakan login dengan password baru.";
                } else {
                    $error = "Terjadi kesalahan. Silakan coba lagi.";
                }
            } else {
                $error = "Token tidak valid atau sudah kadaluarsa!";
            }
        }
    }
}

if ($step === 'reset' && isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = "Token tidak valid atau sudah kadaluarsa!";
        $step = 'request';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Sistem Penjadwalan</title>
    <link rel="stylesheet" href="assets/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Reset Password</h1>
                <p><?php echo $step === 'request' ? 'Masukkan email untuk memulai reset password' : 'Buat password baru Anda'; ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($step === 'request'): ?>
                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Kirim Link Reset</button>
                </form>
                
                <div class="auth-footer">
                    <p>Ingat password? <a href="login.php">Masuk di sini</a></p>
                </div>
                
            <?php elseif ($step === 'reset'): ?>
                <form method="POST" class="auth-form">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                    
                    <div class="form-group">
                        <label for="new_password">Password Baru</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password Baru</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                </form>
                
                <div class="auth-footer">
                    <p><a href="forgot_password.php">Kembali ke permintaan reset</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>