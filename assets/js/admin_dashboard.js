let userChart, similarityChart, courseChart;

document.addEventListener('DOMContentLoaded', function () {
  updateDashboardStats();
  createCharts();
  loadRecentSubmissions();
});

// -------------------------
// 1. Top statistics cards
// -------------------------
function updateDashboardStats() {
  // Prefer server-provided stats if available
  if (window.dashboardStats) {
    const stats = window.dashboardStats;

    document.getElementById('totalUsers').textContent =
      stats.totalUsers ?? 0;
    document.getElementById('totalCourses').textContent =
      stats.totalCourses ?? 0;
    document.getElementById('totalSubmissions').textContent =
      stats.totalSubmissions ?? 0;
    document.getElementById('highRiskCount').textContent =
      stats.highRiskCount ?? 0;

    return;
  }

  // Fallback: localStorage (old behavior)
  try {
    const usersData = JSON.parse(localStorage.getItem('users') || '{"users":[]}');
    const coursesData = JSON.parse(localStorage.getItem('courses') || '{"courses":[]}');
    const submissionsData = JSON.parse(localStorage.getItem('submissions') || '{"submissions":[]}');

    const users = usersData.users || [];
    const courses = coursesData.courses || [];
    const submissions = submissionsData.submissions || [];

    document.getElementById('totalUsers').textContent = users.length;
    document.getElementById('totalCourses').textContent = courses.length;
    document.getElementById('totalSubmissions').textContent = submissions.length;

    const highRisk = submissions.filter(s => s.similarity && s.similarity > 70).length;
    document.getElementById('highRiskCount').textContent = highRisk;
  } catch (e) {
    console.log('Using default values for dashboard stats');
  }
}

// -------------------------
// 2. Charts
// -------------------------
function createCharts() {
  createUserPieChart();
  createSimilarityChart();
  createCourseBarChart();
}

function createUserPieChart() {
  const ctx = document.getElementById('userPieChart');
  if (!ctx) return;

  let students = 0, instructors = 0, admins = 0;

  if (window.dashboardStats && window.dashboardStats.userDistribution) {
    const dist = window.dashboardStats.userDistribution;
    students   = dist.student   ?? dist.students   ?? 0;
    instructors= dist.instructor?? dist.instructors?? 0;
    admins     = dist.admin     ?? dist.admins     ?? 0;
  } else {
    // Fallback: localStorage
    try {
      const usersData = JSON.parse(localStorage.getItem('users') || '{"users":[]}');
      const users = usersData.users || [];
      students   = users.filter(u => u.role === 'student').length;
      instructors= users.filter(u => u.role === 'instructor').length;
      admins     = users.filter(u => u.role === 'admin').length;
    } catch (e) {
      console.error('Error reading users from localStorage', e);
    }
  }

  try {
    if (userChart) userChart.destroy();

    userChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: ['Students', 'Instructors', 'Admins'],
        datasets: [{
          data: [students, instructors, admins],
          backgroundColor: [
            'rgba(0, 198, 255, 0.8)',
            'rgba(126, 243, 182, 0.8)',
            'rgba(255, 169, 77, 0.8)'
          ],
          borderColor: [
            'rgba(0, 198, 255, 1)',
            'rgba(126, 243, 182, 1)',
            'rgba(255, 169, 77, 1)'
          ],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            titleColor: '#fff',
            bodyColor: '#fff',
            callbacks: {
              label: function (context) {
                const total = context.dataset.data.reduce((a, b) => a + b, 0) || 1;
                const percentage = ((context.parsed / total) * 100).toFixed(1);
                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
              }
            }
          }
        }
      }
    });

    document.getElementById('userLegend').innerHTML = `
      <div class="legend-item">
        <div class="legend-color" style="background: rgba(0, 198, 255, 0.8);"></div>
        <span>Students (${students})</span>
      </div>
      <div class="legend-item">
        <div class="legend-color" style="background: rgba(126, 243, 182, 0.8);"></div>
        <span>Instructors (${instructors})</span>
      </div>
      <div class="legend-item">
        <div class="legend-color" style="background: rgba(255, 169, 77, 0.8);"></div>
        <span>Admins (${admins})</span>
      </div>
    `;
  } catch (e) {
    console.error('Error creating user chart:', e);
  }
}

function createSimilarityChart() {
  const ctx = document.getElementById('similarityChart');
  if (!ctx) return;

  let low = 0, medium = 0, high = 0;

  if (window.dashboardStats && window.dashboardStats.similarityDistribution) {
    const dist = window.dashboardStats.similarityDistribution;
    low    = dist['Low (0-30%)']      ?? 0;
    medium = dist['Medium (31-70%)']  ?? 0;
    high   = dist['High (71-100%)']   ?? 0;
  } else {
    // Fallback: localStorage
    try {
      const submissionsData = JSON.parse(localStorage.getItem('submissions') || '{"submissions":[]}');
      const submissions = submissionsData.submissions || [];
      const completed = submissions.filter(s => s.status === 'completed' && s.similarity !== null);
      low    = completed.filter(s => s.similarity <= 30).length;
      medium = completed.filter(s => s.similarity > 30 && s.similarity <= 70).length;
      high   = completed.filter(s => s.similarity > 70).length;
    } catch (e) {
      console.error('Error reading submissions from localStorage', e);
    }
  }

  try {
    if (similarityChart) similarityChart.destroy();

    similarityChart = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Low (0-30%)', 'Medium (31-70%)', 'High (71-100%)'],
        datasets: [{
          data: [low, medium, high],
          backgroundColor: [
            'rgba(126, 243, 182, 0.8)',
            'rgba(255, 169, 77, 0.8)',
            'rgba(255, 90, 107, 0.8)'
          ],
          borderColor: [
            'rgba(126, 243, 182, 1)',
            'rgba(255, 169, 77, 1)',
            'rgba(255, 90, 107, 1)'
          ],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            titleColor: '#fff',
            bodyColor: '#fff'
          }
        },
        cutout: '60%'
      }
    });

    document.getElementById('similarityLegend').innerHTML = `
      <div class="legend-item">
        <div class="legend-color" style="background: rgba(126, 243, 182, 0.8);"></div>
        <span>Low Risk (${low})</span>
      </div>
      <div class="legend-item">
        <div class="legend-color" style="background: rgba(255, 169, 77, 0.8);"></div>
        <span>Medium Risk (${medium})</span>
      </div>
      <div class="legend-item">
        <div class="legend-color" style="background: rgba(255, 90, 107, 0.8);"></div>
        <span>High Risk (${high})</span>
      </div>
    `;
  } catch (e) {
    console.error('Error creating similarity chart:', e);
  }
}

function createCourseBarChart() {
  const ctx = document.getElementById('courseBarChart');
  if (!ctx) return;

  let labels = [];
  let counts = [];

  if (window.dashboardStats && window.dashboardStats.courseActivity) {
    const activity = window.dashboardStats.courseActivity;
    labels = activity.map(a => a.name);
    counts = activity.map(a => a.count);
  } else {
    // Fallback: localStorage
    try {
      const submissionsData = JSON.parse(localStorage.getItem('submissions') || '{"submissions":[]}');
      const coursesData = JSON.parse(localStorage.getItem('courses') || '{"courses":[]}');

      const submissions = submissionsData.submissions || [];
      const courses = coursesData.courses || [];

      labels = courses.map(c => c.code);
      counts = courses.map(course =>
        submissions.filter(s => s.courseId === course.id).length
      );
    } catch (e) {
      console.error('Error reading course data from localStorage', e);
    }
  }

  try {
    if (courseChart) courseChart.destroy();

    courseChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Submissions',
          data: counts,
          backgroundColor: 'rgba(0, 198, 255, 0.7)',
          borderColor: 'rgba(0, 198, 255, 1)',
          borderWidth: 2,
          borderRadius: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              color: '#a7b7d6',
              stepSize: 1
            },
            grid: {
              color: 'rgba(255, 255, 255, 0.05)'
            }
          },
          x: {
            ticks: {
              color: '#a7b7d6'
            },
            grid: {
              display: false
            }
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            titleColor: '#fff',
            bodyColor: '#fff'
          }
        }
      }
    });
  } catch (e) {
    console.error('Error creating course chart:', e);
  }
}

// -------------------------
// 3. Recent submissions
// -------------------------
function loadRecentSubmissions() {
  const container = document.getElementById('recentSubmissions');
  if (!container) return;

  // Prefer backend stats if provided
  if (window.dashboardStats && Array.isArray(window.dashboardStats.recentSubmissions)) {
    const recent = window.dashboardStats.recentSubmissions;
    if (recent.length === 0) {
      container.innerHTML =
        '<div class="empty-state"><i class="fas fa-inbox"></i><p>No submissions yet</p></div>';
      return;
    }

    container.innerHTML = recent.map(sub => {
      const scoreClass =
        sub.similarity === null      ? 'processing' :
        sub.similarity <= 30         ? 'low' :
        sub.similarity <= 70         ? 'medium' : 'high';

      return `
        <div class="submission-item">
          <div class="submission-info">
            <div class="submission-title">Submission #${sub.id}</div>
            <div class="submission-meta">
              <span class="meta-item"><i class="fas fa-user"></i> ${sub.student_name || 'Unknown'}</span>
              <span class="meta-item"><i class="fas fa-book"></i> ${sub.course_name || 'General Submission'}</span>
              <span class="meta-item"><i class="fas fa-calendar"></i> ${new Date(sub.created_at).toLocaleDateString()}</span>
            </div>
          </div>
          <span class="similarity-score ${scoreClass}">
            ${sub.similarity !== null ? sub.similarity + '%' : '⏳ Processing'}
          </span>
        </div>
      `;
    }).join('');

    return;
  }

  // Fallback: localStorage implementation
  try {
    const submissionsData = JSON.parse(localStorage.getItem('submissions') || '{"submissions":[]}');
    const usersData       = JSON.parse(localStorage.getItem('users') || '{"users":[]}');
    const coursesData     = JSON.parse(localStorage.getItem('courses') || '{"courses":[]}');

    const submissions = submissionsData.submissions || [];
    const users       = usersData.users || [];
    const courses     = coursesData.courses || [];

    if (submissions.length === 0) {
      container.innerHTML =
        '<div class="empty-state"><i class="fas fa-inbox"></i><p>No submissions yet</p></div>';
      return;
    }

    const recent = submissions.slice(-5).reverse();

    container.innerHTML = recent.map(sub => {
      const student = users.find(u => u.id === sub.studentId);
      const course  = courses.find(c => c.id === sub.courseId);

      const scoreClass =
        sub.similarity === null      ? 'processing' :
        sub.similarity <= 30         ? 'low' :
        sub.similarity <= 70         ? 'medium' : 'high';

      return `
        <div class="submission-item">
          <div class="submission-info">
            <div class="submission-title">${sub.title}</div>
            <div class="submission-meta">
              <span class="meta-item"><i class="fas fa-user"></i> ${student?.name || 'Unknown'}</span>
              <span class="meta-item"><i class="fas fa-book"></i> ${course?.code || 'N/A'}</span>
              <span class="meta-item"><i class="fas fa-calendar"></i> ${new Date(sub.uploadDate).toLocaleDateString()}</span>
            </div>
          </div>
          <span class="similarity-score ${scoreClass}">
            ${sub.similarity !== null ? sub.similarity.toFixed(1) + '%' : '⏳ Processing'}
          </span>
        </div>
      `;
    }).join('');
  } catch (e) {
    container.innerHTML =
      '<div class="empty-state"><p>No data available</p></div>';
  }
}

// Refresh dashboard when window regains focus
window.addEventListener('focus', function () {
  updateDashboardStats();
  createCharts();
  loadRecentSubmissions();
});
