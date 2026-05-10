<?php
/**
 * Class News - Ruang Unila
 * 
 * Kelas ini menangani operasi CRUD untuk news
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
 * Kelas News untuk operasi database news
 */
class News {
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
     * Mendapatkan news berdasarkan ID
     * 
     * @param int $newsId ID news
     * @return array|null Data news atau null
     */
    public function getById(int $newsId): ?array {
        $sql = "SELECT n.news_id, n.title, n.content, n.category, n.author_id, 
                       n.image, n.status, n.views, n.created_at, n.published_at,
                       u.full_name as author_name, u.profile_photo as author_photo
                FROM news n
                LEFT JOIN users u ON n.author_id = u.user_id
                WHERE n.news_id = :news_id 
                LIMIT 1";
        
        return $this->db->fetchOne($sql, [':news_id' => $newsId]);
    }
    
    /**
     * Mendapatkan semua news dengan pagination
     * 
     * @param int $page Halaman saat ini
     * @param int $perPage Jumlah per halaman
     * @param string|null $category Filter kategori (optional)
     * @param string|null $status Filter status (optional)
     * @return array Data news dan total
     */
    public function getAll(int $page = 1, int $perPage = 10, ?string $category = null, ?string $status = null): array {
        $offset = getPaginationOffset($page, $perPage);
        
        // Hitung total
        $countSql = "SELECT COUNT(*) as total FROM news n";
        $countParams = [];
        $countWhere = [];
        
        if ($category) {
            $countWhere[] = "n.category = :category";
            $countParams[':category'] = $category;
        }
        
        if ($status) {
            $countWhere[] = "n.status = :status";
            $countParams[':status'] = $status;
        }
        
        if (!empty($countWhere)) {
            $countSql .= " WHERE " . implode(' AND ', $countWhere);
        }
        
        $countResult = $this->db->fetchOne($countSql, $countParams);
        $total = $countResult ? (int) $countResult['total'] : 0;
        
        // Ambil data
        $sql = "SELECT n.news_id, n.title, n.content, n.category, n.author_id, 
                       n.image, n.status, n.views, n.created_at, n.published_at,
                       u.full_name as author_name, u.profile_photo as author_photo
                FROM news n
                LEFT JOIN users u ON n.author_id = u.user_id";
        
        $params = [];
        $where = [];
        
        if ($category) {
            $where[] = "n.category = :category";
            $params[':category'] = $category;
        }
        
        if ($status) {
            $where[] = "n.status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY n.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;
        
        $stmt = $this->db->executeQuery($sql, $params);
        $news = $stmt ? $stmt->fetchAll() : [];
        
        return [
            'news' => $news,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => getTotalPages($total, $perPage)
        ];
    }
    
    /**
     * Insert news baru ke database
     * 
     * @param array $data Data news
     * @return int|null ID news atau null jika gagal
     */
    public function insert(array $data): ?int {
        $sql = "INSERT INTO news (title, content, category, author_id, image, status) 
                VALUES (:title, :content, :category, :author_id, :image, :status)";
        
        $params = [
            ':title' => $data['title'],
            ':content' => $data['content'],
            ':category' => $data['category'],
            ':author_id' => $data['author_id'],
            ':image' => $data['image'] ?? null,
            ':status' => $data['status'] ?? 'draft'
        ];
        
        $stmt = $this->db->executeQuery($sql, $params);
        
        if ($stmt) {
            return (int) $this->db->lastInsertId();
        }
        
        return null;
    }
    
    /**
     * Update data news
     * 
     * @param int $newsId ID news
     * @param array $data Data yang akan diupdate
     * @return bool True jika berhasil
     */
    public function update(int $newsId, array $data): bool {
        $fields = [];
        $params = [':news_id' => $newsId];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        
        $sql = "UPDATE news SET " . implode(', ', $fields) . " WHERE news_id = :news_id";
        
        $stmt = $this->db->executeQuery($sql, $params);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    /**
     * Update status news
     * 
     * @param int $newsId ID news
     * @param string $status Status baru
     * @return bool True jika berhasil
     */
    public function updateStatus(int $newsId, string $status): bool {
        $params = [':status' => $status, ':news_id' => $newsId];
        
        if ($status === 'published') {
            $sql = "UPDATE news SET status = :status, published_at = NOW() WHERE news_id = :news_id";
        } else {
            $sql = "UPDATE news SET status = :status WHERE news_id = :news_id";
        }
        
        $stmt = $this->db->executeQuery($sql, $params);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    /**
     * Increment views news
     * 
     * @param int $newsId ID news
     * @return bool True jika berhasil
     */
    public function incrementViews(int $newsId): bool {
        $sql = "UPDATE news SET views = views + 1 WHERE news_id = :news_id";
        $stmt = $this->db->executeQuery($sql, [':news_id' => $newsId]);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    /**
     * Hapus news
     * 
     * @param int $newsId ID news
     * @return bool True jika berhasil
     */
    public function delete(int $newsId): bool {
        $sql = "DELETE FROM news WHERE news_id = :news_id";
        $stmt = $this->db->executeQuery($sql, [':news_id' => $newsId]);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    /**
     * Mendapatkan news populer (views tertinggi)
     * 
     * @param int $limit Jumlah news
     * @return array Data news
     */
    public function getPopular(int $limit = 5): array {
        $sql = "SELECT news_id, title, category, views, image, created_at 
                FROM news 
                WHERE status = 'published' 
                ORDER BY views DESC 
                LIMIT :limit";
        
        $stmt = $this->db->executeQuery($sql, [':limit' => $limit]);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Mendapatkan news terbaru
     * 
     * @param int $limit Jumlah news
     * @return array Data news
     */
    public function getLatest(int $limit = 5): array {
        $sql = "SELECT news_id, title, category, views, image, created_at 
                FROM news 
                WHERE status = 'published' 
                ORDER BY published_at DESC 
                LIMIT :limit";
        
        $stmt = $this->db->executeQuery($sql, [':limit' => $limit]);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Hitung jumlah news per kategori
     * 
     * @return array Jumlah news per kategori
     */
    public function countByCategory(): array {
        $sql = "SELECT category, COUNT(*) as count FROM news WHERE status = 'published' GROUP BY category";
        $result = $this->db->fetchAll($sql);
        
        $counts = [];
        foreach (NEWS_CATEGORIES as $slug => $name) {
            $counts[$slug] = 0;
        }
        
        foreach ($result as $row) {
            $counts[$row['category']] = (int) $row['count'];
        }
        
        return $counts;
    }
    
    /**
     * Hitung jumlah news per status
     * 
     * @return array Jumlah news per status
     */
    public function countByStatus(): array {
        $sql = "SELECT status, COUNT(*) as count FROM news GROUP BY status";
        $result = $this->db->fetchAll($sql);
        
        $counts = [
            'draft' => 0,
            'pending' => 0,
            'published' => 0,
            'rejected' => 0
        ];
        
        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }
        
        return $counts;
    }
    
    /**
     * Mendapatkan news terkait (same category, exclude current)
     * 
     * @param int $newsId ID news saat ini
     * @param string $category Kategori news
     * @param int $limit Jumlah news terkait
     * @return array Data news terkait
     */
    public function getRelated(int $newsId, string $category, int $limit = 3): array {
        $sql = "SELECT news_id, title, category, image, created_at 
                FROM news 
                WHERE category = :category 
                AND news_id != :news_id 
                AND status = 'published' 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->db->executeQuery($sql, [
            ':category' => $category,
            ':news_id' => $newsId,
            ':limit' => $limit
        ]);
        
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Mendapatkan news berdasarkan author
     * 
     * @param int $authorId ID author
     * @param int $page Halaman saat ini
     * @param int $perPage Jumlah per halaman
     * @param string|null $status Filter status (optional)
     * @return array Data news dan total
     */
    public function getByAuthor(int $authorId, int $page = 1, int $perPage = 10, ?string $status = null): array {
        $offset = getPaginationOffset($page, $perPage);
        
        // Hitung total
        $countSql = "SELECT COUNT(*) as total FROM news WHERE author_id = :author_id";
        $countParams = [':author_id' => $authorId];
        
        if ($status) {
            $countSql .= " AND status = :status";
            $countParams[':status'] = $status;
        }
        
        $countResult = $this->db->fetchOne($countSql, $countParams);
        $total = $countResult ? (int) $countResult['total'] : 0;
        
        // Ambil data
        $sql = "SELECT news_id, title, category, image, status, views, created_at 
                FROM news 
                WHERE author_id = :author_id";
        
        $params = [':author_id' => $authorId];
        
        if ($status) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;
        
        $stmt = $this->db->executeQuery($sql, $params);
        $news = $stmt ? $stmt->fetchAll() : [];
        
        return [
            'news' => $news,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => getTotalPages($total, $perPage)
        ];
    }
    
    /**
     * Hitung jumlah news per status berdasarkan author
     * 
     * @param int $authorId ID author
     * @return array Jumlah news per status
     */
    public function countByStatusByAuthor(int $authorId): array {
        $sql = "SELECT status, COUNT(*) as count FROM news WHERE author_id = :author_id GROUP BY status";
        $result = $this->db->fetchAll($sql, [':author_id' => $authorId]);
        
        $counts = [
            'draft' => 0,
            'pending' => 0,
            'published' => 0,
            'rejected' => 0
        ];
        
        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }
        
        return $counts;
    }
}
?>
