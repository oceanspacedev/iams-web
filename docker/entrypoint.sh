#!/bin/sh
set -e

echo "=== Memulai Inisialisasi Container Audit-Flow ==="

# Menunggu koneksi MySQL siap jika DB_HOST diset
if [ -n "$DB_HOST" ]; then
    echo "Menunggu koneksi database MySQL di $DB_HOST:${DB_PORT:-3306}..."
    until php -r "
        try {
            \$pdo = new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'), [PDO::ATTR_TIMEOUT => 3]);
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " 2>/dev/null; do
        echo "Database belum siap, mencoba lagi dalam 2 detik..."
        sleep 2
    done
    echo "Koneksi database berhasil!"
fi

# Link storage publik jika belum ada
if [ ! -L /var/www/public/storage ]; then
    echo "Membuat symbolic link storage..."
    php artisan storage:link --force 2>/dev/null || true
fi

# Jalankan migrasi database otomatis
echo "Menjalankan migrasi database..."
php artisan migrate --force

# Optimasi cache untuk production
if [ "$APP_ENV" = "production" ]; then
    echo "Mengoptimalkan cache konfigurasi dan routing..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Pastikan hak akses folder storage dan cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

echo "=== Inisialisasi selesai. Menjalankan proses utama ==="

# Menjalankan perintah utama yang diteruskan ke docker (misal: php-fpm atau queue:work)
exec "$@"
