---
description: Otomatik FTP Dağıtımı (GitHub Actions) Kurulumu
---

Bu iş akışı, herhangi bir projeye GitHub Actions kullanarak otomatik FTP dağıtımı kurulumunu sağlar.

## Gereksinimler
- Bir GitHub hesabı
- Projenin GitHub'a push edilmiş olması
- FTP Sunucu bilgileri (Sunucu adresi, Kullanıcı adı, Şifre)

## Adımlar

1. **GitHub Action Dosyasını Oluştur**
   
   Proje ana dizininde `.github/workflows/ftp-deploy.yml` dosyasını oluştur ve aşağıdaki içeriği yapıştır:

   ```yaml
   name: 🚀 FTP Deploy

   on:
     push:
       branches:
         - master

   jobs:
     web-deploy:
       name: 🎉 Deploy
       runs-on: ubuntu-latest
       steps:
         - name: 🚚 Get latest code
           uses: actions/checkout@v4

         - name: 📂 Sync files
           uses: SamKirkland/FTP-Deploy-Action@v4.3.4
           with:
             server: ${{ secrets.FTP_SERVER }}
             username: ${{ secrets.FTP_USERNAME }}
             password: ${{ secrets.FTP_PASSWORD }}
             local-dir: ./ # Sunucuya gönderilecek yerel klasör (Örn: ./ veya ./public/)
             server-dir: / # Sunucudaki hedef klasör (Örn: /public_html/)
             exclude: |
               **/.git*
               **/.git*/**
               **/node_modules/**
   ```
   **Not:** `local-dir` ve `server-dir` alanlarını projeye göre düzenlemeyi unutma.

2. **Dosyayı GitHub'a Gönder**
   
   ```bash
   git add .github/workflows/ftp-deploy.yml
   git commit -m "Feat: Add FTP Deploy Workflow"
   git push
   ```

3. **GitHub Secrets Ayarla**
   
   - GitHub deposuna git -> **Settings** -> **Secrets and variables** -> **Actions**
   - **New repository secret** butonuna basarak aşağıdaki 3 anahtarı ekle:
     - `FTP_SERVER`: ftp.siteadi.com
     - `FTP_USERNAME`: ftp_kullanici_adi
     - `FTP_PASSWORD`: ftp_sifresi

4. **Test Et**
   
   Herhangi bir dosyada değişiklik yap ve `git push` komutunu çalıştır. GitHub **Actions** sekmesinden dağıtımın başarılı olup olmadığını kontrol et.
