<section class="settings">
  <h2>System Settings ⚙️</h2>

  <div id="settingsNotification" class="notice" style="display:none;"></div>

  <form id="settingsForm" onsubmit="saveSettings(event)" class="settings-form">
    <label>Max Upload File Size (MB)</label>
    <input type="number" id="maxUploadSize" name="upload_limit" min="1" max="1000" value="10" required>

    <label>Plagiarism Threshold (%)</label>
    <input type="number" id="plagiarismThreshold" min="10" max="90" value="50" required>
    <small style="color:#a7b7d6;">Alert when similarity score exceeds this percentage</small>

    <label>Monthly Submission Quota</label>
    <input type="number" id="submissionQuota" min="5" max="100" value="20" required>
    <small style="color:#a7b7d6;">Maximum submissions per student per month</small>

    <button type="submit" class="btn primary">💾 Save Settings</button>
    <button type="button" class="btn danger" onclick="resetSettings()">🔄 Reset to Defaults</button>
  </form>


</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
  loadSettings();
});

function loadSettings() {
  // Try to load from localStorage
  const saved = localStorage.getItem('systemSettings');
  if (saved) {
    const settings = JSON.parse(saved);
    document.getElementById('maxUploadSize').value = settings.maxUploadSize || 10;
    document.getElementById('plagiarismThreshold').value = settings.plagiarismThreshold || 50;
    document.getElementById('submissionQuota').value = settings.submissionQuota || 20;
  }
}

function saveSettings(event) {
  event.preventDefault();

  const maxUpload = parseInt(document.getElementById('maxUploadSize').value);
  const threshold = parseInt(document.getElementById('plagiarismThreshold').value);
  const quota = parseInt(document.getElementById('submissionQuota').value);

  if (threshold < 10 || threshold > 90) {
    showSettingsNotification('⚠️ Plagiarism threshold must be between 10-90%', 'error');
    return;
  }

  const settings = {
    maxUploadSize: maxUpload,
    plagiarismThreshold: threshold,
    submissionQuota: quota
  };
  
  localStorage.setItem('systemSettings', JSON.stringify(settings));
  
  showSettingsNotification('✅ Settings saved successfully!', 'success');
}

function resetSettings() {
  if (!confirm('Reset settings to default values?')) return;
  
  document.getElementById('maxUploadSize').value = 10;
  document.getElementById('plagiarismThreshold').value = 50;
  document.getElementById('submissionQuota').value = 20;
  
  localStorage.removeItem('systemSettings');
  showSettingsNotification('✅ Settings reset to defaults!', 'success');
}

function showSettingsNotification(message, type) {
  const notification = document.getElementById('settingsNotification');
  notification.textContent = message;
  notification.className = 'notice ' + type;
  notification.style.display = 'block';

  setTimeout(() => {
    notification.style.display = 'none';
  }, 3000);
}
</script>