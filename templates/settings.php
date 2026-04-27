<?php
/**
 * Settings Template — Administrators only
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!FLD_Roles::is_admin()) {
    wp_die(__('You do not have permission to access this page.', 'forminator-lead-dashboard'));
}

// Handle form submission
if (isset($_POST['fld_save_settings']) && wp_verify_nonce($_POST['fld_settings_nonce'], 'fld_save_settings')) {
    update_option('fld_email_notifications', isset($_POST['fld_email_notifications']) ? 1 : 0);
    update_option('fld_notification_email', sanitize_email($_POST['fld_notification_email']));
    update_option('fld_auto_assign', isset($_POST['fld_auto_assign']) ? 1 : 0);
    update_option('fld_default_assignee', intval($_POST['fld_default_assignee']));
    update_option('fld_leads_per_page', intval($_POST['fld_leads_per_page']));

    // Brevo SMTP / OTP settings
    update_option('fld_smtp_host',          sanitize_text_field(wp_unslash($_POST['fld_smtp_host'] ?? '')));
    update_option('fld_smtp_port',          intval($_POST['fld_smtp_port'] ?? 587));
    update_option('fld_smtp_username',      sanitize_text_field(wp_unslash($_POST['fld_smtp_username'] ?? '')));
    // Only update password if a new value was actually submitted (non-empty)
    if (!empty($_POST['fld_smtp_password'])) {
        update_option('fld_smtp_password',  sanitize_text_field(wp_unslash($_POST['fld_smtp_password'])));
    }
    update_option('fld_smtp_encryption',    sanitize_text_field(wp_unslash($_POST['fld_smtp_encryption'] ?? 'tls')));
    update_option('fld_brevo_sender_name',  sanitize_text_field(wp_unslash($_POST['fld_brevo_sender_name'] ?? get_bloginfo('name'))));
    update_option('fld_brevo_sender_email', sanitize_email(wp_unslash($_POST['fld_brevo_sender_email'] ?? '')));
    update_option('fld_otp_enabled_forms',  array_map('intval', (array) ($_POST['fld_otp_enabled_forms'] ?? [])));

    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully!', 'forminator-lead-dashboard' ) . '</p></div>';
}

$email_notifications = get_option('fld_email_notifications', 0);
$notification_email  = get_option('fld_notification_email', get_option('admin_email'));
$auto_assign         = get_option('fld_auto_assign', 0);
$default_assignee    = get_option('fld_default_assignee', 0);
$leads_per_page      = get_option('fld_leads_per_page', 20);

// Brevo SMTP / OTP settings
$smtp_host          = get_option('fld_smtp_host',          'smtp-relay.brevo.com');
$smtp_port          = get_option('fld_smtp_port',          587);
$smtp_username      = get_option('fld_smtp_username',      '');
$smtp_encryption    = get_option('fld_smtp_encryption',    'tls');
$brevo_sender_name  = get_option('fld_brevo_sender_name',  get_bloginfo('name'));
$brevo_sender_email = get_option('fld_brevo_sender_email', get_option('admin_email'));
$otp_enabled_forms  = array_map('intval', (array) get_option('fld_otp_enabled_forms', array()));
$all_forms          = FLD_Leads::get_forms();

$team_users    = FLD_Roles::get_team_users();
$sales_admins  = FLD_Roles::get_sales_admins();
?>

<div class="wrap fld-settings-page">
    <h1 class="fld-page-title">
        <span class="dashicons dashicons-admin-settings"></span>
        <?php _e('Lead Dashboard Settings', 'forminator-lead-dashboard'); ?>
    </h1>

    <form method="post" class="fld-settings-form">
        <?php wp_nonce_field('fld_save_settings', 'fld_settings_nonce'); ?>

        <!-- General Settings -->
        <div class="fld-settings-section">
            <h2><?php _e('General Settings', 'forminator-lead-dashboard'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="fld_leads_per_page"><?php _e('Leads Per Page', 'forminator-lead-dashboard'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="fld_leads_per_page" name="fld_leads_per_page"
                               value="<?php echo esc_attr($leads_per_page); ?>" min="10" max="100">
                        <p class="description"><?php _e('Number of leads to show per page in the leads list.', 'forminator-lead-dashboard'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Notification Settings -->
        <div class="fld-settings-section">
            <h2><?php _e('Notification Settings', 'forminator-lead-dashboard'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="fld_email_notifications"><?php _e('Email Notifications', 'forminator-lead-dashboard'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="fld_email_notifications" name="fld_email_notifications"
                                   value="1" <?php checked($email_notifications, 1); ?>>
                            <?php _e('Send email notifications for new leads', 'forminator-lead-dashboard'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="fld_notification_email"><?php _e('Notification Email', 'forminator-lead-dashboard'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="fld_notification_email" name="fld_notification_email"
                               value="<?php echo esc_attr($notification_email); ?>" class="regular-text">
                        <p class="description"><?php _e('Email address to receive new lead notifications.', 'forminator-lead-dashboard'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Assignment Settings -->
        <div class="fld-settings-section">
            <h2><?php _e('Lead Assignment', 'forminator-lead-dashboard'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="fld_auto_assign"><?php _e('Auto-Assign Leads', 'forminator-lead-dashboard'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="fld_auto_assign" name="fld_auto_assign"
                                   value="1" <?php checked($auto_assign, 1); ?>>
                            <?php _e('Automatically assign new leads to a team member', 'forminator-lead-dashboard'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="fld_default_assignee"><?php _e('Default Assignee', 'forminator-lead-dashboard'); ?></label>
                    </th>
                    <td>
                        <select id="fld_default_assignee" name="fld_default_assignee">
                            <option value="0"><?php _e('— Select —', 'forminator-lead-dashboard'); ?></option>
                            <?php foreach ($team_users as $user): ?>
                                <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($default_assignee, $user->ID); ?>>
                                    <?php echo esc_html($user->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Team member to auto-assign new leads to.', 'forminator-lead-dashboard'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Status Labels -->
        <div class="fld-settings-section">
            <h2><?php _e('Lead Statuses', 'forminator-lead-dashboard'); ?></h2>
            <p class="description"><?php _e('These are the available lead statuses:', 'forminator-lead-dashboard'); ?></p>

            <div class="fld-status-list">
                <div class="fld-status-item">
                    <span class="fld-status-badge fld-status-new"><?php _e('New', 'forminator-lead-dashboard'); ?></span>
                    <span class="fld-status-desc"><?php _e('Newly submitted leads', 'forminator-lead-dashboard'); ?></span>
                </div>
                <div class="fld-status-item">
                    <span class="fld-status-badge fld-status-positive"><?php _e('Positive', 'forminator-lead-dashboard'); ?></span>
                    <span class="fld-status-desc"><?php _e('Qualified, interested leads', 'forminator-lead-dashboard'); ?></span>
                </div>
                <div class="fld-status-item">
                    <span class="fld-status-badge fld-status-negative"><?php _e('Negative', 'forminator-lead-dashboard'); ?></span>
                    <span class="fld-status-desc"><?php _e('Unqualified or uninterested leads', 'forminator-lead-dashboard'); ?></span>
                </div>
                <div class="fld-status-item">
                    <span class="fld-status-badge fld-status-follow_up"><?php _e('Follow Up', 'forminator-lead-dashboard'); ?></span>
                    <span class="fld-status-desc"><?php _e('Requires follow-up action', 'forminator-lead-dashboard'); ?></span>
                </div>
                <div class="fld-status-item">
                    <span class="fld-status-badge fld-status-converted"><?php _e('Converted', 'forminator-lead-dashboard'); ?></span>
                    <span class="fld-status-desc"><?php _e('Lead converted to customer', 'forminator-lead-dashboard'); ?></span>
                </div>
                <div class="fld-status-item">
                    <span class="fld-status-badge fld-status-closed"><?php _e('Closed', 'forminator-lead-dashboard'); ?></span>
                    <span class="fld-status-desc"><?php _e('Lead closed/archived', 'forminator-lead-dashboard'); ?></span>
                </div>
            </div>
        </div>

        <!-- Spam Prevention — Brevo SMTP OTP -->
        <div class="fld-settings-section">
            <h2><?php _e('Spam Prevention — Email OTP', 'forminator-lead-dashboard'); ?></h2>
            <p class="description">
                <?php _e('Require visitors to verify their email via a one-time code sent through Brevo SMTP before a form submission becomes a lead.', 'forminator-lead-dashboard'); ?>
            </p>

            <h3 style="margin-top:16px;"><?php _e('Brevo SMTP Settings', 'forminator-lead-dashboard'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="fld_smtp_host"><?php _e('SMTP Host', 'forminator-lead-dashboard'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="fld_smtp_host" name="fld_smtp_host"
                               value="<?php echo esc_attr($smtp_host); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="fld_smtp_port"><?php _e('SMTP Port', 'forminator-lead-dashboard'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="fld_smtp_port" name="fld_smtp_port"
                               value="<?php echo esc_attr($smtp_port); ?>" min="1" max="65535" style="width:100px;">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="fld_smtp_encryption"><?php _e('Encryption', 'forminator-lead-dashboard'); ?></label>
                    </th>
                    <td>
                        <select id="fld_smtp_encryption" name="fld_smtp_encryption">
                            <option value="tls"  <?php selected($smtp_encryption, 'tls');  ?>>TLS (STARTTLS — Port 587)</option>
                            <option value="ssl"  <?php selected($smtp_encryption, 'ssl');  ?>>SSL — Port 465</option>
                            <option value=""     <?php selected($smtp_encryption, '');     ?>>None</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="fld_smtp_username"><?php _e('SMTP Username', 'forminator-lead-dashboard'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="fld_smtp_username" name="fld_smtp_username"
                               value="<?php echo esc_attr($smtp_username); ?>" class="regular-text"
                               autocomplete="off">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="fld_smtp_password"><?php _e('SMTP Password', 'forminator-lead-dashboard'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="fld_smtp_password" name="fld_smtp_password"
                               value="" placeholder="<?php esc_attr_e('Leave blank to keep current password', 'forminator-lead-dashboard'); ?>"
                               class="regular-text" autocomplete="new-password">
                        <p class="description"><?php _e('Leave blank to keep the saved password. Enter a new value only if you want to change it.', 'forminator-lead-dashboard'); ?></p>
                    </td>
                </tr>
            </table>

            <h3 style="margin-top:20px;"><?php _e('Sender Identity', 'forminator-lead-dashboard'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="fld_brevo_sender_name"><?php _e('From Name', 'forminator-lead-dashboard'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="fld_brevo_sender_name" name="fld_brevo_sender_name"
                               value="<?php echo esc_attr($brevo_sender_name); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="fld_brevo_sender_email"><?php _e('From Email', 'forminator-lead-dashboard'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="fld_brevo_sender_email" name="fld_brevo_sender_email"
                               value="<?php echo esc_attr($brevo_sender_email); ?>" class="regular-text">
                        <p class="description"><?php _e('Must match a verified sender in your Brevo account.', 'forminator-lead-dashboard'); ?></p>
                    </td>
                </tr>
            </table>

            <h3 style="margin-top:20px;"><?php _e('Enable OTP for Forms', 'forminator-lead-dashboard'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Protected Forms', 'forminator-lead-dashboard'); ?></th>
                    <td>
                        <?php if (empty($all_forms)): ?>
                            <p class="description"><?php _e('No Forminator forms found.', 'forminator-lead-dashboard'); ?></p>
                        <?php else: ?>
                            <?php foreach ($all_forms as $form): ?>
                                <label style="display:block;margin-bottom:6px;">
                                    <input type="checkbox"
                                           name="fld_otp_enabled_forms[]"
                                           value="<?php echo esc_attr($form['id']); ?>"
                                           <?php checked(in_array(intval($form['id']), $otp_enabled_forms, true)); ?>>
                                    <?php echo esc_html($form['name']); ?>
                                    <span style="color:#999;font-size:12px;">(ID: <?php echo esc_html($form['id']); ?>)</span>
                                </label>
                            <?php endforeach; ?>
                            <p class="description" style="margin-top:8px;">
                                <?php _e('Checked forms require email verification before submission is accepted as a lead.', 'forminator-lead-dashboard'); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <input type="submit" name="fld_save_settings" class="button button-primary button-large"
                   value="<?php _e('Save Settings', 'forminator-lead-dashboard'); ?>">
        </p>
    </form>

    <!-- ============================================================
         Sales Admin User Management — visible to administrators only
         ============================================================ -->
    <div class="fld-settings-section fld-user-management">
        <h2><?php _e('Sales Admin Users', 'forminator-lead-dashboard'); ?></h2>
        <p class="description">
            <?php
            printf(
                /* translators: 1: opening <strong> tag, 2: closing </strong> tag */
                esc_html__( 'Users with the %1$sSales Admin%2$s role can log in and access the Lead Dashboard. They can view all leads and add feedback. Only Administrators can access Settings.', 'forminator-lead-dashboard' ),
                '<strong>',
                '</strong>'
            );
            ?>
        </p>

        <!-- Current Sales Admins -->
        <h3><?php _e('Current Sales Admins', 'forminator-lead-dashboard'); ?></h3>
        <table class="wp-list-table widefat fixed striped" id="fld-sales-admin-table">
            <thead>
                <tr>
                    <th><?php _e('Name', 'forminator-lead-dashboard'); ?></th>
                    <th><?php _e('Email', 'forminator-lead-dashboard'); ?></th>
                    <th><?php _e('Action', 'forminator-lead-dashboard'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales_admins)): ?>
                    <tr id="fld-no-sales-admins">
                        <td colspan="3"><?php _e('No Sales Admin users yet.', 'forminator-lead-dashboard'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sales_admins as $sa_user): ?>
                        <tr id="fld-sa-row-<?php echo esc_attr($sa_user->ID); ?>">
                            <td><?php echo esc_html($sa_user->display_name); ?></td>
                            <td><?php echo esc_html($sa_user->user_email); ?></td>
                            <td>
                                <button class="button fld-remove-sales-admin"
                                        data-id="<?php echo esc_attr($sa_user->ID); ?>"
                                        data-name="<?php echo esc_attr($sa_user->display_name); ?>">
                                    <?php _e('Remove', 'forminator-lead-dashboard'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Add Sales Admin -->
        <h3 style="margin-top:24px;"><?php _e('Add Sales Admin', 'forminator-lead-dashboard'); ?></h3>
        <p class="description"><?php _e('Assign the Sales Admin role to any existing WordPress user (except administrators).', 'forminator-lead-dashboard'); ?></p>

        <div class="fld-add-sales-admin-form">
            <select id="fld-assign-user-select" style="min-width:280px;">
                <option value=""><?php _e('— Select a user —', 'forminator-lead-dashboard'); ?></option>
            </select>
            <button id="fld-assign-sales-admin" class="button button-primary">
                <?php _e('Add as Sales Admin', 'forminator-lead-dashboard'); ?>
            </button>
            <span id="fld-assign-status" style="margin-left:12px;"></span>
        </div>
    </div>

    <!-- Database Tools -->
    <div class="fld-settings-section fld-danger-zone">
        <h2><?php _e('Database Tools', 'forminator-lead-dashboard'); ?></h2>
        <p class="description"><?php _e('Use these tools with caution.', 'forminator-lead-dashboard'); ?></p>

        <div class="fld-tools">
            <button id="fld-clear-activity" class="button">
                <?php _e('Clear Activity Log', 'forminator-lead-dashboard'); ?>
            </button>
            <button id="fld-reset-statuses" class="button">
                <?php _e('Reset All Statuses', 'forminator-lead-dashboard'); ?>
            </button>
        </div>
    </div>
</div>

<script>
(function($) {
    'use strict';

    // Load assignable users on page ready
    $(document).ready(function() {
        loadAssignableUsers();

        // Assign button
        $('#fld-assign-sales-admin').on('click', function() {
            var userId = $('#fld-assign-user-select').val();
            if (!userId) {
                setStatus('warning', '<?php echo esc_js( __( 'Please select a user.', 'forminator-lead-dashboard' ) ); ?>');
                return;
            }
            assignSalesAdmin(userId);
        });

        // Remove buttons (delegated for dynamically added rows)
        $(document).on('click', '.fld-remove-sales-admin', function() {
            var userId = $(this).data('id');
            var name   = $(this).data('name');
            if (!confirm('<?php echo esc_js(__('Remove Sales Admin role from', 'forminator-lead-dashboard')); ?> ' + name + '?')) {
                return;
            }
            removeSalesAdmin(userId);
        });
    });

    function loadAssignableUsers() {
        $.ajax({
            url: fld_ajax.ajax_url,
            type: 'POST',
            data: { action: 'fld_get_assignable_users', nonce: fld_ajax.nonce },
            success: function(response) {
                if (!response.success) return;
                var select = $('#fld-assign-user-select');
                select.find('option:not(:first)').remove();
                $.each(response.data, function(i, user) {
                    if (!user.is_sales_admin) {
                        select.append($('<option>', { value: user.id, text: user.name + ' (' + user.email + ')' }));
                    }
                });
            }
        });
    }

    function assignSalesAdmin(userId) {
        setStatus('info', '<?php echo esc_js(__('Saving…', 'forminator-lead-dashboard')); ?>');
        $.ajax({
            url: fld_ajax.ajax_url,
            type: 'POST',
            data: { action: 'fld_assign_sales_admin', nonce: fld_ajax.nonce, user_id: userId },
            success: function(response) {
                if (response.success) {
                    setStatus('success', response.data.message);
                    addRowToTable(userId);
                    loadAssignableUsers();
                } else {
                    setStatus('error', response.data);
                }
            },
            error: function() { setStatus('error', fld_ajax.strings.error); }
        });
    }

    function removeSalesAdmin(userId) {
        $.ajax({
            url: fld_ajax.ajax_url,
            type: 'POST',
            data: { action: 'fld_remove_sales_admin', nonce: fld_ajax.nonce, user_id: userId },
            success: function(response) {
                if (response.success) {
                    $('#fld-sa-row-' + userId).remove();
                    // Show "no users" row if table is now empty
                    if ($('#fld-sales-admin-table tbody tr').length === 0) {
                        $('#fld-sales-admin-table tbody').append(
                            '<tr id="fld-no-sales-admins"><td colspan="3"><?php echo esc_js(__('No Sales Admin users yet.', 'forminator-lead-dashboard')); ?></td></tr>'
                        );
                    }
                    setStatus('success', response.data.message);
                    loadAssignableUsers();
                } else {
                    setStatus('error', response.data);
                }
            },
            error: function() { setStatus('error', fld_ajax.strings.error); }
        });
    }

    function addRowToTable(userId) {
        // Get user info from select option
        var option = $('#fld-assign-user-select option[value="' + userId + '"]');
        var text   = option.text(); // "Name (email)"
        var parts  = text.match(/^(.*)\s\(([^)]+)\)$/);
        var name   = parts ? parts[1] : text;
        var email  = parts ? parts[2] : '';

        $('#fld-no-sales-admins').remove();
        $('#fld-sales-admin-table tbody').append(
            '<tr id="fld-sa-row-' + userId + '">' +
            '<td>' + $('<span>').text(name).html() + '</td>' +
            '<td>' + $('<span>').text(email).html() + '</td>' +
            '<td><button class="button fld-remove-sales-admin" data-id="' + userId + '" data-name="' + $('<span>').text(name).html() + '"><?php echo esc_js(__('Remove', 'forminator-lead-dashboard')); ?></button></td>' +
            '</tr>'
        );
    }

    function setStatus(type, message) {
        var colors = { success: '#22c55e', error: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
        $('#fld-assign-status').text(message).css('color', colors[type] || '#000');
    }

})(jQuery);
</script>
