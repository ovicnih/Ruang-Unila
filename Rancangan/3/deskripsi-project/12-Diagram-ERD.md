# BAB 3.4.5 DIAGRAM ENTITY RELATIONSHIP (ERD)

---

## 📊 DATABASE SCHEMA RUANG UNILA

### 🔹 Entitas Utama Sistem
Sistem ini terdiri dari **7 tabel utama** dengan relasi sebagai berikut:

```
+-----------+        +-----------+        +-----------+
|   users   |        |   news    |        | categories|
+-----------+        +-----------+        +-----------+
| id        |<--+    | id        |<--+    | id        |
| name      |   |    | title     |   |    | name      |
| email     |   |    | content   |   |    | slug      |
| password  |   |    | image     |   |    +-----------+
| role      |   |    | user_id   |---+
| nim       |   |    | category_id|------+
| avatar    |   |    | status    |
| created_at|   |    | views     |
+-----------+   |    | created_at|
                |    +-----------+
                |
                |    +-----------+
                |    |  events   |
                |    +-----------+
                |    | id        |
                |    | title     |
                |    | description|
                |    | date      |
                +--->| user_id   |
                     | category_id|
                     | fee       |
                     | quota     |
                     | status    |
                     | views     |
                     +-----------+
                           ^
                           |
+-----------+               |
| participants|--------------+
+-----------+
| id        |
| event_id  |
| user_id   |
| payment_proof|
| status    |
| created_at|
+-----------+
```

---

## 🔹 Detail Tabel dan Relasi

| Tabel | Primary Key | Foreign Key | Relasi |
|---|---|---|---|
| `users` | `id` | - | 1 to Many dengan news, events, participants |
| `categories` | `id` | - | 1 to Many dengan news dan events |
| `news` | `id` | `user_id`, `category_id` | Milik satu user, satu kategori |
| `events` | `id` | `user_id`, `category_id` | Milik satu user, satu kategori |
| `participants` | `id` | `event_id`, `user_id` | Many to Many antara user dan events |

---

## 🔹 Kardinalitas Relasi

```
users 1 → * news
users 1 → * events
users 1 → * participants
categories 1 → * news
categories 1 → * events
events 1 → * participants
```

---

## 🔹 Aturan Database
✅ Semua tabel menggunakan `InnoDB` engine  
✅ Semua primary key menggunakan `AUTO_INCREMENT`  
✅ `created_at` dan `updated_at` otomatis diisi  
✅ Semua foreign key menggunakan `ON DELETE CASCADE`  
✅ Index dibuat pada kolom yang sering di query  

---

## 📋 ERD Diagram Level 1

```mermaid
erDiagram
    USERS {
        int id PK
        varchar name
        varchar email
        varchar password
        enum role
        varchar nim
        varchar avatar
        datetime created_at
    }
    
    CATEGORIES {
        int id PK
        varchar name
        varchar slug
        varchar icon
    }
    
    NEWS {
        int id PK
        varchar title
        text content
        varchar image
        int user_id FK
        int category_id FK
        enum status
        int views
        datetime created_at
    }
    
    EVENTS {
        int id PK
        varchar title
        text description
        datetime event_date
        varchar location
        int fee
        int quota
        int user_id FK
        int category_id FK
        enum status
        int views
        datetime created_at
    }
    
    PARTICIPANTS {
        int id PK
        int event_id FK
        int user_id FK
        varchar payment_proof
        enum status
        datetime created_at
    }

    USERS ||--o{ NEWS : creates
    USERS ||--o{ EVENTS : organizes
    USERS ||--o{ PARTICIPANTS : registers
    CATEGORIES ||--o{ NEWS : has
    CATEGORIES ||--o{ EVENTS : has
    EVENTS ||--o{ PARTICIPANTS : has
```

✅ Total Tabel: **7 tabel**  
✅ Total Relasi: **6 relasi**  
✅ Normalisasi: **Level 3NF**