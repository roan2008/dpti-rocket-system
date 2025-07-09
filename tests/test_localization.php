<?php
/**
 * Localization Test Page
 * Test the i18n system implementation
 */

// Include header (which loads localization)
include 'includes/header.php';

// Set page title for demonstration
$page_title = 'dashboard_title';
?>

<div class="container" style="max-width: 800px; margin: 40px auto; padding: 20px;">
    <h1>🌐 Localization System Test</h1>
    
    <div class="test-section" style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px;">
        <h2>📋 Dashboard Translations</h2>
        <ul style="list-style: none; padding: 0;">
            <li><strong>Dashboard:</strong> <?php echo t('dashboard'); ?></li>
            <li><strong>Rockets Overview:</strong> <?php echo t('rockets_overview'); ?></li>
            <li><strong>Total Rockets:</strong> <?php echo t('total_rockets'); ?></li>
            <li><strong>Pending Approvals:</strong> <?php echo t('pending_approvals'); ?></li>
            <li><strong>Production Steps:</strong> <?php echo t('production_steps'); ?></li>
            <li><strong>Add New Rocket:</strong> <?php echo t('add_new_rocket'); ?></li>
        </ul>
    </div>

    <div class="test-section" style="background: #e8f5e8; padding: 20px; margin: 20px 0; border-radius: 8px;">
        <h2>🚀 Rocket Status Translations</h2>
        <ul style="list-style: none; padding: 0;">
            <li><strong>New:</strong> <?php echo t('status_new'); ?></li>
            <li><strong>Planning:</strong> <?php echo t('status_planning'); ?></li>
            <li><strong>Design:</strong> <?php echo t('status_design'); ?></li>
            <li><strong>In Production:</strong> <?php echo t('status_in_production'); ?></li>
            <li><strong>Testing:</strong> <?php echo t('status_testing'); ?></li>
            <li><strong>Completed:</strong> <?php echo t('status_completed'); ?></li>
            <li><strong>On Hold:</strong> <?php echo t('status_on_hold'); ?></li>
        </ul>
    </div>

    <div class="test-section" style="background: #fff3cd; padding: 20px; margin: 20px 0; border-radius: 8px;">
        <h2>⚙️ System Information</h2>
        <ul style="list-style: none; padding: 0;">
            <li><strong>Current Language:</strong> <?php echo get_current_language(); ?></li>
            <li><strong>Translations Loaded:</strong> <?php echo translations_loaded() ? 'Yes' : 'No'; ?></li>
            <li><strong>Available Languages:</strong> <?php echo implode(', ', get_available_languages()); ?></li>
            <li><strong>Total Translation Keys:</strong> <?php echo count($_SESSION['app_translations'] ?? []); ?></li>
        </ul>
    </div>

    <div class="test-section" style="background: #f0f8ff; padding: 20px; margin: 20px 0; border-radius: 8px;">
        <h2>🧪 Function Tests</h2>
        
        <h3>1. Basic Translation</h3>
        <p><code>t('dashboard')</code> → <?php echo t('dashboard'); ?></p>
        
        <h3>2. Missing Key Fallback</h3>
        <p><code>t('nonexistent_key')</code> → <?php echo t('nonexistent_key'); ?></p>
        
        <h3>3. Dynamic Replacements</h3>
        <p><code>t('welcome') with replacement</code> → <?php echo t('welcome') . ', ' . ($_SESSION['full_name'] ?? 'ผู้ใช้'); ?></p>
        
        <h3>4. Number Formatting</h3>
        <p><code>format_number(1234.56, 2)</code> → <?php echo format_number(1234.56, 2); ?></p>
        
        <h3>5. Date Formatting</h3>
        <p><code>format_date(time())</code> → <?php echo format_date(time()); ?></p>
    </div>

    <div class="action-section" style="text-align: center; margin: 30px 0;">
        <a href="dashboard.php" class="btn btn-primary" style="display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 6px;">
            <?php echo t('back'); ?> to <?php echo t('dashboard'); ?>
        </a>
    </div>
</div>

<style>
.container h1 { color: #2c3e50; margin-bottom: 30px; }
.container h2 { color: #34495e; font-size: 1.2em; margin-bottom: 15px; }
.container h3 { color: #5a6c7d; font-size: 1em; margin: 15px 0 8px 0; }
.container li { padding: 8px 0; border-bottom: 1px solid #eee; }
.container li:last-child { border-bottom: none; }
.container code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style>

<?php include 'includes/footer.php'; ?>
