<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>emlakimza.com - Yer Gösterme Belgesinde Dijital Standart</title>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-H9LZ3EQXVJ"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'G-H9LZ3EQXVJ');
    </script>
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="description"
        content="Emlak danışmanları için dijital yer gösterme ve sözleşme platformu. WhatsApp üzerinden gönderin, müşteriniz saniyeler içinde telefonundan imzalasın.">
    <meta name="keywords"
        content="dijital imza, emlak imza, yer gösterme belgesi, dijital sözleşme, gayrimenkul teknolojileri, emlak yazılımı">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://emlakimza.com/">
    <meta property="og:title" content="emlakimza.com - Yer Gösterme Belgesinde Dijital Standart">
    <meta property="og:description"
        content="Kağıt israfına son! Yer gösterme belgelerini dijital ortamda hazırlayın ve WhatsApp ile anında imzalatın.">
    <meta property="og:image" content="https://emlakimza.com/emlakimza.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://emlakimza.com/">
    <meta property="twitter:title" content="emlakimza.com - Yer Gösterme Belgesinde Dijital Standart">
    <meta property="twitter:description"
        content="Kağıt israfına son! Yer gösterme belgelerini dijital ortamda hazırlayın ve WhatsApp ile anında imzalatın.">
    <meta property="twitter:image" content="https://emlakimza.com/emlakimza.png">

    <!-- Structured Data (JSON-LD) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "Emlakİmza",
      "operatingSystem": "All",
      "applicationCategory": "BusinessApplication",
      "description": "Real estate digital contract and signature platform for property viewing documents.",
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "TRY"
      }
    }
    </script>

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
                            dark: '#0f172a', // Slate 900 base
                            primary: '#0ea5e9', // Sky 500
                            secondary: '#14b8a6', // Teal 500
                            accent: '#10b981', // Emerald 500
                        }
                    }
                }
            }
        }
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <style>
        body {
            background-color: #0f172a;
            color: white;
            overflow-x: hidden;
        }

        #canvas-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: -1;
            opacity: 0.6;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .gradient-text {
            background: linear-gradient(135deg, #10b981, #0ea5e9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .step-line::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 15px;
            width: 2px;
            background: linear-gradient(to bottom, #10b981, #0ea5e9);
            z-index: 0;
        }
    </style>
</head>

<body class="antialiased">

    <div id="canvas-container"></div>

    <nav
        class="fixed w-full z-50 transition-all duration-300 bg-brand-dark/80 backdrop-blur-md border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex-shrink-0 flex items-center gap-2">
                    <svg class="w-8 h-8 text-brand-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    <a href="index.php" class="font-bold text-2xl tracking-tight text-white">emlak<span
                            class="text-brand-secondary">imza.com</span></a>
                </div>
                <!-- Desktop Menu -->
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-8">
                        <a href="#nasil-calisir"
                            class="hover:text-brand-secondary transition-colors px-3 py-2 rounded-md text-sm font-medium">Nasıl
                            Çalışır?</a>
                        <a href="#ozellikler"
                            class="hover:text-brand-secondary transition-colors px-3 py-2 rounded-md text-sm font-medium">Özellikler</a>
                        <a href="#referanslar"
                            class="hover:text-brand-secondary transition-colors px-3 py-2 rounded-md text-sm font-medium">Referanslar</a>
                        <a href="#iletisim"
                            class="hover:text-brand-secondary transition-colors px-3 py-2 rounded-md text-sm font-medium">İletişim</a>
                        <a href="giris"
                            class="bg-gradient-to-r from-brand-secondary to-brand-primary hover:opacity-90 text-white px-5 py-2.5 rounded-full text-sm font-medium transition-all shadow-lg shadow-brand-secondary/20">Giriş
                            Yap</a>
                    </div>
                </div>
                <!-- Mobile Menu Button -->
                <div class="-mr-2 flex md:hidden">
                    <button type="button" onclick="toggleMobileMenu()"
                        class="bg-gray-800 inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white">
                        <span class="sr-only">Menüyü Aç</span>
                        <!-- Heroicon name: outline/menu -->
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu, show/hide based on menu state. -->
        <div class="md:hidden hidden bg-brand-dark/95 backdrop-blur-xl border-b border-white/10 absolute w-full top-20 left-0"
            id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="#nasil-calisir"
                    class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Nasıl
                    Çalışır?</a>
                <a href="#ozellikler"
                    class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Özellikler</a>
                <a href="#referanslar"
                    class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Referanslar</a>
                <a href="#iletisim"
                    class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">İletişim</a>
                <a href="giris"
                    class="bg-brand-secondary text-brand-dark block px-3 py-2 rounded-md text-base font-bold mt-4 text-center">Giriş
                    Yap</a>
            </div>
        </div>
    </nav>

    <section class="relative h-screen flex items-center justify-center pt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <div class="inline-block mb-4 px-4 py-1.5 rounded-full glass-card border-brand-secondary/30">
                <span class="text-brand-secondary text-sm font-semibold tracking-wide uppercase">Kağıtsız & Hızlı
                    Çözüm</span>
            </div>

            <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
                Yer Göstermenin <br />
                <span class="gradient-text">Dijital Standardı</span>
            </h1>

            <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-400 mb-10 font-light">
                WhatsApp üzerinden gönderin, müşteriniz akıllı telefonuyla saniyeler içinde imzalasın. Kağıt israfına ve
                unutma derdine son verin.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="#iletisim"
                    class="group relative px-8 py-4 bg-white text-brand-dark font-bold rounded-full overflow-hidden transition-all hover:scale-105 shadow-[0_0_40px_-10px_rgba(20,184,166,0.5)]">
                    <span class="relative z-10 flex items-center gap-2">
                        Ücretsiz Deneyin
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </span>
                </a>
                <a href="#nasil-calisir"
                    class="px-8 py-4 glass-card text-white font-medium rounded-full hover:bg-white/10 transition-all border border-white/20">
                    Sistem Nasıl İşler?
                </a>
            </div>
        </div>

        <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 animate-bounce">
            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3">
                </path>
            </svg>
        </div>
    </section>

    <section id="ozellikler" class="py-24 bg-brand-dark relative">
        <div
            class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-brand-secondary/30 to-transparent">
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Neden <span
                        class="text-brand-secondary">Emlakİmza?</span></h2>
                <p class="text-gray-400 max-w-xl mx-auto">Geleneksel kağıt-kalem süreçlerini modernize ederek
                    satışlarınıza hız katın.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="glass-card p-8 rounded-2xl hover:border-brand-secondary/50 transition-colors group">
                    <div
                        class="w-14 h-14 rounded-xl bg-brand-secondary/10 flex items-center justify-center mb-6 group-hover:bg-brand-secondary/20 transition-colors">
                        <svg class="w-8 h-8 text-brand-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-white">Mobil İmza Teknolojisi</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Ekstra bir uygulamaya gerek yok. Müşterileriniz dokunmatik ekranlarını kullanarak parmaklarıyla
                        gerçek imzalarını atarlar.
                    </p>
                </div>

                <div class="glass-card p-8 rounded-2xl hover:border-brand-primary/50 transition-colors group">
                    <div
                        class="w-14 h-14 rounded-xl bg-brand-primary/10 flex items-center justify-center mb-6 group-hover:bg-brand-primary/20 transition-colors">
                        <svg class="w-8 h-8 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-white">Güvenli</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Sistem üzerinden oluşturulan belgeler zaman damgası ve IP kaydı ile birlikte saklanır, yer
                        gösterme kanıtı oluşturur.
                    </p>
                </div>

                <div class="glass-card p-8 rounded-2xl hover:border-brand-accent/50 transition-colors group">
                    <div
                        class="w-14 h-14 rounded-xl bg-brand-accent/10 flex items-center justify-center mb-6 group-hover:bg-brand-accent/20 transition-colors">
                        <svg class="w-8 h-8 text-brand-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-white">WhatsApp Entegrasyonu</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Linki kopyalayın ve WhatsApp'tan atın. E-posta zorunluluğu yok, karmaşık üyelik süreçleri yok.
                        Sadece hız var.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section id="nasil-calisir" class="py-24 relative overflow-hidden">
        <div class="absolute right-0 top-1/4 w-96 h-96 bg-brand-primary/10 rounded-full blur-3xl -z-10"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <h2 class="text-3xl md:text-4xl font-bold mb-8">Süreç Nasıl İşler?</h2>

                    <div class="space-y-12 relative step-line pl-4">
                        <div class="relative pl-8">
                            <div
                                class="absolute left-[-11px] top-1 w-6 h-6 rounded-full bg-brand-dark border-2 border-brand-secondary flex items-center justify-center z-10">
                                <div class="w-2 h-2 rounded-full bg-brand-secondary"></div>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Formu Oluşturun</h3>
                            <p class="text-gray-400">Portföy bilgilerini ve müşteri adını girerek saniyeler içinde
                                dijital yer gösterme belgesini hazırlayın.</p>
                        </div>

                        <div class="relative pl-8">
                            <div
                                class="absolute left-[-11px] top-1 w-6 h-6 rounded-full bg-brand-dark border-2 border-brand-primary flex items-center justify-center z-10">
                                <div class="w-2 h-2 rounded-full bg-brand-primary"></div>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Linki Paylaşın</h3>
                            <p class="text-gray-400">Oluşturulan güvenli linki müşterinize WhatsApp veya SMS yoluyla
                                iletin.</p>
                        </div>

                        <div class="relative pl-8">
                            <div
                                class="absolute left-[-11px] top-1 w-6 h-6 rounded-full bg-brand-dark border-2 border-brand-accent flex items-center justify-center z-10">
                                <div class="w-2 h-2 rounded-full bg-brand-accent"></div>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Dokunmatik İmza</h3>
                            <p class="text-gray-400">Müşteriniz ekrana parmağıyla imzasını atar. İmzalı belge anında PDF
                                olarak her iki tarafa da iletilir.</p>
                        </div>
                    </div>
                </div>

                <div
                    class="relative glass-card rounded-3xl p-6 md:p-10 border-t border-l border-white/10 transform rotate-1 hover:rotate-0 transition-transform duration-500">
                    <div class="bg-brand-dark rounded-xl p-4 shadow-2xl">
                        <div class="flex items-center justify-between mb-4 border-b border-gray-800 pb-2">
                            <div class="text-xs text-gray-500">Yer Gösterme Belgesi #2941</div>
                            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                        </div>
                        <div class="space-y-3 mb-6">
                            <div class="h-2 bg-gray-700 rounded w-3/4"></div>
                            <div class="h-2 bg-gray-700 rounded w-full"></div>
                            <div class="h-2 bg-gray-700 rounded w-5/6"></div>
                        </div>
                        <div
                            class="bg-gray-800 rounded-lg p-4 text-center border border-dashed border-gray-600 relative overflow-hidden">
                            <p class="text-xs text-gray-400 mb-2">Lütfen burayı imzalayınız</p>
                            <svg class="mx-auto w-32 h-16 text-brand-secondary" viewBox="0 0 200 100">
                                <path fill="none" stroke="currentColor" stroke-width="3"
                                    d="M20,50 Q50,20 80,50 T140,50 T180,30" stroke-dasharray="200"
                                    stroke-dashoffset="200">
                                    <animate attributeName="stroke-dashoffset" from="200" to="0" dur="2s"
                                        repeatCount="indefinite" fill="freeze" />
                                </path>
                            </svg>
                        </div>
                        <div class="mt-4">
                            <button
                                class="w-full py-2 bg-brand-secondary text-brand-dark font-bold rounded text-sm">Onayla
                                ve Tamamla</button>
                        </div>
                    </div>
                    <div class="absolute -top-5 -right-5 w-16 h-16 bg-brand-primary rounded-full blur-xl opacity-50">
                    </div>
                    <div class="absolute -bottom-5 -left-5 w-16 h-16 bg-brand-accent rounded-full blur-xl opacity-50">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="referanslar" class="py-24 relative overflow-hidden">
        <div class="absolute left-0 bottom-1/4 w-96 h-96 bg-brand-secondary/10 rounded-full blur-3xl -z-10"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <header class="inline-block mb-4 px-4 py-1.5 rounded-full glass-card border-brand-primary/30">
                    <span
                        class="text-brand-primary text-sm font-semibold tracking-wide uppercase">Referanslarımız</span>
                </header>
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Sektörün <span class="text-brand-secondary">Güvendiği
                        İsimler</span></h2>
                <p class="text-gray-400 max-w-xl mx-auto">Emlakİmza sistemini kullanarak süreçlerini dijitalleştiren ve
                    profesyonelleşen iş ortaklarımız.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Reference 1 -->
                <div
                    class="glass-card p-8 rounded-2xl text-center group hover:border-brand-secondary/50 transition-all duration-300 relative">
                    <div
                        class="absolute -top-3 -right-3 w-10 h-10 bg-brand-secondary/20 rounded-full flex items-center justify-center backdrop-blur-md border border-brand-secondary/30">
                        <svg class="w-5 h-5 text-brand-secondary" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </div>
                    <div
                        class="w-24 h-24 mx-auto mb-6 rounded-2xl overflow-hidden border border-white/10 group-hover:border-brand-secondary/50 transition-all duration-300 transform group-hover:scale-110">
                        <img src="images/demir_grup.png" alt="Demir Grup" class="w-full h-full object-cover">
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Demir Grup</h3>
                    <div class="w-12 h-0.5 bg-brand-secondary/30 mx-auto mb-4 group-hover:w-20 transition-all"></div>
                    <p class="text-gray-400 text-sm leading-relaxed italic">"Süreçlerimiz inanılmaz hızlandı, kağıt
                        israfından ve takip derdinden tamamen kurtulduk."</p>
                </div>

                <!-- Reference 2 -->
                <div
                    class="glass-card p-8 rounded-2xl text-center group hover:border-brand-primary/50 transition-all duration-300 relative">
                    <div
                        class="absolute -top-3 -right-3 w-10 h-10 bg-brand-primary/20 rounded-full flex items-center justify-center backdrop-blur-md border border-brand-primary/30">
                        <svg class="w-5 h-5 text-brand-primary" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </div>
                    <div
                        class="w-24 h-24 mx-auto mb-6 rounded-2xl overflow-hidden border border-white/10 group-hover:border-brand-primary/50 transition-all duration-300 transform group-hover:scale-110">
                        <img src="images/idol_emlak.jpg" alt="İdol Emlak" class="w-full h-full object-cover">
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">İdol Emlak</h3>
                    <div class="w-12 h-0.5 bg-brand-primary/30 mx-auto mb-4 group-hover:w-20 transition-all"></div>
                    <p class="text-gray-400 text-sm leading-relaxed italic">"Müşterilerimiz WhatsApp üzerinden linke
                        tıklayıp anında imza atmayı çok pratik ve güvenilir buluyor."</p>
                </div>

                <!-- Reference 3 -->
                <div
                    class="glass-card p-8 rounded-2xl text-center group hover:border-brand-accent/50 transition-all duration-300 relative">
                    <div
                        class="absolute -top-3 -right-3 w-10 h-10 bg-brand-accent/20 rounded-full flex items-center justify-center backdrop-blur-md border border-brand-accent/30">
                        <svg class="w-5 h-5 text-brand-accent" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </div>
                    <div
                        class="w-24 h-24 mx-auto mb-6 rounded-2xl overflow-hidden border border-white/10 group-hover:border-brand-accent/50 transition-all duration-300 transform group-hover:scale-110">
                        <img src="images/tuncer-dabak.jpg" alt="Tuncer Dabak" class="w-full h-full object-cover">
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Tuncer Dabak</h3>
                    <div class="w-12 h-0.5 bg-brand-accent/30 mx-auto mb-4 group-hover:w-20 transition-all"></div>
                    <p class="text-gray-400 text-sm leading-relaxed italic">"Emlak ofisimiz için verdiğimiz en vizyoner
                        kararlardan biri bu sisteme geçmekti, kesinlikle tavsiye ediyorum."</p>
                </div>
            </div>
        </div>
    </section>

    <section id="packages" class="py-24 bg-brand-dark relative">
        <div
            class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-brand-secondary/30 to-transparent">
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Paket <span
                        class="text-brand-secondary">Seçenekleri</span></h2>
                <p class="text-gray-400 max-w-xl mx-auto">İhtiyacınıza ve hacminize en uygun dijital imza paketini
                    seçin.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">

                <div
                    class="glass-card p-8 rounded-2xl hover:border-brand-secondary/30 transition-all duration-300 relative group">
                    <div
                        class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-gray-500/50 to-transparent opacity-50 rounded-t-2xl">
                    </div>

                    <h3 class="text-xl font-semibold text-gray-300 mb-2">Ücretsiz</h3>
                    <div class="flex items-baseline mb-6">
                        <span class="text-4xl font-bold text-white">₺0</span>
                        <span class="text-gray-500 ml-2">/ ay</span>
                    </div>

                    <ul class="space-y-4 mb-8 text-gray-400 text-sm">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            Ayda 3 Belge
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            1 Kullanıcı
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            Temel Şablonlar
                        </li>
                    </ul>

                    <a href="giris"
                        class="block w-full py-3 px-4 rounded-xl border border-white/10 text-center text-white font-medium hover:bg-white/5 transition-colors">
                        Hemen Başla
                    </a>
                </div>

                <div
                    class="glass-card p-8 rounded-2xl border border-brand-primary/50 shadow-[0_0_40px_-10px_rgba(14,165,233,0.2)] transform md:-translate-y-4 relative z-10">
                    <div
                        class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-brand-primary text-brand-dark text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">
                        En Popüler
                    </div>

                    <h3 class="text-xl font-semibold text-brand-primary mb-2">Başlangıç</h3>
                    <div class="flex items-baseline mb-6">
                        <span class="text-5xl font-bold text-white">₺499</span>
                        <span class="text-gray-400 ml-2">/ ay</span>
                    </div>

                    <ul class="space-y-4 mb-8 text-gray-300 text-sm">
                        <li class="flex items-center text-white">
                            <svg class="w-5 h-5 text-brand-primary mr-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            Ayda 50 Belge
                        </li>
                        <li class="flex items-center text-white">
                            <svg class="w-5 h-5 text-brand-primary mr-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            3 Kullanıcı
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-brand-primary mr-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            WhatsApp Desteği
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-brand-primary mr-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            Logo Yükleme
                        </li>
                    </ul>

                    <a href="giris"
                        class="block w-full py-3 px-4 rounded-xl bg-gradient-to-r from-brand-secondary to-brand-primary text-white font-bold text-center hover:opacity-90 transition-opacity shadow-lg shadow-brand-primary/25">
                        Satın Al
                    </a>
                </div>

                <div
                    class="glass-card p-8 rounded-2xl hover:border-brand-accent/50 transition-all duration-300 relative group">
                    <div
                        class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-brand-accent/50 to-transparent opacity-50 rounded-t-2xl">
                    </div>

                    <h3 class="text-xl font-semibold text-brand-accent mb-2">Profesyonel</h3>
                    <div class="flex items-baseline mb-6">
                        <span class="text-4xl font-bold text-white">₺999</span>
                        <span class="text-gray-500 ml-2">/ ay</span>
                    </div>

                    <ul class="space-y-4 mb-8 text-gray-400 text-sm">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-brand-accent mr-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            Sınırsız Belge
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-brand-accent mr-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            10 Kullanıcı
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-brand-accent mr-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            Öncelikli Destek
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-brand-accent mr-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            Özel Filigran
                        </li>
                    </ul>

                    <a href="giris"
                        class="block w-full py-3 px-4 rounded-xl border border-brand-accent/30 text-center text-brand-accent font-medium hover:bg-brand-accent/10 transition-colors">
                        Satın Al
                    </a>
                </div>

            </div>
        </div>
    </section>

    <footer id="iletisim" class="py-12 bg-black/30 border-t border-white/5 text-center">
        <div class="max-w-4xl mx-auto px-4">
            <h2 class="text-2xl font-bold mb-6">Profesyonel Emlakçılar İçin Tasarlandı</h2>
            <p class="text-gray-400 mb-8">Hemen Emlakİmza ile tanışın, dijital çağın gerisinde kalmayın.</p>
            <p class="text-gray-400 mb-8">Tuncer DABAK 0 542 340 89 43</p>

            <form class="max-w-md mx-auto flex gap-2">
                <input type="email" placeholder="E-posta adresiniz"
                    class="flex-1 bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-secondary transition-colors">
                <button type="submit"
                    class="bg-brand-secondary hover:bg-brand-accent text-brand-dark font-bold px-6 py-3 rounded-lg transition-colors whitespace-nowrap">
                    Kayıt Ol
                </button>
            </form>

            <div class="mt-12 text-gray-500 text-sm flex justify-center gap-6 mb-4">
                <a href="hukuki-gecerlilik" class="hover:text-brand-secondary transition-colors">Hukuki Geçerlilik</a>
                <a href="#iletisim" class="hover:text-brand-secondary transition-colors">Kullanım Şartları</a>
                <a href="#iletisim" class="hover:text-brand-secondary transition-colors">Gizlilik Politikası</a>
            </div>

            <div class="text-gray-600 text-sm">
                &copy; 2024 Emlakİmza. Tüm hakları saklıdır.
            </div>

            <div class="mt-8">
                <img src="footer-alt.png" alt="Footer" class="mx-auto" style="max-width: 100%; height: auto;">
            </div>
        </div>
    </footer>

    <!-- Mobile App Banner -->
    <div style="padding-bottom: env(safe-area-inset-bottom);"
        class="fixed bottom-0 left-0 w-full z-[60] bg-brand-dark/95 backdrop-blur-md border-t border-white/10 p-4 mb-4 md:hidden">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="bg-white p-2 rounded-xl">
                    <img src="favicon.png" alt="Emlakİmza Logo" class="w-8 h-8">
                </div>
                <div>
                    <h3 class="font-bold text-white text-sm">Emlakİmza</h3>
                    <p class="text-xs text-brand-secondary">Mobil Uygulama</p>
                </div>
            </div>
            <a href="indir.php"
                class="bg-brand-secondary text-brand-dark font-bold px-4 py-2 rounded-lg text-sm whitespace-nowrap shadow-lg shadow-brand-secondary/20">
                Yükle
            </a>
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
            } else {
                menu.classList.add('hidden');
            }
        }

        const isMobile = window.innerWidth < 768;

        // Setup Scene
        const scene = new THREE.Scene();
        // Fog to blend background
        scene.fog = new THREE.FogExp2(0x0f172a, 0.002);

        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: !isMobile });

        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2)); // Limit pixel ratio for performance
        document.getElementById('canvas-container').appendChild(renderer.domElement);

        // Create Particles (Starfield / Data points)
        const particlesGeometry = new THREE.BufferGeometry();
        const particlesCount = isMobile ? 400 : 1200;

        const posArray = new Float32Array(particlesCount * 3);
        const colorsArray = new Float32Array(particlesCount * 3);

        const color1 = new THREE.Color(0x14b8a6); // Teal
        const color2 = new THREE.Color(0x0ea5e9); // Blue

        for (let i = 0; i < particlesCount * 3; i += 3) {
            // Position
            posArray[i] = (Math.random() - 0.5) * 15; // x
            posArray[i + 1] = (Math.random() - 0.5) * 15; // y
            posArray[i + 2] = (Math.random() - 0.5) * 15; // z

            // Colors (Mix between teal and blue)
            const mixedColor = Math.random() > 0.5 ? color1 : color2;
            colorsArray[i] = mixedColor.r;
            colorsArray[i + 1] = mixedColor.g;
            colorsArray[i + 2] = mixedColor.b;
        }

        particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
        particlesGeometry.setAttribute('color', new THREE.BufferAttribute(colorsArray, 3));

        const particlesMaterial = new THREE.PointsMaterial({
            size: 0.03,
            vertexColors: true,
            transparent: true,
            opacity: 0.8,
            blending: THREE.AdditiveBlending
        });

        const particlesMesh = new THREE.Points(particlesGeometry, particlesMaterial);
        scene.add(particlesMesh);

        // Create Connection Network (Icosahedron)
        const geometry = new THREE.IcosahedronGeometry(2.5, 1);
        const material = new THREE.MeshBasicMaterial({
            color: 0x14b8a6,
            wireframe: true,
            transparent: true,
            opacity: 0.15
        });
        const sphere = new THREE.Mesh(geometry, material);
        scene.add(sphere);

        // Second slightly larger network
        const geometry2 = new THREE.IcosahedronGeometry(4, 1);
        const material2 = new THREE.MeshBasicMaterial({
            color: 0x0ea5e9,
            wireframe: true,
            transparent: true,
            opacity: 0.05
        });
        const sphere2 = new THREE.Mesh(geometry2, material2);
        scene.add(sphere2);

        camera.position.z = 5;

        // Interaction
        let mouseX = 0;
        let mouseY = 0;

        document.addEventListener('mousemove', (event) => {
            mouseX = event.clientX / window.innerWidth - 0.5;
            mouseY = event.clientY / window.innerHeight - 0.5;
        });

        // Animation Loop
        const clock = new THREE.Clock();

        function animate() {
            requestAnimationFrame(animate);
            const elapsedTime = clock.getElapsedTime();

            // Rotate objects
            particlesMesh.rotation.y = elapsedTime * 0.05;
            particlesMesh.rotation.x = mouseY * 0.5;

            sphere.rotation.y += 0.002;
            sphere.rotation.x += 0.001;

            sphere2.rotation.y -= 0.002; // Reverse direction

            // Mouse interaction parallax
            camera.position.x += (mouseX * 0.5 - camera.position.x) * 0.05;
            camera.position.y += (-mouseY * 0.5 - camera.position.y) * 0.05;
            camera.lookAt(scene.position);

            renderer.render(scene, camera);
        }

        animate();

        // Responsive
        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });
    </script>
</body>

</html>