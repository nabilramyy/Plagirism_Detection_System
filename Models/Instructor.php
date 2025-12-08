<?php
namespace Models;

class Instructor {
    private $conn;

    public function __construct($conn = null) {
        // Prefer an explicitly passed connection
        if ($conn instanceof \mysqli) {
            $this->conn = $conn;
        } elseif (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof \mysqli) {
            // Try global connection set by includes/db.php
            $this->conn = $GLOBALS['conn'];
        } else {
            // Last resort: include db.php using project root
            $rootPath = dirname(__DIR__); // project root
            require_once $rootPath . '/includes/db.php';
            if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof \mysqli) {
                $this->conn = $GLOBALS['conn'];
            }
        }

        if (!$this->conn || !($this->conn instanceof \mysqli)) {
            die("Database connection not available in Instructor model. Please ensure includes/db.php is included and connection is established.");
        }
    }

    public function getInstructor($id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id=? AND role='instructor'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $instructor = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $instructor;
    }

    public function getSubmissions($instructor_id) {
        $submissions = [];

        // Fetch submissions where course instructor_id matches this instructor
        // Also include submissions where teacher name matches (for backward compatibility)
        $sql = "
            SELECT s.id, s.user_id, s.course_id, s.teacher, s.text_content, s.file_path, s.stored_name,
                   s.file_size, s.similarity, s.status, s.created_at, s.feedback,
                   u.name AS student_name, u.email AS student_email,
                   c.name AS course_name
            FROM submissions s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE (c.instructor_id = ? OR s.teacher = (SELECT name FROM users WHERE id = ? AND role='instructor'))
            AND s.status <> 'deleted'
            ORDER BY s.created_at DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $instructor_id, $instructor_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $submissions[] = $row;
        }

        $stmt->close();
        return $submissions;
    }

    public function getTrash($instructor_id) {
        $trash = [];

        // Fetch deleted submissions where course instructor_id matches this instructor
        // Also include submissions where teacher name matches (for backward compatibility)
        $sql = "
            SELECT s.id, s.user_id, s.course_id, s.teacher, s.text_content, s.file_path, s.stored_name,
                   s.file_size, s.similarity, s.status, s.created_at, s.feedback,
                   u.name AS student_name, u.email AS student_email,
                   c.name AS course_name
            FROM submissions s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE (c.instructor_id = ? OR s.teacher = (SELECT name FROM users WHERE id = ? AND role='instructor'))
            AND s.status = 'deleted'
            ORDER BY s.created_at DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $instructor_id, $instructor_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $trash[] = $row;
        }

        $stmt->close();
        return $trash;
    }

    /**
     * Get statistics for instructor dashboard
     * FIXED: Now properly counts submissions by status for THIS instructor
     */
    public function getStats($instructor_id = null) {
        // Get count of students enrolled (all students)
        $studentResult = $this->conn->query("SELECT COUNT(*) AS students_enrolled FROM users WHERE role='student'");
        $studentData = $studentResult->fetch_assoc();
        
        // If no instructor_id provided, try to get from session
        if ($instructor_id === null) {
            if (isset($_SESSION['user']['id'])) {
                $instructor_id = $_SESSION['user']['id'];
            } elseif (isset($_SESSION['user_id'])) {
                $instructor_id = $_SESSION['user_id'];
            } else {
                // Return default stats if no instructor ID
                return [
                    'students_enrolled' => $studentData['students_enrolled'] ?? 0,
                    'total_submissions' => 0,
                    'pending_submissions' => 0,
                    'accepted_submissions' => 0,
                    'rejected_submissions' => 0
                ];
            }
        }
        
        // Count total submissions (excluding deleted) for this instructor
        $totalQuery = "
            SELECT COUNT(*) as total_submissions
            FROM submissions s
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE (c.instructor_id = ? OR s.teacher = (SELECT name FROM users WHERE id = ? AND role='instructor'))
            AND s.status <> 'deleted'
        ";
        $stmt = $this->conn->prepare($totalQuery);
        $stmt->bind_param("ii", $instructor_id, $instructor_id);
        $stmt->execute();
        $totalResult = $stmt->get_result();
        $totalData = $totalResult->fetch_assoc();
        $stmt->close();
        
        // Count pending submissions for this instructor
        $pendingQuery = "
            SELECT COUNT(*) as pending_submissions
            FROM submissions s
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE (c.instructor_id = ? OR s.teacher = (SELECT name FROM users WHERE id = ? AND role='instructor'))
            AND s.status = 'pending'
        ";
        $stmt = $this->conn->prepare($pendingQuery);
        $stmt->bind_param("ii", $instructor_id, $instructor_id);
        $stmt->execute();
        $pendingResult = $stmt->get_result();
        $pendingData = $pendingResult->fetch_assoc();
        $stmt->close();
        
        // Count accepted submissions for this instructor
        $acceptedQuery = "
            SELECT COUNT(*) as accepted_submissions
            FROM submissions s
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE (c.instructor_id = ? OR s.teacher = (SELECT name FROM users WHERE id = ? AND role='instructor'))
            AND s.status = 'accepted'
        ";
        $stmt = $this->conn->prepare($acceptedQuery);
        $stmt->bind_param("ii", $instructor_id, $instructor_id);
        $stmt->execute();
        $acceptedResult = $stmt->get_result();
        $acceptedData = $acceptedResult->fetch_assoc();
        $stmt->close();
        
        // Count rejected submissions for this instructor
        $rejectedQuery = "
            SELECT COUNT(*) as rejected_submissions
            FROM submissions s
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE (c.instructor_id = ? OR s.teacher = (SELECT name FROM users WHERE id = ? AND role='instructor'))
            AND s.status = 'rejected'
        ";
        $stmt = $this->conn->prepare($rejectedQuery);
        $stmt->bind_param("ii", $instructor_id, $instructor_id);
        $stmt->execute();
        $rejectedResult = $stmt->get_result();
        $rejectedData = $rejectedResult->fetch_assoc();
        $stmt->close();
        
        return [
            'students_enrolled' => $studentData['students_enrolled'] ?? 0,
            'total_submissions' => $totalData['total_submissions'] ?? 0,
            'pending_submissions' => $pendingData['pending_submissions'] ?? 0,
            'accepted_submissions' => $acceptedData['accepted_submissions'] ?? 0,
            'rejected_submissions' => $rejectedData['rejected_submissions'] ?? 0
        ];
    }

    public function getEnrolledStudents() {
        $students = [];
        $result = $this->conn->query("SELECT * FROM users WHERE role='student'");
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        return $students;
    }

    /**
     * Get all instructors from database
     */
    public function getAllInstructors() {
        $instructors = [];
        $result = $this->conn->query("SELECT id, name, email FROM users WHERE role='instructor' ORDER BY name ASC");
        while ($row = $result->fetch_assoc()) {
            $instructors[] = $row;
        }
        return $instructors;
    }

    public function getByInstructor(int $instructor_id, string $status = 'active'): array {
        $stmt = $this->conn->prepare("
            SELECT s.*, u.name AS student_name, u.email AS student_email
            FROM submissions s
            JOIN users u ON s.user_id = u.id
            WHERE s.teacher = (
                SELECT name FROM users WHERE id = ?
            ) AND s.status = ?
            ORDER BY s.created_at DESC
        ");
        $stmt->bind_param("is", $instructor_id, $status);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Verify if instructor owns a submission
     */
    public function ownsSubmission(int $instructor_id, int $submission_id): bool {
        // Check if submission belongs to a course taught by this instructor
        // Or if teacher name matches (for backward compatibility)
        $stmt = $this->conn->prepare("
            SELECT s.id, s.teacher, c.instructor_id
            FROM submissions s
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE s.id = ?
        ");
        $stmt->bind_param("i", $submission_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $submission = $res->fetch_assoc();
        $stmt->close();
        
        if (!$submission) {
            return false;
        }
        
        // Check if course instructor matches
        if ($submission['instructor_id'] == $instructor_id) {
            return true;
        }
        
        // Check if teacher name matches (backward compatibility)
        $stmt = $this->conn->prepare("SELECT name FROM users WHERE id = ? AND role='instructor'");
        $stmt->bind_param("i", $instructor_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $instructor = $res->fetch_assoc();
        $stmt->close();
        
        return $instructor && $submission['teacher'] === $instructor['name'];
    }
 }