<?php
/**
 * Class Event - Ruang Unila
 * 
 * Kelas ini menangani operasi CRUD untuk event
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
 * Kelas Event untuk operasi database event
 */
class Event {
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
     * Mendapatkan event berdasarkan ID
     * 
     * @param int $eventId ID event
     * @return array|null Data event atau null
     */
    public function getById(int $eventId): ?array {
        $sql = "SELECT e.event_id, e.title, e.description, e.location, 
                       e.event_date, e.registration_deadline, e.max_participants,
                       e.fee, e.bank_name, e.bank_account_number, e.bank_account_name,
                       e.organizer_id, e.image, e.status, e.created_at,
                       u.full_name as organizer_name
                FROM events e
                LEFT JOIN users u ON e.organizer_id = u.user_id
                WHERE e.event_id = :event_id 
                LIMIT 1";
        
        return $this->db->fetchOne($sql, [':event_id' => $eventId]);
    }
    
    /**
     * Mendapatkan semua event dengan pagination
     * 
     * @param int $page Halaman saat ini
     * @param int $perPage Jumlah per halaman
     * @param string|null $status Filter status (optional)
     * @return array Data event dan total
     */
    public function getAll(int $page = 1, int $perPage = 10, ?string $status = null): array {
        $offset = getPaginationOffset($page, $perPage);
        
        // Hitung total
        $countSql = "SELECT COUNT(*) as total FROM events e";
        $countParams = [];
        
        if ($status) {
            $countSql .= " WHERE e.status = :status";
            $countParams[':status'] = $status;
        }
        
        $countResult = $this->db->fetchOne($countSql, $countParams);
        $total = $countResult ? (int) $countResult['total'] : 0;
        
        // Ambil data
        $sql = "SELECT e.event_id, e.title, e.description, e.location, 
                       e.event_date, e.registration_deadline, e.max_participants,
                       e.fee, e.bank_name, e.bank_account_number, e.bank_account_name,
                       e.organizer_id, e.image, e.status, e.created_at,
                       u.full_name as organizer_name
                FROM events e
                LEFT JOIN users u ON e.organizer_id = u.user_id";
        
        $params = [];
        
        if ($status) {
            $sql .= " WHERE e.status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY e.event_date ASC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;
        
        $stmt = $this->db->executeQuery($sql, $params);
        $events = $stmt ? $stmt->fetchAll() : [];
        
        return [
            'events' => $events,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => getTotalPages($total, $perPage)
        ];
    }
    
    public function getByOrganizer(int $organizerId, int $page = 1, int $perPage = 10, ?string $status = null): array {
        $offset = getPaginationOffset($page, $perPage);
        
        // Hitung total
        $countSql = "SELECT COUNT(*) as total FROM events WHERE organizer_id = :organizer_id";
        $countParams = [':organizer_id' => $organizerId];
        
        if ($status) {
            $countSql .= " AND status = :status";
            $countParams[':status'] = $status;
        }
        
        $countResult = $this->db->fetchOne($countSql, $countParams);
        $total = $countResult ? (int) $countResult['total'] : 0;
        
        // Ambil data
        $sql = "SELECT e.event_id, e.title, e.description, e.location, 
                       e.event_date, e.registration_deadline, e.max_participants,
                       e.fee, e.bank_name, e.bank_account_number, e.bank_account_name,
                       e.organizer_id, e.image, e.status, e.created_at
                FROM events e
                WHERE e.organizer_id = :organizer_id";
        
        $params = [':organizer_id' => $organizerId];
        
        if ($status) {
            $sql .= " AND e.status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY e.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;
        
        $stmt = $this->db->executeQuery($sql, $params);
        $events = $stmt ? $stmt->fetchAll() : [];
        
        return [
            'events' => $events,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => getTotalPages($total, $perPage)
        ];
    }
    
    /**
     * Insert event baru ke database
     * 
     * @param array $data Data event
     * @return int|false ID event atau false jika gagal
     */
    public function insert(array $data): int|false {
        $sql = "INSERT INTO events (title, description, location, event_date, 
                registration_deadline, max_participants, fee, 
                bank_name, bank_account_number, bank_account_name,
                organizer_id, image, status) 
                VALUES (:title, :description, :location, :event_date, 
                :registration_deadline, :max_participants, :fee, 
                :bank_name, :bank_account_number, :bank_account_name,
                :organizer_id, :image, :status)";
        
        $params = [
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':location' => $data['location'],
            ':event_date' => $data['event_date'],
            ':registration_deadline' => $data['registration_deadline'],
            ':max_participants' => $data['max_participants'],
            ':fee' => $data['fee'],
            ':bank_name' => $data['bank_name'] ?? null,
            ':bank_account_number' => $data['bank_account_number'] ?? null,
            ':bank_account_name' => $data['bank_account_name'] ?? null,
            ':organizer_id' => $data['organizer_id'],
            ':image' => $data['image'] ?? null,
            ':status' => $data['status'] ?? 'pending'
        ];
        
        try {
            $stmt = $this->db->executeQuery($sql, $params);
            
            if ($stmt) {
                return (int) $this->db->lastInsertId();
            }
            
            return false;
        } catch (\Exception $e) {
            // Log error untuk debugging
            error_log("Event insert error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update data event
     * 
     * @param int $eventId ID event
     * @param array $data Data yang akan diupdate
     * @return bool True jika berhasil
     */
    public function update(int $eventId, array $data): bool {
        $fields = [];
        $params = [':event_id' => $eventId];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        
        $sql = "UPDATE events SET " . implode(', ', $fields) . " WHERE event_id = :event_id";
        
        $stmt = $this->db->executeQuery($sql, $params);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    /**
     * Update status event
     * 
     * @param int $eventId ID event
     * @param string $status Status baru
     * @return bool True jika berhasil
     */
    public function updateStatus(int $eventId, string $status): bool {
        $sql = "UPDATE events SET status = :status WHERE event_id = :event_id";
        $stmt = $this->db->executeQuery($sql, [':status' => $status, ':event_id' => $eventId]);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    /**
     * Hapus event
     * 
     * @param int $eventId ID event
     * @return bool True jika berhasil
     */
    public function delete(int $eventId): bool {
        $sql = "DELETE FROM events WHERE event_id = :event_id";
        $stmt = $this->db->executeQuery($sql, [':event_id' => $eventId]);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    /**
     * Mendapatkan event upcoming
     * 
     * @param int $limit Jumlah event
     * @return array Data event
     */
    public function getUpcoming(int $limit = 5): array {
        $sql = "SELECT event_id, title, location, event_date, registration_deadline, 
                       max_participants, fee, image 
                FROM events 
                WHERE status = 'upcoming' 
                AND event_date >= NOW()
                ORDER BY event_date ASC 
                LIMIT :limit";
        
        $stmt = $this->db->executeQuery($sql, [':limit' => $limit]);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Cek apakah user sudah terdaftar di event
     * 
     * @param int $eventId ID event
     * @param int $userId ID user
     * @return bool True jika sudah terdaftar (status paid/confirmed)
     */
    public function isUserRegistered(int $eventId, int $userId): bool {
        $sql = "SELECT COUNT(*) as count FROM event_registrations 
                WHERE event_id = :event_id AND user_id = :user_id 
                AND status IN ('confirmed', 'paid')";
        $result = $this->db->fetchOne($sql, [':event_id' => $eventId, ':user_id' => $userId]);
        
        // Debug mode: tampilkan informasi query
        if (isset($_GET['debug']) && $_GET['debug'] === 'event_register') {
            error_log("DEBUG isUserRegistered:");
            error_log("  Event ID: $eventId");
            error_log("  User ID: $userId");
            error_log("  Query: $sql");
            error_log("  Params: event_id=$eventId, user_id=$userId");
            error_log("  Result: " . ($result ? $result['count'] : 'null'));
            error_log("  Return: " . ($result && $result['count'] > 0 ? 'true' : 'false'));
        }
        
        return $result && $result['count'] > 0;
    }
    
    /**
     * Hitung jumlah peserta event
     * 
     * @param int $eventId ID event
     * @return int Jumlah peserta
     */
    public function countParticipants(int $eventId): int {
        $sql = "SELECT COUNT(*) as count FROM event_registrations WHERE event_id = :event_id";
        $result = $this->db->fetchOne($sql, [':event_id' => $eventId]);
        return $result ? (int) $result['count'] : 0;
    }
    
    /**
     * Hitung sisa kuota event
     * 
     * @param int $eventId ID event
     * @return int Sisa kuota
     */
    public function getRemainingQuota(int $eventId): int {
        $event = $this->getById($eventId);
        if (!$event) return 0;
        
        $participants = $this->countParticipants($eventId);
        return max(0, $event['max_participants'] - $participants);
    }
    
    /**
     * Hitung jumlah event per status
     * 
     * @return array Jumlah event per status
     */
    public function countByStatus(): array {
        $sql = "SELECT status, COUNT(*) as count FROM events GROUP BY status";
        $result = $this->db->fetchAll($sql);
        
        $counts = [
            'upcoming' => 0,
            'ongoing' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'pending' => 0
        ];
        
        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }
        
        return $counts;
    }

    /**
     * Hitung jumlah event per status berdasarkan penyelenggara
     * 
     * @param int $organizerId ID penyelenggara
     * @return array Jumlah event per status
     */
    public function countByStatusByOrganizer(int $organizerId): array {
        $sql = "SELECT status, COUNT(*) as count FROM events WHERE organizer_id = :organizer_id GROUP BY status";
        $result = $this->db->fetchAll($sql, [':organizer_id' => $organizerId]);
        
        $counts = [
            'upcoming' => 0,
            'ongoing' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'pending' => 0
        ];
        
        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }
        
        return $counts;
    }
}
?>