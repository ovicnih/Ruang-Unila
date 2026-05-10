<?php
/**
 * Class User - Ruang Unila
 * 
 * Kelas ini menangani operasi CRUD untuk user
 * dengan pattern OOP dan prepared statements.
 * 
 * @package RuangUnila
 * @subpackage Classes
 * @version 1.0.0
 */

// Cegah akses langsung
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Kelas User untuk operasi database user
 */
class User {
    /**
     * Instance database
     * @var Database
     */
    public Database $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Mendapatkan user berdasarkan ID
     * 
     * @param int $userId ID user
     * @return array|null Data user atau null
     */
    public function getById(int $userId): ?array {
        $sql = "SELECT user_id, username, email, full_name, role, profile_photo, status, created_at 
                FROM users 
                WHERE user_id = :user_id 
                LIMIT 1";
        
        return $this->db->fetchOne($sql, [':user_id' => $userId]);
    }
    
    /**
     * Mendapatkan user berdasarkan email
     * 
     * @param string $email Email user
     * @return array|null Data user atau null
     */
    public function getByEmail(string $email): ?array {
        $sql = "SELECT user_id, username, email, full_name, role, profile_photo, status, created_at 
                FROM users 
                WHERE email = :email 
                LIMIT 1";
        
        return $this->db->fetchOne($sql, [':email' => $email]);
    }
    
    /**
     * Mendapatkan user berdasarkan username
     * 
     * @param string $username Username user
     * @return array|null Data user atau null
     */
    public function getByUsername(string $username): ?array {
        $sql = "SELECT user_id, username, email, full_name, role, profile_photo, status, created_at 
                FROM users 
                WHERE username = :username 
                LIMIT 1";
        
        return $this->db->fetchOne($sql, [':username' => $username]);
    }
    
    /**
     * Mendapatkan semua user dengan pagination
     * 
     * @param int $page Halaman saat ini
     * @param int $perPage Jumlah per halaman
     * @param string|null $role Filter role (optional)
     * @return array Data user dan total
     */
    public function getAll(int $page = 1, int $perPage = 10, ?string $role = null): array {
        $offset = getPaginationOffset($page, $perPage);
        
        // Hitung total
        $countSql = "SELECT COUNT(*) as total FROM users";
        $countParams = [];
        
        if ($role) {
            $countSql .= " WHERE role = :role";
            $countParams[':role'] = $role;
        }
        
        $countResult = $this->db->fetchOne($countSql, $countParams);
        $total = $countResult ? (int) $countResult['total'] : 0;
        
        // Ambil data
        $sql = "SELECT user_id, username, email, full_name, role, profile_photo, status, created_at 
                FROM users";
        
        $params = [];
        
        if ($role) {
            $sql .= " WHERE role = :role";
            $params[':role'] = $role;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;
        
        $stmt = $this->db->executeQuery($sql, $params);
        $users = $stmt ? $stmt->fetchAll() : [];
        
        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => getTotalPages($total, $perPage)
        ];
    }
    
    /**
     * Update data user
     * 
     * @param int $userId ID user
     * @param array $data Data yang akan diupdate
     * @return bool True jika berhasil
     */
    public function update(int $userId, array $data): bool {
        $fields = [];
        $params = [':user_id' => $userId];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = :user_id";
        
        $stmt = $this->db->executeQuery($sql, $params);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    /**
     * Update status user
     * 
     * @param int $userId ID user
     * @param string $status Status baru
     * @return bool True jika berhasil
     */
    public function updateStatus(int $userId, string $status): bool {
        $sql = "UPDATE users SET status = :status WHERE user_id = :user_id";
        $stmt = $this->db->executeQuery($sql, [':status' => $status, ':user_id' => $userId]);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    /**
     * Update password user
     * 
     * @param int $userId ID user
     * @param string $password Password baru (sudah dihash)
     * @return bool True jika berhasil
     */
    public function updatePassword(int $userId, string $password): bool {
        $sql = "UPDATE users SET password = :password WHERE user_id = :user_id";
        $stmt = $this->db->executeQuery($sql, [':password' => $password, ':user_id' => $userId]);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    /**
     * Update foto profil user
     * 
     * @param int $userId ID user
     * @param string $photoPath Path foto baru
     * @return bool True jika berhasil
     */
    public function updatePhoto(int $userId, string $photoPath): bool {
        $sql = "UPDATE users SET profile_photo = :photo WHERE user_id = :user_id";
        $stmt = $this->db->executeQuery($sql, [':photo' => $photoPath, ':user_id' => $userId]);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    /**
     * Hapus user
     * 
     * @param int $userId ID user
     * @return bool True jika berhasil
     */
    public function delete(int $userId): bool {
        $sql = "DELETE FROM users WHERE user_id = :user_id";
        $stmt = $this->db->executeQuery($sql, [':user_id' => $userId]);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    /**
     * Hitung jumlah user per role
     * 
     * @return array Jumlah user per role
     */
    public function countByRole(): array {
        $sql = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
        $result = $this->db->fetchAll($sql);
        
        $counts = [
            'admin' => 0,
            'organisation' => 0,
            'student' => 0
        ];
        
        foreach ($result as $row) {
            $counts[$row['role']] = (int) $row['count'];
        }
        
        return $counts;
    }
}
?>