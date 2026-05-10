# BAB 3.4.2 DIAGRAM KELAS

---

## 9.1 Struktur Kelas Sistem

```mermaid
classDiagram
    class Database {
        +PDO $connection
        +__construct()
        +query()
        +bind()
        +execute()
        +resultSet()
        +single()
        +rowCount()
    }

    class User {
        +int $id
        +string $name
        +string $email
        +string $password
        +int $role
        +register()
        +login()
        +update()
        +getById()
        +getAll()
        +changePassword()
    }

    class News {
        +int $id
        +string $title
        +string $content
        +string $image
        +int $user_id
        +int $category_id
        +create()
        +update()
        +delete()
        +getById()
        +getAll()
        +getPopular()
        +incrementViews()
    }

    class Event {
        +int $id
        +string $title
        +datetime $date
        +int $fee
        +int $quota
        +create()
        +update()
        +delete()
        +registerParticipant()
        +getParticipants()
        +confirmPayment()
    }

    class Category {
        +int $id
        +string $name
        +string $slug
        +getAll()
        +getById()
        +getNews()
        +getEvents()
    }

    Database <|-- User
    Database <|-- News
    Database <|-- Event
    Database <|-- Category

    User "1" --> "*" News : creates
    User "1" --> "*" Event : organizes
    Category "1" --> "*" News : has
    Category "1" --> "*" Event : has
```

---

## 9.2 Kelas Utama dan Tanggung Jawab

| Kelas | Tanggung Jawab | Jumlah Metode |
|---|---|---|
| `Database` | Koneksi dan operasi database umum | 7 |
| `User` | Manajemen pengguna, otentikasi | 8 |
| `News` | Manajemen konten berita | 9 |
| `Event` | Manajemen event dan peserta | 10 |
| `Category` | Manajemen kategori sistem | 5 |

✅ Semua kelas menggunakan prinsip inheritance dari kelas Database

---

## 9.3 Visibilitas Atribut dan Metode

✅ Semua atribut dideklarasikan sebagai `private`  
✅ Semua metode akses dideklarasikan sebagai `public`  
✅ Tidak ada atribut publik yang dapat diakses langsung  
✅ Semua operasi data melalui method getter dan setter

---

## 9.4 Relasi Antar Kelas

| Relasi | Kardinalitas |
|---|---|
| User → News | 1 to Many |
| User → Event | 1 to Many |
| Category → News | 1 to Many |
| Category → Event | 1 to Many |
| Event → User | Many to Many |

---

## 9.5 Prinsip OOP yang Diterapkan

✅ ✅ Encapsulation: Semua atribut private  
✅ ✅ Inheritance: Semua kelas turunan dari Database  
✅ ✅ Polymorphism: Metode dengan nama sama perilaku berbeda  
✅ ✅ Abstraction: Detail implementasi tersembunyi  
✅ ✅ Single Responsibility: Setiap kelas hanya satu tanggung jawab