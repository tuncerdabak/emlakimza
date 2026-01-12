<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$token = $_GET['token'] ?? '';
$db = getDB();

// Sözleşme bilgilerini al
$sozlesme_sql = "SELECT s.*, f.firma_adi, k.isim as danisman_adi, k.telefon as danisman_telefon
                 FROM sozlesmeler s
                 INNER JOIN whatsapp_linkleri wl ON s.id = wl.sozlesme_id
                 LEFT JOIN firmalar f ON s.firma_id = f.id
                 LEFT JOIN kullanicilar k ON s.danisman_id = k.id
                 WHERE wl.token = :token AND s.durum = 'COMPLETED'";

$sozlesme_stmt = $db->prepare($sozlesme_sql);
$sozlesme_stmt->execute([':token' => $token]);
$sozlesme = $sozlesme_stmt->fetch();

if (!$sozlesme) {
    die("Sözleşme bulunamadı!");
}

$musteri_bilgileri = json_decode($sozlesme['musteri_bilgileri'], true);
// Status kontrolü
$status = $_GET['status'] ?? 'success';
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $status == 'rejected' ? 'Sözleşme Reddedildi' : 'İşlem Başarılı'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg,
                    <?php echo $status == 'rejected' ? '#ff6b6b' : '#667eea'; ?>
                    0%,
                    <?php echo $status == 'rejected' ? '#c0392b' : '#764ba2'; ?>
                    100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            background:
                <?php echo $status == 'rejected' ? '#fdecea' : '#e7f3ff'; ?>
            ;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px 30px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }

        .btn-whatsapp {
            background: #25D366;
            border: none;
            border-radius: 10px;
            padding: 15px 30px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }

        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #f0f;
            position: absolute;
            animation: fall 3s linear infinite;
        }

        @keyframes fall {
            to {
                transform: translateY(100vh) rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="success-card">
        <div class="mb-4">
            <img src="../emlakimza.png" alt="emlakimza.com" style="height: 50px; width: auto;">
        </div>
        <div class="icon-circle">
            <i class="bi <?php echo $status == 'rejected' ? 'bi-x-lg' : 'bi-check-lg'; ?>"></i>
        </div>

        <?php if ($status == 'rejected'): ?>
            <h2 class="mb-3">Sözleşme Reddedildi</h2>
            <p class="text-muted mb-4">
                Bildiriminiz danışmana iletildi. <br>
                Herhangi bir işlem yapmanıza gerek yoktur.
            </p>
        <?php else: ?>
            <h2 class="mb-3">Tebrikler!</h2>
            <p class="text-muted mb-4">
                Yer gösterme belgesi başarıyla imzalandı. <br>
                Belgenin bir kopyası e-posta adresinize gönderilecektir.
            </p>

            <!-- Sözleşme Bilgileri -->
            <div class="alert alert-light text-start border bg-light mt-4">
                <div class="row">
                    <div class="col-12 mb-2">
                        <small class="text-muted d-block">Sözleşme No</small>
                        <strong><?php echo htmlspecialchars($sozlesme['islem_uuid']); ?></strong>
                    </div>
                </div>
            </div>

            <!-- İşlemler -->
            <div class="mt-4 d-grid gap-2">
                <?php if ($sozlesme['pdf_dosya_yolu']): ?>
                    <a href="../<?php echo htmlspecialchars($sozlesme['pdf_dosya_yolu']); ?>" class="btn btn-outline-primary"
                        target="_blank">
                        <i class="bi bi-download me-2"></i> Belgeyi İndir
                    </a>
                <?php endif; ?>

                <?php if ($sozlesme['danisman_telefon']): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $sozlesme['danisman_telefon']); ?>"
                        class="btn btn-success" target="_blank">
                        <i class="bi bi-whatsapp me-2"></i> Danışmanla İletişim
                    </a>
                <?php endif; ?>
            </div>

            <p class="text-muted small mt-4 mb-0">
                Zaman damgası: <?php echo date('d.m.Y H:i:s'); ?>
            </p>
        <?php endif; ?>

        <div class="mt-4">
            <a href="#" onclick="window.close()" class="btn btn-secondary px-4">
                Sayfayı Kapat
            </a>
        </div>
    </div>

    <?php if ($status != 'rejected'): ?>
        <script>
            // Konfeti animasyonu sadece başarılı işlemde
            function createConfetti() {
                const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff'];
                for (let i = 0; i < 50; i++) {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.animationDelay = Math.random() * 3 + 's';
                    confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                    document.body.appendChild(confetti);

                    setTimeout(() => confetti.remove(), 3000);
                }
            }
            createConfetti();
        </script>
    <?php endif; ?>
</body>

</html>