<?php

require_once __DIR__ . '/DatabaseTestCase.php';

use Controllers\ChatController;
use Helpers\SessionManager;
use Models\ChatMessage;

class ChatControllerTest extends DatabaseTestCase
{
    private ChatController $controller;
    private SessionManager $session;
    private int $studentId;
    private int $instructorId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = SessionManager::getInstance();

        // Seed one student and one instructor for chat scenarios
        self::$conn->query("
            INSERT INTO users (name, email, role, password, status)
            VALUES ('Student One', 'student1@example.com', 'student', 'pwd', 'active')
        ");
        $this->studentId = (int) self::$conn->insert_id;

        self::$conn->query("
            INSERT INTO users (name, email, role, password, status)
            VALUES ('Instructor One', 'instructor1@example.com', 'instructor', 'pwd', 'active')
        ");
        $this->instructorId = (int) self::$conn->insert_id;

        $this->controller = new ChatController(self::$conn);
    }

    private function loginStudent(): void
    {
        $this->session->setUserSession(
            $this->studentId,
            'Student One',
            'student1@example.com',
            'student'
        );
    }

    private function loginInstructor(): void
    {
        $this->session->setUserSession(
            $this->instructorId,
            'Instructor One',
            'instructor1@example.com',
            'instructor'
        );
    }

    public function testSendMessageFailsWhenNotAuthenticated(): void
    {
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'instructor_id' => $this->instructorId,
            'message'       => 'Hello',
        ];

        $response = $this->controller->sendMessage('instructor_id', 'message');

        $this->assertFalse($response['success']);
        $this->assertSame('Not authenticated', $response['message']);
    }

    public function testStudentSendMessagePersistsChat(): void
    {
        $this->loginStudent();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'instructor_id' => $this->instructorId,
            'message'       => '  Hello Instructor  ',
        ];

        $response = $this->controller->sendMessage('instructor_id', 'message');

        $this->assertTrue($response['success']);

        $stmt = self::$conn->prepare("
            SELECT message FROM chat_messages
            WHERE sender_id = ? AND receiver_id = ?
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->bind_param('ii', $this->studentId, $this->instructorId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $this->assertEquals('Hello Instructor', $result['message']);
    }

    public function testInstructorSendMessagePersistsChat(): void
    {
        $this->loginInstructor();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'student_id' => $this->studentId,
            'message'    => 'Reply to student',
        ];

        $response = $this->controller->sendMessage('student_id', 'message');

        $this->assertTrue($response['success']);

        $stmt = self::$conn->prepare("
            SELECT message FROM chat_messages
            WHERE sender_id = ? AND receiver_id = ?
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->bind_param('ii', $this->instructorId, $this->studentId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $this->assertEquals('Reply to student', $result['message']);
    }

    public function testFetchConversationReturnsFormattedMessages(): void
    {
        $this->loginStudent();

        // Seed one message each way with known timestamps to verify ordering/formatting
        $stmt = self::$conn->prepare("
            INSERT INTO chat_messages (sender_id, receiver_id, message, created_at)
            VALUES (?, ?, ?, ?)
        ");
        $msg1 = 'Hi <b>Instructor</b>';
        $time1 = '2025-01-01 10:00:00';
        $stmt->bind_param('iiss', $this->studentId, $this->instructorId, $msg1, $time1);
        $stmt->execute();

        $msg2 = '<script>alert(1)</script>';
        $time2 = '2025-01-01 10:05:00';
        $stmt->bind_param('iiss', $this->instructorId, $this->studentId, $msg2, $time2);
        $stmt->execute();
        $stmt->close();

        $_GET['instructor_id'] = $this->instructorId;

        $response = $this->controller->fetchConversation('instructor_id');

        $this->assertTrue($response['success']);
        $this->assertSame(2, $response['count']);
        $messages = $response['messages'];

        $this->assertSame('student', $messages[0]['sender']);
        $this->assertTrue($messages[0]['is_mine']);
        $this->assertEquals('Hi &lt;b&gt;Instructor&lt;/b&gt;', $messages[0]['message']);

        $this->assertSame('instructor', $messages[1]['sender']);
        $this->assertFalse($messages[1]['is_mine']);
        $this->assertEquals('&lt;script&gt;alert(1)&lt;/script&gt;', $messages[1]['message']);
        $this->assertEquals(strtotime($time2), $messages[1]['timestamp']);
    }
}
