Pencegahan SQL Injection: Selalu gunakan Prepared Statements (PDO atau MySQLi) untuk setiap interaksi dengan database MySQL. Dilarang memasukkan variabel langsung ke dalam query string.

Validasi & Sanitasi: Semua input dari user (terutama pada fitur pendaftaran dan pembayaran event) harus divalidasi tipe datanya dan disanitasi sebelum diproses.

Manajemen Environment: AI dilarang keras menuliskan API Key atau Password database langsung di dalam kode. Gunakan file .env dan AI hanya boleh memberikan contoh formatnya (misal: .env.example).


Otentikasi Berlapis: Pastikan fitur login membedakan hak akses secara ketat antara Mahasiswa, Organisasi, dan Admin.