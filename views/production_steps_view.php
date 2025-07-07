<?php
/**
 * Production Steps View - Overview of all production steps
 * Shows comprehensive list of production steps across all rockets
 */

// Start session and check authentication
session_start();

// Include required files
require_once '../includes/user_functions.php';
require_once '../includes/db_connect.php';
require_once '../includes/production_functions.php';
require_once '../includes/rocket_functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login_view.php');
    exit;
}

// Get optional rocket filter
$rocket_id = (int) ($_GET['rocket_id'] ?? 0);
$search_query = trim($_GET['search'] ?? '');
$step_filter = trim($_GET['step_filter'] ?? '');

// Get all production steps with filtering
if ($rocket_id > 0) {
    $production_steps = getStepsByRocketId($pdo, $rocket_id);
    $page_title = "Production Steps for Rocket #$rocket_id";
} else {
    $production_steps = getAllProductionSteps($pdo);
    $page_title = "All Production Steps";
}

// Apply search and filters
if (!empty($search_query) || !empty($step_filter)) {
    $production_steps = array_filter($production_steps, function($step) use ($search_query, $step_filter) {
        $matches_search = empty($search_query) || 
            stripos($step['step_name'], $search_query) !== false ||
            stripos($step['staff_full_name'], $search_query) !== false ||
            stripos($step['rocket_serial'], $search_query) !== false;
            
        $matches_filter = empty($step_filter) || $step['step_name'] === $step_filter;
        
        return $matches_search && $matches_filter;
    });
}

// Get unique step types for filter dropdown
$all_steps = getAllProductionSteps($pdo);
$step_types = array_unique(array_column($all_steps, 'step_name'));
sort($step_types);

// Pagination
$page = (int) ($_GET['page'] ?? 1);
$per_page = 20;
$total_steps = count($production_steps);
$total_pages = ceil($total_steps / $per_page);
$offset = ($page - 1) * $per_page;
$paginated_steps = array_slice($production_steps, $offset, $per_page);

include '../includes/header.php';
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <div class="user-info">
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            <p>Role: <?php echo htmlspecialchars($_SESSION['role']); ?></p>
            <a href="../controllers/logout_controller.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <!-- Breadcrumb Navigation -->
    <div class="page-header">
        <div class="header-left">
            <h2>Production Steps Overview</h2>
            <p class="breadcrumb">
                <a href="../dashboard.php">Dashboard</a> → 
                <span>Production Steps</span>
            </p>
        </div>
        <div class="header-actions">
            <a href="../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="section">
        <div class="section-header">
            <h2>Search & Filter</h2>
        </div>
        
        <div class="section-content">
            <form method="GET" class="search-form">
                <?php if ($rocket_id > 0): ?>
                    <input type="hidden" name="rocket_id" value="<?php echo $rocket_id; ?>">
                <?php endif; ?>
                
                <div class="search-grid">
                    <div class="form-group">
                        <label for="search">Search Steps</label>
                        <input type="text" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search_query); ?>"
                               placeholder="Search by step name, staff, or rocket...">
                    </div>
                    
                    <div class="form-group">
                        <label for="step_filter">Step Type</label>
                        <select id="step_filter" name="step_filter">
                            <option value="">All Step Types</option>
                            <?php foreach ($step_types as $step_type): ?>
                                <option value="<?php echo htmlspecialchars($step_type); ?>"
                                        <?php echo ($step_filter === $step_type) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($step_type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="production_steps_view.php<?php echo $rocket_id > 0 ? '?rocket_id=' . $rocket_id : ''; ?>" 
                           class="btn btn-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="section">
        <div class="section-header">
            <h2>Statistics</h2>
        </div>
        
        <div class="section-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h4>Total Steps</h4>
                        <div class="stat-number"><?php echo $total_steps; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🚀</div>
                    <div class="stat-content">
                        <h4>Active Rockets</h4>
                        <div class="stat-number"><?php echo count(array_unique(array_column($production_steps, 'rocket_id'))); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h4>Staff Members</h4>
                        <div class="stat-number"><?php echo count(array_unique(array_column($production_steps, 'staff_id'))); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🔧</div>
                    <div class="stat-content">
                        <h4>Step Types</h4>
                        <div class="stat-number"><?php echo count($step_types); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Production Steps List -->
    <div class="section">
        <div class="section-header">
            <h2>Production Steps (<?php echo $total_steps; ?> total)</h2>
            <?php if ($total_pages > 1): ?>
                <div class="pagination-info">
                    Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="section-content">
            <?php if (empty($paginated_steps)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>No Production Steps Found</h3>
                    <?php if (!empty($search_query) || !empty($step_filter)): ?>
                        <p>No production steps match your current search criteria.</p>
                        <a href="production_steps_view.php<?php echo $rocket_id > 0 ? '?rocket_id=' . $rocket_id : ''; ?>" 
                           class="btn btn-primary">Clear Filters</a>
                    <?php else: ?>
                        <p>No production steps have been recorded yet.</p>
                        <a href="../dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="steps-table">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Step ID</th>
                                <th>Rocket</th>
                                <th>Step Name</th>
                                <th>Staff Member</th>
                                <th>Timestamp</th>
                                <th>Data</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paginated_steps as $step): ?>
                                <tr>
                                    <td class="step-id">#<?php echo htmlspecialchars($step['step_id']); ?></td>
                                    <td class="rocket-info">
                                        <div class="rocket-details">
                                            <strong><?php echo htmlspecialchars($step['rocket_serial'] ?? 'Unknown'); ?></strong>
                                            <?php if (!empty($step['rocket_project'])): ?>
                                                <small><?php echo htmlspecialchars($step['rocket_project']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="step-name">
                                        <span class="step-badge step-<?php echo strtolower(str_replace(' ', '-', $step['step_name'])); ?>">
                                            <?php echo htmlspecialchars($step['step_name']); ?>
                                        </span>
                                    </td>
                                    <td class="staff-info">
                                        <div class="staff-details">
                                            <strong><?php echo htmlspecialchars($step['staff_full_name']); ?></strong>
                                            <small>@<?php echo htmlspecialchars($step['staff_username']); ?></small>
                                        </div>
                                    </td>
                                    <td class="timestamp">
                                        <div class="time-info">
                                            <strong><?php echo date('M j, Y', strtotime($step['step_timestamp'])); ?></strong>
                                            <small><?php echo date('g:i A', strtotime($step['step_timestamp'])); ?></small>
                                        </div>
                                    </td>
                                    <td class="data-info">
                                        <?php 
                                        $data = json_decode($step['data_json'], true);
                                        if ($data && is_array($data) && count($data) > 0): 
                                        ?>
                                            <button class="btn btn-small btn-info" onclick="showStepData(<?php echo $step['step_id']; ?>)">
                                                View Data (<?php echo count($data); ?> fields)
                                            </button>
                                        <?php else: ?>
                                            <span class="no-data">No additional data</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <div class="action-buttons">
                                            <a href="rocket_detail_view.php?id=<?php echo $step['rocket_id']; ?>" 
                                               class="btn btn-small btn-secondary" title="View Rocket">
                                                🚀
                                            </a>
                                            <?php if (has_role('admin') || has_role('engineer')): ?>
                                                <button class="btn btn-small btn-warning" 
                                                        onclick="editStep(<?php echo $step['step_id']; ?>)" title="Edit Step">
                                                    ✏️
                                                </button>
                                            <?php endif; ?>
                                            <?php if (has_role('admin')): ?>
                                                <button class="btn btn-small btn-danger" 
                                                        onclick="deleteStep(<?php echo $step['step_id']; ?>)" title="Delete Step">
                                                    🗑️
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        $base_url = "production_steps_view.php?";
                        $params = [];
                        if ($rocket_id > 0) $params[] = "rocket_id=$rocket_id";
                        if (!empty($search_query)) $params[] = "search=" . urlencode($search_query);
                        if (!empty($step_filter)) $params[] = "step_filter=" . urlencode($step_filter);
                        $base_url .= implode('&', $params);
                        if (!empty($params)) $base_url .= '&';
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <a href="<?php echo $base_url; ?>page=<?php echo $page - 1; ?>" class="btn btn-pagination">← Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="<?php echo $base_url; ?>page=<?php echo $i; ?>" 
                               class="btn btn-pagination <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="<?php echo $base_url; ?>page=<?php echo $page + 1; ?>" class="btn btn-pagination">Next →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Step Data Modal -->
<div id="stepDataModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Production Step Data</h3>
            <button class="modal-close" onclick="closeStepDataModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="stepDataContent">
                <!-- Content will be loaded via JavaScript -->
            </div>
        </div>
    </div>
</div>

<style>
/* Production Steps View Styles */
.search-form {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 12px;
    border: 1px solid #dee2e6;
}

.search-grid {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 20px;
    align-items: end;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    border: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-icon {
    font-size: 32px;
    opacity: 0.8;
}

.stat-content h4 {
    margin: 0 0 5px 0;
    color: #6c757d;
    font-size: 14px;
    font-weight: 600;
}

.stat-number {
    font-size: 24px;
    font-weight: 700;
    color: #007bff;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.data-table th {
    background: #007bff;
    color: white;
    padding: 15px 10px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
}

.data-table td {
    padding: 12px 10px;
    border-bottom: 1px solid #e9ecef;
    vertical-align: top;
}

.data-table tr:hover {
    background: #f8f9fa;
}

.step-id {
    font-family: monospace;
    font-weight: 600;
    color: #6c757d;
}

.rocket-details strong {
    display: block;
    color: #007bff;
    font-weight: 600;
}

.rocket-details small {
    color: #6c757d;
    font-size: 12px;
}

.step-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    background: #17a2b8;
    color: white;
}

.staff-details strong {
    display: block;
    color: #212529;
}

.staff-details small {
    color: #6c757d;
    font-size: 12px;
}

.time-info strong {
    display: block;
    color: #212529;
}

.time-info small {
    color: #6c757d;
    font-size: 12px;
}

.no-data {
    color: #6c757d;
    font-style: italic;
    font-size: 12px;
}

.action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 20px;
}

.btn-pagination {
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    background: white;
    color: #007bff;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
}

.btn-pagination:hover {
    background: #e9ecef;
}

.btn-pagination.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.pagination-info {
    color: #6c757d;
    font-size: 14px;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 12px;
    max-width: 600px;
    width: 90%;
    max-height: 80%;
    overflow: hidden;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #dee2e6;
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6c757d;
}

.modal-body {
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.empty-state h3 {
    margin: 0 0 10px 0;
    color: #495057;
}

@media (max-width: 768px) {
    .search-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .data-table {
        font-size: 14px;
    }
    
    .data-table th,
    .data-table td {
        padding: 8px 5px;
    }
}
</style>

<script>
// Step data modal functions
function showStepData(stepId) {
    // Get step data via AJAX or from embedded data
    // For now, we'll use a simple approach
    const modal = document.getElementById('stepDataModal');
    const content = document.getElementById('stepDataContent');
    
    // Find the step data from the page (this is a simplified approach)
    content.innerHTML = '<div class="loading">Loading step data...</div>';
    modal.style.display = 'flex';
    
    // In a real implementation, you'd make an AJAX call here
    setTimeout(() => {
        content.innerHTML = `
            <div class="step-data-display">
                <p><strong>Step ID:</strong> ${stepId}</p>
                <p>Detailed step data would be loaded here via AJAX call to get the JSON data.</p>
                <p><em>Implementation note: This would typically fetch data from the server.</em></p>
            </div>
        `;
    }, 500);
}

function closeStepDataModal() {
    document.getElementById('stepDataModal').style.display = 'none';
}

function editStep(stepId) {
    // Redirect to edit step view (when implemented)
    alert(`Edit step ${stepId} - Feature to be implemented`);
}

function deleteStep(stepId) {
    if (confirm('Are you sure you want to delete this production step? This action cannot be undone.')) {
        // Submit delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../controllers/production_controller.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_step';
        
        const stepInput = document.createElement('input');
        stepInput.type = 'hidden';
        stepInput.name = 'step_id';
        stepInput.value = stepId;
        
        form.appendChild(actionInput);
        form.appendChild(stepInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
document.getElementById('stepDataModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeStepDataModal();
    }
});

// Close modal with escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeStepDataModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?>
