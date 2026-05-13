# MuslimDaily - Jadwal Sholat & Al-Quran Digital

Platform digital modern untuk memantau jadwal sholat, membaca Al-Quran, dan mendapatkan notifikasi ibadah secara otomatis melalui Telegram.

## Fitur Utama

- **Dashboard Modern**: Tampilan premium dengan mode gelap dan terang.
- **Deteksi Lokasi Otomatis**: Mendeteksi kota pengguna secara otomatis menggunakan Geolocation.
- **Jadwal Sholat Akurat**: Data real-time dari MyQuran API v3.
- **Al-Quran Digital**: Baca 114 surat lengkap dengan audio dan terjemahan.
- **Bot Telegram Interaktif**: Cek jadwal, ayat acak, dan ubah lokasi langsung dari Telegram.
- **Notifikasi Azan**: Pengingat otomatis 15 menit sebelum waktu sholat tiba via Telegram.

## Persyaratan Sistem

- PHP >= 8.2
- Composer
- Node.js & NPM
- SQLite (atau database lainnya)

## Instalasi Lokal

1. **Clone Repository**

    ```bash
    git clone https://github.com/umamumam/Waktu_Sholat.git
    cd Waktu_Sholat
    ```

2. **Instal Dependensi**

    ```bash
    composer install
    npm install
    ```

3. **Konfigurasi Environment**
   Salin file `.env.example` ke `.env` dan sesuaikan pengaturannya:

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4. **Migrasi Database**

    ```bash
    php artisan migrate
    ```

5. **Menjalankan Aplikasi**

    ```bash
    # Terminal 1 (Laravel Server)
    php artisan serve

    # Terminal 2 (Vite Assets)
    npm run dev
    ```

## Konfigurasi Telegram Bot

Tambahkan variabel berikut ke file `.env`:

```env
TELEGRAM_BOT_TOKEN=8833252135:AAFrP4xMnnpvrhmh2XD5FNs6uum5o6AXZNA
TELEGRAM_CHAT_ID=1138408697
DEFAULT_CITY=Pati
DEFAULT_CITY_ID=1638
```

Untuk menjalankan bot (Interactive & Notifications):

```bash
php artisan app:telegram-bot-poll
```

## Panduan Deployment ke VPS (Otomatis)

Agar bot Telegram dan notifikasi azan berjalan secara otomatis 24/7 di VPS, gunakan **Supervisor**.

1. **Instal Supervisor**

    ```bash
    sudo apt-get install supervisor
    ```

2. **Buat Konfigurasi Bot**
   Buat file baru di `/etc/supervisor/conf.d/muslim-bot.conf`:

    ```ini
    [program:muslim-bot]
    process_name=%(program_name)s
    command=php /var/www/html/wkt_sholat/artisan app:telegram-bot-poll
    autostart=true
    autorestart=true
    user=www-data
    redirect_stderr=true
    stdout_logfile=/var/www/html/wkt_sholat/storage/logs/telegram-bot.log
    ```

    _Sesuaikan `/var/www/html/wkt_sholat` dengan path project Anda di VPS._

3. **Aktifkan Konfigurasi**
    ```bash
    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl start muslim-bot
    ```

Bot sekarang akan berjalan secara otomatis di background dan akan restart sendiri jika terjadi error atau server mati.

## Lisensi

Aplikasi ini dikembangkan untuk kemaslahatan Ummah.
© 2026 MuslimDaily.
