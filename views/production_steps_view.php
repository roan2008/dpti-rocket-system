<?php
/**
 * Production Steps View - Overview of all production steps
 * Shows comprehensive list of production steps across all rockets
 */

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

<div class="container">
    <!-- GOLDEN RULE #2: Consistent Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-section">
                <h1>Production Steps Overview</h1>
                <p class="page-description">Monitor and track production progress across all rockets</p>
            </div>
            <div class="page-actions">
                <a href="../dashboard.php" class="btn btn-secondary">
                    <span>←</span> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- GOLDEN RULE #1: Single Home for Global Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $total_steps; ?></div>
                <div class="stat-label">Total Steps</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🚀</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo count(array_unique(array_column($production_steps, 'rocket_id'))); ?></div>
                <div class="stat-label">Active Rockets</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo count(array_unique(array_column($production_steps, 'staff_id'))); ?></div>
                <div class="stat-label">Staff Members</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🔧</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo count($step_types); ?></div>
                <div class="stat-label">Step Types</div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="content-card">
        <div class="card-header">
            <h2>Search & Filter</h2>
        </div>
        
        <div class="card-content">
            <form method="GET" class="filter-form">
                <?php if ($rocket_id > 0): ?>
                    <input type="hidden" name="rocket_id" value="<?php echo $rocket_id; ?>">
                <?php endif; ?>
                
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="search">Search Steps</label>
                        <input type="text" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search_query); ?>"
                               placeholder="Search by step name, staff, or rocket..."
                               class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="step_filter">Step Type</label>
                        <select id="step_filter" name="step_filter" class="form-control">
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

    <!-- Production Steps List -->
    <div class="content-card">
        <div class="card-header">
            <h2>Production Steps</h2>
            <div class="card-actions">
                <span class="card-subtitle"><?php echo $total_steps; ?> total steps</span>
                <?php if ($total_pages > 1): ?>
                    <span class="pagination-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (empty($paginated_steps)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
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
            <div class="table-responsive">
                <table class="table-modern">
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
                                <td class="step-id">
                                    <span class="font-mono font-semibold">#<?php echo htmlspecialchars($step['step_id']); ?></span>
                                </td>
                                <td class="rocket-info">
                                    <div class="rocket-details">
                                        <span class="font-medium"><?php echo htmlspecialchars($step['rocket_serial'] ?? 'Unknown'); ?></span>
                                        <?php if (!empty($step['rocket_project'])): ?>
                                            <small class="text-gray-600"><?php echo htmlspecialchars($step['rocket_project']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="step-name">
                                    <span class="status-badge-modern status-info">
                                        <?php echo htmlspecialchars($step['step_name']); ?>
                                    </span>
                                </td>
                                <td class="staff-info">
                                    <div class="staff-details">
                                        <span class="font-medium"><?php echo htmlspecialchars($step['staff_full_name']); ?></span>
                                        <small class="text-gray-600">@<?php echo htmlspecialchars($step['staff_username']); ?></small>
                                    </div>
                                </td>
                                <td class="timestamp">
                                    <div class="time-info">
                                        <span class="font-medium"><?php echo date('M j, Y', strtotime($step['step_timestamp'])); ?></span>
                                        <small class="text-gray-600"><?php echo date('g:i A', strtotime($step['step_timestamp'])); ?></small>
                                    </div>
                                </td>
                                <td class="data-info">
                                    <?php 
                                    $data = json_decode($step['data_json'], true);
                                    if ($data && is_array($data) && count($data) > 0): 
                                    ?>
                                        <button class="btn btn-sm btn-secondary" onclick="showStepData(<?php echo $step['step_id']; ?>)">
                                            View Data (<?php echo count($data); ?> fields)
                                        </button>
                                    <?php else: ?>
                                        <span class="text-gray-600 text-sm">No additional data</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <div class="action-buttons">
                                        <a href="rocket_detail_view.php?id=<?php echo $step['rocket_id']; ?>" 
                                           class="btn btn-sm btn-secondary" title="View Rocket">
                                            View
                                        </a>
                                        <?php if (has_role('admin') || has_role('engineer')): ?>
                                            <button class="btn btn-sm btn-secondary" 
                                                    onclick="editStep(<?php echo $step['step_id']; ?>)" title="Edit Step">
                                                Edit
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
                        <a href="<?php echo $base_url; ?>page=<?php echo $page - 1; ?>" class="btn btn-sm btn-secondary">← Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="<?php echo $base_url; ?>page=<?php echo $i; ?>" 
                           class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo $base_url; ?>page=<?php echo $page + 1; ?>" class="btn btn-sm btn-secondary">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
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
/* Additional styles for production steps page */
.filter-form {
    padding: 0;
}

.filter-grid {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 20px;
    align-items: end;
}

.rocket-details span {
    display: block;
}

.rocket-details small {
    color: #6c757d;
    font-size: 12px;
}

.staff-details span {
    display: block;
}

.staff-details small {
    color: #6c757d;
    font-size: 12px;
}

.time-info span {
    display: block;
}

.time-info small {
    color: #6c757d;
    font-size: 12px;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 20px;
    padding: 20px 0;
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
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #dee2e6;
    background: #f8f9fa;
}

.modal-header h3 {
    margin: 0;
    color: #495057;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6c757d;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    color: #495057;
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
    max-height: calc(80vh - 80px);
}

/* Step Data Display Styling */
.step-data-display {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.step-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.step-header h4 {
    margin: 0;
    color: #495057;
    font-size: 1.25rem;
}

.step-badge {
    background: #007bff;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.step-meta {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.meta-item {
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.meta-item:last-child {
    margin-bottom: 0;
}

.meta-item strong {
    color: #495057;
    font-weight: 600;
}

.step-data-section h5 {
    color: #495057;
    margin: 0 0 15px 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.data-fields {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    overflow: hidden;
}

.data-field {
    display: flex;
    padding: 12px 15px;
    border-bottom: 1px solid #f1f3f4;
    align-items: flex-start;
}

.data-field:last-child {
    border-bottom: none;
}

.data-field.error {
    background: #fff5f5;
    border-left: 4px solid #dc3545;
}

.field-label {
    font-weight: 600;
    color: #495057;
    min-width: 120px;
    margin-right: 15px;
    flex-shrink: 0;
}

.field-value {
    color: #6c757d;
    word-break: break-word;
    flex: 1;
}

.raw-data {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    color: #495057;
    white-space: pre-wrap;
    word-break: break-all;
    margin: 0;
}

.no-data {
    text-align: center;
    padding: 30px;
    color: #6c757d;
    font-style: italic;
}

.loading {
    text-align: center;
    padding: 40px;
    color: #6c757d;
    font-size: 1rem;
}

.error-display {
    text-align: center;
    padding: 30px;
}

.error-display h4 {
    color: #dc3545;
    margin-bottom: 10px;
}

.error-display p {
    color: #6c757d;
    margin-bottom: 20px;
}
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
}

@media (max-width: 768px) {
    .filter-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
// Step data modal functions
function showStepData(stepId) {
    const modal = document.getElementById('stepDataModal');
    const content = document.getElementById('stepDataContent');
    
    // Show loading state
    content.innerHTML = '<div class="loading">Loading step data...</div>';
    modal.style.display = 'flex';
    
    // Make AJAX call to get step data
    fetch(`../controllers/step_ajax.php?action=get_step_data&step_id=${stepId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Build the step data display
                let html = `
                    <div class="step-data-display">
                        <div class="step-header">
                            <h4>Production Step #${data.step_info.step_id}</h4>
                            <span class="step-badge">${data.step_info.step_name}</span>
                        </div>
                        
                        <div class="step-meta">
                            <div class="meta-item">
                                <strong>Rocket:</strong> ${data.step_info.rocket_serial} (ID: ${data.step_info.rocket_id})
                            </div>
                            <div class="meta-item">
                                <strong>Recorded by:</strong> ${data.step_info.staff_name}
                            </div>
                            <div class="meta-item">
                                <strong>Timestamp:</strong> ${data.step_info.formatted_timestamp}
                            </div>
                        </div>
                        
                        <div class="step-data-section">
                            <h5>Step Data (${data.field_count} fields)</h5>
                            <div class="data-fields">
                `;
                
                // Display the data fields
                if (data.data_fields && typeof data.data_fields === 'object') {
                    for (const [key, value] of Object.entries(data.data_fields)) {
                        if (key !== 'parse_error') {
                            html += `
                                <div class="data-field">
                                    <span class="field-label">${key}:</span>
                                    <span class="field-value">${value}</span>
                                </div>
                            `;
                        }
                    }
                    
                    // Show parse error if exists
                    if (data.data_fields.parse_error) {
                        html += `
                            <div class="data-field error">
                                <span class="field-label">Parse Error:</span>
                                <span class="field-value">${data.data_fields.parse_error}</span>
                            </div>
                            <div class="data-field">
                                <span class="field-label">Raw Data:</span>
                                <pre class="raw-data">${data.data_fields.raw_data}</pre>
                            </div>
                        `;
                    }
                } else {
                    html += '<div class="no-data">No additional data available</div>';
                }
                
                html += `
                            </div>
                        </div>
                    </div>
                `;
                
                content.innerHTML = html;
            } else {
                throw new Error(data.error || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Error loading step data:', error);
            content.innerHTML = `
                <div class="error-display">
                    <h4>Error Loading Step Data</h4>
                    <p>Unable to load step data: ${error.message}</p>
                    <button onclick="showStepData(${stepId})" class="btn btn-sm btn-primary">Retry</button>
                </div>
            `;
        });
}

function closeStepDataModal() {
    document.getElementById('stepDataModal').style.display = 'none';
}

function editStep(stepId) {
    // Redirect to edit step view
    window.location.href = `step_edit_view.php?id=${stepId}`;
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
