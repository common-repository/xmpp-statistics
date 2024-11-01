<?php
/**
 * Cleaning the database when uninstalling
 */

// Die if uninstall.php is not called by WordPress
if(!defined('WP_UNINSTALL_PLUGIN')) {
	die;
}

// Remove settings options
delete_option('xmpp_stats_rest_url');
delete_option('xmpp_stats_login');
delete_option('xmpp_stats_password');
delete_option('xmpp_stats_oauth_token');
delete_option('xmpp_stats_save_data');
delete_option('xmpp_stats_delete_old_data');
delete_option('xmpp_stats_chart_line_color');
delete_option('xmpp_stats_chart_line_color2');
delete_option('xmpp_stats_chart_grid_color');
delete_option('xmpp_stats_chart_width');
delete_option('xmpp_stats_chart_height');
delete_option('xmpp_stats_daily_chart_mode');
// Delete statistics table
global $wpdb;
$table_name = $wpdb->prefix . 'xmpp_stats';
$wpdb->query("DROP TABLE IF EXISTS $table_name");
// Remove graphs cache
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_xmpp_stats_%'");