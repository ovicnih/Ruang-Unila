-- Script SQL untuk menambahkan kolom participant_name dan participant_email di tabel event_registrations
-- Tanggal: 2026-04-05
-- Deskripsi: Menambahkan kolom untuk menyimpan nama dan email peserta saat registrasi event

-- Tambah kolom participant_name
ALTER TABLE event_registrations 
ADD COLUMN participant_name VARCHAR(255) AFTER user_id;

-- Tambah kolom participant_email
ALTER TABLE event_registrations 
ADD COLUMN participant_email VARCHAR(255) AFTER participant_name;

-- Isi data untuk registrasi yang sudah ada (ambil dari tabel users)
UPDATE event_registrations er
LEFT JOIN users u ON er.user_id = u.user_id
SET er.participant_name = u.full_name,
    er.participant_email = u.email
WHERE er.participant_name IS NULL AND u.user_id IS NOT NULL;