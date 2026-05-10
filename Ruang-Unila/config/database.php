<?php
/**
 * Database Connection Class - Ruang Unila
 * 
 * Kelas ini menangani koneksi database menggunakan PDO
 * dengan prepared statements untuk keamanan SQL Injection.
 * 
 * @package RuangUnila
 * @version 1.0.0
 * @author Ruang Unila Team
 */

// Cegah akses langsung
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

class Database {
    /**
     * Host database
     * @var string
     */
    private string $host = "localhost";

    /**
     * Nama database
     * @var string
     */
    private string $db_name = "ruangunila";

    /**
     * Username database
     * @var string
     */
    private string $username = "root";

    /**
     * Password database (kosong untuk XAMPP)
     * @var string
     */
    private string $password = "";

    /**
     * Charset database
     * @var string
     */
    private string $charset = "utf8mb4";

    /**
     * Koneksi PDO
     * @var PDO|null
     */
    public ?PDO $conn = null;

    /**
     * Instance singleton
     * @var Database|null
     */
    private static ?Database $instance = null;

    /**
     * Constructor privat untuk singleton pattern
     */
    private function __construct() {
        // Baca konfigurasi dari environment jika ada
        $this->host = $_ENV['DB_HOST'] ?? $this->host;
        $this->db_name = $_ENV['DB_NAME'] ?? $this->db_name;
        $this->username = $_ENV['DB_USER'] ?? $this->username;
        $this->password = $_ENV['DB_PASS'] ?? $this->password;
    }

    /**
     * Mencegah cloning instance
     */
    private function __clone() {}

    /**
     * Mendapatkan instance Database (singleton)
     * 
     * @return Database Instance database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Mendapatkan koneksi PDO ke database
     * 
     * @return PDO|null Koneksi PDO atau null jika gagal
     */
    public function getConnection(): ?PDO {
        // Jika koneksi sudah ada, kembalikan
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            // Data Source Name (DSN)
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            
            // Opsi PDO untuk keamanan dan performa
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true,
            ];
            
            // Buat koneksi PDO
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Set charset setelah koneksi berhasil
            $this->conn->exec("SET NAMES '{$this->charset}' COLLATE '{$this->charset}_unicode_ci'");
            
            return $this->conn;
            
        } catch (PDOException $e) {
            error_log("Database Connection Error: {$e->getMessage()}");
            
            if (defined('APP_ENV') && APP_ENV === 'development') {
                die("Database connection failed: {$e->getMessage()}");
            }
            
            die("Database connection failed. Please try again later.");
            return null;
        }
    }

    /**
     * Menutup koneksi database
     */
    public function closeConnection(): void {
        $this->conn = null;
    }

    /**
     * Mengecek apakah tabel ada
     * 
     * @param string $tableName Nama tabel
     * @return bool True jika tabel ada
     */
    public function tableExists(string $tableName): bool {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Table check error: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Mendapatkan jumlah baris dalam tabel
     * 
     * @param string $tableName Nama tabel
     * @return int Jumlah baris
     */
    public function getRowCount(string $tableName): int {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$tableName`");
            $result = $stmt->fetch();
            return (int) $result['count'];
        } catch (PDOException $e) {
            error_log("Row count error: {$e->getMessage()}");
            return 0;
        }
    }

    /**
     * Menjalankan query dengan prepared statement
     * 
     * @param string $sql Query SQL dengan placeholder
     * @param array $params Parameter untuk prepared statement
     * @return PDOStatement|null Statement atau null jika gagal
     */
    public function executeQuery(string $sql, array $params = []): ?PDOStatement {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query execution error: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Mendapatkan satu baris hasil query
     * 
     * @param string $sql Query SQL dengan placeholder
     * @param array $params Parameter untuk prepared statement
     * @return array|null Array asosiatif atau null
     */
    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->executeQuery($sql, $params);
        if ($stmt) {
            return $stmt->fetch() ?: null;
        }
        return null;
    }

    /**
     * Mendapatkan semua baris hasil query
     * 
     * @param string $sql Query SQL dengan placeholder
     * @param array $params Parameter untuk prepared statement
     * @return array Array of arrays
     */
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->executeQuery($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Memulai transaksi database
     * 
     * @return bool True jika berhasil
     */
    public function beginTransaction(): bool {
        try {
            $pdo = $this->getConnection();
            return $pdo->beginTransaction();
        } catch (PDOException $e) {
            error_log("Begin transaction error: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Commit transaksi database
     * 
     * @return bool True jika berhasil
     */
    public function commit(): bool {
        try {
            $pdo = $this->getConnection();
            return $pdo->commit();
        } catch (PDOException $e) {
            error_log("Commit error: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Rollback transaksi database
     * 
     * @return bool True jika berhasil
     */
    public function rollback(): bool {
        try {
            $pdo = $this->getConnection();
            return $pdo->rollBack();
        } catch (PDOException $e) {
            error_log("Rollback error: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Mendapatkan ID terakhir yang di-insert
     * 
     * @return string ID terakhir
     */
    public function lastInsertId(): string {
        try {
            $pdo = $this->getConnection();
            return $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Last insert ID error: {$e->getMessage()}");
            return "";
        }
    }
}
?>