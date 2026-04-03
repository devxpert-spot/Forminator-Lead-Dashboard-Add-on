<?php
/**
 * All Leads Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$forms = FLD_Leads::get_forms();
$statuses = FLD_Leads::get_statuses();
$users = FLD_Roles::get_team_users();
?>

<div class="wrap fld-leads-page">
    <h1 class="fld-page-title">
        <span class="dashicons dashicons-id"></span>
        <?php _e('All Leads', 'forminator-lead-dashboard'); ?>
    </h1>

    <!-- Filters -->
    <div class="fld-filters">
        <div class="fld-filter-row">
            <div class="fld-filter-item">
                <label><?php _e('Form:', 'forminator-lead-dashboard'); ?></label>
                <select id="fld-filter-form">
                    <option value=""><?php _e('All Forms', 'forminator-lead-dashboard'); ?></option>
                    <?php foreach ($forms as $form): ?>
                        <option value="<?php echo esc_attr($form['id']); ?>">
                            <?php echo esc_html($form['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="fld-filter-item">
                <label><?php _e('Status:', 'forminator-lead-dashboard'); ?></label>
                <select id="fld-filter-status">
                    <option value=""><?php _e('All Statuses', 'forminator-lead-dashboard'); ?></option>
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="fld-filter-item">
                <label><?php _e('Date From:', 'forminator-lead-dashboard'); ?></label>
                <input type="date" id="fld-filter-date-from">
            </div>

            <div class="fld-filter-item">
                <label><?php _e('Date To:', 'forminator-lead-dashboard'); ?></label>
                <input type="date" id="fld-filter-date-to">
            </div>

            <div class="fld-filter-item">
                <label><?php _e('Search:', 'forminator-lead-dashboard'); ?></label>
                <input type="text" id="fld-filter-search" placeholder="<?php _e('Search leads...', 'forminator-lead-dashboard'); ?>">
            </div>

            <div class="fld-filter-actions">
                <button id="fld-apply-filters" class="button button-primary">
                    <?php _e('Apply Filters', 'forminator-lead-dashboard'); ?>
                </button>
                <button id="fld-reset-filters" class="button">
                    <?php _e('Reset', 'forminator-lead-dashboard'); ?>
                </button>
                <button id="fld-export-leads" class="button">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Export CSV', 'forminator-lead-dashboard'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="fld-bulk-actions">
        <select id="fld-bulk-action">
            <option value=""><?php _e('Bulk Actions', 'forminator-lead-dashboard'); ?></option>
            <?php foreach ($statuses as $key => $label): ?>
                <option value="status_<?php echo esc_attr($key); ?>">
                    <?php printf(__('Mark as %s', 'forminator-lead-dashboard'), $label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button id="fld-apply-bulk" class="button"><?php _e('Apply', 'forminator-lead-dashboard'); ?></button>
        <span id="fld-selected-count">0 <?php _e('selected', 'forminator-lead-dashboard'); ?></span>
    </div>

    <!-- Leads Table -->
    <div class="fld-table-wrapper">
        <table class="fld-table fld-leads-table" id="fld-leads-table">
            <thead>
                <tr>
                    <th class="fld-col-check">
                        <input type="checkbox" id="fld-select-all">
                    </th>
                    <th class="fld-col-id"><?php _e('ID', 'forminator-lead-dashboard'); ?></th>
                    <th class="fld-col-date"><?php _e('Date', 'forminator-lead-dashboard'); ?></th>
                    <th class="fld-col-form"><?php _e('Form', 'forminator-lead-dashboard'); ?></th>
                    <th class="fld-col-contact"><?php _e('Contact Info', 'forminator-lead-dashboard'); ?></th>
                    <th class="fld-col-status"><?php _e('Status', 'forminator-lead-dashboard'); ?></th>
                    <th class="fld-col-feedback"><?php _e('Feedback', 'forminator-lead-dashboard'); ?></th>
                    <th class="fld-col-actions"><?php _e('Actions', 'forminator-lead-dashboard'); ?></th>
                </tr>
            </thead>
            <tbody id="fld-leads-tbody">
                <!-- Populated by JS -->
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="fld-pagination" id="fld-pagination">
        <!-- Populated by JS -->
    </div>

    <!-- Loading Overlay -->
    <div id="fld-loading" class="fld-loading" style="display: none;">
        <div class="fld-spinner"></div>
        <p><?php _e('Loading...', 'forminator-lead-dashboard'); ?></p>
    </div>
</div>

<!-- Lead Detail Modal -->
<div id="fld-lead-modal" class="fld-modal" style="display: none;">
    <div class="fld-modal-content fld-modal-large">
        <div class="fld-modal-header">
            <h2><?php _e('Lead Details', 'forminator-lead-dashboard'); ?> #<span id="fld-modal-lead-id"></span></h2>
            <button class="fld-modal-close">&times;</button>
        </div>
        <div class="fld-modal-body">
            <div class="fld-lead-detail-grid">
                <!-- Lead Info -->
                <div class="fld-lead-info">
                    <h4><?php _e('Submission Details', 'forminator-lead-dashboard'); ?></h4>
                    <div id="fld-lead-meta">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- Status & Actions -->
                <div class="fld-lead-actions-panel">
                    <h4><?php _e('Lead Status', 'forminator-lead-dashboard'); ?></h4>
                    <div class="fld-status-selector">
                        <?php foreach ($statuses as $key => $label): ?>
                            <label class="fld-status-option fld-status-<?php echo esc_attr($key); ?>">
                                <input type="radio" name="fld-lead-status" value="<?php echo esc_attr($key); ?>">
                                <span><?php echo esc_html($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button id="fld-save-status" class="button button-primary button-large">
                        <?php _e('Save Status', 'forminator-lead-dashboard'); ?>
                    </button>
                </div>
            </div>

            <!-- Feedback Section -->
            <div class="fld-feedback-panel">
                <h4><?php _e('Sales Team Feedback', 'forminator-lead-dashboard'); ?></h4>
                
                <div id="fld-feedback-list" class="fld-feedback-list">
                    <!-- Populated by JS -->
                </div>

                <div class="fld-add-feedback-form">
                    <h5><?php _e('Add New Feedback', 'forminator-lead-dashboard'); ?></h5>
                    <div class="fld-feedback-rating-selector">
                        <label class="fld-rating-option fld-rating-positive">
                            <input type="radio" name="fld-new-rating" value="positive">
                            <span>👍 <?php _e('Positive', 'forminator-lead-dashboard'); ?></span>
                        </label>
                        <label class="fld-rating-option fld-rating-neutral">
                            <input type="radio" name="fld-new-rating" value="neutral" checked>
                            <span>😐 <?php _e('Neutral', 'forminator-lead-dashboard'); ?></span>
                        </label>
                        <label class="fld-rating-option fld-rating-negative">
                            <input type="radio" name="fld-new-rating" value="negative">
                            <span>👎 <?php _e('Negative', 'forminator-lead-dashboard'); ?></span>
                        </label>
                    </div>
                    <textarea id="fld-new-feedback" rows="3" placeholder="<?php _e('Enter your feedback about this lead...', 'forminator-lead-dashboard'); ?>"></textarea>
                    <button id="fld-submit-feedback" class="button button-primary">
                        <?php _e('Add Feedback', 'forminator-lead-dashboard'); ?>
                    </button>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="fld-activity-panel">
                <h4><?php _e('Activity Log', 'forminator-lead-dashboard'); ?></h4>
                <div id="fld-activity-log">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>
    </div>
</div>
