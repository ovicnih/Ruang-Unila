-- SQL Script untuk menambahkan kolom fee ke tabel events
-- Jalankan script ini di phpMyAdmin atau MySQL command line

-- 1. Tambahkan kolom fee jika belum ada
ALTER TABLE events ADD COLUMN IF NOT EXISTS fee DECIMAL(10,2) DEFAULT 0 AFTER location;

-- 2. Update data event yang sudah ada dengan fee default (gratis)
UPDATE events SET fee = 0 WHERE fee IS NULL;

-- 3. Set fee untuk event tertentu (contoh: event_id = 2 dengan fee 50000)
-- Uncomment dan jalankan baris berikut jika perlu:
-- UPDATE events SET fee = 50000 WHERE event_id = 2;

-- 4. Verifikasi kolom sudah ada
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT, IS_NULLABLE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'RuangUnila' 
  AND TABLE_NAME = 'events' 
  AND COLUMN_NAME = 'fee';

-- 5. Tampilkan semua event dengan fee
SELECT event_id, title, location, fee, event_date, registration_deadline 
FROM events;