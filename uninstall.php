<?php
/**
 * Uninstall routine — runs when the plugin is deleted from Plugins > Installed Plugins.
 * Drops all plugin tables and removes all plugin options from wp_options.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop custom tables.
$tables = array(
	$wpdb->prefix . 'fld_lead_status',
	$wpdb->prefix . 'fld_feedback',
	$wpdb->prefix . 'fld_activity_log',
);

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name is constructed from trusted $wpdb->prefix and a hardcoded suffix.
	$wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $table ) . '`' );
}

// Remove all plugin options.
$options = array(
	'fld_version',
	'fld_db_version',
	'fld_email_notifications',
	'fld_notification_email',
	'fld_auto_assign',
	'fld_default_assignee',
	'fld_leads_per_page',
	'fld_smtp_host',
	'fld_smtp_port',
	'fld_smtp_username',
	'fld_smtp_password',
	'fld_smtp_encryption',
	'fld_brevo_sender_name',
	'fld_brevo_sender_email',
	'fld_otp_enabled_forms',
);

foreach ( $options as $option ) {
	delete_option( $option );
}
