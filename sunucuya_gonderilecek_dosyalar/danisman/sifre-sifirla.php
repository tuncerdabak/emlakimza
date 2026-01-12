<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Zaten giriş yapmışsa yönlendir
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Token kontrolü
$token_data = null;
if (!empty($token)) {
    $token_data = verifyPasswordResetToken($token);
    if (!$token_data) {
        $error = 'Geçersiz veya süresi dolmuş şifre sıfırlama linki.';
    }
}

// Şifre sıfırlama formu gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $token = $_POST['token'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Şifreler eşleşmiyor.';
    } else {
        $result = resetPassword($token, $new_password);

        if ($result['success']) {
            $success = $result['message'];
            $token_data = null; // Formu gizle
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırla - emlakimza.com</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: white;
            font-size: 40px;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            margin-bottom: 15px;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            color: white;
            font-weight: 600;
            width: 100%;
        }

        .btn-reset:hover {
            opacity: 0.9;
        }

        h4 {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }

        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: -10px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="logo">
            <i class="bi bi-shield-lock"></i>
        </div>

        <h4>emlakimza.com</h4>
        <p class="subtitle">Yeni Şifre Belirleyin</p>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <div class="text-center mt-3">
                <a href="login.php" class="btn btn-reset">
                    <i class="bi bi-box-arrow-in-right"></i> Giriş Yap
                </a>
            </div>
        <?php elseif ($token_data): ?>
            <div class="alert alert-info">
                <small><i class="bi bi-person"></i> <?php echo htmlspecialchars($token_data['isim']); ?></small>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="input-group mb-3">
                    <span class="input-group-text">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" class="form-control" name="password" id="password" placeholder="Yeni şifreniz"
                        required minlength="6">
                </div>
                <div class="password-strength" id="strength-bar"></div>

                <div class="input-group mb-3">
                    <span class="input-group-text">
                        <i class="bi bi-lock-fill"></i>
                    </span>
                    <input type="password" class="form-control" name="confirm_password" placeholder="Yeni şifreniz (tekrar)"
                        required minlength="6">
                </div>

                <button type="submit" name="reset_password" class="btn-reset">
                    <i class="bi bi-check-circle"></i> Şifremi Güncelle
                </button>
            </form>
        <?php endif; ?>

        <hr class="my-4">

        <div class="text-center">
            <small class="text-muted">
                <a href="login.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Giriş sayfasına
                    dön</a>
            </small>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">
                © 2025 emlakimza.com. Tüm hakları saklıdır.
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Şifre gücü göstergesi
        const password = document.getElementById('password');
        const strengthBar = document.getElementById('strength-bar');

        if (password && strengthBar) {
            password.addEventListener('input', function () {
                const val = this.value;
                let strength = 0;

                if (val.length >= 6) strength++;
                if (val.length >= 10) strength++;
                if (/[a-z]/.test(val) && /[A-Z]/.test(val)) strength++;
                if (/\d/.test(val)) strength++;
                if (/[^a-zA-Z\d]/.test(val)) strength++;

                const colors = ['#dc3545', '#ffc107', '#28a745'];
                const widths = ['33%', '66%', '100%'];

                if (strength <= 1) {
                    strengthBar.style.backgroundColor = colors[0];
                    strengthBar.style.width = widths[0];
                } else if (strength <= 3) {
                    strengthBar.style.backgroundColor = colors[1];
                    strengthBar.style.width = widths[1];
                } else {
                    strengthBar.style.backgroundColor = colors[2];
                    strengthBar.style.width = widths[2];
                }
            });
        }
    </script>
</body>

</html>