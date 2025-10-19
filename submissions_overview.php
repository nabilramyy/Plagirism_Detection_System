<section class="submissions-overview">
  <h2>Submissions Overview 📄</h2>

  <!-- Statistics Cards -->
  <div class="stats-cards">
    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-file-alt"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="subTotal">0</div>
        <div class="stat-label">Total Submissions</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-chart-line"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="subAvg">0%</div>
        <div class="stat-label">Average Similarity</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="subHighRisk">0</div>
        <div class="stat-label">High-Risk (>70%)</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-calendar"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="todayDate"></div>
        <div class="stat-label">Today's Date</div>
      </div>
    </div>
  </div>

  <!-- Search Bar -->
  <div class="search-filter-bar">
    <input type="text" id="searchInput" class="search-bar" placeholder="🔍 Search by student name or title..." onkeyup="filterSubmissions()">
    
    <select id="statusFilter" class="filter-select" onchange="filterSubmissions()">
      <option value="">All Status</option>
      <option value="completed">Completed</option>
      <option value="processing">Processing</option>
    </select>

    <select id="riskFilter" class="filter-select" onchange="filterSubmissions()">
      <option value="">All Risk Levels</option>
      <option value="low">Low (0-30%)</option>
      <option value="medium">Medium (31-60%)</option>
      <option value="high">High (>60%)</option>
    </select>

    <button class="btn primary" onclick="exportToCSV()">
      <i class="fas fa-download"></i> Export CSV
    </button>
  </div>

  <!-- Submissions Table -->
  <div class="submissions-table-container">
    <table class="submissions-table" id="submissionsTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Student Name</th>
          <th>Document Title</th>
          <th>Course</th>
          <th>Instructor</th>
          <th>Submission Date</th>
          <th>Similarity Score</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="submissionsTableBody">
        <!-- Submissions loaded by JavaScript -->
      </tbody>
    </table>
  </div>
</section>

<!-- Submission Details Modal -->
<div id="submissionModal" class="modal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>📄 Submission Details</h3>
      <button class="close-btn" onclick="closeSubmissionModal()">&times;</button>
    </div>
    <div class="modal-body">
      <div class="submission-details" id="submissionDetailsContent">
        <!-- Details loaded by JavaScript -->
      </div>
      <div style="margin-top: 20px; display: flex; gap: 10px;">
        <button class="btn danger" onclick="deleteSubmission()">🗑️ Delete Submission</button>
        <button class="btn" onclick="closeSubmissionModal()">Close</button>
      </div>
    </div>
  </div>
</div>

<style>
.search-filter-bar {
  display: flex;
  gap: 10px;
  margin: 20px 0;
  flex-wrap: wrap;
}

.search-bar {
  flex: 1;
  min-width: 250px;
  padding: 10px 15px;
  border-radius: 10px;
  border: none;
  background: rgba(255,255,255,0.05);
  color: #fff;
}

.filter-select {
  padding: 10px 15px;
  border-radius: 10px;
  border: none;
  background: rgba(255,255,255,0.05);
  color: #fff;
  cursor: pointer;
}

.submissions-table-container {
  background: rgba(255,255,255,0.02);
  padding: 20px;
  border-radius: 14px;
  overflow-x: auto;
  margin-top: 20px;
}

.submissions-table {
  width: 100%;
  border-collapse: collapse;
}

.submissions-table thead {
  background: rgba(255,255,255,0.05);
}

.submissions-table th,
.submissions-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  color: #eaf2ff;
}

.submissions-table th {
  font-weight: 600;
  color: var(--accent1);
  font-size: 13px;
}

.submissions-table tbody tr:hover {
  background: rgba(255,255,255,0.03);
}

.similarity-score {
  display: inline-block;
  padding: 5px 12px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
}

.similarity-score.low {
  background: rgba(46,204,113,0.2);
  color: #7ef3b6;
}

.similarity-score.medium {
  background: rgba(243,156,18,0.2);
  color: #ffa94d;
}

.similarity-score.high {
  background: rgba(231,76,60,0.2);
  color: #ff9696;
}

.similarity-score.processing {
  background: rgba(52,152,219,0.2);
  color: #74b9ff;
}

.status-badge {
  display: inline-block;
  padding: 5px 12px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 600;
}

.status-badge.completed {
  background: rgba(46,204,113,0.2);
  color: #7ef3b6;
}

.status-badge.processing {
  background: rgba(52,152,219,0.2);
  color: #74b9ff;
}

.submission-details {
  background: rgba(255,255,255,0.03);
  padding: 20px;
  border-radius: 10px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-row strong {
  color: var(--accent1);
}
</style>

<script>
// Initialize with hardcoded submissions
let submissions = [
  { 
    id: 1, 
    studentId: 1, 
    courseId: 1, 
    instructorId: 4,
    title: 'Introduction to Programming - Assignment 1', 
    filename: 'assignment1.pdf',
    uploadDate: '2024-10-15T10:30:00',
    similarity: 15.5,
    status: 'completed'
  },
  { 
    id: 2, 
    studentId: 2, 
    courseId: 1, 
    instructorId: 4,
    title: 'Programming Basics Essay', 
    filename: 'essay_programming.docx',
    uploadDate: '2024-10-16T14:20:00',
    similarity: 45.2,
    status: 'completed'
  },
  { 
    id: 3, 
    studentId: 1, 
    courseId: 2, 
    instructorId: 5,
    title: 'Academic Writing - Research Paper', 
    filename: 'research_paper.pdf',
    uploadDate: '2024-10-17T09:15:00',
    similarity: 78.9,
    status: 'completed'
  },
  { 
    id: 4, 
    studentId: 3, 
    courseId: 3, 
    instructorId: 4,
    title: 'Calculus Problem Set 1', 
    filename: 'calculus_hw1.pdf',
    uploadDate: '2024-10-18T16:45:00',
    similarity: 22.3,
    status: 'completed'
  },
  { 
    id: 5, 
    studentId: 2, 
    courseId: 3, 
    instructorId: 5,
    title: 'Mathematical Analysis Report', 
    filename: 'math_report.pdf',
    uploadDate: '2024-10-19T11:00:00',
    similarity: null,
    status: 'processing'
  },
];

let nextSubmissionId = 6;
let currentSubmissionId = null;

// Load from localStorage
function loadSubmissions() {
  const saved = localStorage.getItem('submissions');
  if (saved) {
    const data = JSON.parse(saved);
    submissions = data.submissions;
    nextSubmissionId = data.nextSubmissionId;
  }
}

// Save to localStorage
function saveSubmissions() {
  localStorage.setItem('submissions', JSON.stringify({
    submissions: submissions,
    nextSubmissionId: nextSubmissionId
  }));
  
  updateStats();
  updateDashboard();
}

// Get users
function getUsers() {
  const saved = localStorage.getItem('users');
  if (saved) {
    return JSON.parse(saved).users;
  }
  return [
    { id: 1, name: 'Ahmed Hassan', email: 'ahmed@student.edu', role: 'student' },
    { id: 2, name: 'Fatma Ali', email: 'fatma@student.edu', role: 'student' },
    { id: 3, name: 'Mohamed Omar', email: 'mohamed@student.edu', role: 'student' },
    { id: 4, name: 'Dr. Ahmed Mohamed', email: 'ahmed.m@university.edu', role: 'instructor' },
    { id: 5, name: 'Prof. Sara Ali', email: 'sara.ali@university.edu', role: 'instructor' },
  ];
}

// Get courses
function getCourses() {
  const saved = localStorage.getItem('courses');
  if (saved) {
    return JSON.parse(saved).courses;
  }
  return [
    { id: 1, code: 'CS101', name: 'Introduction to Programming' },
    { id: 2, code: 'ENG201', name: 'Academic Writing' },
    { id: 3, code: 'MATH150', name: 'Calculus I' },
  ];
}

// Helper functions
function getUserById(id) {
  return getUsers().find(u => u.id === id);
}

function getCourseById(id) {
  return getCourses().find(c => c.id === id);
}

function getSubmissionById(id) {
  return submissions.find(s => s.id === id);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
  loadSubmissions();
  updateStats();
  renderSubmissions();
  
  const today = new Date();
  document.getElementById('todayDate').textContent = today.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
});

function updateStats() {
  const completed = submissions.filter(s => s.status === 'completed');
  const scores = completed.map(s => s.similarity).filter(s => s !== null);
  
  document.getElementById('subTotal').textContent = submissions.length;
  
  if (scores.length > 0) {
    const avg = scores.reduce((a,b) => a+b, 0) / scores.length;
    document.getElementById('subAvg').textContent = avg.toFixed(1) + '%';
    document.getElementById('subHighRisk').textContent = scores.filter(s => s > 70).length;
  } else {
    document.getElementById('subAvg').textContent = '0%';
    document.getElementById('subHighRisk').textContent = '0';
  }
}

function renderSubmissions() {
  const tbody = document.getElementById('submissionsTableBody');
  
  if (submissions.length === 0) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#a7b7d6;padding:20px;">No submissions yet.</td></tr>';
    return;
  }
  
  tbody.innerHTML = submissions.map(sub => {
    const student = getUserById(sub.studentId);
    const course = getCourseById(sub.courseId);
    const instructor = sub.instructorId ? getUserById(sub.instructorId) : null;
    
    const scoreClass = sub.similarity === null ? 'processing' 
      : sub.similarity <= 30 ? 'low'
      : sub.similarity <= 60 ? 'medium' : 'high';
    
    return `
      <tr data-student="${student?.name.toLowerCase() || ''}" 
          data-title="${sub.title.toLowerCase()}" 
          data-status="${sub.status}"
          data-risk="${scoreClass}">
        <td><strong>#${sub.id}</strong></td>
        <td>${student?.name || 'Unknown'}</td>
        <td>${sub.title}</td>
        <td><span class="badge">${course?.code || 'N/A'}</span></td>
        <td><small>${instructor?.name || 'None'}</small></td>
        <td><small>${new Date(sub.uploadDate).toLocaleString()}</small></td>
        <td>
          <span class="similarity-score ${scoreClass}">
            ${sub.similarity !== null ? sub.similarity.toFixed(1) + '%' : '—'}
          </span>
        </td>
        <td>
          <span class="status-badge ${sub.status}">
            ${sub.status.charAt(0).toUpperCase() + sub.status.slice(1)}
          </span>
        </td>
        <td>
          <button class="btn small" onclick="viewSubmissionDetails(${sub.id})">
            👁️ View
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

function filterSubmissions() {
  const searchInput = document.getElementById('searchInput').value.toLowerCase();
  const statusFilter = document.getElementById('statusFilter').value;
  const riskFilter = document.getElementById('riskFilter').value;
  
  const rows = document.querySelectorAll('#submissionsTable tbody tr');
  
  rows.forEach(row => {
    const student = row.getAttribute('data-student');
    const title = row.getAttribute('data-title');
    const status = row.getAttribute('data-status');
    const risk = row.getAttribute('data-risk');
    
    let showRow = true;
    
    if (searchInput && student && title && !student.includes(searchInput) && !title.includes(searchInput)) {
      showRow = false;
    }
    
    if (statusFilter && status !== statusFilter) {
      showRow = false;
    }
    
    if (riskFilter && risk !== riskFilter) {
      showRow = false;
    }
    
    row.style.display = showRow ? '' : 'none';
  });
}

function viewSubmissionDetails(submissionId) {
  currentSubmissionId = submissionId;
  const sub = getSubmissionById(submissionId);
  if (!sub) return;
  
  const student = getUserById(sub.studentId);
  const course = getCourseById(sub.courseId);
  const instructor = sub.instructorId ? getUserById(sub.instructorId) : null;
  
  const scoreClass = sub.similarity === null ? 'processing' 
    : sub.similarity <= 30 ? 'low'
    : sub.similarity <= 60 ? 'medium' : 'high';
  
  document.getElementById('submissionDetailsContent').innerHTML = `
    <div class="detail-row"><strong>Submission ID:</strong> <span>#${sub.id}</span></div>
    <div class="detail-row"><strong>Student:</strong> <span>${student?.name || 'Unknown'}</span></div>
    <div class="detail-row"><strong>Student Email:</strong> <span>${student?.email || 'N/A'}</span></div>
    <div class="detail-row"><strong>Document Title:</strong> <span>${sub.title}</span></div>
    <div class="detail-row"><strong>Filename:</strong> <span>${sub.filename}</span></div>
    <div class="detail-row"><strong>Course:</strong> <span>${course?.code} - ${course?.name || 'N/A'}</span></div>
    <div class="detail-row"><strong>Instructor:</strong> <span>${instructor?.name || 'None assigned'}</span></div>
    <div class="detail-row"><strong>Submitted:</strong> <span>${new Date(sub.uploadDate).toLocaleString()}</span></div>
    <div class="detail-row">
      <strong>Similarity Score:</strong> 
      <span class="similarity-score ${scoreClass}">
        ${sub.similarity !== null ? sub.similarity.toFixed(1) + '%' : 'Processing...'}
      </span>
    </div>
    <div class="detail-row">
      <strong>Status:</strong> 
      <span class="status-badge ${sub.status}">${sub.status.charAt(0).toUpperCase() + sub.status.slice(1)}</span>
    </div>
  `;
  
  document.getElementById('submissionModal').style.display = 'flex';
}

function closeSubmissionModal() {
  document.getElementById('submissionModal').style.display = 'none';
  currentSubmissionId = null;
}

function deleteSubmission() {
  if (!currentSubmissionId) return;
  
  if (!confirm('Are you sure you want to delete this submission?')) return;
  
  const index = submissions.findIndex(s => s.id === currentSubmissionId);
  if (index !== -1) {
    submissions.splice(index, 1);
    saveSubmissions();
    renderSubmissions();
    closeSubmissionModal();
    alert('✅ Submission deleted successfully!');
  }
}

function exportToCSV() {
  const headers = ['ID', 'Student', 'Title', 'Course', 'Instructor', 'Date', 'Similarity', 'Status'];
  const rows = submissions.map(sub => {
    const student = getUserById(sub.studentId);
    const course = getCourseById(sub.courseId);
    const instructor = getUserById(sub.instructorId);
    
    return [
      sub.id,
      student?.name || 'Unknown',
      sub.title,
      course?.code || 'N/A',
      instructor?.name || 'None',
      new Date(sub.uploadDate).toLocaleString(),
      sub.similarity !== null ? sub.similarity.toFixed(1) + '%' : 'Processing',
      sub.status
    ];
  });
  
  let csvContent = headers.join(',') + '\n';
  rows.forEach(row => {
    csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
  });
  
  const blob = new Blob([csvContent], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `submissions_${new Date().toISOString().split('T')[0]}.csv`;
  a.click();
  window.URL.revokeObjectURL(url);
}

function updateDashboard() {
  const totalSub = document.getElementById('totalSubmissions');
  if (totalSub) {
    totalSub.textContent = submissions.length;
  }
}

// Close modal when clicking outside
window.onclick = function(event) {
  if (event.target.id === 'submissionModal') {
    closeSubmissionModal();
  }
}
</script>