<?php
namespace Controllers;

require_once __DIR__ . '/../Models/Course.php';
require_once __DIR__ . '/../app/Helpers/Csrf.php';

use Models\Course;
use Helpers\Csrf;

class CourseController {
    protected $conn;
    protected $courseModel;

    public function __construct(?\mysqli $conn = null) {
        if ($conn === null) {
            require __DIR__ . '/../../includes/db.php';
            $this->conn = $conn;
        } else {
            $this->conn = $conn;
        }

        // Use shared connection for the Course model (treated as testConnection to avoid double-close)
        $this->courseModel = new Course($this->conn);
    }

    /**
     * List courses with optional search/instructor filter and simple pagination
     */
    public function getCourses(int $page = 1, int $limit = 10, string $search = '', $instructorId = ''): array {
        $filters = [];
        if ($search !== '') {
            $filters['search'] = $search;
        }
        if ($instructorId !== '' && $instructorId !== null) {
            $filters['instructor_id'] = intval($instructorId);
        }

        $allCourses = $this->courseModel->getAll($filters);
        $total = $this->courseModel->getCount($filters);

        $offset = max(0, ($page - 1) * $limit);
        $paged = array_slice($allCourses, $offset, $limit);

        return [
            'success' => true,
            'data' => $paged,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ];
    }

    /**
     * Get single course with instructor details
     */
    public function getCourse(int $id): array {
        $sql = "
            SELECT c.*, u.name AS instructor_name, u.email AS instructor_email
            FROM courses c
            LEFT JOIN users u ON c.instructor_id = u.id
            WHERE c.id = ?
            LIMIT 1
        ";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed'];
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        if (!$data) {
            return ['success' => false, 'message' => 'Course not found'];
        }

        return ['success' => true, 'data' => $data];
    }

    /**
     * Create a new course
     */
    public function addCourse(array $data): array {
        if (!Csrf::verify($data['_csrf'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid CSRF token'];
        }

        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $instructorId = intval($data['instructor_id'] ?? 0);

        if ($name === '' || $instructorId <= 0) {
            return ['success' => false, 'message' => 'Name and instructor are required'];
        }

        if ($this->courseModel->nameExists($name)) {
            return ['success' => false, 'message' => 'Course name already exists'];
        }

        // Validate instructor exists
        $instStmt = $this->conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'instructor'");
        $instStmt->bind_param("i", $instructorId);
        $instStmt->execute();
        $instResult = $instStmt->get_result();
        $hasInstructor = (bool) $instResult->fetch_assoc();
        $instStmt->close();

        if (!$hasInstructor) {
            return ['success' => false, 'message' => 'Instructor not found'];
        }

        $this->courseModel->setName($name);
        $this->courseModel->setDescription($description);
        $this->courseModel->setInstructorId($instructorId);

        $created = $this->courseModel->save();
        if (!$created) {
            return ['success' => false, 'message' => 'Failed to create course'];
        }

        $newId = $this->courseModel->getId();
        $courseData = $this->getCourse($newId);

        return [
            'success' => true,
            'message' => 'Course created successfully',
            'data' => $courseData['data'] ?? null
        ];
    }

    /**
     * Edit an existing course
     */
    public function editCourse(array $data): array {
        if (!Csrf::verify($data['_csrf'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid CSRF token'];
        }

        $courseId = intval($data['course_id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $instructorId = intval($data['instructor_id'] ?? 0);

        if ($courseId <= 0 || $name === '' || $instructorId <= 0) {
            return ['success' => false, 'message' => 'Course ID, name, and instructor are required'];
        }

        if (!$this->courseModel->findById($courseId)) {
            return ['success' => false, 'message' => 'Course not found'];
        }

        if ($this->courseModel->nameExists($name, $courseId)) {
            return ['success' => false, 'message' => 'Course name already exists'];
        }

        // Validate instructor exists
        $instStmt = $this->conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'instructor'");
        $instStmt->bind_param("i", $instructorId);
        $instStmt->execute();
        $instResult = $instStmt->get_result();
        $hasInstructor = (bool) $instResult->fetch_assoc();
        $instStmt->close();

        if (!$hasInstructor) {
            return ['success' => false, 'message' => 'Instructor not found'];
        }

        $this->courseModel->setName($name);
        $this->courseModel->setDescription($description);
        $this->courseModel->setInstructorId($instructorId);

        $updated = $this->courseModel->update();
        if (!$updated) {
            return ['success' => false, 'message' => 'Failed to update course'];
        }

        $courseData = $this->getCourse($courseId);

        return [
            'success' => true,
            'message' => 'Course updated successfully',
            'data' => $courseData['data'] ?? null
        ];
    }

    /**
     * Delete a course
     */
    public function deleteCourse(int $id): array {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid course ID'];
        }

        if (!$this->courseModel->findById($id)) {
            return ['success' => false, 'message' => 'Course not found'];
        }

        $deleted = $this->courseModel->delete();
        if (!$deleted) {
            return ['success' => false, 'message' => 'Failed to delete course'];
        }

        return ['success' => true, 'message' => 'Course deleted successfully'];
    }

    /**
     * Get instructors list for dropdowns
     */
    public function getInstructors(): array {
        $instructors = [];
        $stmt = $this->conn->prepare("SELECT id, name, email FROM users WHERE role = 'instructor' ORDER BY name ASC");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $instructors[] = $row;
            }
            $stmt->close();
        }

        return [
            'success' => true,
            'data' => $instructors
        ];
    }
}
