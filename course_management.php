<section class="course-management">
  <h2>Course Management 📚</h2>

  <div id="courseNotification" class="notice" style="display:none;"></div>

  <!-- Add New Course Form -->
  <div class="add-user-form">
    <h3>Create New Course ➕</h3>
    <form id="addCourseForm" class="add-form" onsubmit="addCourse(event)">
      <input type="text" id="newCourseCode" placeholder="Course Code (e.g., CS101)" required>
      <input type="text" id="newCourseName" placeholder="Course Name" required>
      <input type="text" id="newDepartment" placeholder="Department" required>
      <select id="newTerm" required>
        <option value="">Select Term</option>
        <option value="Fall 2024">Fall 2024</option>
        <option value="Spring 2025">Spring 2025</option>
        <option value="Summer 2025">Summer 2025</option>
      </select>
      <button type="submit" class="btn primary">Create Course</button>
    </form>
  </div>

  <!-- Courses Table -->
  <div class="courses-table-container">
    <h3>All Courses</h3>
    <table class="courses-table">
      <thead>
        <tr>
          <th>Course Code</th>
          <th>Course Name</th>
          <th>Department</th>
          <th>Term</th>
          <th>Instructors</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="coursesTableBody">
        <!-- Courses will be loaded here by JavaScript -->
      </tbody>
    </table>
  </div>
</section>

<!-- Course Details Modal -->
<div id="courseDetailsModal" class="modal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>📖 Course Details</h3>
      <button class="close-btn" onclick="closeCourseModal()">&times;</button>
    </div>
    <div class="modal-body">
      <div class="course-info-section">
        <h4>Course Information</h4>
        <p><strong>Code:</strong> <span id="modalCourseCode"></span></p>
        <p><strong>Name:</strong> <span id="modalCourseName"></span></p>
        <p><strong>Department:</strong> <span id="modalDepartment"></span></p>
        <p><strong>Term:</strong> <span id="modalTerm"></span></p>
      </div>

      <div class="instructors-section">
        <h4>👨‍🏫 Assigned Instructors</h4>
        <button class="btn small primary" onclick="openAssignInstructorModal()">+ Assign Instructor</button>
        <div class="instructors-list" id="modalInstructorsList"></div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Course Modal -->
<div id="editCourseModal" class="modal" style="display: none;">
  <div class="modal-content small">
    <div class="modal-header">
      <h3>✏️ Edit Course</h3>
      <button class="close-btn" onclick="closeEditCourseModal()">&times;</button>
    </div>
    <div class="modal-body">
      <form id="editCourseForm" onsubmit="saveCourseEdit(event)">
        <input type="hidden" id="editCourseId">
        
        <label>Course Code</label>
        <input type="text" id="editCourseCode" class="edit-input" required>

        <label>Course Name</label>
        <input type="text" id="editCourseName" class="edit-input" required>

        <label>Department</label>
        <input type="text" id="editDepartment" class="edit-input" required>

        <label>Term</label>
        <select id="editTerm" class="edit-input" required>
          <option value="Fall 2024">Fall 2024</option>
          <option value="Spring 2025">Spring 2025</option>
          <option value="Summer 2025">Summer 2025</option>
        </select>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
          <button type="submit" class="btn primary">💾 Save Changes</button>
          <button type="button" class="btn" onclick="closeEditCourseModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Assign Instructor Modal -->
<div id="assignInstructorModal" class="modal" style="display: none;">
  <div class="modal-content small">
    <div class="modal-header">
      <h3>Assign Instructor</h3>
      <button class="close-btn" onclick="closeAssignInstructorModal()">&times;</button>
    </div>
    <div class="modal-body">
      <div class="instructor-select-list" id="instructorSelectList"></div>
      <div style="margin-top: 15px;">
        <button class="btn primary" onclick="assignSelectedInstructors()">Assign Selected</button>
        <button class="btn" onclick="closeAssignInstructorModal()">Cancel</button>
      </div>
    </div>
  </div>
</div>

<style>
.courses-table-container {
  margin-top: 20px;
  background: rgba(255,255,255,0.02);
  padding: 20px;
  border-radius: 14px;
  overflow-x: auto;
}

.courses-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}

.courses-table thead {
  background: rgba(255,255,255,0.05);
}

.courses-table th,
.courses-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  color: #eaf2ff;
}

.courses-table th {
  font-weight: 600;
  color: var(--accent1);
}

.courses-table tbody tr:hover {
  background: rgba(255,255,255,0.03);
}

.badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 12px;
  background: rgba(0,198,255,0.15);
  color: var(--accent1);
  font-size: 12px;
  font-weight: 600;
}

.modal-content {
  background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.03));
  border-radius: 16px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}

.course-info-section,
.instructors-section {
  margin-bottom: 25px;
  padding: 15px;
  background: rgba(255,255,255,0.02);
  border-radius: 10px;
}

.course-info-section h4,
.instructors-section h4 {
  color: var(--accent1);
  margin-bottom: 12px;
}

.instructor-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  margin: 8px 0;
  background: rgba(255,255,255,0.03);
  border-radius: 8px;
}

.instructors-list {
  margin-top: 10px;
  max-height: 200px;
  overflow-y: auto;
}

.instructor-select-list {
  max-height: 300px;
  overflow-y: auto;
}

.instructor-option {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  background: rgba(255,255,255,0.03);
  border-radius: 8px;
  margin-bottom: 8px;
  cursor: pointer;
}

.instructor-option:hover {
  background: rgba(255,255,255,0.06);
}

.instructor-option input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}
</style>

<script>
// Hardcoded data
let courses = [
  { 
    id: 1, 
    code: 'CS101', 
    name: 'Introduction to Programming', 
    department: 'Computer Science', 
    term: 'Fall 2024',
    instructors: [4] // instructor IDs
  },
  { 
    id: 2, 
    code: 'ENG201', 
    name: 'Academic Writing', 
    department: 'English', 
    term: 'Fall 2024',
    instructors: [5]
  },
  { 
    id: 3, 
    code: 'MATH150', 
    name: 'Calculus I', 
    department: 'Mathematics', 
    term: 'Spring 2025',
    instructors: [4, 5]
  },
];

let nextCourseId = 4;
let currentCourseId = null;

// Get users from localStorage
function getUsers() {
  const saved = localStorage.getItem('users');
  if (saved) {
    return JSON.parse(saved).users;
  }
  // Fallback hardcoded users
  return [
    { id: 4, name: 'Dr. Ahmed Mohamed', email: 'ahmed.m@university.edu', role: 'instructor' },
    { id: 5, name: 'Prof. Sara Ali', email: 'sara.ali@university.edu', role: 'instructor' },
  ];
}

// Load from localStorage
function loadCourses() {
  const saved = localStorage.getItem('courses');
  if (saved) {
    const data = JSON.parse(saved);
    courses = data.courses;
    nextCourseId = data.nextCourseId;
  }
}

// Save to localStorage
function saveCourses() {
  localStorage.setItem('courses', JSON.stringify({
    courses: courses,
    nextCourseId: nextCourseId
  }));
  
  // Update dashboard
  const totalCourses = document.getElementById('totalCourses');
  if (totalCourses) {
    totalCourses.textContent = courses.length;
  }
}

document.addEventListener('DOMContentLoaded', function() {
  loadCourses();
  renderCourses();
});

function renderCourses() {
  const tbody = document.getElementById('coursesTableBody');
  
  if (courses.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#a7b7d6;padding:20px;">No courses yet. Create one above!</td></tr>';
    return;
  }
  
  tbody.innerHTML = courses.map(course => {
    const instructorCount = course.instructors.length;
    
    return `
      <tr>
        <td><strong>${course.code}</strong></td>
        <td>${course.name}</td>
        <td>${course.department}</td>
        <td>${course.term}</td>
        <td><span class="badge">${instructorCount} Assigned</span></td>
        <td>
          <button class="btn small" onclick="viewCourseDetails(${course.id})">👁️ View</button>
          <button class="btn small" onclick="openEditCourseModal(${course.id})">✏️ Edit</button>
          <button class="btn small danger" onclick="deleteCourse(${course.id})">🗑️ Delete</button>
        </td>
      </tr>
    `;
  }).join('');
}

function addCourse(event) {
  event.preventDefault();
  
  const code = document.getElementById('newCourseCode').value.trim();
  const name = document.getElementById('newCourseName').value.trim();
  const department = document.getElementById('newDepartment').value.trim();
  const term = document.getElementById('newTerm').value;

  // Check for duplicate course code
  if (courses.find(c => c.code.toLowerCase() === code.toLowerCase())) {
    showCourseNotification('⚠️ Course code already exists!', 'error');
    return;
  }

  const newCourse = {
    id: nextCourseId++,
    code: code,
    name: name,
    department: department,
    term: term,
    instructors: []
  };

  courses.push(newCourse);
  saveCourses();

  document.getElementById('addCourseForm').reset();
  renderCourses();
  showCourseNotification('✅ Course created successfully!', 'success');
}

function viewCourseDetails(courseId) {
  currentCourseId = courseId;
  const course = courses.find(c => c.id === courseId);
  if (!course) return;

  document.getElementById('modalCourseCode').textContent = course.code;
  document.getElementById('modalCourseName').textContent = course.name;
  document.getElementById('modalDepartment').textContent = course.department;
  document.getElementById('modalTerm').textContent = course.term;

  // Load instructors
  const users = getUsers();
  const instructors = course.instructors.map(id => users.find(u => u.id === id)).filter(u => u);
  
  document.getElementById('modalInstructorsList').innerHTML = instructors.length > 0 
    ? instructors.map(inst => `
      <div class="instructor-item">
        <div>
          <strong>${inst.name}</strong><br>
          <small>${inst.email}</small>
        </div>
        <button class="btn small danger" onclick="removeInstructor(${courseId}, ${inst.id})">Remove</button>
      </div>
    `).join('')
    : '<p style="color:#a7b7d6;padding:10px;">No instructors assigned yet.</p>';

  document.getElementById('courseDetailsModal').style.display = 'flex';
}

function closeCourseModal() {
  document.getElementById('courseDetailsModal').style.display = 'none';
  currentCourseId = null;
}

function openEditCourseModal(courseId) {
  const course = courses.find(c => c.id === courseId);
  if (!course) return;

  document.getElementById('editCourseId').value = course.id;
  document.getElementById('editCourseCode').value = course.code;
  document.getElementById('editCourseName').value = course.name;
  document.getElementById('editDepartment').value = course.department;
  document.getElementById('editTerm').value = course.term;

  document.getElementById('editCourseModal').style.display = 'flex';
}

function closeEditCourseModal() {
  document.getElementById('editCourseModal').style.display = 'none';
}

function saveCourseEdit(event) {
  event.preventDefault();

  const courseId = parseInt(document.getElementById('editCourseId').value);
  const code = document.getElementById('editCourseCode').value.trim();
  const name = document.getElementById('editCourseName').value.trim();
  const department = document.getElementById('editDepartment').value.trim();
  const term = document.getElementById('editTerm').value;

  // Check for duplicate code (excluding current course)
  if (courses.find(c => c.code.toLowerCase() === code.toLowerCase() && c.id !== courseId)) {
    showCourseNotification('⚠️ Course code already exists!', 'error');
    return;
  }

  const course = courses.find(c => c.id === courseId);
  if (course) {
    course.code = code;
    course.name = name;
    course.department = department;
    course.term = term;
    
    saveCourses();
    renderCourses();
    closeEditCourseModal();
    showCourseNotification('✅ Course updated successfully!', 'success');
  }
}

function deleteCourse(courseId) {
  if (!confirm('Are you sure you want to delete this course?')) return;

  const index = courses.findIndex(c => c.id === courseId);
  if (index !== -1) {
    courses.splice(index, 1);
    saveCourses();
    renderCourses();
    showCourseNotification('🗑️ Course deleted successfully!', 'success');
  }
}

function openAssignInstructorModal() {
  const users = getUsers();
  const instructors = users.filter(u => u.role === 'instructor');
  const course = courses.find(c => c.id === currentCourseId);
  
  if (instructors.length === 0) {
    alert('No instructors available! Please add instructors in User Management first.');
    return;
  }
  
  document.getElementById('instructorSelectList').innerHTML = instructors.map(inst => {
    const isAssigned = course.instructors.includes(inst.id);
    return `
      <label class="instructor-option">
        <input type="checkbox" value="${inst.id}" ${isAssigned ? 'checked disabled' : ''}>
        <span>
          <strong>${inst.name}</strong><br>
          <small>${inst.email}</small>
          ${isAssigned ? '<br><small style="color:#7ef3b6;">✓ Already assigned</small>' : ''}
        </span>
      </label>
    `;
  }).join('');

  document.getElementById('assignInstructorModal').style.display = 'flex';
}

function closeAssignInstructorModal() {
  document.getElementById('assignInstructorModal').style.display = 'none';
}

function assignSelectedInstructors() {
  const checkboxes = document.querySelectorAll('#instructorSelectList input[type="checkbox"]:checked:not(:disabled)');
  const instructorIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
  
  if (instructorIds.length === 0) {
    showCourseNotification('⚠️ Please select at least one instructor!', 'error');
    return;
  }

  const course = courses.find(c => c.id === currentCourseId);
  if (course) {
    instructorIds.forEach(id => {
      if (!course.instructors.includes(id)) {
        course.instructors.push(id);
      }
    });
    
    saveCourses();
    closeAssignInstructorModal();
    viewCourseDetails(currentCourseId); // Refresh the modal
    renderCourses(); // Refresh the table
    showCourseNotification('✅ Instructor(s) assigned successfully!', 'success');
  }
}

function removeInstructor(courseId, instructorId) {
  if (!confirm('Remove this instructor from the course?')) return;

  const course = courses.find(c => c.id === courseId);
  if (course) {
    const index = course.instructors.indexOf(instructorId);
    if (index !== -1) {
      course.instructors.splice(index, 1);
      saveCourses();
      viewCourseDetails(courseId); // Refresh the modal
      renderCourses(); // Refresh the table
      showCourseNotification('✅ Instructor removed successfully!', 'success');
    }
  }
}

function showCourseNotification(message, type) {
  const notification = document.getElementById('courseNotification');
  notification.textContent = message;
  notification.className = 'notice ' + type;
  notification.style.display = 'block';

  setTimeout(() => {
    notification.style.display = 'none';
  }, 3000);
}

// Close modals when clicking outside
window.onclick = function(event) {
  if (event.target.id === 'courseDetailsModal') {
    closeCourseModal();
  }
  if (event.target.id === 'editCourseModal') {
    closeEditCourseModal();
  }
  if (event.target.id === 'assignInstructorModal') {
    closeAssignInstructorModal();
  }
}
</script>