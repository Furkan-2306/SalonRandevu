# 💇‍♂️💇‍♀️ Güzellik Salonu & Berber Randevu Sistemi - Proje Detay Raporu

**Proje Ekibi:** Yunus Emre Koç, Mustafa Kabataş, Umutcan Çadırcı  
**Proje Danışmanı:** Aysun Yılmaz Kızılboğa  
**Canlı Sistem Adresi:** [salonrandevu.shop](https://salonrandevu.shop)

---

## 1. Projenin Amacı ve Çıkış Noktası
Günümüzde güzellik salonları, berberler ve kuaförler randevu süreçlerini genellikle telefon aramaları veya mesajlaşma uygulamaları üzerinden manuel olarak yönetmektedir. Bu durum; zaman kayıplarına, randevu çakışmalarına, personelin verimsiz yönetilmesine ve müşteri tarafında bekleme sürelerinin artmasına neden olmaktadır. 

Geliştirdiğimiz **Salon Randevu Sistemi**, bu kaotik süreci merkezi bir dijital platforma taşıyarak hem işletmelerin operasyonel yükünü hafifletmeyi hem de müşterilere 7/24 kesintisiz, hızlı ve güvenilir bir randevu deneyimi sunmayı amaçlamaktadır. Projemiz, **B2C (Business-to-Consumer)** modelini temel alan, çok kullanıcılı (Multi-tenant) bir SaaS (Software as a Service) altyapısına sahiptir.

---

## 2. Sistem Mimarisi ve Kullanılan Teknolojiler
Proje, karmaşık kod yapısını (spaghetti code) önlemek adına MVC (Model-View-Controller) mimarisine yakınsayan modüler bir yapıda tasarlanmıştır.

* **Backend:** PHP 8.x (Nesne Yönelimli Programlama - OOP prensipleri kullanılarak).
* **Veritabanı Katmanı:** MySQL / MariaDB. SQL Injection saldırılarını önlemek için tüm veritabanı işlemleri **PDO (PHP Data Objects)** ve `Prepared Statements` (Hazırlanmış İfadeler) kullanılarak yapılmıştır.
* **Frontend:** Kullanıcı arayüzü Bootstrap 5 ile tamamen mobil uyumlu (Responsive) olarak tasarlanmış, dinamik etkileşimler Vanilla JavaScript (ES6) ile sağlanmıştır.
* **Asenkron Veri Akışı:** Sayfa yenilenmeden dinamik saat sorgulamaları için Fetch API (AJAX) kullanılmıştır.

---

## 3. Temel Fonksiyonlar ve İş Mantığı (Business Logic)

Proje, üç ana aktör etrafında şekillenmiştir: Müşteriler, İşletme Sahipleri ve Sistem (Otomasyon).

### A. Müşteri (Son Kullanıcı) Deneyimi
Müşteriler sisteme kayıt olduktan sonra kapsamlı bir arama motoru ile karşılaşır. 
* **Dinamik Filtreleme:** Türkiye API entegrasyonu sayesinde İl/İlçe seçimi dinamik olarak yapılır. Kullanıcılar ayrıca "Sadece Kadın", "Sadece Erkek" veya "Unisex" filtreleriyle hedeflerine uygun işletmeleri bulabilir ve minimum yıldız puanına göre filtreleme yapabilir.
* **Akıllı Randevu Sihirbazı (4 Adımlı Süreç):**
  1. **Hizmet Seçimi:** Kullanıcı dilediği hizmetleri seçer. Sistem arka planda sepet tutarını ve toplam süreyi hesaplar (Maksimum 60 dakika kota kontrolü).
  2. **Personel Seçimi:** İşletmedeki ilgili personeller listelenir.
  3. **Tarih ve Dinamik Saat Seçimi:** En kritik mühendislik algoritmalarından biri buradadır. Müşteri bir tarih seçtiğinde, JavaScript ile o personelin o gün **izinli olup olmadığı** anında denetlenir. Eğer izinli değilse, Fetch API aracılığıyla veritabanına istek atılır. Geçmiş saatler ve başka müşteriler tarafından doldurulmuş saatler **"Geçti"**, **"Dolu"** veya **"İşlemde"** olarak işaretlenerek seçilmesi engellenir.
  4. **Özet ve Onay:** Tüm seçimler özetlenir ve veritabanına "Bekliyor" statüsü ile kaydedilir.

### B. İşletme Sahibi Deneyimi
İşletme sahipleri, kendi dükkanlarını sisteme kaydettikten sonra onay sürecinden geçer. Onaylanan işletmeler kendi özel yönetim panellerine erişir.
* **Rol Tabanlı Erişim (RBAC):** İşletme sahipleri, kendi dükkanlarına müşteri gibi randevu alamazlar. Sistem rol kontrolü yaparak bu tür finansal mantık hatalarını (Self-booking) engeller.
* **Hizmet ve Fiyat Yönetimi:** İşletme, sunduğu hizmetleri dakika ve fiyat bazında sisteme tanımlar.
* **Gelişmiş Personel Mesai Yönetimi:** İşletme, çalışanlarını sisteme ekler. Her personel için haftanın hangi günleri izinli olduğu ve mesai başlangıç-bitiş saatleri belirlenir. Bu veriler `personel_mesai` adlı ilişkisel bir tabloda tutulur. Personel bilgileri güncellendiğinde, sistem eski mesai verilerini temizler ve yeni verileri (Transaction blokları içinde güvenli bir şekilde) yeniden yazar.
* **Randevu Yönetim Paneli:** İşletme, gelen randevuları anlık olarak görür. "Bekliyor", "Onaylandı", "Tamamlandı" veya "İptal" statülerini tek tuşla yönetebilir.

### C. Otomatik E-Posta Entegrasyonu (PHPMailer)
Sistemin "canlı" hissettirmesi ve iletişimin kopmaması için arka planda güçlü bir mail sınıfı (`MailIslemleri`) yazılmıştır.
* Müşteri randevu oluşturduğunda, **işletme sahibine** anında "Yeni bir randevunuz var, paneli kontrol edin" maili gider.
* Aynı anda **müşteriye** "Randevu talebiniz alındı, onay bekleniyor" maili gider.
* İşletme randevuyu onayladığında, iptal ettiğinde veya işlem tamamlandığında müşteriye durumun değiştiğini bildiren özel tasarımlı (HTML/CSS tabanlı) e-postalar otomatik olarak gönderilir.

---

## 4. Güvenlik ve Veri Bütünlüğü
* **Çakışma Kontrolü (Collision Detection):** Müşteri "Onayla" butonuna bastığı milisaniyede, seçilen personelin o saat aralığında başka bir randevusu olup olmadığı PHP backend tarafında SQL sorgularıyla tekrar kontrol edilir. Bu, "Double-booking" (Aynı saate iki kişiye randevu verme) sorununu sıfıra indirir.
* **Transaction Yönetimi:** Çoklu tabloya veri yazılması gereken durumlarda (örneğin personel ve mesai günlerinin güncellenmesi), veritabanı tutarsızlıklarını önlemek için PDO `beginTransaction()`, `commit()` ve hata anında `rollBack()` yapıları kullanılmıştır.
* **Veri Kaybı Önleme:** HTML formlarından gelen tüm veriler `htmlspecialchars()` ve `trim()` gibi fonksiyonlardan geçirilerek XSS (Cross-Site Scripting) saldırılarına karşı sanitize edilmiştir.

---

## 5. Sonuç
Bu proje, yalnızca temel bir CRUD (Create, Read, Update, Delete) uygulaması olmanın ötesine geçerek; rol tabanlı yetkilendirme, asenkron veri iletişimi, ilişkisel veritabanı tasarımı, gerçek zamanlı zaman/kota denetimi ve üçüncü parti entegrasyonlar (Harita, Mail, API) barındıran tam teşekküllü bir yazılım ürünü olarak başarıyla tamamlanmıştır.
