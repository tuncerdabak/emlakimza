-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost:3306
-- Üretim Zamanı: 18 Ara 2025, 18:02:24
-- Sunucu sürümü: 10.5.29-MariaDB
-- PHP Sürümü: 8.4.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `tuncerda_emlak_imza`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `denetim_kayitlari`
--

CREATE TABLE `denetim_kayitlari` (
  `id` int(11) NOT NULL,
  `firma_id` int(11) DEFAULT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `islem` varchar(255) NOT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `ip` varchar(50) DEFAULT NULL,
  `tarih` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `denetim_kayitlari`
--

INSERT INTO `denetim_kayitlari` (`id`, `firma_id`, `kullanici_id`, `islem`, `meta`, `ip`, `tarih`) VALUES
(2, NULL, NULL, 'Admin girişi yapıldı', '{\"admin_id\":\"1\",\"identifier\":\"teknik@tuncerdabak.com\"}', '149.86.141.204', '2025-12-18 17:54:46');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `dosya_depolama`
--

CREATE TABLE `dosya_depolama` (
  `id` int(11) NOT NULL,
  `firma_id` int(11) DEFAULT NULL,
  `sozlesme_id` int(11) DEFAULT NULL,
  `dosya_turu` varchar(50) DEFAULT NULL,
  `dosya_url` text DEFAULT NULL,
  `dosya_hash` varchar(128) DEFAULT NULL,
  `olusturma_tarihi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `firmalar`
--

CREATE TABLE `firmalar` (
  `id` int(11) NOT NULL,
  `firma_adi` varchar(255) NOT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `sahip_kullanici_id` int(11) DEFAULT NULL,
  `plan` enum('free','starter','pro','enterprise') DEFAULT 'starter',
  `olusturma_tarihi` datetime DEFAULT current_timestamp(),
  `durum` tinyint(1) DEFAULT 1,
  `logo_yolu` varchar(255) DEFAULT NULL,
  `yetki_belge_no` varchar(100) DEFAULT NULL,
  `yetkili_adi` varchar(255) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `adres` text DEFAULT NULL,
  `uyelik_baslangic` datetime DEFAULT NULL,
  `uyelik_bitis` datetime DEFAULT NULL,
  `belge_limiti` int(11) DEFAULT 3,
  `kullanici_limiti` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

CREATE TABLE `kullanicilar` (
  `id` int(11) NOT NULL,
  `firma_id` int(11) NOT NULL,
  `rol` enum('super_admin','firma_sahibi','broker','danisman','destek') NOT NULL,
  `isim` varchar(200) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `parola_hash` varchar(255) DEFAULT NULL,
  `telefon` varchar(50) DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `olusturma_tarihi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `kullanici_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `olusturma_tarihi` datetime DEFAULT current_timestamp(),
  `son_kullanma_tarihi` datetime NOT NULL,
  `kullanildi` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `firma_id` int(11) NOT NULL,
  `order_id` varchar(100) NOT NULL,
  `package_name` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `portfoyler`
--

CREATE TABLE `portfoyler` (
  `id` int(11) NOT NULL,
  `firma_id` int(11) NOT NULL,
  `danisman_id` int(11) NOT NULL,
  `baslik` varchar(255) NOT NULL,
  `adres` text NOT NULL,
  `il` varchar(50) DEFAULT NULL,
  `ilce` varchar(50) DEFAULT NULL,
  `mahalle` varchar(100) DEFAULT NULL,
  `ada` varchar(20) DEFAULT NULL,
  `parsel` varchar(20) DEFAULT NULL,
  `bagimsiz_bolum` varchar(20) DEFAULT NULL,
  `nitelik` varchar(50) DEFAULT NULL,
  `fiyat` decimal(15,2) DEFAULT 0.00,
  `notlar` text DEFAULT NULL,
  `durum` enum('yayinda','pasif','satildi','kiralandi') DEFAULT 'yayinda',
  `olusturma_tarihi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `site_ayarlari`
--

CREATE TABLE `site_ayarlari` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Tablo döküm verisi `site_ayarlari`
--

INSERT INTO `site_ayarlari` (`setting_key`, `setting_value`, `updated_at`) VALUES
('shopier_api_key', '4be8c9de083ebf1e1cb638aeb9259d9f', '2025-12-12 13:56:20'),
('shopier_secret', '75ef079a24244229cffe0bb6f3645219', '2025-12-12 13:56:20'),
('smtp_active', '1', '2025-12-11 18:32:07'),
('smtp_host', 'mail.emlakimza.com', '2025-12-18 13:43:30'),
('smtp_password', 'Td3492549*', '2025-12-11 18:32:07'),
('smtp_port', '465', '2025-12-11 18:32:07'),
('smtp_secure', 'tls', '2025-12-11 16:47:06'),
('smtp_username', 'destek@emlakimza.com', '2025-12-18 13:43:30');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sozlesmeler`
--

CREATE TABLE `sozlesmeler` (
  `id` int(11) NOT NULL,
  `firma_id` int(11) NOT NULL,
  `danisman_id` int(11) DEFAULT NULL,
  `sablon_id` int(11) DEFAULT NULL,
  `islem_uuid` varchar(64) NOT NULL,
  `gayrimenkul_adres` text DEFAULT NULL,
  `fiyat` bigint(20) DEFAULT NULL,
  `musteri_email` varchar(255) DEFAULT NULL,
  `durum` enum('PENDING','SIGNED_MAIN','COMPLETED','CANCELLED') DEFAULT 'PENDING',
  `olusturma_tarihi` datetime DEFAULT current_timestamp(),
  `guncelleme_tarihi` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `dinamik_veriler` longtext DEFAULT NULL,
  `pdf_dosya_yolu` varchar(500) DEFAULT NULL,
  `musteri_bilgileri` longtext DEFAULT NULL,
  `gayrimenkul_detaylari` text DEFAULT NULL,
  `portfoy_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sozlesme_imzalar`
--

CREATE TABLE `sozlesme_imzalar` (
  `id` int(11) NOT NULL,
  `sozlesme_id` int(11) NOT NULL,
  `tip` enum('ANA','TEYIT') NOT NULL,
  `imzalama_tarihi` datetime DEFAULT NULL,
  `imzalayan_ip` varchar(50) DEFAULT NULL,
  `imza_base64` longtext DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `olusturma_tarihi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sozlesme_sablonlari`
--

CREATE TABLE `sozlesme_sablonlari` (
  `id` int(11) NOT NULL,
  `firma_id` int(11) NOT NULL,
  `ad` varchar(255) NOT NULL,
  `dosya_yolu` varchar(500) NOT NULL,
  `placeholder_list` text DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `olusturma_tarihi` datetime DEFAULT current_timestamp(),
  `sahalar` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `whatsapp_linkleri`
--

CREATE TABLE `whatsapp_linkleri` (
  `id` int(11) NOT NULL,
  `sozlesme_id` int(11) NOT NULL,
  `token` varchar(128) NOT NULL,
  `son_kullanma_tarihi` datetime DEFAULT NULL,
  `kullanildi` tinyint(1) DEFAULT 0,
  `olusturma_tarihi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `yoneticiler`
--

CREATE TABLE `yoneticiler` (
  `id` int(11) NOT NULL,
  `adsoyad` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `sifre` varchar(255) NOT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `yoneticiler`
--

INSERT INTO `yoneticiler` (`id`, `adsoyad`, `email`, `telefon`, `sifre`, `olusturma_tarihi`) VALUES
(1, 'Tuncer DABAK', 'teknik@tuncerdabak.com', NULL, '$2y$10$Cz06dVILhtC2wDYBON6ghO2arY1TC.KjacA0Nn5xPjBG5HGEOVMAW', '2025-11-30 08:41:08');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `denetim_kayitlari`
--
ALTER TABLE `denetim_kayitlari`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_denetim_firma` (`firma_id`),
  ADD KEY `fk_denetim_kullanici` (`kullanici_id`);

--
-- Tablo için indeksler `dosya_depolama`
--
ALTER TABLE `dosya_depolama`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dosya_firma` (`firma_id`),
  ADD KEY `fk_dosya_sozlesme` (`sozlesme_id`);

--
-- Tablo için indeksler `firmalar`
--
ALTER TABLE `firmalar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `kullanicilar`
--
ALTER TABLE `kullanicilar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kullanicilar_firma` (`firma_id`);

--
-- Tablo için indeksler `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kullanici_id` (`kullanici_id`),
  ADD KEY `token` (`token`);

--
-- Tablo için indeksler `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `firma_id` (`firma_id`),
  ADD KEY `status` (`status`);

--
-- Tablo için indeksler `portfoyler`
--
ALTER TABLE `portfoyler`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `site_ayarlari`
--
ALTER TABLE `site_ayarlari`
  ADD PRIMARY KEY (`setting_key`);

--
-- Tablo için indeksler `sozlesmeler`
--
ALTER TABLE `sozlesmeler`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `islem_uuid` (`islem_uuid`),
  ADD KEY `fk_sozlesmeler_danisman` (`danisman_id`),
  ADD KEY `idx_sozlesmeler_firma` (`firma_id`),
  ADD KEY `idx_sozlesmeler_durum` (`durum`),
  ADD KEY `sablon_id` (`sablon_id`);

--
-- Tablo için indeksler `sozlesme_imzalar`
--
ALTER TABLE `sozlesme_imzalar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_imzalar_sozlesme` (`sozlesme_id`);

--
-- Tablo için indeksler `sozlesme_sablonlari`
--
ALTER TABLE `sozlesme_sablonlari`
  ADD PRIMARY KEY (`id`),
  ADD KEY `firma_id` (`firma_id`);

--
-- Tablo için indeksler `whatsapp_linkleri`
--
ALTER TABLE `whatsapp_linkleri`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_whatsapp_sozlesme` (`sozlesme_id`),
  ADD KEY `idx_whatsapp_token` (`token`);

--
-- Tablo için indeksler `yoneticiler`
--
ALTER TABLE `yoneticiler`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `denetim_kayitlari`
--
ALTER TABLE `denetim_kayitlari`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `dosya_depolama`
--
ALTER TABLE `dosya_depolama`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `firmalar`
--
ALTER TABLE `firmalar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `kullanicilar`
--
ALTER TABLE `kullanicilar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `portfoyler`
--
ALTER TABLE `portfoyler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `sozlesmeler`
--
ALTER TABLE `sozlesmeler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Tablo için AUTO_INCREMENT değeri `sozlesme_imzalar`
--
ALTER TABLE `sozlesme_imzalar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Tablo için AUTO_INCREMENT değeri `sozlesme_sablonlari`
--
ALTER TABLE `sozlesme_sablonlari`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `whatsapp_linkleri`
--
ALTER TABLE `whatsapp_linkleri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Tablo için AUTO_INCREMENT değeri `yoneticiler`
--
ALTER TABLE `yoneticiler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `denetim_kayitlari`
--
ALTER TABLE `denetim_kayitlari`
  ADD CONSTRAINT `fk_denetim_firma` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_denetim_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `dosya_depolama`
--
ALTER TABLE `dosya_depolama`
  ADD CONSTRAINT `fk_dosya_firma` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dosya_sozlesme` FOREIGN KEY (`sozlesme_id`) REFERENCES `sozlesmeler` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `kullanicilar`
--
ALTER TABLE `kullanicilar`
  ADD CONSTRAINT `fk_kullanicilar_firma` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `sozlesmeler`
--
ALTER TABLE `sozlesmeler`
  ADD CONSTRAINT `fk_sozlesmeler_danisman` FOREIGN KEY (`danisman_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sozlesmeler_firma` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sozlesmeler_ibfk_1` FOREIGN KEY (`sablon_id`) REFERENCES `sozlesme_sablonlari` (`id`);

--
-- Tablo kısıtlamaları `sozlesme_imzalar`
--
ALTER TABLE `sozlesme_imzalar`
  ADD CONSTRAINT `fk_imzalar_sozlesme` FOREIGN KEY (`sozlesme_id`) REFERENCES `sozlesmeler` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `sozlesme_sablonlari`
--
ALTER TABLE `sozlesme_sablonlari`
  ADD CONSTRAINT `sozlesme_sablonlari_ibfk_1` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `whatsapp_linkleri`
--
ALTER TABLE `whatsapp_linkleri`
  ADD CONSTRAINT `fk_whatsapp_sozlesme` FOREIGN KEY (`sozlesme_id`) REFERENCES `sozlesmeler` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
