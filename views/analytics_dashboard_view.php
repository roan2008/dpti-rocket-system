<?php
/**
 * Analytics Dashboard View
 * Comprehensive system-wide analytics and reporting interface
 */

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/user_functions.php';

// Access Control: Only engineers and admins can access analytics
if (!isset($_SESSION['user_id']) || !is_logged_in()) {
    header('Location: login_view.php');
    exit;
}

if (!has_role('admin') && !has_role('engineer')) {
    header('Location: ../dashboard.php?error=insufficient_permissions');
    exit;
}

// Get analytics data
$analytics = getSystemWideAnalytics($pdo);

include '../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-section">
                <h1>📊 Analytics Dashboard</h1>
                <p class="page-description">Comprehensive system-wide analytics and performance metrics</p>
            </div>
            <div class="page-actions">
                <button onclick="refreshAnalytics()" class="btn btn-secondary">
                    🔄 Refresh Data
                </button>
                <button onclick="exportReport()" class="btn btn-primary">
                    📄 Export Report
                </button>
            </div>
        </div>
    </div>

    <!-- Analytics Cards Summary -->
    <div class="analytics-summary">
        <div class="summary-card">
            <div class="card-icon">🚀</div>
            <div class="card-content">
                <h3><?php echo number_format($analytics['summary']['total_rockets']); ?></h3>
                <p>Total Rockets</p>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="card-icon">⚙️</div>
            <div class="card-content">
                <h3><?php echo number_format($analytics['summary']['total_steps']); ?></h3>
                <p>Production Steps</p>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="card-icon">⏳</div>
            <div class="card-content">
                <h3><?php echo number_format($analytics['summary']['pending_approvals']); ?></h3>
                <p>Pending Approvals</p>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="card-icon">👥</div>
            <div class="card-content">
                <h3><?php echo number_format($analytics['summary']['active_users']); ?></h3>
                <p>Active Users</p>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="card-icon">✅</div>
            <div class="card-content">
                <h3><?php echo $analytics['summary']['completion_rate']; ?>%</h3>
                <p>Completion Rate</p>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="card-icon">👍</div>
            <div class="card-content">
                <h3><?php echo $analytics['summary']['approval_rate']; ?>%</h3>
                <p>Approval Rate</p>
            </div>
        </div>
    </div>

    <!-- Main Analytics Grid -->
    <div class="analytics-grid">
        <!-- Rocket Statistics -->
        <div class="analytics-panel">
            <div class="panel-header">
                <h2>🚀 Rocket Statistics</h2>
                <span class="panel-subtitle">Production overview</span>
            </div>
            <div class="panel-content">
                <!-- Rocket Status Pie Chart -->
                <div class="chart-container">
                    <canvas id="rocketStatusChart" width="400" height="200"></canvas>
                </div>
                
                <!-- Rocket Status Table -->
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics['rockets']['by_status'] as $status): ?>
                                <?php $percentage = $analytics['rockets']['total'] > 0 ? round(($status['count'] / $analytics['rockets']['total']) * 100, 1) : 0; ?>
                                <tr>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($status['current_status']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($status['current_status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($status['count']); ?></td>
                                    <td><?php echo $percentage; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Production Step Analytics -->
        <div class="analytics-panel">
            <div class="panel-header">
                <h2>⚙️ Production Analytics</h2>
                <span class="panel-subtitle">Step completion & performance</span>
            </div>
            <div class="panel-content">
                <!-- Production Step Bar Chart -->
                <div class="chart-container">
                    <canvas id="productionStepsChart" width="400" height="200"></canvas>
                </div>
                
                <!-- Key Metrics -->
                <div class="metrics-grid">
                    <div class="metric-item">
                        <label>Average Time per Step:</label>
                        <value><?php echo $analytics['production_steps']['average_time_per_step']; ?> hours</value>
                    </div>
                    <div class="metric-item">
                        <label>Completion Rate:</label>
                        <value><?php echo $analytics['production_steps']['completion_rate']; ?>%</value>
                    </div>
                    <div class="metric-item">
                        <label>Total Steps:</label>
                        <value><?php echo number_format($analytics['production_steps']['total']); ?></value>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Analytics -->
        <div class="analytics-panel">
            <div class="panel-header">
                <h2>👍 Approval Analytics</h2>
                <span class="panel-subtitle">Quality control metrics</span>
            </div>
            <div class="panel-content">
                <!-- Approval Trend Line Chart -->
                <div class="chart-container">
                    <canvas id="approvalTrendChart" width="400" height="200"></canvas>
                </div>
                
                <!-- Top Engineers -->
                <div class="top-performers">
                    <h4>Top Approving Engineers</h4>
                    <div class="performers-list">
                        <?php foreach ($analytics['approvals']['approvals_by_engineer'] as $index => $engineer): ?>
                            <div class="performer-item">
                                <span class="rank">#<?php echo $index + 1; ?></span>
                                <span class="name"><?php echo htmlspecialchars($engineer['engineer_name']); ?></span>
                                <span class="count"><?php echo $engineer['approvals_count']; ?> approvals</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Activity -->
        <div class="analytics-panel">
            <div class="panel-header">
                <h2>👥 User Activity</h2>
                <span class="panel-subtitle">Team performance</span>
            </div>
            <div class="panel-content">
                <!-- Users by Role Doughnut Chart -->
                <div class="chart-container">
                    <canvas id="userRoleChart" width="400" height="200"></canvas>
                </div>
                
                <!-- Most Active Staff -->
                <div class="top-performers">
                    <h4>Most Active Staff</h4>
                    <div class="performers-list">
                        <?php foreach (array_slice($analytics['users']['most_active_staff'], 0, 5) as $index => $staff): ?>
                            <div class="performer-item">
                                <span class="rank">#<?php echo $index + 1; ?></span>
                                <span class="name">
                                    <?php echo htmlspecialchars($staff['staff_name']); ?>
                                    <small>(<?php echo ucfirst($staff['role']); ?>)</small>
                                </span>
                                <span class="count"><?php echo $staff['steps_created']; ?> steps</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Productivity -->
        <div class="analytics-panel full-width">
            <div class="panel-header">
                <h2>📈 Daily Productivity Trend</h2>
                <span class="panel-subtitle">Last 14 days performance</span>
            </div>
            <div class="panel-content">
                <div class="chart-container">
                    <canvas id="dailyProductivityChart" width="800" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- System Health -->
        <div class="analytics-panel">
            <div class="panel-header">
                <h2>🖥️ System Health</h2>
                <span class="panel-subtitle">Performance metrics</span>
            </div>
            <div class="panel-content">
                <div class="health-metrics">
                    <div class="health-item">
                        <label>Database Records:</label>
                        <div class="health-breakdown">
                            <?php foreach ($analytics['system_health']['database_size'] as $table): ?>
                                <div class="table-stat">
                                    <span class="table-name"><?php echo ucfirst($table['table_name']); ?></span>
                                    <span class="record-count"><?php echo number_format($table['record_count']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="health-item">
                        <label>Performance:</label>
                        <value>
                            Avg Query Time: <?php echo $analytics['system_health']['performance_metrics']['avg_query_time']; ?>ms<br>
                            Uptime: <?php echo $analytics['system_health']['performance_metrics']['uptime_percentage']; ?>%
                        </value>
                    </div>
                    
                    <div class="health-item">
                        <label>Last Backup:</label>
                        <value><?php echo date('M j, Y g:i A', strtotime($analytics['system_health']['performance_metrics']['last_backup'])); ?></value>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Prepare data for charts
const analyticsData = <?php echo json_encode($analytics); ?>;

// Color schemes for charts
const colors = {
    primary: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d', '#17a2b8'],
    gradients: {
        blue: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        green: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        orange: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'
    }
};

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
});

function initializeCharts() {
    // 1. Rocket Status Pie Chart
    if (analyticsData.rockets.by_status.length > 0) {
        const rocketCtx = document.getElementById('rocketStatusChart').getContext('2d');
        new Chart(rocketCtx, {
            type: 'pie',
            data: {
                labels: analyticsData.rockets.by_status.map(item => item.current_status.charAt(0).toUpperCase() + item.current_status.slice(1)),
                datasets: [{
                    data: analyticsData.rockets.by_status.map(item => item.count),
                    backgroundColor: colors.primary,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Rockets by Status'
                    }
                }
            }
        });
    }

    // 2. Production Steps Bar Chart
    if (analyticsData.production_steps.by_step_type.length > 0) {
        const stepsCtx = document.getElementById('productionStepsChart').getContext('2d');
        new Chart(stepsCtx, {
            type: 'bar',
            data: {
                labels: analyticsData.production_steps.by_step_type.map(item => item.step_name),
                datasets: [{
                    label: 'Step Count',
                    data: analyticsData.production_steps.by_step_type.map(item => item.count),
                    backgroundColor: colors.primary[0],
                    borderColor: colors.primary[0],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Production Steps by Type'
                    }
                }
            }
        });
    }

    // 3. Approval Trend Line Chart
    if (analyticsData.approvals.approval_trend.length > 0) {
        const approvalCtx = document.getElementById('approvalTrendChart').getContext('2d');
        new Chart(approvalCtx, {
            type: 'line',
            data: {
                labels: analyticsData.approvals.approval_trend.map(item => item.date),
                datasets: [
                    {
                        label: 'Approved',
                        data: analyticsData.approvals.approval_trend.map(item => item.approved || 0),
                        borderColor: colors.primary[1],
                        backgroundColor: colors.primary[1] + '20',
                        tension: 0.4
                    },
                    {
                        label: 'Rejected',
                        data: analyticsData.approvals.approval_trend.map(item => item.rejected || 0),
                        borderColor: colors.primary[3],
                        backgroundColor: colors.primary[3] + '20',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Approval Trend (Last 7 Days)'
                    }
                }
            }
        });
    }

    // 4. User Role Doughnut Chart
    if (analyticsData.users.by_role.length > 0) {
        const userCtx = document.getElementById('userRoleChart').getContext('2d');
        new Chart(userCtx, {
            type: 'doughnut',
            data: {
                labels: analyticsData.users.by_role.map(item => item.role.charAt(0).toUpperCase() + item.role.slice(1)),
                datasets: [{
                    data: analyticsData.users.by_role.map(item => item.count),
                    backgroundColor: colors.primary,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Users by Role'
                    }
                }
            }
        });
    }

    // 5. Daily Productivity Chart
    if (analyticsData.production_steps.daily_productivity.length > 0) {
        const productivityCtx = document.getElementById('dailyProductivityChart').getContext('2d');
        new Chart(productivityCtx, {
            type: 'line',
            data: {
                labels: analyticsData.production_steps.daily_productivity.map(item => item.date),
                datasets: [
                    {
                        label: 'Steps Started',
                        data: analyticsData.production_steps.daily_productivity.map(item => item.steps_started),
                        borderColor: colors.primary[0],
                        backgroundColor: colors.primary[0] + '20',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Steps Completed',
                        data: analyticsData.production_steps.daily_productivity.map(item => item.steps_completed),
                        borderColor: colors.primary[1],
                        backgroundColor: colors.primary[1] + '20',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Daily Productivity (Last 14 Days)'
                    }
                }
            }
        });
    }
}

// Utility functions
function refreshAnalytics() {
    window.location.reload();
}

function exportReport() {
    // Simple CSV export functionality
    const csvContent = generateCSVReport();
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'analytics_report_' + new Date().toISOString().split('T')[0] + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function generateCSVReport() {
    let csv = 'Analytics Report - Generated on ' + new Date().toLocaleString() + '\n\n';
    
    // Summary
    csv += 'SUMMARY METRICS\n';
    csv += 'Metric,Value\n';
    csv += 'Total Rockets,' + analyticsData.summary.total_rockets + '\n';
    csv += 'Total Production Steps,' + analyticsData.summary.total_steps + '\n';
    csv += 'Pending Approvals,' + analyticsData.summary.pending_approvals + '\n';
    csv += 'Active Users,' + analyticsData.summary.active_users + '\n';
    csv += 'Completion Rate,' + analyticsData.summary.completion_rate + '%\n';
    csv += 'Approval Rate,' + analyticsData.summary.approval_rate + '%\n\n';
    
    // Rocket status breakdown
    csv += 'ROCKET STATUS BREAKDOWN\n';
    csv += 'Status,Count\n';
    analyticsData.rockets.by_status.forEach(item => {
        csv += item.current_status + ',' + item.count + '\n';
    });
    
    return csv;
}
</script>

<!-- Analytics Dashboard Specific Styling -->
<style>
/* Analytics Summary Cards */
.analytics-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.summary-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.summary-card:hover {
    transform: translateY(-2px);
}

.summary-card .card-icon {
    font-size: 2rem;
    opacity: 0.8;
}

.summary-card .card-content h3 {
    margin: 0;
    font-size: 2rem;
    font-weight: bold;
}

.summary-card .card-content p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9rem;
}

/* Analytics Grid */
.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
}

.analytics-panel {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.analytics-panel.full-width {
    grid-column: 1 / -1;
}

.panel-header {
    background: #f8f9fa;
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.panel-header h2 {
    margin: 0;
    font-size: 1.25rem;
    color: #495057;
}

.panel-subtitle {
    color: #6c757d;
    font-size: 0.875rem;
}

.panel-content {
    padding: 1.5rem;
}

/* Chart Containers */
.chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 1rem;
}

/* Data Tables */
.data-table {
    margin-top: 1rem;
}

.data-table table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.data-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

/* Status Badges */
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.status-active {
    background-color: #d4edda;
    color: #155724;
}

.status-badge.status-completed {
    background-color: #cce5f0;
    color: #004085;
}

.status-badge.status-maintenance {
    background-color: #fff3cd;
    color: #856404;
}

.status-badge.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}

/* Metrics Grid */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.metric-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.metric-item label {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 600;
}

.metric-item value {
    font-size: 1.125rem;
    font-weight: bold;
    color: #495057;
}

/* Top Performers */
.top-performers {
    margin-top: 1rem;
}

.top-performers h4 {
    margin: 0 0 1rem 0;
    color: #495057;
}

.performers-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.performer-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 0.5rem;
}

.performer-item .rank {
    background: #007bff;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: bold;
}

.performer-item .name {
    flex: 1;
    font-weight: 500;
}

.performer-item .name small {
    color: #6c757d;
}

.performer-item .count {
    font-size: 0.875rem;
    color: #6c757d;
}

/* Health Metrics */
.health-metrics {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.health-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.health-item label {
    font-weight: 600;
    color: #495057;
}

.health-breakdown {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.table-stat {
    display: flex;
    justify-content: space-between;
    padding: 0.25rem 0;
    border-bottom: 1px solid #e9ecef;
}

.table-name {
    color: #6c757d;
}

.record-count {
    font-weight: 500;
}

/* Responsive Design */
@media (max-width: 768px) {
    .analytics-summary {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .analytics-grid {
        grid-template-columns: 1fr;
    }
    
    .summary-card {
        flex-direction: column;
        text-align: center;
    }
    
    .chart-container {
        height: 250px;
    }
}

@media (max-width: 480px) {
    .analytics-summary {
        grid-template-columns: 1fr;
    }
    
    .metrics-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
