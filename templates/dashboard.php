<?php
/**
 * Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$forms = FLD_Leads::get_forms();
$statuses = FLD_Leads::get_statuses();
?>

<div class="wrap fld-dashboard">
    <h1 class="fld-page-title">
        <span class="dashicons dashicons-chart-line"></span>
        <?php _e('Lead Dashboard', 'forminator-lead-dashboard'); ?>
    </h1>

    <!-- Date Range Filter -->
    <div class="fld-filters-bar">
        <div class="fld-date-range">
            <label><?php _e('Date Range:', 'forminator-lead-dashboard'); ?></label>
            <select id="fld-date-range">
                <option value="7"><?php _e('Last 7 Days', 'forminator-lead-dashboard'); ?></option>
                <option value="30" selected><?php _e('Last 30 Days', 'forminator-lead-dashboard'); ?></option>
                <option value="90"><?php _e('Last 90 Days', 'forminator-lead-dashboard'); ?></option>
                <option value="365"><?php _e('Last Year', 'forminator-lead-dashboard'); ?></option>
            </select>
        </div>
        <button id="fld-refresh-stats" class="button">
            <span class="dashicons dashicons-update"></span>
            <?php _e('Refresh', 'forminator-lead-dashboard'); ?>
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="fld-stats-grid">
        <div class="fld-stat-card fld-stat-total">
            <div class="fld-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="fld-stat-content">
                <h3 id="stat-total-leads">0</h3>
                <p><?php _e('Total Leads', 'forminator-lead-dashboard'); ?></p>
            </div>
        </div>

        <div class="fld-stat-card fld-stat-new">
            <div class="fld-stat-icon">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <div class="fld-stat-content">
                <h3 id="stat-new-leads">0</h3>
                <p><?php _e('New Leads', 'forminator-lead-dashboard'); ?></p>
            </div>
        </div>

        <div class="fld-stat-card fld-stat-positive">
            <div class="fld-stat-icon">
                <span class="dashicons dashicons-thumbs-up"></span>
            </div>
            <div class="fld-stat-content">
                <h3 id="stat-positive-leads">0</h3>
                <p><?php _e('Positive Leads', 'forminator-lead-dashboard'); ?></p>
            </div>
        </div>

        <div class="fld-stat-card fld-stat-negative">
            <div class="fld-stat-icon">
                <span class="dashicons dashicons-thumbs-down"></span>
            </div>
            <div class="fld-stat-content">
                <h3 id="stat-negative-leads">0</h3>
                <p><?php _e('Negative Leads', 'forminator-lead-dashboard'); ?></p>
            </div>
        </div>

        <div class="fld-stat-card fld-stat-conversion">
            <div class="fld-stat-icon">
                <span class="dashicons dashicons-chart-pie"></span>
            </div>
            <div class="fld-stat-content">
                <h3 id="stat-conversion-rate">0%</h3>
                <p><?php _e('Conversion Rate', 'forminator-lead-dashboard'); ?></p>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="fld-charts-row">
        <div class="fld-chart-card">
            <h3><?php _e('Leads Over Time', 'forminator-lead-dashboard'); ?></h3>
            <div class="fld-chart-wrap">
                <canvas id="fld-leads-chart"></canvas>
            </div>
        </div>

        <div class="fld-chart-card">
            <h3><?php _e('Leads by Status', 'forminator-lead-dashboard'); ?></h3>
            <div class="fld-chart-wrap">
                <canvas id="fld-status-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Forms Table -->
    <div class="fld-table-card">
        <h3><?php _e('Top Forms by Leads', 'forminator-lead-dashboard'); ?></h3>
        <table class="fld-table" id="fld-forms-table">
            <thead>
                <tr>
                    <th><?php _e('Form Name', 'forminator-lead-dashboard'); ?></th>
                    <th><?php _e('Total Leads', 'forminator-lead-dashboard'); ?></th>
                    <th><?php _e('Actions', 'forminator-lead-dashboard'); ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- Populated by JS -->
            </tbody>
        </table>
    </div>

    <!-- Recent Leads -->
    <div class="fld-table-card">
        <div class="fld-table-header">
            <h3><?php _e('Recent Leads', 'forminator-lead-dashboard'); ?></h3>
            <a href="<?php echo admin_url('admin.php?page=lead-dashboard-leads'); ?>" class="button">
                <?php _e('View All', 'forminator-lead-dashboard'); ?>
            </a>
        </div>
        <table class="fld-table" id="fld-recent-leads">
            <thead>
                <tr>
                    <th><?php _e('ID', 'forminator-lead-dashboard'); ?></th>
                    <th><?php _e('Date', 'forminator-lead-dashboard'); ?></th>
                    <th><?php _e('Form', 'forminator-lead-dashboard'); ?></th>
                    <th><?php _e('Status', 'forminator-lead-dashboard'); ?></th>
                    <th><?php _e('Feedback', 'forminator-lead-dashboard'); ?></th>
                    <th><?php _e('Actions', 'forminator-lead-dashboard'); ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- Populated by JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- Lead Detail Modal -->
<div id="fld-lead-modal" class="fld-modal" style="display: none;">
    <div class="fld-modal-content">
        <div class="fld-modal-header">
            <h2><?php _e('Lead Details', 'forminator-lead-dashboard'); ?></h2>
            <button class="fld-modal-close">&times;</button>
        </div>
        <div class="fld-modal-body">
            <div id="fld-lead-details">
                <!-- Populated by JS -->
            </div>

            <!-- Status Update -->
            <div class="fld-lead-status-section">
                <h4><?php _e('Update Status', 'forminator-lead-dashboard'); ?></h4>
                <select id="fld-lead-status">
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button id="fld-update-status" class="button button-primary">
                    <?php _e('Update Status', 'forminator-lead-dashboard'); ?>
                </button>
            </div>

            <!-- Feedback Section -->
            <div class="fld-feedback-section">
                <h4><?php _e('Sales Team Feedback', 'forminator-lead-dashboard'); ?></h4>
                
                <div id="fld-feedback-list">
                    <!-- Populated by JS -->
                </div>

                <div class="fld-add-feedback">
                    <h5><?php _e('Add Feedback', 'forminator-lead-dashboard'); ?></h5>
                    <div class="fld-feedback-rating">
                        <label>
                            <input type="radio" name="fld-feedback-rating" value="positive"> 
                            👍 <?php _e('Positive', 'forminator-lead-dashboard'); ?>
                        </label>
                        <label>
                            <input type="radio" name="fld-feedback-rating" value="neutral" checked> 
                            😐 <?php _e('Neutral', 'forminator-lead-dashboard'); ?>
                        </label>
                        <label>
                            <input type="radio" name="fld-feedback-rating" value="negative"> 
                            👎 <?php _e('Negative', 'forminator-lead-dashboard'); ?>
                        </label>
                    </div>
                    <textarea id="fld-feedback-text" placeholder="<?php _e('Enter your feedback...', 'forminator-lead-dashboard'); ?>"></textarea>
                    <button id="fld-submit-feedback" class="button button-primary">
                        <?php _e('Submit Feedback', 'forminator-lead-dashboard'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
