<?php

require_once __DIR__ . '/DatabaseTestCase.php';

use PHPUnit\Framework\TestCase;

/**
 * Test Course Management (CourseController and Course Model)
 * Tests all CRUD operations for courses in the admin panel
 */
class CourseManagementTest extends DatabaseTestCase
{
    private $instructorId;
    private $instructorId2;
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test instructors with unique identifiers to avoid conflicts
        $timestamp = time();
        $this->instructorId = $this->createTestInstructor(
            'Instructor One', 
            "instructor1_{$timestamp}@test.com"
        );
        $this->instructorId2 = $this->createTestInstructor(
            'Instructor Two', 
            "instructor2_{$timestamp}@test.com"
        );

        // Initialize controller with test connection
        require_once __DIR__ . '/../app/Controllers/CourseController.php';
        $this->controller = new Controllers\CourseController(self::$conn);
    }

    /**
     * Helper: Create a test instructor user
     */
    private function createTestInstructor(string $name, string $email): int
    {
        // Check if instructor with this email already exists (shouldn't due to rollback, but safety check)
        $checkStmt = self::$conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->bind_param('s', $email);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $checkStmt->close();
            return (int)$row['id'];
        }
        $checkStmt->close();

        $stmt = self::$conn->prepare(
            "INSERT INTO users (name, email, role, password, status) 
             VALUES (?, ?, 'instructor', 'hashed_password', 'active')"
        );
        $stmt->bind_param('ss', $name, $email);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    /**
     * Helper: Add description column to courses table if it doesn't exist
     */
    private function ensureDescriptionColumn(): void
    {
        $result = self::$conn->query("SHOW COLUMNS FROM courses LIKE 'description'");
        if ($result->num_rows === 0) {
            self::$conn->query("ALTER TABLE courses ADD COLUMN description TEXT NULL AFTER name");
        }
        if ($result) {
            $result->close();
        }

        // Also ensure created_at and updated_at exist
        $result = self::$conn->query("SHOW COLUMNS FROM courses LIKE 'created_at'");
        if ($result->num_rows === 0) {
            self::$conn->query("ALTER TABLE courses ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER instructor_id");
        }
        if ($result) {
            $result->close();
        }

        $result = self::$conn->query("SHOW COLUMNS FROM courses LIKE 'updated_at'");
        if ($result->num_rows === 0) {
            self::$conn->query("ALTER TABLE courses ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        }
        if ($result) {
            $result->close();
        }
    }

    // ============================================
    // TEST COURSE CREATION
    // ============================================

    public function testCreateCourseSuccess(): void
    {
        $this->ensureDescriptionColumn();

        $data = [
            'name' => 'Introduction to PHP',
            'description' => 'Learn PHP basics',
            'instructor_id' => $this->instructorId
        ];

        $result = $this->controller->addCourse($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Course created successfully', $result['message']);
        $this->assertNotNull($result['data']);
        $this->assertEquals('Introduction to PHP', $result['data']['name']);
        $this->assertEquals('Learn PHP basics', $result['data']['description']);
        $this->assertEquals($this->instructorId, $result['data']['instructor_id']);
    }

    public function testCreateCourseWithoutName(): void
    {
        $data = [
            'name' => '',
            'description' => 'Some description',
            'instructor_id' => $this->instructorId
        ];

        $result = $this->controller->addCourse($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Name and instructor are required', $result['message']);
    }

    public function testCreateCourseWithoutInstructor(): void
    {
        $data = [
            'name' => 'Test Course',
            'description' => 'Test description',
            'instructor_id' => 0
        ];

        $result = $this->controller->addCourse($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Name and instructor are required', $result['message']);
    }

    public function testCreateCourseWithInvalidInstructor(): void
    {
        $data = [
            'name' => 'Test Course',
            'description' => 'Test description',
            'instructor_id' => 99999 // Non-existent instructor
        ];

        $result = $this->controller->addCourse($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Instructor not found', $result['message']);
    }

    public function testCreateCourseWithDuplicateName(): void
    {
        $this->ensureDescriptionColumn();

        // Create first course
        $data1 = [
            'name' => 'Duplicate Course',
            'description' => 'First course',
            'instructor_id' => $this->instructorId
        ];
        $this->controller->addCourse($data1);

        // Try to create duplicate
        $data2 = [
            'name' => 'Duplicate Course',
            'description' => 'Second course',
            'instructor_id' => $this->instructorId2
        ];
        $result = $this->controller->addCourse($data2);

        $this->assertFalse($result['success']);
        $this->assertEquals('Course name already exists', $result['message']);
    }

    public function testCreateCourseWithoutDescription(): void
    {
        $this->ensureDescriptionColumn();

        $data = [
            'name' => 'Course Without Description',
            'description' => '',
            'instructor_id' => $this->instructorId
        ];

        $result = $this->controller->addCourse($data);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['data']);
    }

    // ============================================
    // TEST GET COURSE
    // ============================================

    public function testGetCourseSuccess(): void
    {
        $this->ensureDescriptionColumn();

        // Create a course first
        $data = [
            'name' => 'Get Test Course',
            'description' => 'Test description',
            'instructor_id' => $this->instructorId
        ];
        $createResult = $this->controller->addCourse($data);
        $courseId = $createResult['data']['id'];

        // Get the course
        $result = $this->controller->getCourse($courseId);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['data']);
        $this->assertEquals('Get Test Course', $result['data']['name']);
        $this->assertEquals('Test description', $result['data']['description']);
        $this->assertEquals($this->instructorId, $result['data']['instructor_id']);
        $this->assertArrayHasKey('instructor_name', $result['data']);
        $this->assertArrayHasKey('instructor_email', $result['data']);
    }

    public function testGetCourseNotFound(): void
    {
        // Use a very large ID that definitely doesn't exist
        $result = $this->controller->getCourse(999999);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Course not found', $result['message']);
    }

    // ============================================
    // TEST GET ALL COURSES
    // ============================================

    public function testGetCoursesList(): void
    {
        $this->ensureDescriptionColumn();

        // Create multiple courses
        $this->controller->addCourse([
            'name' => 'Course One',
            'description' => 'Description one',
            'instructor_id' => $this->instructorId
        ]);
        $this->controller->addCourse([
            'name' => 'Course Two',
            'description' => 'Description two',
            'instructor_id' => $this->instructorId2
        ]);
        $this->controller->addCourse([
            'name' => 'Course Three',
            'description' => 'Description three',
            'instructor_id' => $this->instructorId
        ]);

        $result = $this->controller->getCourses(1, 10);

        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(3, $result['total']);
        $this->assertCount(3, $result['data']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['limit']);
    }

    public function testGetCoursesWithPagination(): void
    {
        $this->ensureDescriptionColumn();

        // Create 5 courses
        for ($i = 1; $i <= 5; $i++) {
            $this->controller->addCourse([
                'name' => "Course {$i}",
                'description' => "Description {$i}",
                'instructor_id' => $this->instructorId
            ]);
        }

        // Get first page (limit 2)
        $result = $this->controller->getCourses(1, 2);

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['total']);
        $this->assertCount(2, $result['data']);
        $this->assertEquals(1, $result['page']);

        // Get second page
        $result2 = $this->controller->getCourses(2, 2);
        $this->assertTrue($result2['success']);
        $this->assertCount(2, $result2['data']);
        $this->assertEquals(2, $result2['page']);
    }

    public function testGetCoursesWithSearch(): void
    {
        $this->ensureDescriptionColumn();

        $this->controller->addCourse([
            'name' => 'PHP Programming',
            'description' => 'Learn PHP',
            'instructor_id' => $this->instructorId
        ]);
        $this->controller->addCourse([
            'name' => 'JavaScript Basics',
            'description' => 'Learn JS',
            'instructor_id' => $this->instructorId
        ]);
        $this->controller->addCourse([
            'name' => 'Python Advanced',
            'description' => 'Advanced Python',
            'instructor_id' => $this->instructorId
        ]);

        // Search for "PHP"
        $result = $this->controller->getCourses(1, 10, 'PHP');

        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(1, $result['total']);
        $this->assertStringContainsString('PHP', $result['data'][0]['name']);
    }

    public function testGetCoursesWithInstructorFilter(): void
    {
        $this->ensureDescriptionColumn();

        $this->controller->addCourse([
            'name' => 'Course for Instructor 1',
            'description' => 'Description',
            'instructor_id' => $this->instructorId
        ]);
        $this->controller->addCourse([
            'name' => 'Course for Instructor 2',
            'description' => 'Description',
            'instructor_id' => $this->instructorId2
        ]);

        // Filter by instructor 1
        $result = $this->controller->getCourses(1, 10, '', (string)$this->instructorId);

        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(1, $result['total']);
        foreach ($result['data'] as $course) {
            $this->assertEquals($this->instructorId, $course['instructor_id']);
        }
    }

    // ============================================
    // TEST UPDATE COURSE
    // ============================================

    public function testUpdateCourseSuccess(): void
    {
        $this->ensureDescriptionColumn();

        // Create a course
        $createData = [
            'name' => 'Original Name',
            'description' => 'Original description',
            'instructor_id' => $this->instructorId
        ];
        $createResult = $this->controller->addCourse($createData);
        $courseId = $createResult['data']['id'];

        // Update the course
        $updateData = [
            'course_id' => $courseId,
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'instructor_id' => $this->instructorId2
        ];
        $result = $this->controller->editCourse($updateData);

        $this->assertTrue($result['success']);
        $this->assertEquals('Course updated successfully', $result['message']);
        $this->assertEquals('Updated Name', $result['data']['name']);
        $this->assertEquals('Updated description', $result['data']['description']);
        $this->assertEquals($this->instructorId2, $result['data']['instructor_id']);
    }

    public function testUpdateCourseNotFound(): void
    {
        $data = [
            'course_id' => 99999,
            'name' => 'Test',
            'description' => 'Test',
            'instructor_id' => $this->instructorId
        ];

        $result = $this->controller->editCourse($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Course not found', $result['message']);
    }

    public function testUpdateCourseWithDuplicateName(): void
    {
        $this->ensureDescriptionColumn();

        // Create two courses
        $create1 = $this->controller->addCourse([
            'name' => 'Course A',
            'description' => 'Description',
            'instructor_id' => $this->instructorId
        ]);
        $create2 = $this->controller->addCourse([
            'name' => 'Course B',
            'description' => 'Description',
            'instructor_id' => $this->instructorId
        ]);

        $courseId2 = $create2['data']['id'];

        // Try to update Course B to have the same name as Course A
        $updateData = [
            'course_id' => $courseId2,
            'name' => 'Course A', // Duplicate name
            'description' => 'Description',
            'instructor_id' => $this->instructorId
        ];
        $result = $this->controller->editCourse($updateData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Course name already exists', $result['message']);
    }

    public function testUpdateCourseWithInvalidData(): void
    {
        $data = [
            'course_id' => 0,
            'name' => '',
            'description' => '',
            'instructor_id' => 0
        ];

        $result = $this->controller->editCourse($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Course ID, name, and instructor are required', $result['message']);
    }

    // ============================================
    // TEST DELETE COURSE
    // ============================================

    public function testDeleteCourseSuccess(): void
    {
        $this->ensureDescriptionColumn();

        // Create a course
        $createResult = $this->controller->addCourse([
            'name' => 'Course to Delete',
            'description' => 'Will be deleted',
            'instructor_id' => $this->instructorId
        ]);
        $courseId = $createResult['data']['id'];

        // Delete the course
        $result = $this->controller->deleteCourse($courseId);

        $this->assertTrue($result['success']);
        $this->assertEquals('Course deleted successfully', $result['message']);

        // Verify it's deleted
        $getResult = $this->controller->getCourse($courseId);
        $this->assertFalse($getResult['success']);
    }

    public function testDeleteCourseNotFound(): void
    {
        $result = $this->controller->deleteCourse(99999);

        $this->assertFalse($result['success']);
        $this->assertEquals('Course not found', $result['message']);
    }

    public function testDeleteCourseWithInvalidId(): void
    {
        $result = $this->controller->deleteCourse(0);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid course ID', $result['message']);
    }

    // ============================================
    // TEST GET INSTRUCTORS
    // ============================================

    public function testGetInstructors(): void
    {
        $result = $this->controller->getInstructors();

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertGreaterThanOrEqual(2, count($result['data'])); // At least our 2 test instructors

        // Verify structure
        if (count($result['data']) > 0) {
            $instructor = $result['data'][0];
            $this->assertArrayHasKey('id', $instructor);
            $this->assertArrayHasKey('name', $instructor);
            $this->assertArrayHasKey('email', $instructor);
        }
    }

    // ============================================
    // TEST COURSE MODEL METHODS
    // ============================================

    public function testCourseModelNameExists(): void
    {
        $this->ensureDescriptionColumn();

        require_once __DIR__ . '/../app/Models/Course.php';
        $courseModel = new Models\Course(self::$conn);

        // Create a course via controller
        $this->controller->addCourse([
            'name' => 'Unique Course Name',
            'description' => 'Description',
            'instructor_id' => $this->instructorId
        ]);

        // Test nameExists
        $this->assertTrue($courseModel->nameExists('Unique Course Name'));
        $this->assertFalse($courseModel->nameExists('Non-existent Course'));
    }

    public function testCourseModelNameExistsWithExclude(): void
    {
        $this->ensureDescriptionColumn();

        require_once __DIR__ . '/../app/Models/Course.php';
        $courseModel = new Models\Course(self::$conn);

        // Create a course
        $createResult = $this->controller->addCourse([
            'name' => 'Exclude Test Course',
            'description' => 'Description',
            'instructor_id' => $this->instructorId
        ]);
        $courseId = $createResult['data']['id'];

        // Should return false when excluding itself
        $this->assertFalse($courseModel->nameExists('Exclude Test Course', $courseId));
        
        // Should return true when not excluding
        $this->assertTrue($courseModel->nameExists('Exclude Test Course'));
    }
}

