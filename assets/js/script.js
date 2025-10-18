document.addEventListener('DOMContentLoaded', () => {
  // sidebar toggle (button)
  const sidebar = document.getElementById('sidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const main = document.getElementById('mainContent');

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
      if (sidebar.classList.contains('forced-open')) {
        sidebar.classList.remove('forced-open');
        sidebar.style.width = '';
        main.style.marginLeft = '';
      } else {
        sidebar.classList.add('forced-open');
        sidebar.style.width = '220px';
        main.style.marginLeft = '220px';
      }
    });
  }

  // filtering (client-side helper for pages that need it)
  const filterButtons = document.querySelectorAll('.filter-row .btn');
  if (filterButtons.length) {
    filterButtons.forEach(b => {
      b.addEventListener('click', function(e){
        filterButtons.forEach(x => x.classList.remove('active'));
        this.classList.add('active');
      });
    });
  }
});
