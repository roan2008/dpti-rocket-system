<?php
/**
 * Motor Charging Report View
 * Professional HTML table layout for Motor Charging Report
 * Designed for printing and official documentation
 */

// Security check - this file should only be accessed through the controller
if (!isset($report_data) || !is_array($report_data)) {
    header('Location: ../dashboard.php?error=direct_access_denied');
    exit;
}

// Extract data for easier access
$rocket = $report_data['rocket_info'];
$steps = $report_data['production_steps'];
$metadata = $report_data['report_metadata'];
$final_approver = $report_data['final_approver'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motor Charging Report - <?php echo htmlspecialchars($rocket['serial_number']); ?></title>
    
    <!-- Include main CSS for screen viewing -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/search-filters.css">
    <link rel="stylesheet" href="../assets/css/motor-report-print.css">
    
    <!-- Additional inline styles for report layout -->
    <style>
        /* Enhanced screen styles that complement the print CSS */
        
        .print-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        
        .print-actions {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 1rem;
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        
        /* Common styles for both screen and print */
        .report-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #333;
        }
        
        .report-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .report-subtitle {
            font-size: 1.2rem;
            color: #7f8c8d;
            margin-bottom: 1rem;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            background: white;
        }
        
        .report-table th,
        .report-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            vertical-align: top;
        }
        
        .report-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .report-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .report-table tbody tr:hover {
            background-color: #f0f8ff;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 2rem 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #bdc3c7;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 1rem 0;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .info-label {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .info-value {
            color: #34495e;
        }
        
        .status-approved {
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-pending {
            color: #f39c12;
            font-weight: bold;
        }
        
        .status-rejected {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .signatures {
            margin-top: 3rem;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .signature-box {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #333;
        }
        
        .signature-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .signature-info {
            font-size: 0.9rem;
            color: #7f8c8d;
        }
    </style>
</head>
<body>

    <!-- Print Actions (Screen Only) -->
    <div class="print-actions no-print">
        <button onclick="window.print()" class="btn btn-primary">
            🖨️ Print Report
        </button>
        <a href="rocket_detail_view.php?id=<?php echo $rocket['rocket_id']; ?>" class="btn btn-secondary">
            ← Back to Rocket
        </a>
    </div>

    <div class="print-container">
        
        <!-- Report Header -->
        <div class="report-header no-page-break">
            <div class="report-title">MOTOR CHARGING REPORT</div>
            <div class="report-subtitle">Defense Propulsion Technology Institute (DPTI)</div>
            <div class="report-meta">
                <strong>Rocket Serial Number:</strong> <?php echo htmlspecialchars($rocket['serial_number']); ?><br>
                <strong>Project Name:</strong> <?php echo htmlspecialchars($rocket['project_name']); ?><br>
                <strong>Report Generated:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($report_data['generated_at'])); ?><br>
                <strong>Generated By:</strong> <?php echo htmlspecialchars($report_data['generated_by']); ?>
            </div>
        </div>

        <!-- Rocket Basic Information -->
        <div class="section-title">1. ROCKET SPECIFICATION</div>
        <div class="info-grid no-page-break">
            <div class="info-item">
                <span class="info-label">Serial Number:</span>
                <span class="info-value"><?php echo htmlspecialchars($rocket['serial_number']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Project Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($rocket['project_name']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Current Status:</span>
                <span class="info-value"><?php echo htmlspecialchars($rocket['current_status']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Created Date:</span>
                <span class="info-value"><?php echo date('F j, Y', strtotime($rocket['created_at'])); ?></span>
            </div>
        </div>

        <!-- Production Steps Summary -->
        <div class="section-title">2. PRODUCTION STEPS SUMMARY</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 25%;">Step Name</th>
                    <th style="width: 15%;">Performed By</th>
                    <th style="width: 15%;">Date Completed</th>
                    <th style="width: 15%;">Approval Status</th>
                    <th style="width: 15%;">Approved By</th>
                    <th style="width: 10%;">Approval Date</th>
                </tr>
            </thead>
            <tbody>
                <?php $step_number = 1; ?>
                <?php foreach ($steps as $step): ?>
                    <tr>
                        <td><?php echo $step_number++; ?></td>
                        <td><?php echo htmlspecialchars($step['step_name']); ?></td>
                        <td><?php echo htmlspecialchars($step['staff_name']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($step['step_timestamp'])); ?></td>
                        <td>
                            <?php if ($step['approval_info']): ?>
                                <span class="status-<?php echo $step['approval_info']['status']; ?>">
                                    <?php echo strtoupper($step['approval_info']['status']); ?>
                                </span>
                            <?php else: ?>
                                <span class="status-pending">PENDING</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($step['approval_info']): ?>
                                <?php echo htmlspecialchars($step['approval_info']['approver_name']); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($step['approval_info']): ?>
                                <?php echo date('M j, Y', strtotime($step['approval_info']['approval_date'])); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Detailed Step Information -->
        <div class="section-title">3. DETAILED STEP INFORMATION</div>
        <?php foreach ($steps as $index => $step): ?>
            <?php if ($index > 0 && $index % 3 === 0): ?>
                <div class="page-break"></div>
            <?php endif; ?>
            
            <div class="no-page-break" style="margin-bottom: 2rem;">
                <h4 style="color: #2c3e50; margin-bottom: 1rem;">
                    <?php echo ($index + 1) . '. ' . htmlspecialchars($step['step_name']); ?>
                </h4>
                
                <table class="report-table" style="margin-bottom: 1rem;">
                    <tr>
                        <th style="width: 25%;">Staff Member</th>
                        <td><?php echo htmlspecialchars($step['staff_name']); ?> (<?php echo htmlspecialchars($step['staff_role']); ?>)</td>
                    </tr>
                    <tr>
                        <th>Completion Time</th>
                        <td><?php echo date('F j, Y \a\t g:i A', strtotime($step['step_timestamp'])); ?></td>
                    </tr>
                    <?php if ($step['approval_info']): ?>
                    <tr>
                        <th>Approval Status</th>
                        <td>
                            <span class="status-<?php echo $step['approval_info']['status']; ?>">
                                <?php echo strtoupper($step['approval_info']['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Approved By</th>
                        <td><?php echo htmlspecialchars($step['approval_info']['approver_name']); ?> (<?php echo htmlspecialchars($step['approval_info']['approver_role']); ?>)</td>
                    </tr>
                    <tr>
                        <th>Approval Date</th>
                        <td><?php echo date('F j, Y \a\t g:i A', strtotime($step['approval_info']['approval_date'])); ?></td>
                    </tr>
                    <?php if (!empty($step['approval_info']['comments'])): ?>
                    <tr>
                        <th>Approval Comments</th>
                        <td><?php echo htmlspecialchars($step['approval_info']['comments']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php endif; ?>
                </table>

                <!-- Technical Details from JSON Data -->
                <?php if (!empty($step['parsed_data'])): ?>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th colspan="2">Technical Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($step['parsed_data'] as $key => $value): ?>
                            <?php if (is_array($value)): ?>
                                <tr>
                                    <th style="width: 30%;"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?></th>
                                    <td><?php echo htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)); ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <th style="width: 30%;"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?></th>
                                    <td><?php echo htmlspecialchars($value); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Report Summary -->
        <div class="section-title">4. REPORT SUMMARY</div>
        <div class="no-page-break">
            <table class="report-table">
                <tr>
                    <th style="width: 30%;">Total Production Steps</th>
                    <td><?php echo $metadata['total_steps']; ?></td>
                </tr>
                <tr>
                    <th>Approved Steps</th>
                    <td><?php echo $metadata['approved_steps']; ?></td>
                </tr>
                <tr>
                    <th>Completion Rate</th>
                    <td>
                        <?php 
                        $completion_rate = $metadata['total_steps'] > 0 ? 
                            round(($metadata['approved_steps'] / $metadata['total_steps']) * 100, 1) : 0;
                        echo $completion_rate . '%';
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Report Status</th>
                    <td><strong><?php echo strtoupper($metadata['report_status']); ?></strong></td>
                </tr>
                <tr>
                    <th>Final Approval</th>
                    <td>
                        <?php if ($final_approver): ?>
                            Approved by <?php echo htmlspecialchars($final_approver['final_approver_name']); ?> 
                            on <?php echo date('F j, Y', strtotime($final_approver['final_approval_date'])); ?>
                        <?php else: ?>
                            Pending Final Approval
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Signatures -->
        <div class="signatures no-page-break">
            <div class="signature-box">
                <div class="signature-title">Technical Officer</div>
                <div class="signature-info">
                    Date: ________________<br>
                    Signature: ________________
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Quality Assurance</div>
                <div class="signature-info">
                    Date: ________________<br>
                    Signature: ________________
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Project Manager</div>
                <div class="signature-info">
                    Date: ________________<br>
                    Signature: ________________
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="margin-top: 3rem; text-align: center; font-size: 0.9rem; color: #7f8c8d; border-top: 1px solid #bdc3c7; padding-top: 1rem;">
            <strong>Defense Propulsion Technology Institute (DPTI)</strong><br>
            Motor Charging Report - Generated on <?php echo date('F j, Y \a\t g:i A'); ?><br>
            Document Reference: MCR-<?php echo $rocket['rocket_id']; ?>-<?php echo date('Ymd'); ?>
        </div>

    </div>

</body>
</html>
