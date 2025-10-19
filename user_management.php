<section class="user-management">
  <h2>User Management 👥</h2>

  <div id="notification" class="notice" style="display:none;"></div>

  <div class="filter-row">
    <button class="btn active" onclick="filterUsers('all')">All</button>
    <button class="btn" onclick="filterUsers('student')">Students</button>
    <button class="btn" onclick="filterUsers('instructor')">Instructors</button>
    <button class="btn" onclick="filterUsers('admin')">Admins</button>
  </div>

  <div class="add-user-form">
    <h3>Add New User ➕</h3>
    <form id="addUserForm" class="add-form" onsubmit="addUser(event)">
      <input type="text" id="newName" placeholder="Full Name" required>
      <input type="email" id="newEmail" placeholder="Email Address" required>
      <select id="newRole" required>
        <option value="">Select Role</option>
        <option value="student">Student</option>
        <option value="instructor">Instructor</option>
        <option value="admin">Admin</option>
      </select>
      <button type="submit" class="btn primary">Add User</button>
    </form>
  </div>

  <div class="user-cards" id="userCardsContainer">
    <!-- Users will be loaded here -->
  </div>
</section>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal" style="display: none;">
  <div class="modal-content small">
    <div class="modal-header">
      <h3>✏️ Edit User</h3>
      <button class="close-btn" onclick="closeEditModal()">&times;</button>
    </div>
    <div class="modal-body">
      <form id="editUserForm" onsubmit="saveUserEdit(event)">
        <input type="hidden" id="edit_user_id">
        
        <label>Full Name</label>
        <input type="text" id="edit_name" class="edit-input" required>

        <label>Email</label>
        <input type="email" id="edit_email" class="edit-input" required>

        <label>Role</label>
        <select id="edit_role" class="edit-input" required>
          <option value="student">Student</option>
          <option value="instructor">Instructor</option>
          <option value="admin">Admin</option>
        </select>

        <label>Status</label>
        <select id="edit_status" class="edit-input" required>
          <option value="active">Active</option>
          <option value="banned">Banned</option>
        </select>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
          <button type="submit" class="btn primary">💾 Save Changes</button>
          <button type="button" class="btn" onclick="closeEditModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.7);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content.small {
  background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.03));
  border-radius: 16px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}

.modal-header {
  padding: 20px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  color: #fff;
}

.close-btn {
  background: none;
  border: none;
  color: #fff;
  font-size: 28px;
  cursor: pointer;
  transition: transform 0.2s;
}

.close-btn:hover {
  transform: rotate(90deg);
  color: #ff5a6b;
}

.modal-body {
  padding: 20px;
}

.edit-input {
  width: 100%;
  padding: 10px;
  border-radius: 8px;
  border: none;
  background: rgba(255,255,255,0.05);
  color: #fff;
  margin-bottom: 15px;
  font-size: 14px;
}

.edit-input:focus {
  outline: none;
  background: rgba(255,255,255,0.08);
}

.modal-body label {
  display: block;
  color: #a7b7d6;
  margin-bottom: 5px;
  font-size: 13px;
  font-weight: 600;
}
</style>

<script>
// Initialize with hardcoded data
let users = [
  { id: 1, name: 'Ahmed Hassan', email: 'ahmed@student.edu', role: 'student', status: 'active' },
  { id: 2, name: 'Fatma Ali', email: 'fatma@student.edu', role: 'student', status: 'active' },
  { id: 3, name: 'Mohamed Omar', email: 'mohamed@student.edu', role: 'student', status: 'banned' },
  { id: 4, name: 'Dr. Ahmed Mohamed', email: 'ahmed.m@university.edu', role: 'instructor', status: 'active' },
  { id: 5, name: 'Prof. Sara Ali', email: 'sara.ali@university.edu', role: 'instructor', status: 'active' },
  { id: 6, name: 'Admin User', email: 'admin@university.edu', role: 'admin', status: 'active' },
];

let nextUserId = 7;
let currentFilter = 'all';

// Load from localStorage if exists
function loadUsers() {
  const saved = localStorage.getItem('users');
  if (saved) {
    const data = JSON.parse(saved);
    users = data.users;
    nextUserId = data.nextUserId;
  }
}

// Save to localStorage
function saveUsers() {
  localStorage.setItem('users', JSON.stringify({
    users: users,
    nextUserId: nextUserId
  }));
  
  // Update dashboard if exists
  updateDashboard();
}

// Load users on page load
document.addEventListener('DOMContentLoaded', function() {
  loadUsers();
  renderUsers();
});

function renderUsers() {
  const container = document.getElementById('userCardsContainer');
  let filteredUsers = currentFilter === 'all' 
    ? users 
    : users.filter(u => u.role === currentFilter);

  if (filteredUsers.length === 0) {
    container.innerHTML = '<p style="color:#a7b7d6;padding:20px;text-align:center;">No users found.</p>';
    return;
  }

  container.innerHTML = filteredUsers.map(user => `
    <div class="user-card" data-role="${user.role}" id="user-card-${user.id}">
      <div class="user-top">
        <div class="avatar">${user.name.charAt(0).toUpperCase()}</div>
        <div>
          <div class="user-name">${user.name}</div>
          <div class="user-email">${user.email}</div>
        </div>
      </div>

      <div class="user-info">
        <div><strong>Role:</strong> <span>${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</span></div>
        <div><strong>Status:</strong> <span class="status ${user.status}">${user.status.charAt(0).toUpperCase() + user.status.slice(1)}</span></div>
      </div>

      <div class="user-actions">
        <button class="btn small ${user.status === 'active' ? 'danger' : 'success'}" onclick="toggleUserStatus(${user.id})">
          ${user.status === 'active' ? 'Ban' : 'Unban'}
        </button>

        <button class="btn small" onclick="openEditModal(${user.id})">✏️ Edit</button>

        <button class="btn small danger" onclick="deleteUser(${user.id})">🗑️ Delete</button>
      </div>
    </div>
  `).join('');
}

function filterUsers(role) {
  currentFilter = role;
  
  // Update active button
  document.querySelectorAll('.filter-row .btn').forEach((btn, index) => {
    btn.classList.remove('active');
    if ((role === 'all' && index === 0) ||
        (role === 'student' && index === 1) ||
        (role === 'instructor' && index === 2) ||
        (role === 'admin' && index === 3)) {
      btn.classList.add('active');
    }
  });
  
  renderUsers();
}

function addUser(event) {
  event.preventDefault();
  
  const name = document.getElementById('newName').value.trim();
  const email = document.getElementById('newEmail').value.trim();
  const role = document.getElementById('newRole').value;

  // Check for duplicate email
  if (users.find(u => u.email.toLowerCase() === email.toLowerCase())) {
    showNotification('⚠️ Email already exists!', 'error');
    return;
  }

  const newUser = {
    id: nextUserId++,
    name: name,
    email: email,
    role: role,
    status: 'active'
  };

  users.push(newUser);
  saveUsers();

  document.getElementById('addUserForm').reset();
  renderUsers();
  showNotification('✅ User added successfully!', 'success');
}

function openEditModal(userId) {
  const user = users.find(u => u.id === userId);
  if (!user) return;

  document.getElementById('edit_user_id').value = user.id;
  document.getElementById('edit_name').value = user.name;
  document.getElementById('edit_email').value = user.email;
  document.getElementById('edit_role').value = user.role;
  document.getElementById('edit_status').value = user.status;

  document.getElementById('editUserModal').style.display = 'flex';
}

function closeEditModal() {
  document.getElementById('editUserModal').style.display = 'none';
}

function saveUserEdit(event) {
  event.preventDefault();

  const userId = parseInt(document.getElementById('edit_user_id').value);
  const name = document.getElementById('edit_name').value.trim();
  const email = document.getElementById('edit_email').value.trim();
  const role = document.getElementById('edit_role').value;
  const status = document.getElementById('edit_status').value;

  // Check for duplicate email (excluding current user)
  if (users.find(u => u.email.toLowerCase() === email.toLowerCase() && u.id !== userId)) {
    showNotification('⚠️ Email already exists!', 'error');
    return;
  }

  const user = users.find(u => u.id === userId);
  if (user) {
    user.name = name;
    user.email = email;
    user.role = role;
    user.status = status;
    
    saveUsers();
    renderUsers();
    closeEditModal();
    showNotification('✅ User updated successfully!', 'success');
  }
}

function toggleUserStatus(userId) {
  const user = users.find(u => u.id === userId);
  if (user) {
    user.status = user.status === 'active' ? 'banned' : 'active';
    saveUsers();
    renderUsers();
    showNotification(`✅ User ${user.status === 'active' ? 'unbanned' : 'banned'} successfully!`, 'success');
  }
}

function deleteUser(userId) {
  if (!confirm('Are you sure you want to delete this user?')) return;

  const index = users.findIndex(u => u.id === userId);
  if (index !== -1) {
    users.splice(index, 1);
    saveUsers();
    renderUsers();
    showNotification('🗑️ User deleted successfully!', 'success');
  }
}

function showNotification(message, type) {
  const notification = document.getElementById('notification');
  notification.textContent = message;
  notification.className = 'notice ' + type;
  notification.style.display = 'block';

  setTimeout(() => {
    notification.style.display = 'none';
  }, 3000);
}

function updateDashboard() {
  const totalUsers = document.getElementById('totalUsers');
  if (totalUsers) {
    totalUsers.textContent = users.length;
  }
}

// Close modal when clicking outside
window.onclick = function(event) {
  const modal = document.getElementById('editUserModal');
  if (event.target == modal) {
    closeEditModal();
  }
}
</script>