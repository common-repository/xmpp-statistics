<?php
/**
 * Save statistics to the database
 */

if(!defined('ABSPATH')) {
	exit;
}

// Activation hook
function xmpp_stats_activated() {
	// Add statistics cron job
	wp_schedule_event(time(), 'everyfiveminutes', 'xmpp_stats_cron');
	// Create table for statistics
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		$charset_collate = $wpdb->get_charset_collate();
		$wpdb->query("CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			type tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
			value int(12) UNSIGNED NOT NULL DEFAULT 0,
			UNIQUE KEY id (id)
		) $charset_collate;");
	}
}
register_activation_hook(XMPP_STATS_DIR_PATH.'xmpp-stats.php', 'xmpp_stats_activated');

// Deactivation hook
function xmpp_stats_deactivated() {
	// Remove statistics cron job
	wp_clear_scheduled_hook('xmpp_stats_cron');
}
register_deactivation_hook(XMPP_STATS_DIR_PATH.'xmpp-stats.php', 'xmpp_stats_deactivated' );

// Add cron schedule
function xmpp_stats_schedule_recurrence($schedules) {
	$schedules['everyfiveminutes'] = array(
		'interval' => 300,
		'display' => __('Once Every 5 Minutes', 'xmpp-statistics')
	);
	return $schedules;
}
add_filter('cron_schedules', 'xmpp_stats_schedule_recurrence');

// Add statistics cron job action
function xmpp_stats_cron() {
	// If statistics are to be saved
	if(get_option('xmpp_stats_save_data')) {
		// Get current time in UTC
		$now = date('Y-m-d H:i:s', time());
		// Get statistics
		$online = xmpp_stats_restapi_data('stats', array('name' => 'onlineusers'));
		$registered = xmpp_stats_restapi_data('stats', array('name' => 'registeredusers'));
		$s2s_out = xmpp_stats_restapi_data('outgoing_s2s_number');
		$s2s_in = xmpp_stats_restapi_data('incoming_s2s_number');
		$xmpp_uptime = xmpp_stats_restapi_data('stats', array('name' => 'uptimeseconds'));
		$system_uptime = json_decode(xmpp_stats_restapi_data('system_uptime'))->stat;
		$memory_usage = json_decode(xmpp_stats_restapi_data('system_memory'))->stat;
		$disk_usage = json_decode(xmpp_stats_restapi_data('system_disk'))->stat;
		// Save statistics to database
		global $wpdb;
		$table_name = $wpdb->prefix . 'xmpp_stats';
		$wpdb->insert($table_name, array('timestamp' => $now, 'type' => '1', 'value' => $online));
		$wpdb->insert($table_name, array('timestamp' => $now, 'type' => '2', 'value' => $registered));
		$wpdb->insert($table_name, array('timestamp' => $now, 'type' => '3', 'value' => $s2s_out));
		$wpdb->insert($table_name, array('timestamp' => $now, 'type' => '4', 'value' => $s2s_in));
		$wpdb->insert($table_name, array('timestamp' => $now, 'type' => '5', 'value' => $xmpp_uptime));
		$wpdb->insert($table_name, array('timestamp' => $now, 'type' => '6', 'value' => $system_uptime));
		$wpdb->insert($table_name, array('timestamp' => $now, 'type' => '7', 'value' => $memory_usage));
		$wpdb->insert($table_name, array('timestamp' => $now, 'type' => '8', 'value' => $disk_usage));
		// Delete unnecessary data older than 2 weeks
		if(get_option('xmpp_stats_delete_old_data')) {
			$latest = $wpdb->get_row("SELECT * FROM $table_name WHERE id = (SELECT MAX(id) FROM $table_name);");
			$time = date('Y-m-d H:i:s', strtotime($latest->timestamp)-(14*24*60*60));
			$wpdb->query("DELETE FROM $table_name WHERE timestamp < '$time'");
			$all = $wpdb->get_col("SELECT id FROM $table_name");
			foreach($all as $col) {
				$count++;
				$wpdb->query("UPDATE $table_name SET id=$count WHERE id=$col");
			}
			$count++;
			$wpdb->query("ALTER TABLE $table_name AUTO_INCREMENT=$count;");
		}
		// Create new graphs cache
		xmpp_stats_online_users_daily_cache();
		xmpp_stats_online_users_weekly_cache();
		xmpp_stats_registered_users_daily_cache();
		xmpp_stats_registered_users_weekly_cache();
		xmpp_stats_server_uptime_daily_cache();
		xmpp_stats_server_uptime_weekly_cache();
		xmpp_stats_system_uptime_daily_cache();
		xmpp_stats_system_uptime_weekly_cache();
		xmpp_stats_memory_usage_daily_cache();
		xmpp_stats_memory_usage_weekly_cache();
		xmpp_stats_disk_usage_daily_cache();
		xmpp_stats_disk_usage_weekly_cache();
		xmpp_stats_s2s_out_daily_cache();
		xmpp_stats_s2s_out_weekly_cache();
		xmpp_stats_s2s_in_daily_cache();
		xmpp_stats_s2s_in_weekly_cache();
	}
}
add_action('xmpp_stats_cron', 'xmpp_stats_cron');
