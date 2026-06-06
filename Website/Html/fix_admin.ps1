$path = "c:\xampp\htdocs\Searchar\Website\Html\Admin.html"
$content = Get-Content $path -Raw
$insertion = @"
    <div id="sub-admins" class="main-section">
      <h2>Sub-Admins & Logs</h2>
      <div class="sub-admin-grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
        <div class="setting-section">
          <h4>Add New Sub-Admin</h4>
          <form id="add-sub-admin-form" style="display:flex; flex-direction:column; gap:10px;">
            <input type="text" id="sub-admin-name" placeholder="Full Name" required class="ai-input-full" style="padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            <input type="email" id="sub-admin-email" placeholder="Email" required class="ai-input-full" style="padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            <input type="text" id="sub-admin-mobile" placeholder="Mobile" required class="ai-input-full" style="padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            <input type="password" id="sub-admin-password" placeholder="Password" required class="ai-input-full" style="padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            <button type="submit" class="approve-btn" style="margin-top: 10px; padding: 10px; border-radius: 4px; cursor: pointer; border: none; background: #007bff; color: #fff;">Add Sub-Admin</button>
          </form>
        </div>

        <div class="setting-section">
          <h4>Existing Sub-Admins</h4>
          <table class="styled-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Added On</th>
              </tr>
            </thead>
            <tbody id="sub-admins-table-body">
              <tr><td colspan="4">Loading sub-admins...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="setting-section" style="margin-top: 20px;">
        <h4>Admin Activity Logs</h4>
        <table class="styled-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Admin</th>
              <th>Action</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody id="admin-logs-table-body">
            <tr><td colspan="4">Loading logs...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  <script>
"@

$newContent = $content -replace '<script>', $insertion
Set-Content -Path $path -Value $newContent
