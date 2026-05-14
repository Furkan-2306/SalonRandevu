<?php
// --- GÜVENLİK DUVARI BAŞLANGICI ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['giris_yapildi']) || $_SESSION['giris_yapildi'] !== true) {
    $_SESSION['mesaj_turu'] = "hata";
    $_SESSION['mesaj'] = "İşletme başvurusu yapmak için önce giriş yapmalı veya yeni bir hesap oluşturmalısınız.";
    header("Location: /kuafor-randevu/giris-kayit");
    exit;
}
// --- GÜVENLİK DUVARI BİTİŞİ ---

include 'uygulama/gorunumler/ortak/ust_bilgi.php'; 
?>

<div class="container py-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <div class="text-center mb-4">
                <h2 class="fw-bold">İşletmenizi Sisteme Ekleyin</h2>
                <p class="text-muted">Binlerce yeni müşteriye ulaşmak için sadece birkaç adım kaldı.</p>
            </div>

            <div class="card shadow border-0 rounded-4 p-4">
                
                <div class="position-relative mb-5 mt-2 px-3">
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-primary" id="kayitProgressBar" role="progressbar" style="width: 33%;" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="d-flex justify-content-between position-absolute w-100 top-50 translate-middle-y start-0 px-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold step-indicator" style="width: 35px; height: 35px;">1</div>
                        <div class="bg-light text-muted border rounded-circle d-flex align-items-center justify-content-center fw-bold step-indicator" id="step2-indicator" style="width: 35px; height: 35px;">2</div>
                        <div class="bg-light text-muted border rounded-circle d-flex align-items-center justify-content-center fw-bold step-indicator" id="step3-indicator" style="width: 35px; height: 35px;">3</div>
                    </div>
                </div>

                <form id="isletmeKayitFormu" action="/kuafor-randevu/uygulama/kontrolculer/isletme_kontrol.php" method="POST">
                    
                    <div class="form-step active" id="adim1">
                        <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-shop me-2"></i>Kurumsal Bilgiler</h5>
                        
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control rounded-3 zorunlu-alan" id="dukkanAdi" name="dukkan_ad" placeholder="Dükkan Adı">
                            <label for="dukkanAdi">Dükkan / Salon Adı <span class="text-danger">*</span></label>
                        </div>

                        <div class="form-floating mb-3">
                            <select class="form-select rounded-3 zorunlu-alan" id="cinsiyetTipi" name="cinsiyet_tipi">
                                <option value="" selected disabled>Seçiniz...</option>
                                <option value="erkek">Sadece Erkek (Berber / Erkek Kuaförü)</option>
                                <option value="kadin">Sadece Kadın (Bayan Kuaförü / Güzellik Merkezi)</option>
                                <option value="hepsi">Unisex (Hem Erkek Hem Kadın)</option>
                            </select>
                            <label for="cinsiyetTipi">Hizmet Verilen Cinsiyet <span class="text-danger">*</span></label>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control rounded-3 zorunlu-alan" id="vergiNo" name="vergi_no" placeholder="Vergi No">
                                    <label for="vergiNo">Vergi Numarası <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control rounded-3 zorunlu-alan" id="tcKimlik" name="tc_kimlik" placeholder="TC Kimlik" maxlength="11">
                                    <label for="tcKimlik">T.C. Kimlik Numarası <span class="text-danger">*</span></label>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-primary px-5 py-2 fw-bold rounded-pill" onclick="ileriGit(1, 2)">İleri <i class="bi bi-arrow-right ms-1"></i></button>
                        </div>
                    </div>

                    <div class="form-step d-none" id="adim2">
                        <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-geo-alt me-2"></i>Lokasyon Bilgileri</h5>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select rounded-3 zorunlu-alan" id="sehir" name="sehir">
                                        <option value="" selected disabled>Önce İl Seçiniz...</option>
                                    </select>
                                    <label for="sehir">İl <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select rounded-3 zorunlu-alan" id="ilce" name="ilce" disabled>
                                        <option value="" selected disabled>İlçe Seçiniz...</option>
                                    </select>
                                    <label for="ilce">İlçe <span class="text-danger">*</span></label>
                                </div>
                            </div>
                        </div>
                        <div class="form-floating mb-3">
                            <textarea class="form-control rounded-3 zorunlu-alan" id="acikAdres" name="adres" placeholder="Açık Adres" style="height: 100px"></textarea>
                            <label for="acikAdres">Açık Adresiniz <span class="text-danger">*</span></label>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control rounded-3" id="enlem" name="enlem" placeholder="Enlem">
                                    <label for="enlem">Harita Enlem (İsteğe Bağlı)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control rounded-3" id="boylam" name="boylam" placeholder="Boylam">
                                    <label for="boylam">Harita Boylam (İsteğe Bağlı)</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-light px-4 py-2 fw-bold rounded-pill border" onclick="geriGit(2, 1)"><i class="bi bi-arrow-left me-1"></i> Geri</button>
                            <button type="button" class="btn btn-primary px-5 py-2 fw-bold rounded-pill" onclick="ileriGit(2, 3)">İleri <i class="bi bi-arrow-right ms-1"></i></button>
                        </div>
                    </div>

                    <div class="form-step d-none" id="adim3">
                        <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-info-circle me-2"></i>Son Detaylar</h5>

                        <div class="form-floating mb-3">
                            <textarea class="form-control rounded-3 zorunlu-alan" id="aciklama" name="aciklama" placeholder="Açıklama" style="height: 120px"></textarea>
                            <label for="aciklama">İşletme Hakkında (Telefon, Çalışma Saatleri vb.) <span class="text-danger">*</span></label>
                        </div>

                        <div class="text-center py-4">
                            <div class="alert alert-warning text-start rounded-3 mt-2 mb-4" role="alert">
                                <i class="bi bi-info-circle-fill me-2"></i> Başvurunuz tamamlandıktan sonra yöneticilerimiz tarafından incelenecektir. Onaylandıktan sonra paneliniz aktif olacaktır.
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-light px-4 py-2 fw-bold rounded-pill border" onclick="geriGit(3, 2)"><i class="bi bi-arrow-left me-1"></i> Geri</button>
                                <button type="submit" name="isletme_kaydet" class="btn btn-success px-5 py-2 fw-bold rounded-pill" onclick="return formDogrula(3)">Başvuruyu Tamamla <i class="bi bi-check2 ms-1"></i></button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
            
        </div>
    </div>
</div>

<script>
    // --- PROFESYONEL TÜRKİYE API KODLARI ---
    let turkiyeVerisi = [];

    document.addEventListener("DOMContentLoaded", function() {
        fetch('https://turkiyeapi.dev/api/v1/provinces')
            .then(response => response.json())
            .then(res => {
                turkiyeVerisi = res.data;
                let sehirSelect = document.getElementById("sehir");
                
                turkiyeVerisi.sort((a, b) => a.name.localeCompare(b.name, 'tr'));
                
                turkiyeVerisi.forEach(il => {
                    let option = document.createElement("option");
                    option.value = il.name;
                    option.textContent = il.name;
                    sehirSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error("API Hatası:", error);
                alert("Şehir verileri yüklenemedi. Lütfen internet bağlantınızı kontrol edin.");
            });
    });

    document.getElementById("sehir").addEventListener("change", function() {
        let secilenIlAdi = this.value;
        let ilceSelect = document.getElementById("ilce");
        
        ilceSelect.innerHTML = '<option value="" selected disabled>İlçe Seçiniz...</option>';
        ilceSelect.disabled = false;

        let secilenIlVerisi = turkiyeVerisi.find(il => il.name === secilenIlAdi);

        if (secilenIlVerisi && secilenIlVerisi.districts) {
            let ilceler = secilenIlVerisi.districts.sort((a, b) => a.name.localeCompare(b.name, 'tr'));

            ilceler.forEach(ilce => {
                let option = document.createElement("option");
                option.value = ilce.name;
                option.textContent = ilce.name;
                ilceSelect.appendChild(option);
            });
        }
    });

    // BOŞ ALAN KONTROLÜ
    function formDogrula(adimNo) {
        let currentStepDiv = document.getElementById('adim' + adimNo);
        let inputs = currentStepDiv.querySelectorAll('.zorunlu-alan');
        let isValid = true;

        inputs.forEach(input => {
            if(!input.value || !input.value.trim()) {
                input.classList.add('is-invalid'); 
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });

        if(!isValid) {
            alert("Lütfen kırmızı ile işaretlenmiş tüm zorunlu alanları doldurun!");
        }
        return isValid;
    }

    function ileriGit(mevcutAdim, sonrakiAdim) {
        if(!formDogrula(mevcutAdim)) {
            return; 
        }

        document.getElementById('adim' + mevcutAdim).classList.add('d-none');
        document.getElementById('adim' + sonrakiAdim).classList.remove('d-none');
        
        let progress = (sonrakiAdim / 3) * 100;
        document.getElementById('kayitProgressBar').style.width = progress + '%';
        
        let indicator = document.getElementById('step' + sonrakiAdim + '-indicator');
        if(indicator) {
            indicator.classList.remove('bg-light', 'text-muted', 'border');
            indicator.classList.add('bg-primary', 'text-white');
        }
    }

    function geriGit(mevcutAdim, oncekiAdim) {
        document.getElementById('adim' + mevcutAdim).classList.add('d-none');
        document.getElementById('adim' + oncekiAdim).classList.remove('d-none');
        
        let progress = (oncekiAdim / 3) * 100;
        document.getElementById('kayitProgressBar').style.width = progress + '%';
        
        let indicator = document.getElementById('step' + mevcutAdim + '-indicator');
        if(indicator) {
            indicator.classList.remove('bg-primary', 'text-white');
            indicator.classList.add('bg-light', 'text-muted', 'border');
        }
    }
</script>

<?php include 'uygulama/gorunumler/ortak/alt_bilgi.php'; ?>