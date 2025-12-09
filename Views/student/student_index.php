<?php
/**
 * Protected Student Dashboard
 * Only accessible by authenticated student users
 */

require_once __DIR__ . '/../../Helpers/SessionManager.php';
require_once __DIR__ . '/../../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../Helpers/Csrf.php';
require_once __DIR__ . '/../../Controllers/SubmissionController.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Controllers\SubmissionController;
use Helpers\Csrf;

// Initialize authentication
$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

// Require student role
$auth->requireRole('student');

// Get authenticated user info
$currentUser = $auth->getCurrentUser();
$userId = $currentUser['id'];
$username = $currentUser['name'];

// Initialize controller
$ctrl = new SubmissionController();

// Verify CSRF for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_csrf'] ?? '')) {
        die('CSRF token validation failed. Please refresh and try again.');
    }
}

// Handle DELETE action
if (isset($_POST['delete_id'])) {
    if ($auth->ownsResource($_POST['delete_id'])) {
        $ctrl->delete($_POST['delete_id'], $userId);
        header("Location: student_index.php");
        exit;
    } else {
        die('Unauthorized: You can only delete your own submissions.');
    }
}

// Handle RESTORE action
if (isset($_POST['restore_id'])) {
    if ($auth->ownsResource($_POST['restore_id'])) {
        $ctrl->restore($_POST['restore_id'], $userId);
        header("Location: student_index.php");
        exit;
    } else {
        die('Unauthorized: You can only restore your own submissions.');
    }
}

// Handle SUBMISSION
$submissionResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id']) && !isset($_POST['restore_id'])) {
    $_POST['user_id'] = $userId; // Force correct user ID
    $submissionResult = $ctrl->submit();
}

// Generate CSRF token
$csrfToken = Csrf::token();

// Fetch submissions
$submissions = $ctrl->getUserSubmissions($userId, 'active');
$deletedSubmissions = $ctrl->getUserSubmissions($userId, 'deleted');

// Fetch instructors
$instructors = [];
try {
    $reflection = new ReflectionClass($ctrl);
    $connProperty = $reflection->getProperty('conn');
    $connProperty->setAccessible(true);
    $conn = $connProperty->getValue($ctrl);

    if ($conn && method_exists($conn, 'query')) {
        $instructorQuery = $conn->query("SELECT id, name, email FROM users WHERE role='instructor' AND status='active' ORDER BY name ASC");
        if ($instructorQuery && $instructorQuery->num_rows > 0) {
            while ($row = $instructorQuery->fetch_assoc()) {
                $instructors[] = $row;
            }
        }
    }
} catch (Exception $e) {
    $rootPath = dirname(dirname(__DIR__));
    require_once $rootPath . '/includes/db.php';
    if (isset($conn) && method_exists($conn, 'query')) {
        $instructorQuery = $conn->query("SELECT id, name, email FROM users WHERE role='instructor' AND status='active' ORDER BY name ASC");
        if ($instructorQuery && $instructorQuery->num_rows > 0) {
            while ($row = $instructorQuery->fetch_assoc()) {
                $instructors[] = $row;
            }
        }
    }
}

// Count unseen notifications
$notificationCount = 0;
foreach ($submissions as $sub) {
    $hasFeedback = !empty($sub['feedback']);
    $isAccepted = $sub['status'] === 'accepted';
    $isRejected = $sub['status'] === 'rejected';
    $seen = $sub['notification_seen'] ?? 0;

    if (($hasFeedback || $isAccepted || $isRejected) && !$seen) {
        $notificationCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Plagiarism Detection - Student Dashboard</title>
<link rel="stylesheet" href="../../assets/css/student.css">
<link rel="stylesheet" href="../../assets/css/user.css">
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="user-profile">
        <p class="username"><?= htmlspecialchars($username) ?></p>
        <p class="user-role">Student</p>
        <p class="user-id">ID: <?= htmlspecialchars($userId) ?></p>
    </div>
    <div class="menu">
        <a href="#" id="homeBtn" data-tooltip="Home">🏠</a>
        <a href="#" id="historyBtn" data-tooltip="Past History">📜</a>
        <a href="#" id="notificationsBtn" data-tooltip="Notifications" style="position: relative;">
            🔔
            <?php if ($notificationCount > 0): ?>
                <span id="notificationBadge" class="notification-badge"
                      style="background: #ef4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; position: absolute; top: -5px; right: -5px; min-width: 18px; text-align: center; line-height: 14px; font-weight: bold;">
                    <?= $notificationCount ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="#" id="trashBtn" data-tooltip="Trash">🗑️</a>
        <a href="#" id="chatBtn" data-tooltip="Chat with Instructor">💬</a>
    </div>
    <a href="<?= htmlspecialchars('../../logout.php', ENT_QUOTES) ?>" class="logout" data-tooltip="Logout">↻</a>
</nav>

<!-- Main content -->
<main class="main-content">

    <!-- Submission Page -->
    <section id="mainPage" class="page active">
        <h1>Submit Your Work</h1>
        <div class="content-grid">
            <!-- Submission Form -->
            <div class="submission-box">
                <h2>Submission Form</h2>
                <form id="submissionForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId, ENT_QUOTES) ?>">

                    <label for="instructorSelect">Instructor (Optional)</label>
                    <select id="instructorSelect" name="instructorSelect">
                        <option value="">-- General Submission --</option>
                        <?php foreach ($instructors as $instructor): ?>
                            <option value="<?= htmlspecialchars($instructor['id']) ?>">
                                <?= htmlspecialchars($instructor['name']) ?> (<?= htmlspecialchars($instructor['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="textInput">Text</label>
                    <textarea id="textInput" name="textInput" rows="8"></textarea>

                    <label for="fileInput">Upload File</label>
                    <input type="file" id="fileInput" name="fileInput" accept=".txt,.docx">

                    <button type="submit">Submit</button>
                </form>
            </div>

            <!-- Plagiarism Overview -->
            <aside class="results-box">
                <h2>Plagiarism Overview</h2>
                <div class="ring-container">
                    <div class="ring" id="ring"></div>
                    <div class="ring-value" id="ringValue"><?= $submissionResult['plagiarised'] ?? 0 ?>%</div>
                </div>
                <div class="percent-breakdown">
                    <div>
                        <span class="indicator-box unique-box"></span>
                        Unique: <?= 100 - ($submissionResult['plagiarised'] ?? 0) ?>%
                    </div>
                    <div>
                        <span class="indicator-box exact-box"></span>
                        Exact Match: <?= $submissionResult['exact'] ?? 0 ?>%
                    </div>
                    <div>
                        <span class="indicator-box partial-box"></span>
                        Partial Match: <?= $submissionResult['partial'] ?? 0 ?>%
                    </div>
                </div>

                <?php if ($submissionResult && !empty($submissionResult['alert_message'])): ?>
                    <div class="alert-warning"><?= htmlspecialchars($submissionResult['alert_message']) ?></div>
                    <a href="download.php?id=<?= $submissionResult['submission_id'] ?>" class="download-btn">Download Report</a>
                <?php endif; ?>
            </aside>
        </div>
    </section>

    <!-- History Page -->
    <section id="historyPage" class="page">
        <h1>Past Submissions</h1>
        <?php if ($submissions): ?>
            <?php foreach ($submissions as $sub):
                $statusColor = $sub['status'] === 'accepted' ? '#10b981' : ($sub['status'] === 'rejected' ? '#ef4444' : '#f59e0b');
                $statusBadge = $sub['status'] === 'accepted' ? '✅ Accepted' : ($sub['status'] === 'rejected' ? '❌ Rejected' : '⏳ Pending');
                $plagColor = $sub['similarity'] > 70 ? '#ef4444' : ($sub['similarity'] > 40 ? '#f59e0b' : '#10b981');
            ?>
                <div class="history-item">
                    <h3>Submission #<?= $sub['id'] ?></h3>
                    <span style="background: <?= $statusColor ?>; color: white; padding: 6px 12px; border-radius: 20px; font-weight: bold;"><?= $statusBadge ?></span>
                    <p>Date: <?= htmlspecialchars($sub['created_at']) ?></p>
                    <p>Plagiarism: <span style="color: <?= $plagColor ?>; font-weight:bold;"><?= $sub['similarity'] ?>%</span></p>
                    <?php if(!empty($sub['teacher'])): ?><p>Instructor: <?= htmlspecialchars($sub['teacher']) ?></p><?php endif; ?>
                    <?php if(!empty($sub['feedback'])): ?>
                        <div class="feedback-box"><?= nl2br(htmlspecialchars($sub['feedback'])) ?></div>
                    <?php endif; ?>
                    <?php if(!empty($sub['file_path'])): ?>
                        <a href="<?= htmlspecialchars($sub['file_path']) ?>" download>Download File</a>
                    <?php endif; ?>
                    <?php if ($ctrl->getReportPath($sub['id'])): ?>
                        <a href="download.php?id=<?= $sub['id'] ?>">Download Report</a>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                        <input type="hidden" name="delete_id" value="<?= $sub['id'] ?>">
                        <button type="submit">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No submissions yet.</p>
        <?php endif; ?>
    </section>

    <!-- Notifications Page -->
    <section id="notificationsPage" class="page">
        <h1>Notifications</h1>
        <?php 
        $hasNotifications = false;
        foreach ($submissions as $sub):
            $hasFeedback = !empty($sub['feedback']);
            $isAccepted = $sub['status'] === 'accepted';
            $isRejected = $sub['status'] === 'rejected';
            $seen = $sub['notification_seen'] ?? 0;
            
            if ((!$hasFeedback && !$isAccepted && !$isRejected) || $seen) continue;
            $hasNotifications = true;
            $notifColor = $isAccepted ? '#10b981' : ($isRejected ? '#ef4444' : '#3b82f6');
            $notifIcon = $isAccepted ? '✅' : ($isRejected ? '❌' : '💬');
            $notifTitle = $isAccepted ? 'Submission Accepted' : ($isRejected ? 'Submission Rejected' : 'Feedback Received');
            $plagColor = $sub['similarity'] > 70 ? '#ef4444' : ($sub['similarity'] > 40 ? '#f59e0b' : '#10b981');
        ?>
            <div class="notification-card" style="border-left: 4px solid <?= $notifColor ?>">
                <strong><?= $notifIcon ?> <?= $notifTitle ?></strong>
                <p>Submission #<?= $sub['id'] ?> • <?= date('M j, Y g:i A', strtotime($sub['created_at'])) ?></p>
                <p>Plagiarism: <span style="color: <?= $plagColor ?>; font-weight:bold"><?= $sub['similarity'] ?>%</span></p>
                <?php if(!empty($sub['feedback'])): ?>
                    <p>Feedback: <?= nl2br(htmlspecialchars($sub['feedback'])) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach;
        if (!$hasNotifications): ?>
            <p>No new notifications</p>
        <?php endif; ?>
    </section>

    <!-- Trash Page -->
    <section id="trashPage" class="page">
        <h1>Trash</h1>
        <?php if ($deletedSubmissions): ?>
            <?php foreach ($deletedSubmissions as $sub): ?>
                <div class="history-item deleted">
                    <h3>Submission #<?= $sub['id'] ?></h3>
                    <p>Date: <?= htmlspecialchars($sub['created_at']) ?></p>
                    <p>Plagiarism: <?= $sub['similarity'] ?>%</p>
                    <?php if(!empty($sub['teacher'])): ?><p>Instructor: <?= htmlspecialchars($sub['teacher']) ?></p><?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                        <input type="hidden" name="restore_id" value="<?= $sub['id'] ?>">
                        <button type="submit">Restore</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Trash is empty.</p>
        <?php endif; ?>
    </section>

    <!-- Chat Page -->
    <section id="chatPage" class="page">
        <h1>💬 Chat with Instructor</h1>
        
        <div style="margin-bottom: 20px;">
            <label for="chatInstructorSelect" style="display: block; margin-bottom: 8px; font-weight: 600;">Select Instructor:</label>
            <select id="chatInstructorSelect" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                <option value="">-- Select Instructor --</option>
                <?php foreach($instructors as $ins): ?>
                    <option value="<?= htmlspecialchars($ins['id']) ?>">
                        <?= htmlspecialchars($ins['name']) ?> (<?= htmlspecialchars($ins['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="chatWindow" style="margin-top:20px; border:1px solid #e2e8f0; border-radius:6px; padding:15px; height:400px; overflow-y:auto; background:#f9fafb; margin-bottom: 15px;">
            <p style="text-align:center;color:#64748b;padding:20px;">Select an instructor to start chatting</p>
        </div>

        <form id="chatForm" style="display:flex; gap:10px;">
            <input 
                type="text" 
                id="chatMessage" 
                placeholder="Type your message..." 
                style="flex:1; padding:10px; border-radius:6px; border:1px solid #cbd5e1; font-size: 14px;"
                disabled
            >
            <button 
                type="submit" 
                id="chatSendBtn"
                style="padding:10px 20px; border-radius:6px; background:#3b82f6; color:white; border:none; cursor: pointer; font-weight: 600;"
                disabled
            >
                Send 📤
            </button>
        </form>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // Page navigation
    const pages = {home:'mainPage', history:'historyPage', notifications:'notificationsPage', trash:'trashPage', chat:'chatPage'};
    Object.keys(pages).forEach(key => {
        document.getElementById(key+'Btn')?.addEventListener('click', e=>{
            e.preventDefault();
            document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
            document.getElementById(pages[key]).classList.add('active');

            if(key === 'notifications'){
                const badge = document.getElementById('notificationBadge');
                if(badge) badge.remove();

                // Mark notifications as seen via AJAX
                fetch('mark_notifications_seen.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: '_csrf=<?= $csrfToken ?>&user_id=<?= $userId ?>'
                });
            }
        });
    });

    // ============================================
    // CHAT FUNCTIONALITY - COMPLETELY REWRITTEN
    // ============================================
    (function() {
        const chatSelect = document.getElementById('chatInstructorSelect');
        const chatWindow = document.getElementById('chatWindow');
        const chatForm = document.getElementById('chatForm');
        const chatInput = document.getElementById('chatMessage');
        const chatSendBtn = document.getElementById('chatSendBtn');
        
        let chatInstructorId = null;
        let fetchInterval = null;

        // Render messages in chat window
        function renderMessages(messages) {
            chatWindow.innerHTML = '';
            
            if (!messages || messages.length === 0) {
                chatWindow.innerHTML = '<p style="text-align:center;color:#64748b;padding:20px;">No messages yet. Start the conversation!</p>';
                return;
            }

            messages.forEach(msg => {
                const div = document.createElement('div');
                div.style.marginBottom = '12px';
                div.style.textAlign = msg.sender === 'student' ? 'right' : 'left';
                
                const bubble = document.createElement('div');
                bubble.style.display = 'inline-block';
                bubble.style.maxWidth = '70%';
                bubble.style.padding = '10px 14px';
                bubble.style.borderRadius = '12px';
                bubble.style.background = msg.sender === 'student' ? '#3b82f6' : '#e2e8f0';
                bubble.style.color = msg.sender === 'student' ? 'white' : '#1e293b';
                bubble.style.textAlign = 'left';
                bubble.style.wordWrap = 'break-word';
                
                bubble.innerHTML = `
                    <strong style="font-size: 12px; opacity: 0.9;">${msg.sender_name}</strong><br>
                    ${msg.message}<br>
                    <small style="font-size: 11px; opacity: 0.8;">${msg.time}</small>
                `;
                
                div.appendChild(bubble);
                chatWindow.appendChild(div);
            });

            // Auto-scroll to bottom
            chatWindow.scrollTop = chatWindow.scrollHeight;
        }

        // Fetch messages from server
        async function fetchMessages() {
            if (!chatInstructorId) return;

            try {
                const res = await fetch(`chat_fetch.php?instructor_id=${chatInstructorId}`);
                const data = await res.json();
                
                if (data.success) {
                    renderMessages(data.messages);
                } else {
                    console.error('Failed to fetch messages:', data.error);
                }
            } catch (err) {
                console.error('Fetch messages error:', err);
            }
        }

        // Handle instructor selection
        chatSelect.addEventListener('change', () => {
            chatInstructorId = chatSelect.value || null;
            
            // Clear existing interval
            if (fetchInterval) {
                clearInterval(fetchInterval);
                fetchInterval = null;
            }

            if (chatInstructorId) {
                // Enable chat input
                chatInput.disabled = false;
                chatSendBtn.disabled = false;
                
                // Load messages
                chatWindow.innerHTML = '<p style="text-align:center;color:#64748b;padding:20px;">Loading messages...</p>';
                fetchMessages();
                
                // Auto-refresh every 3 seconds
                fetchInterval = setInterval(fetchMessages, 3000);
            } else {
                // Disable chat input
                chatInput.disabled = true;
                chatSendBtn.disabled = true;
                chatWindow.innerHTML = '<p style="text-align:center;color:#64748b;padding:20px;">Select an instructor to start chatting</p>';
            }
        });

        // Send message
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!chatInstructorId) {
                alert('Please select an instructor first!');
                return;
            }
            
            const msg = chatInput.value.trim();
            if (!msg) return;

            const originalMsg = msg;
            chatInput.value = ''; // Clear immediately for better UX

            const formData = new FormData();
            formData.append('_csrf', '<?= $csrfToken ?>');
            formData.append('instructor_id', chatInstructorId);
            formData.append('message', msg);

            try {
                const res = await fetch('chat_send.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await res.json();
                
                if (data.success) {
                    fetchMessages(); // Immediately fetch new messages
                } else {
                    alert(data.message || 'Failed to send message');
                    chatInput.value = originalMsg; // Restore message on failure
                }
            } catch (err) {
                console.error('Send message error:', err);
                alert('Network error. Please check your connection.');
                chatInput.value = originalMsg;
            }
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (fetchInterval) {
                clearInterval(fetchInterval);
            }
        });
    })();
});
</script>

</body> 
</html>