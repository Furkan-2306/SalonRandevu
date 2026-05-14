-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 09 May 2026, 23:58:46
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `kuafor_randevu_sistemi`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `degerlendirmeler`
--

CREATE TABLE `degerlendirmeler` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `randevu_id` bigint(20) UNSIGNED NOT NULL,
  `dukkan_id` bigint(20) UNSIGNED NOT NULL,
  `musteri_id` bigint(20) UNSIGNED NOT NULL,
  `puan` tinyint(4) NOT NULL COMMENT '1-5 arası yıldız',
  `yorum` text DEFAULT NULL,
  `tarih` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `dukkanlar`
--

CREATE TABLE `dukkanlar` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sahip_id` bigint(20) UNSIGNED NOT NULL,
  `ad` varchar(255) NOT NULL,
  `vergi_no` varchar(50) NOT NULL,
  `tc_kimlik` varchar(11) NOT NULL,
  `sehir` varchar(100) NOT NULL,
  `ilce` varchar(100) NOT NULL,
  `adres` text NOT NULL,
  `enlem` decimal(10,8) DEFAULT NULL,
  `boylam` decimal(11,8) DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `aktif_mi` tinyint(1) DEFAULT 0,
  `kayit_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `dukkanlar`
--

INSERT INTO `dukkanlar` (`id`, `sahip_id`, `ad`, `vergi_no`, `tc_kimlik`, `sehir`, `ilce`, `adres`, `enlem`, `boylam`, `aciklama`, `aktif_mi`, `kayit_tarihi`) VALUES
(1, 1, 'gülpembe', '11111', '111', 'Balıkesir', 'Kepsut', 'sdfsdf', 40.98909700, 28.86975300, 'asdfasddas', 1, '2026-04-15 07:25:30'),
(2, 2, 'jkdsdk', '123123198&quot;', '289371829', 'Bayburt', 'Demirözü', 'sdfsdas', NULL, NULL, 'adasdas', 0, '2026-04-15 11:01:25');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `dukkan_gorselleri`
--

CREATE TABLE `dukkan_gorselleri` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `dukkan_id` bigint(20) UNSIGNED NOT NULL,
  `gorsel_yolu` varchar(255) NOT NULL,
  `ana_gorsel_mi` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `dukkan_gorselleri`
--

INSERT INTO `dukkan_gorselleri` (`id`, `dukkan_id`, `gorsel_yolu`, `ana_gorsel_mi`) VALUES
(1, 1, 'yuklemeler/dukkanlar/vitrin_1_69df707b6e39c.png', 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `hizmetler`
--

CREATE TABLE `hizmetler` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `dukkan_id` bigint(20) UNSIGNED NOT NULL,
  `ad` varchar(150) NOT NULL,
  `sure_dakika` int(11) NOT NULL,
  `fiyat` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `hizmetler`
--

INSERT INTO `hizmetler` (`id`, `dukkan_id`, `ad`, `sure_dakika`, `fiyat`) VALUES
(1, 1, 'saç kesim', 30, 500.00),
(2, 1, 'fön', 15, 200.00);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

CREATE TABLE `kullanicilar` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rol` enum('admin','musteri','dukkan_sahibi') NOT NULL,
  `eposta` varchar(255) NOT NULL,
  `dogrulama_kodu` varchar(255) DEFAULT NULL,
  `kod_olusturulma_tarihi` datetime DEFAULT NULL,
  `sifre_hash` varchar(255) NOT NULL,
  `dogrulandi_mi` tinyint(1) DEFAULT 0,
  `kayit_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `kullanicilar`
--

INSERT INTO `kullanicilar` (`id`, `rol`, `eposta`, `dogrulama_kodu`, `kod_olusturulma_tarihi`, `sifre_hash`, `dogrulandi_mi`, `kayit_tarihi`) VALUES
(1, 'dukkan_sahibi', 'furkanyasar171717@gmail.com', NULL, NULL, '$2y$10$hy54sKtGq98xiqmW/PCknejfKL8Zv7cz1O..VP7vcE1zzyTqVFPHC', 1, '2026-04-15 07:25:00'),
(2, 'musteri', 'yesherr.07@gmail.com', NULL, NULL, '$2y$10$5IuroqaAM2W1r.eQ7dMqou3/uVy0ZdeZpW47evpdvwE8nEYA3ars.', 1, '2026-04-15 10:30:47'),
(3, 'musteri', 'yesherr.06@gmail.com', NULL, NULL, '$2y$10$6qD8q3vYJVvAJoHfzygFl.sgOcMov/31tIMAcO36cd0tPqXL5ojWy', 1, '2026-04-15 11:32:00');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanici_profilleri`
--

CREATE TABLE `kullanici_profilleri` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kullanici_id` bigint(20) UNSIGNED NOT NULL,
  `ad` varchar(100) NOT NULL,
  `soyad` varchar(100) NOT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `cinsiyet` enum('erkek','kadin','uniseks','diger') DEFAULT NULL,
  `yas` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `kullanici_profilleri`
--

INSERT INTO `kullanici_profilleri` (`id`, `kullanici_id`, `ad`, `soyad`, `telefon`, `cinsiyet`, `yas`) VALUES
(1, 1, 'Furkan', 'Yaşar', '5389483400', 'erkek', NULL),
(2, 2, 'aa', 'sda', '0 538 948 34 00', 'kadin', NULL),
(3, 3, 'yasar', 'tyesher', '0 538 948 34 00', 'erkek', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `personeller`
--

CREATE TABLE `personeller` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `dukkan_id` bigint(20) UNSIGNED NOT NULL,
  `ad` varchar(100) NOT NULL,
  `soyad` varchar(100) NOT NULL,
  `profil_foto_yolu` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `personeller`
--

INSERT INTO `personeller` (`id`, `dukkan_id`, `ad`, `soyad`, `profil_foto_yolu`) VALUES
(4, 1, 'seyfo', 'atılgan', NULL),
(5, 1, 'fu', 'ya', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `personel_mesai`
--

CREATE TABLE `personel_mesai` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `personel_id` bigint(20) UNSIGNED NOT NULL,
  `haftanin_gunu` tinyint(4) NOT NULL COMMENT '1: Pazartesi, 7: Pazar',
  `baslangic_saati` time NOT NULL,
  `bitis_saati` time NOT NULL,
  `izinli_mi` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `personel_mesai`
--

INSERT INTO `personel_mesai` (`id`, `personel_id`, `haftanin_gunu`, `baslangic_saati`, `bitis_saati`, `izinli_mi`) VALUES
(12, 4, 1, '09:00:00', '18:00:00', 0),
(13, 4, 2, '09:00:00', '18:00:00', 0),
(14, 4, 3, '09:00:00', '18:00:00', 0),
(15, 4, 4, '09:00:00', '18:00:00', 0),
(16, 4, 5, '09:00:00', '18:00:00', 0),
(17, 4, 6, '09:00:00', '18:00:00', 0),
(18, 4, 7, '00:00:00', '00:00:00', 1),
(19, 5, 1, '08:00:00', '19:00:00', 0),
(20, 5, 2, '00:00:00', '00:00:00', 1),
(21, 5, 3, '08:00:00', '19:00:00', 0),
(22, 5, 4, '08:00:00', '19:00:00', 0),
(23, 5, 5, '08:00:00', '19:00:00', 0),
(24, 5, 6, '08:00:00', '19:00:00', 0),
(25, 5, 7, '00:00:00', '00:00:00', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `randevular`
--

CREATE TABLE `randevular` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `musteri_id` bigint(20) UNSIGNED NOT NULL,
  `dukkan_id` bigint(20) UNSIGNED NOT NULL,
  `personel_id` bigint(20) UNSIGNED NOT NULL,
  `hizmet_id` bigint(20) UNSIGNED NOT NULL,
  `randevu_tarih_saat` datetime NOT NULL,
  `durum` enum('bekliyor','tamamlandi','gelmedi','iptal') DEFAULT 'bekliyor',
  `toplam_tutar` decimal(10,2) NOT NULL,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `degerlendirmeler`
--
ALTER TABLE `degerlendirmeler`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `randevu_id` (`randevu_id`),
  ADD KEY `dukkan_id` (`dukkan_id`),
  ADD KEY `musteri_id` (`musteri_id`);

--
-- Tablo için indeksler `dukkanlar`
--
ALTER TABLE `dukkanlar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sahip_id` (`sahip_id`);

--
-- Tablo için indeksler `dukkan_gorselleri`
--
ALTER TABLE `dukkan_gorselleri`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dukkan_id` (`dukkan_id`);

--
-- Tablo için indeksler `hizmetler`
--
ALTER TABLE `hizmetler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dukkan_id` (`dukkan_id`);

--
-- Tablo için indeksler `kullanicilar`
--
ALTER TABLE `kullanicilar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `eposta` (`eposta`);

--
-- Tablo için indeksler `kullanici_profilleri`
--
ALTER TABLE `kullanici_profilleri`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kullanici_id` (`kullanici_id`);

--
-- Tablo için indeksler `personeller`
--
ALTER TABLE `personeller`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dukkan_id` (`dukkan_id`);

--
-- Tablo için indeksler `personel_mesai`
--
ALTER TABLE `personel_mesai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personel_id` (`personel_id`);

--
-- Tablo için indeksler `randevular`
--
ALTER TABLE `randevular`
  ADD PRIMARY KEY (`id`),
  ADD KEY `musteri_id` (`musteri_id`),
  ADD KEY `dukkan_id` (`dukkan_id`),
  ADD KEY `personel_id` (`personel_id`),
  ADD KEY `hizmet_id` (`hizmet_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `degerlendirmeler`
--
ALTER TABLE `degerlendirmeler`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `dukkanlar`
--
ALTER TABLE `dukkanlar`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `dukkan_gorselleri`
--
ALTER TABLE `dukkan_gorselleri`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `hizmetler`
--
ALTER TABLE `hizmetler`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `kullanicilar`
--
ALTER TABLE `kullanicilar`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `kullanici_profilleri`
--
ALTER TABLE `kullanici_profilleri`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `personeller`
--
ALTER TABLE `personeller`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `personel_mesai`
--
ALTER TABLE `personel_mesai`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Tablo için AUTO_INCREMENT değeri `randevular`
--
ALTER TABLE `randevular`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `degerlendirmeler`
--
ALTER TABLE `degerlendirmeler`
  ADD CONSTRAINT `degerlendirmeler_ibfk_1` FOREIGN KEY (`randevu_id`) REFERENCES `randevular` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `degerlendirmeler_ibfk_2` FOREIGN KEY (`dukkan_id`) REFERENCES `dukkanlar` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `degerlendirmeler_ibfk_3` FOREIGN KEY (`musteri_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `dukkanlar`
--
ALTER TABLE `dukkanlar`
  ADD CONSTRAINT `dukkanlar_ibfk_1` FOREIGN KEY (`sahip_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `dukkan_gorselleri`
--
ALTER TABLE `dukkan_gorselleri`
  ADD CONSTRAINT `dukkan_gorselleri_ibfk_1` FOREIGN KEY (`dukkan_id`) REFERENCES `dukkanlar` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `hizmetler`
--
ALTER TABLE `hizmetler`
  ADD CONSTRAINT `hizmetler_ibfk_1` FOREIGN KEY (`dukkan_id`) REFERENCES `dukkanlar` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `kullanici_profilleri`
--
ALTER TABLE `kullanici_profilleri`
  ADD CONSTRAINT `kullanici_profilleri_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `personeller`
--
ALTER TABLE `personeller`
  ADD CONSTRAINT `personeller_ibfk_1` FOREIGN KEY (`dukkan_id`) REFERENCES `dukkanlar` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `personel_mesai`
--
ALTER TABLE `personel_mesai`
  ADD CONSTRAINT `personel_mesai_ibfk_1` FOREIGN KEY (`personel_id`) REFERENCES `personeller` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `randevular`
--
ALTER TABLE `randevular`
  ADD CONSTRAINT `randevular_ibfk_1` FOREIGN KEY (`musteri_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `randevular_ibfk_2` FOREIGN KEY (`dukkan_id`) REFERENCES `dukkanlar` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `randevular_ibfk_3` FOREIGN KEY (`personel_id`) REFERENCES `personeller` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `randevular_ibfk_4` FOREIGN KEY (`hizmet_id`) REFERENCES `hizmetler` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
