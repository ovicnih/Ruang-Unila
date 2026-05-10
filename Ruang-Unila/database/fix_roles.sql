-- Fix Role Values in Database
-- Script ini akan mengubah role dari 'student'/'organisation' ke 'mahasiswa'/'organisasi'
-- Jika database Anda sudah menggunakan 'mahasiswa'/'organisasi', script ini tidak akan mengubah apa-apa

-- Update role 'student' ke 'mahasiswa'
UPDATE users SET role = 'mahasiswa' WHERE role = 'student';

-- Update role 'organisation' ke 'organisasi'
UPDATE users SET role = 'organisasi' WHERE role = 'organisation';

-- Verifikasi perubahan
SELECT user_id, username, email, role, full_name FROM users ORDER BY role, user_id;