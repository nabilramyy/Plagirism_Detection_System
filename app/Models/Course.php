<?php

declare(strict_types=1);
namespace Models;


/**
 * Course Model - Handles all course database operations
 * Follows OOP principles and supports injected test/production connections
 */
class Course
{
    private \mysqli $db;
    private ?int $id            = null;
    private string $name        = '';
    private ?string $description = null;
    private int $instructor_id  = 0;
    private ?string $created_at = null;
    private ?string $updated_at = null;
    private bool $isTestConnection = false;

    /**
     * @param \mysqli|null $connection  Shared/injected DB connection.
     *                                  If null, you can still fall back to direct connection,
     *                                  but normally the controller injects the shared one.
     */
    public function __construct(?\mysqli $connection = null)
    {
        if ($connection !== null) {
            // Use provided connection (test or shared)
            $this->db              = $connection;
            $this->isTestConnection = true;   // do not close from here
        } else {
            // Direct connection fallback (if you still need it)
            $host   = 'localhost';
            $user   = 'root';
            $pass   = '';
            $dbname = 'pal';

            $this->db = new \mysqli($host, $user, $pass, $dbname);

            if ($this->db->connect_error) {
                die('Connection failed: ' . $this->db->connect_error);
            }

            $this->db->set_charset('utf8mb4');
        }
    }

    // ========== GETTERS ==========
    public function getId(): ?int          { return $this->id; }
    public function getName(): string      { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getInstructorId(): int { return $this->instructor_id; }
    public function getCreatedAt(): ?string{ return $this->created_at; }
    public function getUpdatedAt(): ?string{ return $this->updated_at; }

    // ========== SETTERS ==========
    public function setName(string $name): void
    {
        $this->name = htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8');
    }

    public function setDescription(?string $description): void
    {
        $desc = $description ?? '';
        $this->description = htmlspecialchars(trim($desc), ENT_QUOTES, 'UTF-8');
    }

    public function setInstructorId(int $instructor_id): void
    {
        $this->instructor_id = (int) $instructor_id;
    }

    // ========== DATABASE OPERATIONS ==========

    /**
     * Find course by ID and hydrate the model
     */
    public function findById(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT * FROM courses WHERE id = ?');
        if (!$stmt) {
            error_log('Prepare failed: ' . $this->db->error);
            return false;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $this->id            = (int) $row['id'];
            $this->name          = (string) $row['name'];
            $this->description   = $row['description'] ?? null;
            $this->instructor_id = (int) $row['instructor_id'];
            $this->created_at    = $row['created_at'] ?? null;
            $this->updated_at    = $row['updated_at'] ?? null;
            $stmt->close();
            return true;
        }

        $stmt->close();
        return false;
    }

    /**
     * Check if course name already exists (optionally excluding one ID)
     */
    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare(
                'SELECT id FROM courses WHERE name = ? AND id != ?'
            );
            if (!$stmt) {
                error_log('Prepare failed: ' . $this->db->error);
                return false;
            }
            $stmt->bind_param('si', $name, $excludeId);
        } else {
            $stmt = $this->db->prepare(
                'SELECT id FROM courses WHERE name = ?'
            );
            if (!$stmt) {
                error_log('Prepare failed: ' . $this->db->error);
                return false;
            }
            $stmt->bind_param('s', $name);
        }

        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    /**
     * Save new course to database
     */
    public function save(): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO courses (name, description, instructor_id, created_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())'
        );
        if (!$stmt) {
            error_log('Prepare failed: ' . $this->db->error);
            return false;
        }

        $stmt->bind_param(
            'ssi',
            $this->name,
            $this->description,
            $this->instructor_id
        );

        $success = $stmt->execute();
        if ($success) {
            $this->id = $stmt->insert_id;
        } else {
            error_log('Execute failed: ' . $stmt->error);
        }

        $stmt->close();
        return $success;
    }

    /**
     * Update existing course in database
     */
    public function update(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $stmt = $this->db->prepare(
            'UPDATE courses
             SET name = ?, description = ?, instructor_id = ?, updated_at = NOW()
             WHERE id = ?'
        );
        if (!$stmt) {
            error_log('Prepare failed: ' . $this->db->error);
            return false;
        }

        $stmt->bind_param(
            'ssii',
            $this->name,
            $this->description,
            $this->instructor_id,
            $this->id
        );

        $success = $stmt->execute();
        if (!$success) {
            error_log('Execute failed: ' . $stmt->error);
        }

        $stmt->close();
        return $success;
    }

    /**
     * Delete course from database
     */
    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $stmt = $this->db->prepare('DELETE FROM courses WHERE id = ?');
        if (!$stmt) {
            error_log('Prepare failed: ' . $this->db->error);
            return false;
        }

        $stmt->bind_param('i', $this->id);
        $success = $stmt->execute();
        if (!$success) {
            error_log('Execute failed: ' . $stmt->error);
        }

        $stmt->close();
        return $success;
    }

    /**
     * Get all courses with optional filters and instructor information
     *
     * @param array $filters ['search' => string, 'instructor_id' => int]
     * @return array
     */
    public function getAll(array $filters = []): array
    {
        $sql = "SELECT c.*,
                       u.id    AS instructor_user_id,
                       u.name  AS instructor_name,
                       u.email AS instructor_email
                FROM courses c
                LEFT JOIN users u ON c.instructor_id = u.id
                WHERE 1=1";

        $params = [];
        $types  = '';

        // Search filter by name or description
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql       .= ' AND (c.name LIKE ? OR c.description LIKE ?)';
            $params[]   = $searchTerm;
            $params[]   = $searchTerm;
            $types     .= 'ss';
        }

        // Filter by instructor
        if (!empty($filters['instructor_id'])) {
            $sql       .= ' AND c.instructor_id = ?';
            $params[]   = (int) $filters['instructor_id'];
            $types     .= 'i';
        }

        $sql .= ' ORDER BY c.created_at DESC';

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('Prepare failed: ' . $this->db->error);
            return [];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result  = $stmt->get_result();
        $courses = [];

        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }

        $stmt->close();
        return $courses;
    }

    /**
     * Get course count with optional filters
     */
    public function getCount(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM courses WHERE 1=1';

        $params = [];
        $types  = '';

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql       .= ' AND (name LIKE ? OR description LIKE ?)';
            $params[]   = $searchTerm;
            $params[]   = $searchTerm;
            $types     .= 'ss';
        }

        if (!empty($filters['instructor_id'])) {
            $sql       .= ' AND instructor_id = ?';
            $params[]   = (int) $filters['instructor_id'];
            $types     .= 'i';
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('Prepare failed: ' . $this->db->error);
            return 0;
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc() ?: ['total' => 0];
        $stmt->close();

        return (int) $row['total'];
    }

    /**
     * Close connection when done (only if this model created it)
     */
    public function __destruct()
    {
        // Do not close externally managed/test connections
        if (isset($this->db) && !$this->isTestConnection && !defined('PHPUNIT_RUNNING')) {
            $this->db->close();
        }
    }
}
