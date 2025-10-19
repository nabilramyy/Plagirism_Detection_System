<section class="dashboard">
  <h2>Dashboard Overview 📊</h2>

  <!-- Stats Cards -->
  <div class="stats-cards">
    <div class="stat-card gradient-blue">
      <div class="icon-wrap"><i class="fas fa-users"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="totalUsers">6</div>
        <div class="stat-label">Total Users</div>
      </div>
    </div>

    <div class="stat-card gradient-green">
      <div class="icon-wrap"><i class="fas fa-file-alt"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="totalSubmissions">5</div>
        <div class="stat-label">Total Submissions</div>
      </div>
    </div>

    <div class="stat-card gradient-purple">
      <div class="icon-wrap"><i class="fas fa-book-open"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="totalCourses">3</div>
        <div class="stat-label">Total Courses</div>
      </div>
    </div>

    <div class="stat-card gradient-red">
      <div class="icon-wrap"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="highRiskCount">0</div>
        <div class="stat-label">High-Risk Submissions</div>
      </div>
    </div>
  </div>

  <!-- Charts Row -->
  <div class="charts-grid">
    <!-- Pie Chart - User Distribution -->
    <div class="chart-card">
      <h3><i class="fas fa-chart-pie"></i> User Distribution</h3>
      <canvas id="userPieChart"></canvas>
      <div class="chart-legend" id="userLegend"></div>
    </div>

    <!-- Doughnut Chart - Similarity Distribution -->
    <div class="chart-card">
      <h3><i class="fas fa-chart-donut"></i> Similarity Score Distribution</h3>
      <canvas id="similarityChart"></canvas>
      <div class="chart-legend" id="similarityLegend"></div>
    </div>

    <!-- Bar Chart - Course Activity -->
    <div class="chart-card wide">
      <h3><i class="fas fa-chart-bar"></i> Course Activity</h3>
      <canvas id="courseBarChart"></canvas>
    </div>
  </div>

  <!-- Recent Activity -->
  <div class="dashboard-section">
    <h3><i class="fas fa-clock"></i> Recent Submissions</h3>
    <div class="recent-submissions" id="recentSubmissions">
      <!-- Loaded by JavaScript -->
    </div>
  </div>
</section>

<style>
.gradient-blue {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.gradient-green {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.gradient-purple {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.gradient-red {
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.charts-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-top: 30px;
}

.chart-card {
  background: rgba(255,255,255,0.03);
  padding: 25px;
  border-radius: 16px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.1);
}

.chart-card.wide {
  grid-column: 1 / -1;
}

.chart-card h3 {
  color: var(--accent1);
  margin-bottom: 20px;
  font-size: 18px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.chart-card canvas {
  max-height: 300px;
}

.chart-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-top: 15px;
  justify-content: center;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #eaf2ff;
}

.legend-color {
  width: 16px;
  height: 16px;
  border-radius: 4px;
}

.dashboard-section {
  background: rgba(255,255,255,0.02);
  padding: 25px;
  border-radius: 16px;
  margin-top: 20px;
  border: 1px solid rgba(255,255,255,0.1);
}

.dashboard-section h3 {
  color: var(--accent1);
  margin-bottom: 20px;
  font-size: 18px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.recent-submissions {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.submission-item {
  background: rgba(255,255,255,0.03);
  padding: 18px;
  border-radius: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: all 0.3s ease;
  border-left: 4px solid transparent;
}

.submission-item:hover {
  transform: translateX(8px);
  background: rgba(255,255,255,0.06);
  border-left-color: var(--accent1);
}

.submission-info {
  flex: 1;
}

.submission-title {
  color: #fff;
  font-weight: 600;
  margin-bottom: 6px;
  font-size: 15px;
}

.submission-meta {
  color: #a7b7d6;
  font-size: 13px;
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 5px;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #a7b7d6;
}

.empty-state i {
  font-size: 64px;
  margin-bottom: 20px;
  opacity: 0.3;
  display: block;
}

.empty-state p {
  font-size: 16px;
}

@media (max-width: 968px) {
  .charts-grid {
    grid-template-columns: 1fr;
  }
  
  .chart-card.wide {
    grid-column: 1;
  }
}
</style>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
let userChart, similarityChart, courseChart;

document.addEventListener('DOMContentLoaded', function() {
  updateDashboardStats();
  createCharts();
  loadRecentSubmissions();
});

function updateDashboardStats() {
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
  } catch(e) {
    console.log('Using default values');
  }
}

function createCharts() {
  createUserPieChart();
  createSimilarityChart();
  createCourseBarChart();
}

function createUserPieChart() {
  const ctx = document.getElementById('userPieChart');
  
  try {
    const usersData = JSON.parse(localStorage.getItem('users') || '{"users":[]}');
    const users = usersData.users || [];
    
    const students = users.filter(u => u.role === 'student').length;
    const instructors = users.filter(u => u.role === 'instructor').length;
    const admins = users.filter(u => u.role === 'admin').length;
    
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
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            titleColor: '#fff',
            bodyColor: '#fff',
            callbacks: {
              label: function(context) {
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = ((context.parsed / total) * 100).toFixed(1);
                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
              }
            }
          }
        }
      }
    });
    
    // Create legend
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
  } catch(e) {
    console.error('Error creating user chart:', e);
  }
}

function createSimilarityChart() {
  const ctx = document.getElementById('similarityChart');
  
  try {
    const submissionsData = JSON.parse(localStorage.getItem('submissions') || '{"submissions":[]}');
    const submissions = submissionsData.submissions || [];
    
    const completed = submissions.filter(s => s.status === 'completed' && s.similarity !== null);
    
    const low = completed.filter(s => s.similarity <= 30).length;
    const medium = completed.filter(s => s.similarity > 30 && s.similarity <= 70).length;
    const high = completed.filter(s => s.similarity > 70).length;
    
    if (similarityChart) similarityChart.destroy();
    
    similarityChart = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Low (0-30%)', 'Medium (31-70%)', 'High (>70%)'],
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
          legend: {
            display: false
          },
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
    
    // Create legend
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
  } catch(e) {
    console.error('Error creating similarity chart:', e);
  }
}

function createCourseBarChart() {
  const ctx = document.getElementById('courseBarChart');
  
  try {
    const submissionsData = JSON.parse(localStorage.getItem('submissions') || '{"submissions":[]}');
    const coursesData = JSON.parse(localStorage.getItem('courses') || '{"courses":[]}');
    
    const submissions = submissionsData.submissions || [];
    const courses = coursesData.courses || [];
    
    const courseLabels = courses.map(c => c.code);
    const courseCounts = courses.map(course => {
      return submissions.filter(s => s.courseId === course.id).length;
    });
    
    if (courseChart) courseChart.destroy();
    
    courseChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: courseLabels,
        datasets: [{
          label: 'Submissions',
          data: courseCounts,
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
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            titleColor: '#fff',
            bodyColor: '#fff'
          }
        }
      }
    });
  } catch(e) {
    console.error('Error creating course chart:', e);
  }
}

function loadRecentSubmissions() {
  const container = document.getElementById('recentSubmissions');
  
  try {
    const submissionsData = JSON.parse(localStorage.getItem('submissions') || '{"submissions":[]}');
    const usersData = JSON.parse(localStorage.getItem('users') || '{"users":[]}');
    const coursesData = JSON.parse(localStorage.getItem('courses') || '{"courses":[]}');
    
    const submissions = submissionsData.submissions || [];
    const users = usersData.users || [];
    const courses = coursesData.courses || [];
    
    if (submissions.length === 0) {
      container.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>No submissions yet</p></div>';
      return;
    }
    
    const recent = submissions.slice(-5).reverse();
    
    container.innerHTML = recent.map(sub => {
      const student = users.find(u => u.id === sub.studentId);
      const course = courses.find(c => c.id === sub.courseId);
      
      const scoreClass = sub.similarity === null ? 'processing' 
        : sub.similarity <= 30 ? 'low'
        : sub.similarity <= 70 ? 'medium' : 'high';
      
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
  } catch(e) {
    container.innerHTML = '<div class="empty-state"><p>No data available</p></div>';
  }
}

// Refresh dashboard when window regains focus
window.addEventListener('focus', function() {
  updateDashboardStats();
  createCharts();
  loadRecentSubmissions();
});
</script>