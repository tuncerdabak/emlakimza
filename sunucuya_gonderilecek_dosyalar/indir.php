<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uygulamayı İndir - Emlakİmza</title>
    <link rel="icon" type="image/png" href="favicon.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            dark: '#0f172a',
                            primary: '#0ea5e9',
                            secondary: '#14b8a6',
                            accent: '#10b981',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #0f172a;
            color: white;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 bg-brand-dark">

    <div class="max-w-md w-full text-center">
        <!-- Logo -->
        <div class="flex items-center justify-center gap-2 mb-8">
             <svg class="w-10 h-10 text-brand-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            <span class="font-bold text-3xl tracking-tight text-white">emlak<span class="text-brand-secondary">imza.com</span></span>
        </div>

        <div class="glass-card p-8 rounded-2xl shadow-xl border border-white/10">
            <h1 class="text-2xl font-bold mb-4">Android Uygulamasını İndir</h1>
            <p class="text-gray-400 mb-8">Daha hızlı ve pratik bir deneyim için uygulamamızı indirin.</p>

            <div class="mb-8">
                <a href="android_uygulama/emlakimza_v1.apk" class="inline-flex items-center justify-center w-full px-6 py-4 text-base font-bold text-brand-dark bg-brand-secondary rounded-xl hover:bg-brand-accent transition-all shadow-lg hover:shadow-brand-secondary/30 transform hover:-translate-y-1">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    APK İndir (v1.0)
                </a>
            </div>

            <div class="text-left bg-white/5 p-4 rounded-xl text-sm text-gray-400">
                <h3 class="font-bold text-white mb-2 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Kurulum İpucu
                </h3>
                <p class="mb-2">İndirme tamamlandığında dosyayı açın. Eğer "Bilinmeyen Kaynaklar" uyarısı alırsanız:</p>
                <ol class="list-decimal list-inside space-y-1 ml-1 text-gray-500">
                    <li>Ayarlar'a gidin.</li>
                    <li>"Bu kaynaktan izin ver" seçeneğini aktif edin.</li>
                    <li>Geri dönüp Yükle butonuna basın.</li>
                </ol>
            </div>
        </div>

        <div class="mt-8 text-sm text-gray-500">
            <a href="index.php" class="hover:text-white transition-colors">← Ana Sayfaya Dön</a>
        </div>
    </div>

</body>
</html>
