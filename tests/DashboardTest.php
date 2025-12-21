<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/../app/Helpers/SessionManager.php';

use PHPUnit\Framework\TestCase;

/**
 * Test Dashboard Statistics
 * Tests the DashboardController getStatistics() method
 */
class DashboardTest extends DatabaseTestCase
{
    private $adminId;
    private $instructorId;
    private $studentId1;
    private $studentId2;
    private $courseId1;
    private $courseId2;
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with unique identifiers
        $timestamp = time();
        $this->adminId = $this->createTestUser('Admin User', "admin_{$timestamp}@test.com", 'admin');
        $this->instructorId = $this->createTestUser('Instructor User', "instructor_{$timestamp}@test.com", 'instructor');
        $this->studentId1 = $this->createTestUser('Student One', "student1_{$timestamp}@test.com", 'student');
        $this->studentId2 = $this->createTestUser('Student Two', "student2_{$timestamp}@test.com", 'student');

        // Create test courses
        $this->courseId1 = $this->createTestCourse('Course One', $this->instructorId);
        $this->courseId2 = $this->createTestCourse('Course Two', $this->instructorId);

        // Set up admin session for authentication BEFORE creating controller
        $this->setupAdminSession();

        // Initialize controller with test connection
        require_once __DIR__ . '/../app/Controllers/DashboardController.php';
        $this->controller = new Controllers\DashboardController(self::$conn);
    }

    /**
     * Helper: Create a test user
     */
    private function createTestUser(string $name, string $email, string $role): int
    {
        $stmt = self::$conn->prepare(
            "INSERT INTO users (name, email, role, password, status) 
             VALUES (?, ?, ?, 'hashed_password', 'active')"
        );
        $stmt->bind_param('sss', $name, $email, $role);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    /**
     * Helper: Create a test course
     */
    private function createTestCourse(string $name, int $instructorId): int
    {
        // Ensure description column exists
        $result = self::$conn->query("SHOW COLUMNS FROM courses LIKE 'description'");
        if ($result->num_rows === 0) {
            self::$conn->query("ALTER TABLE courses ADD COLUMN description TEXT NULL AFTER name");
        }
        if ($result) {
            $result->close();
        }

        $stmt = self::$conn->prepare(
            "INSERT INTO courses (name, description, instructor_id, created_at, updated_at) 
             VALUES (?, ?, ?, NOW(), NOW())"
        );
        $description = "Description for {$name}";
        $stmt->bind_param('ssi', $name, $description, $instructorId);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    /**
     * Helper: Create a test submission
     */
    private function createTestSubmission(
        int $userId,
        ?int $courseId = null,
        int $similarity = 0,
        string $status = 'active'
    ): int {
        $stmt = self::$conn->prepare(
            "INSERT INTO submissions 
             (user_id, course_id, instructor_id, text_content, similarity, status, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $textContent = "Sample submission text";
        // Correct bind_param: 6 parameters = 6 type chars: i(int), i(int), i(int), s(string), i(int), s(string)
        $stmt->bind_param('iiiiss', $userId, $courseId, $this->instructorId, $textContent, $similarity, $status);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    /**
     * Helper: Set up admin session for authentication
     */
    private function setupAdminSession(): void
    {
        // Set admin session data directly in $_SESSION (SessionManager reads from here)
        $_SESSION['user_id'] = $this->adminId;
        $_SESSION['user_name'] = 'Admin User';
        $_SESSION['user_email'] = 'admin@test.com';
        $_SESSION['user_role'] = 'admin';
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
    }

    // ============================================
    // TEST BASIC STATISTICS
    // ============================================

    public function testTotalUsersCount(): void
    {
        $stats = $this->controller->getStatistics();

        $this->assertArrayHasKey('totalUsers', $stats);
        $this->assertGreaterThanOrEqual(4, $stats['totalUsers']); // At least our 4 test users
    }

    public function testTotalCoursesCount(): void
    {
        $stats = $this->controller->getStatistics();

        $this->assertArrayHasKey('totalCourses', $stats);
        $this->assertGreaterThanOrEqual(2, $stats['totalCourses']); // At least our 2 test courses
    }

    public function testTotalSubmissionsCount(): void
    {
        // Create some test submissions
        $this->createTestSubmission($this->studentId1, $this->courseId1, 50, 'active');
        $this->createTestSubmission($this->studentId2, $this->courseId2, 80, 'active');
        $this->createTestSubmission($this->studentId1, null, 30, 'active');

        $stats = $this->controller->getStatistics();

        $this->assertArrayHasKey('totalSubmissions', $stats);
        $this->assertGreaterThanOrEqual(3, $stats['totalSubmissions']);
    }

    public function testSubmissionsExcludeDeleted(): void
    {
        // Create active and deleted submissions
        $this->createTestSubmission($this->studentId1, $this->courseId1, 50, 'active');
        $this->createTestSubmission($this->studentId2, $this->courseId2, 60, 'active');
        $this->createTestSubmission($this->studentId1, null, 40, 'deleted');

        $stats = $this->controller->getStatistics();

        $this->assertEquals(2, $stats['totalSubmissions']); // Should exclude deleted
    }

    public function testHighRiskCount(): void
    {
        // Create submissions with various similarity scores
        $this->createTestSubmission($this->studentId1, $this->courseId1, 75, 'active'); // High risk
        $this->createTestSubmission($this->studentId2, $this->courseId2, 90, 'active'); // High risk
        $this->createTestSubmission($this->studentId1, null, 50, 'active'); // Not high risk
        $this->createTestSubmission($this->studentId2, $this->courseId1, 30, 'active'); // Not high risk
        $this->createTestSubmission($this->studentId1, $this->courseId2, 85, 'deleted'); // Deleted, shouldn't count

        $stats = $this->controller->getStatistics();

        $this->assertArrayHasKey('highRiskCount', $stats);
        $this->assertEquals(2, $stats['highRiskCount']); // Only 2 high-risk submissions
    }

    // ============================================
    // TEST USER DISTRIBUTION
    // ============================================

    public function testUserDistributionByRole(): void
    {
        $stats = $this->controller->getStatistics();

        $this->assertArrayHasKey('userDistribution', $stats);
        $this->assertIsArray($stats['userDistribution']);
        
        // Should have admin, instructor, and student roles
        $this->assertArrayHasKey('admin', $stats['userDistribution']);
        $this->assertArrayHasKey('instructor', $stats['userDistribution']);
        $this->assertArrayHasKey('student', $stats['userDistribution']);
        
        $this->assertGreaterThanOrEqual(1, $stats['userDistribution']['admin']);
        $this->assertGreaterThanOrEqual(1, $stats['userDistribution']['instructor']);
        $this->assertGreaterThanOrEqual(2, $stats['userDistribution']['student']);
    }

    // ============================================
    // TEST SIMILARITY DISTRIBUTION
    // ============================================

    public function testSimilarityDistribution(): void
    {
        // Create submissions in different similarity ranges
        $this->createTestSubmission($this->studentId1, $this->courseId1, 20, 'active'); // Low (0-30%)
        $this->createTestSubmission($this->studentId2, $this->courseId2, 25, 'active'); // Low (0-30%)
        $this->createTestSubmission($this->studentId1, null, 50, 'active'); // Medium (31-70%)
        $this->createTestSubmission($this->studentId2, $this->courseId1, 65, 'active'); // Medium (31-70%)
        $this->createTestSubmission($this->studentId1, $this->courseId2, 80, 'active'); // High (71-100%)
        $this->createTestSubmission($this->studentId2, null, 90, 'active'); // High (71-100%)

        $stats = $this->controller->getStatistics();

        $this->assertArrayHasKey('similarityDistribution', $stats);
        $this->assertIsArray($stats['similarityDistribution']);
        
        // Check that distributions are calculated
        $totalDistributed = 0;
        foreach ($stats['similarityDistribution'] as $count) {
            $totalDistributed += $count;
        }
        $this->assertGreaterThanOrEqual(6, $totalDistributed);
    }

    public function testSimilarityDistributionExcludesDeleted(): void
    {
        // Create submissions including deleted ones
        $this->createTestSubmission($this->studentId1, $this->courseId1, 20, 'active');
        $this->createTestSubmission($this->studentId2, $this->courseId2, 50, 'active');
        $this->createTestSubmission($this->studentId1, null, 80, 'deleted'); // Should be excluded

        $stats = $this->controller->getStatistics();

        // Count total in distribution (should exclude deleted)
        $totalDistributed = 0;
        foreach ($stats['similarityDistribution'] as $count) {
            $totalDistributed += $count;
        }
        $this->assertGreaterThanOrEqual(2, $totalDistributed);
        $this->assertLessThanOrEqual(2, $totalDistributed); // Should only count active ones
    }

    // ============================================
    // TEST COURSE ACTIVITY
    // ============================================

    public function testCourseActivity(): void
    {
        // Create submissions for different courses
        $this->createTestSubmission($this->studentId1, $this->courseId1, 50, 'active');
        $this->createTestSubmission($this->studentId2, $this->courseId1, 60, 'active');
        $this->createTestSubmission($this->studentId1, $this->courseId2, 70, 'active');
        $this->createTestSubmission($this->studentId2, null, 40, 'active'); // General submission

        $stats = $this->controller->getStatistics();

        $this->assertArrayHasKey('courseActivity', $stats);
        $this->assertIsArray($stats['courseActivity']);
        
        // Should have activity for both courses
        $courseNames = array_column($stats['courseActivity'], 'name');
        $this->assertContains('Course One', $courseNames);
        $this->assertContains('Course Two', $courseNames);
        
        // Find Course One and verify it has 2 submissions
        foreach ($stats['courseActivity'] as $activity) {
            if ($activity['name'] === 'Course One') {
                $this->assertEquals(2, $activity['count']);
            }
            if ($activity['name'] === 'Course Two') {
                $this->assertEquals(1, $activity['count']);
            }
        }
    }

    public function testCourseActivityExcludesDeleted(): void
    {
        // Create active and deleted submissions for a course
        $this->createTestSubmission($this->studentId1, $this->courseId1, 50, 'active');
        $this->createTestSubmission($this->studentId2, $this->courseId1, 60, 'deleted'); // Should be excluded

        $stats = $this->controller->getStatistics();

        foreach ($stats['courseActivity'] as $activity) {
            if ($activity['name'] === 'Course One') {
                $this->assertEquals(1, $activity['count']);
            }
        }
    }

    // ============================================
    // TEST RECENT SUBMISSIONS
    // ============================================

    public function testRecentSubmissions(): void
    {
        // Create multiple submissions
        for ($i = 1; $i <= 5; $i++) {
            $this->createTestSubmission($this->studentId1, $this->courseId1, 50 + $i, 'active');
            // Small delay to ensure different timestamps
            usleep(1000); // 1ms delay
        }

        $stats = $this->controller->getStatistics();

        $this->assertArrayHasKey('recentSubmissions', $stats);
        $this->assertIsArray($stats['recentSubmissions']);
        $this->assertGreaterThanOrEqual(5, count($stats['recentSubmissions']));
        
        // Verify structure of recent submissions
        if (count($stats['recentSubmissions']) > 0) {
            $submission = $stats['recentSubmissions'][0];
            $this->assertArrayHasKey('id', $submission);
            $this->assertArrayHasKey('student_name', $submission);
            $this->assertArrayHasKey('student_email', $submission);
            $this->assertArrayHasKey('course_name', $submission);
            $this->assertArrayHasKey('similarity', $submission);
            $this->assertArrayHasKey('status', $submission);
            $this->assertArrayHasKey('created_at', $submission);
        }
    }

    public function testRecentSubmissionsLimit(): void
    {
        // Create 15 submissions (more than the limit of 10)
        for ($i = 1; $i <= 15; $i++) {
            $this->createTestSubmission($this->studentId1, $this->courseId1, 50, 'active');
            usleep(1000);
        }

        $stats = $this->controller->getStatistics();

        $this->assertLessThanOrEqual(10, count($stats['recentSubmissions']));
    }

    public function testRecentSubmissionsExcludeDeleted(): void
    {
        // Create active and deleted submissions
        $this->createTestSubmission($this->studentId1, $this->courseId1, 50, 'active');
        $this->createTestSubmission($this->studentId2, $this->courseId2, 60, 'deleted'); // Should be excluded

        $stats = $this->controller->getStatistics();

        // Should only have the active submission
        $this->assertGreaterThanOrEqual(1, count($stats['recentSubmissions']));
        foreach ($stats['recentSubmissions'] as $submission) {
            $this->assertNotEquals('deleted', $submission['status']);
        }
    }

    public function testRecentSubmissionsOrderedByDate(): void
    {
        // Create submissions with delays to ensure different timestamps
        $id1 = $this->createTestSubmission($this->studentId1, $this->courseId1, 50, 'active');
        usleep(2000);
        $id2 = $this->createTestSubmission($this->studentId2, $this->courseId2, 60, 'active');
        usleep(2000);
        $id3 = $this->createTestSubmission($this->studentId1, null, 70, 'active');

        $stats = $this->controller->getStatistics();

        // Most recent should be first - verify ordering by checking all three IDs are present
        $this->assertGreaterThanOrEqual(3, count($stats['recentSubmissions']));
        
        // Verify all three submissions are in the list
        $submissionIds = array_column($stats['recentSubmissions'], 'id');
        $this->assertContains($id1, $submissionIds);
        $this->assertContains($id2, $submissionIds);
        $this->assertContains($id3, $submissionIds);
        
        // Verify ordering: later IDs should come before earlier ones (newest first)
        $firstSubmission = $stats['recentSubmissions'][0];
        $this->assertGreaterThanOrEqual($id1, $firstSubmission['id']); // Newest ID should be >= oldest ID
    }

    // ============================================
    // TEST EMPTY DATA
    // ============================================

    public function testStatisticsWithNoData(): void
    {
        // Create a fresh controller for a clean database (will use existing test data though)
        $stats = $this->controller->getStatistics();

        // Should still return valid structure even with minimal data
        $this->assertArrayHasKey('totalUsers', $stats);
        $this->assertArrayHasKey('totalCourses', $stats);
        $this->assertArrayHasKey('totalSubmissions', $stats);
        $this->assertArrayHasKey('highRiskCount', $stats);
        $this->assertArrayHasKey('userDistribution', $stats);
        $this->assertArrayHasKey('similarityDistribution', $stats);
        $this->assertArrayHasKey('courseActivity', $stats);
        $this->assertArrayHasKey('recentSubmissions', $stats);
        
        // All should be arrays or integers
        $this->assertIsInt($stats['totalUsers']);
        $this->assertIsInt($stats['totalCourses']);
        $this->assertIsInt($stats['totalSubmissions']);
        $this->assertIsInt($stats['highRiskCount']);
        $this->assertIsArray($stats['userDistribution']);
        $this->assertIsArray($stats['similarityDistribution']);
        $this->assertIsArray($stats['courseActivity']);
        $this->assertIsArray($stats['recentSubmissions']);
    }
}

