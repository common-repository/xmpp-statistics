<?php
/**
 * Charts
 */

if(!defined('ABSPATH')) {
	exit;
}

// Enqueue charts style
function xmpp_stats_enqueue_charts_scripts() {
	global $post;
	if(is_a($post, 'WP_Post') && (
		has_shortcode($post->post_content, 'xmpp_online_users_daily_chart')
		|| has_shortcode($post->post_content, 'xmpp_online_users_weekly_chart')
		|| has_shortcode($post->post_content, 'xmpp_registered_users_daily_chart')
		|| has_shortcode($post->post_content, 'xmpp_registered_users_weekly_chart')
		|| has_shortcode($post->post_content, 'xmpp_uptime_daily_chart')
		|| has_shortcode($post->post_content, 'xmpp_uptime_weekly_chart')
		|| has_shortcode($post->post_content, 'system_uptime_daily_chart')
		|| has_shortcode($post->post_content, 'system_uptime_weekly_chart')
		|| has_shortcode($post->post_content, 'memory_usage_daily_chart')
		|| has_shortcode($post->post_content, 'memory_usage_weekly_chart')
		|| has_shortcode($post->post_content, 'disk_usage_daily_chart')
		|| has_shortcode($post->post_content, 'disk_usage_weekly_chart')
		|| has_shortcode($post->post_content, 'xmpp_s2s_out_daily_chart')
		|| has_shortcode($post->post_content, 'xmpp_s2s_out_weekly_chart')
		|| has_shortcode($post->post_content, 'xmpp_s2s_in_daily_chart')
		|| has_shortcode($post->post_content, 'xmpp_s2s_in_weekly_chart')
	)) {
		wp_enqueue_style('xmpp-stats', XMPP_STATS_DIR_URL.'css/style.min.css', array(), XMPP_STATS_VERSION, 'all');
		wp_enqueue_script('canvaswrapper', XMPP_STATS_DIR_URL.'js/flot/jquery.canvaswrapper.js', array('jquery'), '4.2.3', true);
		wp_enqueue_script('colorhelpers', XMPP_STATS_DIR_URL.'js/flot/jquery.colorhelpers.js', array('jquery'), '4.2.3', true);
		wp_enqueue_script('flot', XMPP_STATS_DIR_URL.'js/flot/jquery.flot.js', array('jquery'), '4.2.3', true);
		wp_enqueue_script('flot-browser', XMPP_STATS_DIR_URL.'js/flot/jquery.flot.browser.js', array('flot'), '4.2.3', true);
		wp_enqueue_script('flot-drawSeries', XMPP_STATS_DIR_URL.'js/flot/jquery.flot.drawSeries.js', array('flot'), '4.2.3', true);
		wp_enqueue_script('flot-hover', XMPP_STATS_DIR_URL.'js/flot/jquery.flot.hover.js', array('flot'), '4.2.3', true);
		wp_enqueue_script('flot-resize', XMPP_STATS_DIR_URL.'js/flot/jquery.flot.resize.js', array('flot'), '4.2.3', true);
		wp_enqueue_script('flot-saturated', XMPP_STATS_DIR_URL.'js/flot/jquery.flot.saturated.js', array('flot'), '4.2.3', true);
		wp_enqueue_script('flot-time', XMPP_STATS_DIR_URL.'js/flot/jquery.flot.time.js', array('flot'), '4.2.3', true);
		wp_enqueue_script('flot-uiConstants', XMPP_STATS_DIR_URL.'js/flot/jquery.flot.uiConstants.js', array('flot'), '4.2.3', true);
		wp_enqueue_script('xmpp-stats-charts', XMPP_STATS_DIR_URL.'js/jquery.xmpp-stats-charts.min.js', array('jquery', 'flot'), XMPP_STATS_VERSION, true);
		wp_localize_script('xmpp-stats-charts', 'xmpp_stats', array(
			'rest_api' => esc_url_raw(rest_url().'xmpp-statistics/')
		));
	}
}
add_action('wp_enqueue_scripts', 'xmpp_stats_enqueue_charts_scripts');

// Online users daily chart shortcode
function xmpp_stats_online_users_daily_chart_shortcode() {
	return '<div class="xmpp-stats-chart-title">'.__('Logged in users', 'xmpp-statistics').' - '.__('by day', 'xmpp-statistics').'</div><div data-action="online-users-daily-chart" class="xmpp-stats-chart" style="max-width:'.get_option('xmpp_stats_chart_width', 440).'px; height:'.get_option('xmpp_stats_chart_height', 220).'px;"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('xmpp_online_users_daily_chart', 'xmpp_stats_online_users_daily_chart_shortcode');

// Route online users daily chart
function xmpp_stats_route_online_users_daily_chart() {
	register_rest_route('xmpp-statistics', 'online-users-daily-chart', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_online_users_daily_chart',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_online_users_daily_chart');

// Online users daily chart cache data
function xmpp_stats_online_users_daily_cache() {
	// Datebase data
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	// Get latest record
	$latest_record = $wpdb->get_row("SELECT * FROM $table_name WHERE type = '1' ORDER BY timestamp DESC");
	// Calculating oldest date
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(24*60*60));
	// Get data from the last 24 hours
	$current_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '1' AND timestamp > '$oldest' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($current_rows as $current_row) {
		// Get current timestamp
		$timestamp = strtotime($current_row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				$current_data[] = array($timestamp-1800, null);
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		$current_data[] = array($timestamp, $current_row->value);
	}
	// Calculating previous older and oldest date
	$xmpp_stats_daily_chart_mode = get_option('xmpp_stats_daily_chart_mode', 0);
	if($xmpp_stats_daily_chart_mode == 0) {
		$older = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(24*60*60));
		$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(2*24*60*60));
	} else {
		$older = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
		$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(8*24*60*60));
	}
	// Get the previous data from yesterday / last week
	$previous_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '1' AND timestamp > '$oldest' AND timestamp < '$older' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($previous_rows as $previous_row) {
		// Get current timestamp
		$timestamp = strtotime($previous_row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				if($xmpp_stats_daily_chart_mode == 0) {
					$previous_data[] = array($timestamp+(24*60*60)-1800, null);
				} else {
					$previous_data[] = array($timestamp+(7*24*60*60)-1800, null);
				}
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		if($xmpp_stats_daily_chart_mode == 0) {
			$previous_data[] = array($timestamp+(24*60*60), $previous_row->value);
		} else {
			$previous_data[] = array($timestamp+(7*24*60*60), $previous_row->value);
		}
	}
	// Save cache
	set_transient('xmpp_stats_online_users_daily_chart', array('current_data' => $current_data, 'previous_data' => $previous_data), 0);
}

// Online users daily chart data
function xmpp_stats_online_users_daily_chart() {
	// Get data from cache
	$data = get_transient('xmpp_stats_online_users_daily_chart');
	// Chart options
	$options = array(
		'xaxis' => array(
			'mode' => 'time',
			'timezone' => 'browser',
			'tickSize' => array(4, 'hour')
		),
		'yaxis' => array(
			'minTickSize' => 1,
			'tickDecimals' => 0
		),
		'series' => array(
			'lines' => array(
				'lineWidth' => 1
			),
			'shadowSize' => 0
		),
		'grid' => array(
			'clickable' => true,
			'hoverable' => true,
			'color' => get_option('xmpp_stats_chart_grid_color', '#eeeeee'),
			'borderWidth' => 1,
		)
	);
	// Response
	$response = array(
		'data' => array(
			array('color' => get_option('xmpp_stats_chart_line_color', '#71c73e'), 'previously' => '', 'at' => ' '.__('users at', 'xmpp-statistics').' ', 'data' => $data['current_data']),
			array('color' => get_option('xmpp_stats_chart_line_color2', 'rgba(0,102,179,0.3)'), 'previously' => __('Previously', 'xmpp-statistics').' ', 'at' => ' '.__('users at', 'xmpp-statistics').' ', 'data' => $data['previous_data'])
		),
		'options' => $options
	);
	// Return response
	xmpp_stats_cache_header(120);
	return rest_ensure_response($response);
}

// Online users weekly chart shortcode
function xmpp_stats_online_users_weekly_chart_shortcode() {
	return '<div class="xmpp-stats-chart-title">'.__('Logged in users', 'xmpp-statistics').' - '.__('by week', 'xmpp-statistics').'</div><div data-action="online-users-weekly-chart" class="xmpp-stats-chart" style="max-width:'.get_option('xmpp_stats_chart_width', 440).'px; height:'.get_option('xmpp_stats_chart_height', 220).'px;"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('xmpp_online_users_weekly_chart', 'xmpp_stats_online_users_weekly_chart_shortcode');

// Route online users weekly chart
function xmpp_stats_route_online_users_weekly_chart() {
	register_rest_route('xmpp-statistics', 'online-users-weekly-chart', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_online_users_weekly_chart',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_online_users_weekly_chart');

// Online users weekly chart cache data
function xmpp_stats_online_users_weekly_cache() {
	// Datebase data
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	// Get latest record
	$latest_record = $wpdb->get_row("SELECT * FROM $table_name WHERE type = '1' ORDER BY timestamp DESC");
	// Calculating oldest date
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
	// Get data from the last week
	$current_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '1' AND timestamp > '$oldest' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($current_rows as $current_row) {
		// Get current timestamp
		$timestamp = strtotime($current_row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				$current_data[] = array($timestamp-1800, null);
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		$current_data[] = array($timestamp, $current_row->value);
	}
	// Calculating previous older and oldest date
	$older = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(14*24*60*60));
	// Get data from the previous week
	$previous_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '1' AND timestamp > '$oldest' AND timestamp < '$older' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($previous_rows as $previous_row) {
		// Get current timestamp
		$timestamp = strtotime($previous_row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				$previous_data[] = array($timestamp+(7*24*60*60)-1800, null);
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		$previous_data[] = array($timestamp+(7*24*60*60), $previous_row->value);
	}
	// Save cache
	set_transient('xmpp_stats_online_users_weekly_chart', array('current_data' => $current_data, 'previous_data' => $previous_data), 0);
}

// Online users weekly chart data
function xmpp_stats_online_users_weekly_chart() {
	// Get data from cache
	$data = get_transient('xmpp_stats_online_users_weekly_chart');
	// Chart options
	$options = array(
		'xaxis' => array(
			'mode' => 'time',
			'timezone' => 'browser',
			'tickSize' => array(1, 'day'),
			'timeformat' => '%e.%m'
		),
		'yaxis' => array(
			'minTickSize' => 1,
			'tickDecimals' => 0
		),
		'series' => array(
			'lines' => array(
				'lineWidth' => 1
			),
			'shadowSize' => 0
		),
		'grid' => array(
			'clickable' => true,
			'hoverable' => true,
			'color' => get_option('xmpp_stats_chart_grid_color', '#eeeeee'),
			'borderWidth' => 1
		)
	);
	// Response
	$response = array(
		'data' => array(
			array('color' => get_option('xmpp_stats_chart_line_color', '#71c73e'), 'previously' => '', 'at' => ' '.__('users at', 'xmpp-statistics').' ', 'data' => $data['current_data']),
			array('color' => get_option('xmpp_stats_chart_line_color2', 'rgba(0,102,179,0.3)'), 'previously' => __('Previously', 'xmpp-statistics').' ', 'at' => ' '.__('users at', 'xmpp-statistics').' ', 'data' => $data['previous_data'])
		),
		'options' => $options
	);
	// Return response
	xmpp_stats_cache_header(120);
	return rest_ensure_response($response);
}

// Registered users daily chart shortcode
function xmpp_stats_registered_users_daily_chart_shortcode() {
	return '<div class="xmpp-stats-chart-title">'.__('Registered users', 'xmpp-statistics').' - '.__('by day', 'xmpp-statistics').'</div><div data-action="registered-users-daily-chart" class="xmpp-stats-chart" style="max-width:'.get_option('xmpp_stats_chart_width', 440).'px; height:'.get_option('xmpp_stats_chart_height', 220).'px;"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('xmpp_registered_users_daily_chart', 'xmpp_stats_registered_users_daily_chart_shortcode');

// Route registered users daily chart
function xmpp_stats_route_registered_users_daily_chart() {
	register_rest_route('xmpp-statistics', 'registered-users-daily-chart', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_registered_users_daily_chart',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_registered_users_daily_chart');

// Registered users daily chart cache data
function xmpp_stats_registered_users_daily_cache() {
	// Datebase data
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	// Get latest record
	$latest_record = $wpdb->get_row("SELECT * FROM $table_name WHERE type = '2' ORDER BY timestamp DESC");
	// Calculating oldest date
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(24*60*60));
	//$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(12*60*60));
	// Get data from the last 24 hours
	$rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '2' AND timestamp > '$oldest' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($rows as $index=>$row) {
		$value = $row->value;
		$prev_value = $rows[$index-1]->value??null;
		$next_value = $rows[$index+1]->value??null;
		// Get current timestamp
		$timestamp = strtotime($row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				$data[] = array(strtotime($rows[$index-1]->timestamp), $prev_value);
				$data[] = array($timestamp-1800, null);
				$is_gap = true;
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		if(($value != $prev_value) || ($value != $next_value) || ($is_gap)) {
			$is_gap = false;
			$data[] = array($timestamp, (int)$value);
		}
	}
	// Save cache
	set_transient('xmpp_stats_registered_users_daily_chart', $data, 0);
}

// Registered users daily chart data
function xmpp_stats_registered_users_daily_chart() {
	// Get data from cache
	$data = get_transient('xmpp_stats_registered_users_daily_chart');
	// Chart options
	$options = array(
		'xaxis' => array(
			'mode' => 'time',
			'timezone' => 'browser',
			'tickSize' => array(4, 'hour')
		),
		'yaxis' => array(
			'minTickSize' => 1,
			'tickDecimals' => 0
		),
		'series' => array(
			'lines' => array(
				'lineWidth' => 1
			),
			'shadowSize' => 0
		),
		'grid' => array(
			'clickable' => true,
			'hoverable' => true,
			'color' => get_option('xmpp_stats_chart_grid_color', '#eeeeee'),
			'borderWidth' => 1
		)
	);
	// Response
	$response = array(
		'data' => array(
			array('color' => get_option('xmpp_stats_chart_line_color', '#71c73e'), 'previously' => '', 'at' => ' '.__('users at', 'xmpp-statistics').' ', 'data' => $data)
		),
		'options' => $options
	);
	// Return response
	xmpp_stats_cache_header(120);
	return rest_ensure_response($response);
}

// Registered users weekly chart shortcode
function xmpp_stats_registered_users_weekly_chart_shortcode() {
	return '<div class="xmpp-stats-chart-title">'.__('Registered users', 'xmpp-statistics').' - '.__('by week', 'xmpp-statistics').'</div><div data-action="registered-users-weekly-chart" class="xmpp-stats-chart" style="max-width:'.get_option('xmpp_stats_chart_width', 440).'px; height:'.get_option('xmpp_stats_chart_height', 220).'px;"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('xmpp_registered_users_weekly_chart', 'xmpp_stats_registered_users_weekly_chart_shortcode');

// Route registered users weekly chart
function xmpp_stats_route_registered_users_weekly_chart() {
	register_rest_route('xmpp-statistics', 'registered-users-weekly-chart', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_registered_users_weekly_chart',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_registered_users_weekly_chart');

// Registered users weekly chart cache data
function xmpp_stats_registered_users_weekly_cache() {
	// Datebase data
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	// Get latest record
	$latest_record = $wpdb->get_row("SELECT * FROM $table_name WHERE type = '2' ORDER BY timestamp DESC");
	// Calculating oldest date
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
	// Get data from the last week
	$rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '2' AND timestamp > '$oldest' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($rows as $index=>$row) {
		$value = $row->value;
		$prev_value = $rows[$index-1]->value??null;
		$next_value = $rows[$index+1]->value??null;
		// Get current timestamp
		$timestamp = strtotime($row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			if($prev_timestamp>3600) {
				$data[] = array(strtotime($rows[$index-1]->timestamp), (int)$prev_value);
				$data[] = array($timestamp-1800, null);
				$is_gap = true;
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		if(($value != $prev_value) || ($value != $next_value) || ($is_gap)) {
			$is_gap = false;
			$data[] = array($timestamp, (int)$value);
		}
	}
	// Save cache
	set_transient('xmpp_stats_registered_users_weekly_chart', $data, 0);
}

// Registered users weekly chart data
function xmpp_stats_registered_users_weekly_chart() {
	// Get data from cache
	$data = get_transient('xmpp_stats_registered_users_weekly_chart');
	// Chart options
	$options = array(
		'xaxis' => array(
			'mode' => 'time',
			'timezone' => 'browser',
			'tickSize' => array(1, 'day'),
			'timeformat' => '%e.%m'
		),
		'yaxis' => array(
			'minTickSize' => 1,
			'tickDecimals' => 0
		),
		'series' => array(
			'lines' => array(
				'lineWidth' => 1
			),
			'shadowSize' => 0
		),
		'grid' => array(
			'clickable' => true,
			'hoverable' => true,
			'color' => get_option('xmpp_stats_chart_grid_color', '#eeeeee'),
			'borderWidth' => 1
		)
	);
	// Response
	$response = array(
		'data' => array(
			array('color' => get_option('xmpp_stats_chart_line_color', '#71c73e'), 'previously' => '', 'at' => ' '.__('users at', 'xmpp-statistics').' ', 'data' => $data)
		),
		'options' => $options
	);
	// Return response
	xmpp_stats_cache_header(120);
	return rest_ensure_response($response);
}

// Server uptime daily chart
function xmpp_stats_server_uptime_daily_chart_shortcode() {
	return '<div class="xmpp-stats-chart-title">'.__('XMPP server uptime', 'xmpp-statistics').' - '.__('by day', 'xmpp-statistics').'</div><div data-action="server-uptime-daily-chart" class="xmpp-stats-chart" style="max-width:'.get_option('xmpp_stats_chart_width', 440).'px; height:'.get_option('xmpp_stats_chart_height', 220).'px;"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('xmpp_uptime_daily_chart', 'xmpp_stats_server_uptime_daily_chart_shortcode');

// Route server uptime daily chart
function xmpp_stats_route_server_uptime_daily_chart() {
	register_rest_route('xmpp-statistics', 'server-uptime-daily-chart', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_server_uptime_daily_chart',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_server_uptime_daily_chart');

// Server uptime daily chart cache data
function xmpp_stats_server_uptime_daily_cache() {
	// Datebase data
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	// Get latest record
	$latest_record = $wpdb->get_row("SELECT * FROM $table_name WHERE type = '5' ORDER BY timestamp DESC");
	// Calculating oldest date
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(24*60*60));
	// Get data from the last 24 hours
	$rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '5' AND timestamp > '$oldest' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($rows as $index=>$row) {
		$value = $row->value;
		$prev_value = $rows[$index-1]->value??null;
		$next_value = $rows[$index+1]->value??null;
		// Get current timestamp
		$timestamp = strtotime($row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				$data[] = array(strtotime($rows[$index-1]->timestamp), $prev_value/(60*60*24));
				$data[] = array($timestamp-1800, null);
				$is_gap = true;
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		if((!$prev_value) || ($prev_value > $value) || ($value > $next_value) || ($is_gap)) {
			$is_gap = false;
			$data[] = array($timestamp, $value/(60*60*24));
		}
	}
	// Save cache
	set_transient('xmpp_stats_xmpp_uptime_daily_chart', $data, 0);
}

// Server uptime daily chart data
function xmpp_stats_server_uptime_daily_chart() {
	// Get data from cache
	$data = get_transient('xmpp_stats_xmpp_uptime_daily_chart');
	// Chart options
	$options = array(
		'xaxis' => array(
			'mode' => 'time',
			'timezone' => 'browser',
			'tickSize' => array(4, 'hour')
		),
		'yaxis' => array(
			'minTickSize' => 1,
			'tickDecimals' => 0
		),
		'series' => array(
			'lines' => array(
				'lineWidth' => 0,
				'fill' => true
			),
			'shadowSize' => 0
		),
		'grid' => array(
			'color' => get_option('xmpp_stats_chart_grid_color', '#eeeeee'),
			'borderWidth' => 1
		)
	);
	// Response
	$response = array(
		'data' => array(
			array('color' => get_option('xmpp_stats_chart_line_color', '#71c73e'), 'previously' => '', 'at' => '', 'data' => $data)
		),
		'options' => $options
	);
	// Return response
	xmpp_stats_cache_header(120);
	return rest_ensure_response($response);
}

// Server uptime weekly chart shortcode
function xmpp_stats_server_uptime_weekly_chart_shortcode() {
	return '<div class="xmpp-stats-chart-title">'.__('XMPP server uptime', 'xmpp-statistics').' - '.__('by week', 'xmpp-statistics').'</div><div data-action="server-uptime-weekly-chart" class="xmpp-stats-chart" style="max-width:'.get_option('xmpp_stats_chart_width', 440).'px; height:'.get_option('xmpp_stats_chart_height', 220).'px;"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('xmpp_uptime_weekly_chart', 'xmpp_stats_server_uptime_weekly_chart_shortcode');

// Route server uptime weekly chart
function xmpp_stats_route_server_uptime_weekly_chart() {
	register_rest_route('xmpp-statistics', 'server-uptime-weekly-chart', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_server_uptime_weekly_chart',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_server_uptime_weekly_chart');

// Server uptime weekly chart cache data
function xmpp_stats_server_uptime_weekly_cache() {
	// Datebase data
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	// Get latest record
	$latest_record = $wpdb->get_row("SELECT * FROM $table_name WHERE type = '5' ORDER BY timestamp DESC");
	// Calculating oldest date
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
	// Get data from the last week
	$rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '5' AND timestamp > '$oldest' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($rows as $index=>$row) {
		$value = $row->value;
		$prev_value = $rows[$index-1]->value??null;
		$next_value = $rows[$index+1]->value??null;
		// Get current timestamp
		$timestamp = strtotime($row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				$data[] = array(strtotime($rows[$index-1]->timestamp), $prev_value/(60*60*24));
				$data[] = array($timestamp-1800, null);
				$is_gap = true;
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		if((!$prev_value) || ($prev_value > $value) || ($value > $next_value) || ($is_gap)) {
			$is_gap = false;
			$data[] = array($timestamp, $value/(60*60*24));
		}
	}
	// Save cache
	set_transient('xmpp_stats_xmpp_uptime_weekly_chart', $data, 0);
}

// Server uptime weekly chart data
function xmpp_stats_server_uptime_weekly_chart() {
	// Get data from cache
	$data = get_transient('xmpp_stats_xmpp_uptime_weekly_chart');
	// Chart options
	$options = array(
		'xaxis' => array(
			'mode' => 'time',
			'timezone' => 'browser',
			'tickSize' => array(1, 'day'),
			'timeformat' => '%e.%m'
		),
		'yaxis' => array(
			'minTickSize' => 1,
			'tickDecimals' => 0
		),
		'series' => array(
			'lines' => array(
				'lineWidth' => 0,
				'fill' => true
			),
			'shadowSize' => 0
		),
		'grid' => array(
			'color' => get_option('xmpp_stats_chart_grid_color', '#eeeeee'),
			'borderWidth' => 1
		)
	);
	// Response
	$response = array(
		'data' => array(
			array('color' => get_option('xmpp_stats_chart_line_color', '#71c73e'), 'previously' => '', 'at' => '', 'data' => $data)
		),
		'options' => $options
	);
	// Return response
	xmpp_stats_cache_header(120);
	return rest_ensure_response($response);
}

// System uptime daily chart
function xmpp_stats_system_uptime_daily_chart_shortcode() {
	return '<div class="xmpp-stats-chart-title">'.__('System uptime', 'xmpp-statistics').' - '.__('by day', 'xmpp-statistics').'</div><div data-action="system-uptime-daily-chart" class="xmpp-stats-chart" style="max-width:'.get_option('xmpp_stats_chart_width', 440).'px; height:'.get_option('xmpp_stats_chart_height', 220).'px;"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('system_uptime_daily_chart', 'xmpp_stats_system_uptime_daily_chart_shortcode');

// Route system uptime daily chart
function xmpp_stats_route_system_uptime_daily_chart() {
	register_rest_route('xmpp-statistics', 'system-uptime-daily-chart', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_system_uptime_daily_chart',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_system_uptime_daily_chart');

// System uptime daily chart cache data
function xmpp_stats_system_uptime_daily_cache() {
	// Datebase data
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	// Get latest record
	$latest_record = $wpdb->get_row("SELECT * FROM $table_name WHERE type = '6' ORDER BY timestamp DESC");
	// Calculating oldest date
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(24*60*60));
	// Get data from the last 24 hours
	$rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '6' AND timestamp > '$oldest' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($rows as $index=>$row) {
		$value = $row->value;
		$prev_value = $rows[$index-1]->value??null;
		$next_value = $rows[$index+1]->value??null;
		// Get current timestamp
		$timestamp = strtotime($row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				$data[] = array(strtotime($rows[$index-1]->timestamp), $prev_value/(60*60*24));
				$data[] = array($timestamp-1800, null);
				$is_gap = true;
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		if((!$prev_value) || ($prev_value > $value) || ($value > $next_value) || ($is_gap)) {
			$is_gap = false;
			$data[] = array($timestamp, $value/(60*60*24));
		}
	}
	// Save cache
	set_transient('xmpp_stats_system_uptime_daily_chart', $data, 0);
}

// System uptime daily chart data
function xmpp_stats_system_uptime_daily_chart() {
	// Get data from cache
	$data = get_transient('xmpp_stats_system_uptime_daily_chart');
	// Chart options
	$options = array(
		'xaxis' => array(
			'mode' => 'time',
			'timezone' => 'browser',
			'tickSize' => array(4, 'hour')
		),
		'yaxis' => array(
			'minTickSize' => 1,
			'tickDecimals' => 0
		),
		'series' => array(
			'lines' => array(
				'lineWidth' => 0,
				'fill' => true
			),
			'shadowSize' => 0
		),
		'grid' => array(
			'color' => get_option('xmpp_stats_chart_grid_color', '#eeeeee'),
			'borderWidth' => 1
		)
	);
	// Response
	$response = array(
		'data' => array(
			array('color' => get_option('xmpp_stats_chart_line_color', '#71c73e'), 'previously' => '', 'at' => '', 'data' => $data)
		),
		'options' => $options
	);
	// Return response
	xmpp_stats_cache_header(120);
	return rest_ensure_response($response);
}

// System uptime weekly chart shortcode
function xmpp_stats_system_uptime_weekly_chart_shortcode() {
	return '<div class="xmpp-stats-chart-title">'.__('System uptime', 'xmpp-statistics').' - '.__('by week', 'xmpp-statistics').'</div><div data-action="system-uptime-weekly-chart" class="xmpp-stats-chart" style="max-width:'.get_option('xmpp_stats_chart_width', 440).'px; height:'.get_option('xmpp_stats_chart_height', 220).'px;"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('system_uptime_weekly_chart', 'xmpp_stats_system_uptime_weekly_chart_shortcode');

// Route system uptime weekly chart
function xmpp_stats_route_system_uptime_weekly_chart() {
	register_rest_route('xmpp-statistics', 'system-uptime-weekly-chart', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_system_uptime_weekly_chart',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_system_uptime_weekly_chart');

// System uptime weekly chart cache data
function xmpp_stats_system_uptime_weekly_cache() {
	// Datebase data
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	// Get latest record
	$latest_record = $wpdb->get_row("SELECT * FROM $table_name WHERE type = '6' ORDER BY timestamp DESC");
	// Calculating oldest date
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
	// Get data from the last week
	$rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '6' AND timestamp > '$oldest' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($rows as $index=>$row) {
		$value = $row->value;
		$prev_value = $rows[$index-1]->value??null;
		$next_value = $rows[$index+1]->value??null;
		// Get current timestamp
		$timestamp = strtotime($row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				$data[] = array(strtotime($rows[$index-1]->timestamp), $prev_value/(60*60*24));
				$data[] = array($timestamp-1800, null);
				$is_gap = true;
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		if((!$prev_value) || ($prev_value > $value) || ($value > $next_value) || ($is_gap)) {
			$is_gap = false;
			$data[] = array($timestamp, $value/(60*60*24));
		}
	}
	// Save cache
	set_transient('xmpp_stats_system_uptime_weekly_chart', $data, 0);
}

// System uptime weekly chart data
function xmpp_stats_system_uptime_weekly_chart() {
	// Get data from cache
	$data = get_transient('xmpp_stats_system_uptime_weekly_chart');
	// Chart options
	$options = array(
		'xaxis' => array(
			'mode' => 'time',
			'timezone' => 'browser',
			'tickSize' => array(1, 'day'),
			'timeformat' => '%e.%m'
		),
		'yaxis' => array(
			'minTickSize' => 1,
			'tickDecimals' => 0
		),
		'series' => array(
			'lines' => array(
				'lineWidth' => 0,
				'fill' => true
			),
			'shadowSize' => 0
		),
		'grid' => array(
			'color' => get_option('xmpp_stats_chart_grid_color', '#eeeeee'),
			'borderWidth' => 1
		)
	);
	// Response
	$response = array(
		'data' => array(
			array('color' => get_option('xmpp_stats_chart_line_color', '#71c73e'), 'previously' => '', 'at' => '', 'data' => $data)
		),
		'options' => $options
	);
	// Return response
	xmpp_stats_cache_header(120);
	return rest_ensure_response($response);
}

// System memory usage daily chart shortcode
function xmpp_stats_memory_usage_daily_chart_shortcode() {
	return '<div class="xmpp-stats-chart-title">'.__('RAM memory usage', 'xmpp-statistics').' - '.__('by day', 'xmpp-statistics').'</div><div data-action="system-memory-usage-daily-chart" class="xmpp-stats-chart" style="max-width:'.get_option('xmpp_stats_chart_width', 440).'px; height:'.get_option('xmpp_stats_chart_height', 220).'px;"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('memory_usage_daily_chart', 'xmpp_stats_memory_usage_daily_chart_shortcode');

// Route system memory usage daily chart
function xmpp_stats_route_memory_usage_daily_chart() {
	register_rest_route('xmpp-statistics', 'system-memory-usage-daily-chart', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_memory_usage_daily_chart',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_memory_usage_daily_chart');

// System memory usage daily chart cache data
function xmpp_stats_memory_usage_daily_cache() {
	// Datebase data
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	// Get latest record
	$latest_record = $wpdb->get_row("SELECT * FROM $table_name WHERE type = '7' ORDER BY timestamp DESC");
	// Calculating oldest date
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(24*60*60));
	// Get data from the last 24 hours
	$current_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '7' AND timestamp > '$oldest' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($current_rows as $current_row) {
		// Get current timestamp
		$timestamp = strtotime($current_row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				$current_data[] = array($timestamp-1800, null);
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		$current_data[] = array($timestamp, (int)$current_row->value);
	}
	// Calculating previous older and oldest date
	$xmpp_stats_daily_chart_mode = get_option('xmpp_stats_daily_chart_mode', 0);
	if($xmpp_stats_daily_chart_mode == 0) {
		$older = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(24*60*60));
		$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(2*24*60*60));
	} else {
		$older = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
		$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(8*24*60*60));
	}
	// Get the previous data from yesterday / last week
	$previous_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '7' AND timestamp > '$oldest' AND timestamp < '$older' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($previous_rows as $previous_row) {
		// Get current timestamp
		$timestamp = strtotime($previous_row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				if($xmpp_stats_daily_chart_mode == 0) {
					$previous_data[] = array($timestamp+(24*60*60)-1800, null);
				} else {
					$previous_data[] = array($timestamp+(7*24*60*60)-1800, null);
				}
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		if($xmpp_stats_daily_chart_mode == 0) {
			$previous_data[] = array($timestamp+(24*60*60), (int)$previous_row->value);
		} else {
			$previous_data[] = array($timestamp+(7*24*60*60), (int)$previous_row->value);
		}
	}
	// Save cache
	set_transient('xmpp_stats_memory_usage_daily_chart', array('current_data' => $current_data, 'previous_data' => $previous_data), 0);
}

// System memory usage daily chart data
function xmpp_stats_memory_usage_daily_chart() {
	// Get data from cache
	$data = get_transient('xmpp_stats_memory_usage_daily_chart');
	// Chart options
	$options = array(
		'xaxis' => array(
			'mode' => 'time',
			'timezone' => 'browser',
			'tickSize' => array(4, 'hour')
		),
		'yaxis' => array(
			'minTickSize' => 1,
			'tickDecimals' => 0
		),
		'series' => array(
			'lines' => array(
				'lineWidth' => 1
			),
			'shadowSize' => 0
		),
		'grid' => array(
			'clickable' => true,
			'hoverable' => true,
			'color' => get_option('xmpp_stats_chart_grid_color', '#eeeeee'),
			'borderWidth' => 1
		)
	);
	// Response
	$response = array(
		'data' => array(
			array('color' => get_option('xmpp_stats_chart_line_color', '#71c73e'), 'previously' => '', 'at' => __('MB at', 'xmpp-statistics').' ', 'data' => $data['current_data']),
			array('color' => get_option('xmpp_stats_chart_line_color2', 'rgba(0,102,179,0.3)'), 'previously' => __('Previously', 'xmpp-statistics').' ', 'at' => __('MB at', 'xmpp-statistics').' ', 'data' => $data['previous_data'])
		),
		'options' => $options
	);
	// Return response
	xmpp_stats_cache_header(120);
	return rest_ensure_response($response);
}

// System memory usage weekly chart shortcode
function xmpp_stats_memory_usage_weekly_chart_shortcode() {
	return '<div class="xmpp-stats-chart-title">'.__('RAM memory usage', 'xmpp-statistics').' - '.__('by week', 'xmpp-statistics').'</div><div data-action="system-memory-usage-weekly-chart" class="xmpp-stats-chart" style="max-width:'.get_option('xmpp_stats_chart_width', 440).'px; height:'.get_option('xmpp_stats_chart_height', 220).'px;"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('memory_usage_weekly_chart', 'xmpp_stats_memory_usage_weekly_chart_shortcode');

// Route memory usage weekly chart
function xmpp_stats_route_memory_usage_weekly_chart() {
	register_rest_route('xmpp-statistics', 'system-memory-usage-weekly-chart', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_memory_usage_weekly_chart',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_memory_usage_weekly_chart');

// Memory usage weekly chart cache data
function xmpp_stats_memory_usage_weekly_cache() {
	// Datebase data
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	// Get latest record
	$latest_record = $wpdb->get_row("SELECT * FROM $table_name WHERE type = '7' ORDER BY timestamp DESC");
	// Calculating oldest date
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
	// Get data from the last week
	$current_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '7' AND timestamp > '$oldest' ORDER BY timestamp ASC");
	// Auxiliary variables
	$lenght = count($current_rows);
	$prev_timestamp = 0;
	// Foreach data
	foreach($current_rows as $index=>$current_row) {
		if((($index+1)%6==0)||($index==$lenght-1)) {
			// Get current timestamp
			$timestamp = strtotime($current_row->timestamp);
			// Check previous timestamp
			if($prev_timestamp) {
				$prev_timestamp = $timestamp - $prev_timestamp;
				// Gaps in data
				if($prev_timestamp>3600) {
					$current_data[] = array($timestamp-1800, null);
				}
			}
			// Save current timestamp as previous
			$prev_timestamp = $timestamp;
			// Put data in array
			$current_data[] = array($timestamp, (int)$current_row->value);
		}
	};
	// Calculating previous older and oldest date
	$older = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(14*24*60*60));
	// Get data from the previous week
	$previous_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '7' AND timestamp > '$oldest' AND timestamp < '$older' ORDER BY timestamp ASC");
	// Auxiliary variables
	$lenght = count($previous_rows);
	$prev_timestamp = 0;
	// Foreach data
	foreach($previous_rows as $index=>$previous_row) {
		if((($index+1)%6==0)||($index==$lenght-1)) {
			// Get current timestamp
			$timestamp = strtotime($previous_row->timestamp);
			// Check previous timestamp
			if($prev_timestamp) {
				$prev_timestamp = $timestamp - $prev_timestamp;
				// Gaps in data
				if($prev_timestamp>3600) {
					$previous_data[] = array($timestamp+(7*24*60*60)-1800, null);
				}
			}
			// Save current timestamp as previous
			$prev_timestamp = $timestamp;
			// Put data in array
			$previous_data[] = array($timestamp+(7*24*60*60), (int)$previous_row->value);
		}
	}
	// Save cache
	set_transient('xmpp_stats_memory_usage_weekly_chart', array('current_data' => $current_data, 'previous_data' => $previous_data), 0);
}

// Memory usage weekly chart data
function xmpp_stats_memory_usage_weekly_chart() {
	// Get data from cache
	$data = get_transient('xmpp_stats_memory_usage_weekly_chart');
	// Chart options
	$options = array(
		'xaxis' => array(
			'mode' => 'time',
			'timezone' => 'browser',
			'tickSize' => array(1, 'day'),
			'timeformat' => '%e.%m'
		),
		'yaxis' => array(
			'minTickSize' => 1,
			'tickDecimals' => 0
		),
		'series' => array(
			'lines' => array(
				'lineWidth' => 1
			),
			'shadowSize' => 0
		),
		'grid' => array(
			'clickable' => true,
			'hoverable' => true,
			'color' => get_option('xmpp_stats_chart_grid_color', '#eeeeee'),
			'borderWidth' => 1
		)
	);
	// Response
	$response = array(
		'data' => array(
			array('color' => get_option('xmpp_stats_chart_line_color', '#71c73e'), 'previously' => '', 'at' => __('MB at', 'xmpp-statistics').' ', 'data' => $data['current_data']),
			array('color' => get_option('xmpp_stats_chart_line_color2', 'rgba(0,102,179,0.3)'), 'previously' => __('Previously', 'xmpp-statistics').' ', 'at' => __('MB at', 'xmpp-statistics').' ', 'data' => $data['previous_data'])
		),
		'options' => $options
	);
	// Return response
	xmpp_stats_cache_header(120);
	return rest_ensure_response($response);
}

// System disk usage daily chart shortcode
function xmpp_stats_disk_usage_daily_chart_shortcode() {
	return '<div class="xmpp-stats-chart-title">'.__('Disk usage', 'xmpp-statistics').' - '.__('by day', 'xmpp-statistics').'</div><div data-action="system-disk-usage-daily-chart" class="xmpp-stats-chart" style="max-width:'.get_option('xmpp_stats_chart_width', 440).'px; height:'.get_option('xmpp_stats_chart_height', 220).'px;"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('disk_usage_daily_chart', 'xmpp_stats_disk_usage_daily_chart_shortcode');

// Route system disk usage daily chart
function xmpp_stats_route_disk_usage_daily_chart() {
	register_rest_route('xmpp-statistics', 'system-disk-usage-daily-chart', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_disk_usage_daily_chart',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_disk_usage_daily_chart');

// System disk usage daily chart cache data
function xmpp_stats_disk_usage_daily_cache() {
	// Datebase data
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	// Get latest record
	$latest_record = $wpdb->get_row("SELECT * FROM $table_name WHERE type = '8' ORDER BY timestamp DESC");
	// Calculating oldest date
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(24*60*60));
	// Get data from the last 24 hours
	$current_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '8' AND timestamp > '$oldest' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($current_rows as $current_row) {
		// Get current timestamp
		$timestamp = strtotime($current_row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				$current_data[] = array($timestamp-1800, null);
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		$current_data[] = array($timestamp, (int)$current_row->value);
	}
	// Calculating previous older and oldest date
	$xmpp_stats_daily_chart_mode = get_option('xmpp_stats_daily_chart_mode', 0);
	if($xmpp_stats_daily_chart_mode == 0) {
		$older = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(24*60*60));
		$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(2*24*60*60));
	} else {
		$older = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
		$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(8*24*60*60));
	}
	// Get the previous data from yesterday / last week
	$previous_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '8' AND timestamp > '$oldest' AND timestamp < '$older' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($previous_rows as $previous_row) {
		// Get current timestamp
		$timestamp = strtotime($previous_row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				if($xmpp_stats_daily_chart_mode == 0) {
					$previous_data[] = array($timestamp+(24*60*60)-1800, null);
				} else {
					$previous_data[] = array($timestamp+(7*24*60*60)-1800, null);
				}
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		if($xmpp_stats_daily_chart_mode == 0) {
			$previous_data[] = array($timestamp+(24*60*60), (int)$previous_row->value);
		} else {
			$previous_data[] = array($timestamp+(7*24*60*60), (int)$previous_row->value);
		}
	}
	// Save cache
	set_transient('xmpp_stats_disk_usage_daily_chart', array('current_data' => $current_data, 'previous_data' => $previous_data), 0);
}

// System disk usage daily chart data
function xmpp_stats_disk_usage_daily_chart() {
	// Get data from cache
	$data = get_transient('xmpp_stats_disk_usage_daily_chart');
	// Chart options
	$options = array(
		'xaxis' => array(
			'mode' => 'time',
			'timezone' => 'browser',
			'tickSize' => array(4, 'hour')
		),
		'yaxis' => array(
			'minTickSize' => 1,
			'tickDecimals' => 0
		),
		'series' => array(
			'lines' => array(
				'lineWidth' => 1
			),
			'shadowSize' => 0
		),
		'grid' => array(
			'clickable' => true,
			'hoverable' => true,
			'color' => get_option('xmpp_stats_chart_grid_color', '#eeeeee'),
			'borderWidth' => 1
		)
	);
	// Response
	$response = array(
		'data' => array(
			array('color' => get_option('xmpp_stats_chart_line_color', '#71c73e'), 'previously' => '', 'at' => __('MB at', 'xmpp-statistics').' ', 'data' => $data['current_data']),
			array('color' => get_option('xmpp_stats_chart_line_color2', 'rgba(0,102,179,0.3)'), 'previously' => __('Previously', 'xmpp-statistics').' ', 'at' => __('MB at', 'xmpp-statistics').' ', 'data' => $data['previous_data'])
		),
		'options' => $options
	);
	// Return response
	xmpp_stats_cache_header(120);
	return rest_ensure_response($response);
}

// System disk usage weekly chart shortcode
function xmpp_stats_disk_usage_weekly_chart_shortcode() {
	return '<div class="xmpp-stats-chart-title">'.__('Disk usage', 'xmpp-statistics').' - '.__('by week', 'xmpp-statistics').'</div><div data-action="system-disk-usage-weekly-chart" class="xmpp-stats-chart" style="max-width:'.get_option('xmpp_stats_chart_width', 440).'px; height:'.get_option('xmpp_stats_chart_height', 220).'px;"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('disk_usage_weekly_chart', 'xmpp_stats_disk_usage_weekly_chart_shortcode');

// Route disk usage weekly chart
function xmpp_stats_route_disk_usage_weekly_chart() {
	register_rest_route('xmpp-statistics', 'system-disk-usage-weekly-chart', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_disk_usage_weekly_chart',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_disk_usage_weekly_chart');

// disk usage weekly chart cache data
function xmpp_stats_disk_usage_weekly_cache() {
	// Datebase data
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	// Get latest record
	$latest_record = $wpdb->get_row("SELECT * FROM $table_name WHERE type = '8' ORDER BY timestamp DESC");
	// Calculating oldest date
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
	// Get data from the last week
	$current_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '8' AND timestamp > '$oldest' ORDER BY timestamp ASC");
	// Auxiliary variables
	$lenght = count($current_rows);
	$prev_timestamp = 0;
	// Foreach data
	foreach($current_rows as $index=>$current_row) {
		if((($index+1)%6==0)||($index==$lenght-1)) {
			// Get current timestamp
			$timestamp = strtotime($current_row->timestamp);
			// Check previous timestamp
			if($prev_timestamp) {
				$prev_timestamp = $timestamp - $prev_timestamp;
				// Gaps in data
				if($prev_timestamp>3600) {
					$current_data[] = array($timestamp-1800, null);
				}
			}
			// Save current timestamp as previous
			$prev_timestamp = $timestamp;
			// Put data in array
			$current_data[] = array($timestamp, (int)$current_row->value);
		}
	};
	// Calculating previous older and oldest date
	$older = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(14*24*60*60));
	// Get data from the previous week
	$previous_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '8' AND timestamp > '$oldest' AND timestamp < '$older' ORDER BY timestamp ASC");
	// Auxiliary variables
	$lenght = count($previous_rows);
	$prev_timestamp = 0;
	// Foreach data
	foreach($previous_rows as $index=>$previous_row) {
		if((($index+1)%6==0)||($index==$lenght-1)) {
			// Get current timestamp
			$timestamp = strtotime($previous_row->timestamp);
			// Check previous timestamp
			if($prev_timestamp) {
				$prev_timestamp = $timestamp - $prev_timestamp;
				// Gaps in data
				if($prev_timestamp>3600) {
					$previous_data[] = array($timestamp+(7*24*60*60)-1800, null);
				}
			}
			// Save current timestamp as previous
			$prev_timestamp = $timestamp;
			// Put data in array
			$previous_data[] = array($timestamp+(7*24*60*60), (int)$previous_row->value);
		}
	}
	// Save cache
	set_transient('xmpp_stats_disk_usage_weekly_chart', array('current_data' => $current_data, 'previous_data' => $previous_data), 0);
}

// disk usage weekly chart data
function xmpp_stats_disk_usage_weekly_chart() {
	// Get data from cache
	$data = get_transient('xmpp_stats_disk_usage_weekly_chart');
	// Chart options
	$options = array(
		'xaxis' => array(
			'mode' => 'time',
			'timezone' => 'browser',
			'tickSize' => array(1, 'day'),
			'timeformat' => '%e.%m'
		),
		'yaxis' => array(
			'minTickSize' => 1,
			'tickDecimals' => 0
		),
		'series' => array(
			'lines' => array(
				'lineWidth' => 1
			),
			'shadowSize' => 0
		),
		'grid' => array(
			'clickable' => true,
			'hoverable' => true,
			'color' => get_option('xmpp_stats_chart_grid_color', '#eeeeee'),
			'borderWidth' => 1
		)
	);
	// Response
	$response = array(
		'data' => array(
			array('color' => get_option('xmpp_stats_chart_line_color', '#71c73e'), 'previously' => '', 'at' => __('MB at', 'xmpp-statistics').' ', 'data' => $data['current_data']),
			array('color' => get_option('xmpp_stats_chart_line_color2', 'rgba(0,102,179,0.3)'), 'previously' => __('Previously', 'xmpp-statistics').' ', 'at' => __('MB at', 'xmpp-statistics').' ', 'data' => $data['previous_data'])
		),
		'options' => $options
	);
	// Return response
	xmpp_stats_cache_header(120);
	return rest_ensure_response($response);
}

// S2S outgoing connections daily chart shortcode
function xmpp_stats_s2s_out_daily_chart_shortcode() {
	return '<div class="xmpp-stats-chart-title">'.__('Outgoing S2S connections', 'xmpp-statistics').' - '.__('by day', 'xmpp-statistics').'</div><div data-action="s2s-out-daily-chart" class="xmpp-stats-chart" style="max-width:'.get_option('xmpp_stats_chart_width', 440).'px; height:'.get_option('xmpp_stats_chart_height', 220).'px;"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('xmpp_s2s_out_daily_chart', 'xmpp_stats_s2s_out_daily_chart_shortcode');

// Route S2S outgoing connections daily chart
function xmpp_stats_route_s2s_out_daily_chart() {
	register_rest_route('xmpp-statistics', 's2s-out-daily-chart', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_s2s_out_daily_chart',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_s2s_out_daily_chart');

// S2S outgoing connections daily chart cache data
function xmpp_stats_s2s_out_daily_cache() {
	// Datebase data
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	// Get latest record
	$latest_record = $wpdb->get_row("SELECT * FROM $table_name WHERE type = '3' ORDER BY timestamp DESC");
	// Calculating oldest date
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(24*60*60));
	// Get data from the last 24 hours
	$current_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '3' AND timestamp > '$oldest' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($current_rows as $current_row) {
		// Get current timestamp
		$timestamp = strtotime($current_row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				$current_data[] = array($timestamp-1800, null);
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		$current_data[] = array($timestamp, (int)$current_row->value);
	}
	// Calculating previous older and oldest date
	$xmpp_stats_daily_chart_mode = get_option('xmpp_stats_daily_chart_mode', 0);
	if($xmpp_stats_daily_chart_mode == 0) {
		$older = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(24*60*60));
		$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(2*24*60*60));
	} else {
		$older = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
		$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(8*24*60*60));
	}
	// Get the previous data from yesterday / last week
	$previous_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '3' AND timestamp > '$oldest' AND timestamp < '$older' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($previous_rows as $previous_row) {
		// Get current timestamp
		$timestamp = strtotime($previous_row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				if($xmpp_stats_daily_chart_mode == 0) {
					$previous_data[] = array($timestamp+(24*60*60)-1800, null);
				} else {
					$previous_data[] = array($timestamp+(7*24*60*60)-1800, null);
				}
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		if($xmpp_stats_daily_chart_mode == 0) {
			$previous_data[] = array($timestamp+(24*60*60), (int)$previous_row->value);
		} else {
			$previous_data[] = array($timestamp+(7*24*60*60), (int)$previous_row->value);
		}
	}
	// Save cache
	set_transient('xmpp_stats_s2s_out_daily_chart', array('current_data' => $current_data, 'previous_data' => $previous_data), 0);
}

// S2S outgoing connections daily chart data
function xmpp_stats_s2s_out_daily_chart() {
	// Get data from cache
	$data = get_transient('xmpp_stats_s2s_out_daily_chart');
	// Chart options
	$options = array(
		'xaxis' => array(
			'mode' => 'time',
			'timezone' => 'browser',
			'tickSize' => array(4, 'hour')
		),
		'yaxis' => array(
			'minTickSize' => 1,
			'tickDecimals' => 0
		),
		'series' => array(
			'lines' => array(
				'lineWidth' => 1
			),
			'shadowSize' => 0
		),
		'grid' => array(
			'clickable' => true,
			'hoverable' => true,
			'color' => get_option('xmpp_stats_chart_grid_color', '#eeeeee'),
			'borderWidth' => 1
		)
	);
	// Response
	$response = array(
		'data' => array(
			array('color' => get_option('xmpp_stats_chart_line_color', '#71c73e'), 'previously' => '', 'at' => ' '.__('outgoing connections at', 'xmpp-statistics').' ', 'data' => $data['current_data']),
			array('color' => get_option('xmpp_stats_chart_line_color2', 'rgba(0,102,179,0.3)'), 'previously' => __('Previously', 'xmpp-statistics').' ', 'at' => ' '.__('outgoing connections at', 'xmpp-statistics').' ', 'data' => $data['previous_data'])
		),
		'options' => $options
	);
	// Return response
	xmpp_stats_cache_header(120);
	return rest_ensure_response($response);
}

// S2S outgoing connections weekly chart shortcode
function xmpp_stats_s2s_out_weekly_chart_shortcode() {
	return '<div class="xmpp-stats-chart-title">'.__('Outgoing S2S connections', 'xmpp-statistics').' - '.__('by week', 'xmpp-statistics').'</div><div data-action="s2s-out-weekly-chart" class="xmpp-stats-chart" style="max-width:'.get_option('xmpp_stats_chart_width', 440).'px; height:'.get_option('xmpp_stats_chart_height', 220).'px;"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('xmpp_s2s_out_weekly_chart', 'xmpp_stats_s2s_out_weekly_chart_shortcode');

// Route S2S outgoing connections weekly chart
function xmpp_stats_route_s2s_out_weekly_chart() {
	register_rest_route('xmpp-statistics', 's2s-out-weekly-chart', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_s2s_out_weekly_chart',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_s2s_out_weekly_chart');

// S2S outgoing connections weekly chart cache data
function xmpp_stats_s2s_out_weekly_cache() {
	// Datebase data
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	// Get latest record
	$latest_record = $wpdb->get_row("SELECT * FROM $table_name WHERE type = '3' ORDER BY timestamp DESC");
	// Calculating oldest date
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
	// Get data from the last week
	$current_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '3' AND timestamp > '$oldest' ORDER BY timestamp ASC");
	// Auxiliary variables
	$lenght = count($current_rows);
	$prev_timestamp = 0;
	// Foreach data
	foreach($current_rows as $index=>$current_row) {
		if((($index+1)%6==0)||($index==$lenght-1)) {
			// Get current timestamp
			$timestamp = strtotime($current_row->timestamp);
			// Check previous timestamp
			if($prev_timestamp) {
				$prev_timestamp = $timestamp - $prev_timestamp;
				// Gaps in data
				if($prev_timestamp>3600) {
					$current_data[] = array($timestamp-1800, null);
				}
			}
			// Save current timestamp as previous
			$prev_timestamp = $timestamp;
			// Put data in array
			$current_data[] = array($timestamp, (int)$current_row->value);
		}
	};
	// Calculating previous older and oldest date
	$older = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(14*24*60*60));
	// Get data from the previous week
	$previous_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '3' AND timestamp > '$oldest' AND timestamp < '$older' ORDER BY timestamp ASC");
	// Auxiliary variables
	$lenght = count($previous_rows);
	$prev_timestamp = 0;
	// Foreach data
	foreach($previous_rows as $index=>$previous_row) {
		if((($index+1)%6==0)||($index==$lenght-1)) {
			// Get current timestamp
			$timestamp = strtotime($previous_row->timestamp);
			// Check previous timestamp
			if($prev_timestamp) {
				$prev_timestamp = $timestamp - $prev_timestamp;
				// Gaps in data
				if($prev_timestamp>3600) {
					$previous_data[] = array($timestamp+(7*24*60*60)-1800, null);
				}
			}
			// Save current timestamp as previous
			$prev_timestamp = $timestamp;
			// Put data in array
			$previous_data[] = array($timestamp+(7*24*60*60), (int)$previous_row->value);
		}
	}
	// Save cache
	set_transient('xmpp_stats_s2s_out_weekly_chart', array('current_data' => $current_data, 'previous_data' => $previous_data), 0);
}

// S2S outgoing connections weekly chart data
function xmpp_stats_s2s_out_weekly_chart() {
	// Get data from cache
	$data = get_transient('xmpp_stats_s2s_out_weekly_chart');
	// Chart options
	$options = array(
		'xaxis' => array(
			'mode' => 'time',
			'timezone' => 'browser',
			'tickSize' => array(1, 'day'),
			'timeformat' => '%e.%m'
		),
		'yaxis' => array(
			'minTickSize' => 1,
			'tickDecimals' => 0
		),
		'series' => array(
			'lines' => array(
				'lineWidth' => 1
			),
			'shadowSize' => 0
		),
		'grid' => array(
			'clickable' => true,
			'hoverable' => true,
			'color' => get_option('xmpp_stats_chart_grid_color', '#eeeeee'),
			'borderWidth' => 1
		)
	);
	// Response
	$response = array(
		'data' => array(
			array('color' => get_option('xmpp_stats_chart_line_color', '#71c73e'), 'previously' => '', 'at' => ' '.__('outgoing connections at', 'xmpp-statistics').' ', 'data' => $data['current_data']),
			array('color' => get_option('xmpp_stats_chart_line_color2', 'rgba(0,102,179,0.3)'), 'previously' => __('Previously', 'xmpp-statistics').' ', 'at' => ' '.__('outgoing connections at', 'xmpp-statistics').' ', 'data' => $data['previous_data'])
		),
		'options' => $options
	);
	// Return response
	xmpp_stats_cache_header(120);
	return rest_ensure_response($response);
}

// S2S incoming connections daily chart shortcode
function xmpp_stats_s2s_in_daily_chart_shortcode() {
	return '<div class="xmpp-stats-chart-title">'.__('Incoming S2S connections', 'xmpp-statistics').' - '.__('by day', 'xmpp-statistics').'</div><div data-action="s2s-in-daily-chart" class="xmpp-stats-chart" style="max-width:'.get_option('xmpp_stats_chart_width', 440).'px; height:'.get_option('xmpp_stats_chart_height', 220).'px;"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('xmpp_s2s_in_daily_chart', 'xmpp_stats_s2s_in_daily_chart_shortcode');

// Route S2S incoming connections daily chart
function xmpp_stats_route_s2s_in_daily_chart() {
	register_rest_route('xmpp-statistics', 's2s-in-daily-chart', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_s2s_in_daily_chart',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_s2s_in_daily_chart');

// S2S incoming connections daily chart cache data
function xmpp_stats_s2s_in_daily_cache() {
	// Datebase data
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	// Get latest record
	$latest_record = $wpdb->get_row("SELECT * FROM $table_name WHERE type = '4' ORDER BY timestamp DESC");
	// Calculating oldest date
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(24*60*60));
	// Get data from the last 24 hours
	$current_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '4' AND timestamp > '$oldest' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($current_rows as $current_row) {
		// Get current timestamp
		$timestamp = strtotime($current_row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				$current_data[] = array($timestamp-1800, null);
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		$current_data[] = array($timestamp, (int)$current_row->value);
	}
	// Calculating previous older and oldest date
	$xmpp_stats_daily_chart_mode = get_option('xmpp_stats_daily_chart_mode', 0);
	if($xmpp_stats_daily_chart_mode == 0) {
		$older = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(24*60*60));
		$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(2*24*60*60));
	} else {
		$older = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
		$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(8*24*60*60));
	}
	// Get the previous data from yesterday / last week
	$previous_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '4' AND timestamp > '$oldest' AND timestamp < '$older' ORDER BY timestamp ASC");
	// Auxiliary variables
	$prev_timestamp = 0;
	// Foreach data
	foreach($previous_rows as $previous_row) {
		// Get current timestamp
		$timestamp = strtotime($previous_row->timestamp);
		// Check previous timestamp
		if($prev_timestamp) {
			$prev_timestamp = $timestamp - $prev_timestamp;
			// Gaps in data
			if($prev_timestamp>3600) {
				if($xmpp_stats_daily_chart_mode == 0) {
					$previous_data[] = array($timestamp+(24*60*60)-1800, null);
				} else {
					$previous_data[] = array($timestamp+(7*24*60*60)-1800, null);
				}
			}
		}
		// Save current timestamp as previous
		$prev_timestamp = $timestamp;
		// Put data in array
		if($xmpp_stats_daily_chart_mode == 0) {
			$previous_data[] = array($timestamp+(24*60*60), (int)$previous_row->value);
		} else {
			$previous_data[] = array($timestamp+(7*24*60*60), (int)$previous_row->value);
		}
	}
	// Save cache
	set_transient('xmpp_stats_s2s_in_daily_chart', array('current_data' => $current_data, 'previous_data' => $previous_data), 0);
}

// S2S incoming connections daily chart data
function xmpp_stats_s2s_in_daily_chart() {
	// Get data from cache
	$data = get_transient('xmpp_stats_s2s_in_daily_chart');
	// Chart options
	$options = array(
		'xaxis' => array(
			'mode' => 'time',
			'timezone' => 'browser',
			'tickSize' => array(4, 'hour')
		),
		'yaxis' => array(
			'minTickSize' => 1,
			'tickDecimals' => 0
		),
		'series' => array(
			'lines' => array(
				'lineWidth' => 1
			),
			'shadowSize' => 0
		),
		'grid' => array(
			'clickable' => true,
			'hoverable' => true,
			'color' => get_option('xmpp_stats_chart_grid_color', '#eeeeee'),
			'borderWidth' => 1
		)
	);
	// Response
	$response = array(
		'data' => array(
			array('color' => get_option('xmpp_stats_chart_line_color', '#71c73e'), 'previously' => '', 'at' => ' '.__('incoming connections at', 'xmpp-statistics').' ', 'data' => $data['current_data']),
			array('color' => get_option('xmpp_stats_chart_line_color2', 'rgba(0,102,179,0.3)'), 'previously' => __('Previously', 'xmpp-statistics').' ', 'at' => ' '.__('incoming connections at', 'xmpp-statistics').' ', 'data' => $data['previous_data'])
		),
		'options' => $options
	);
	// Return response
	xmpp_stats_cache_header(120);
	return rest_ensure_response($response);
}

// S2S incoming connections weekly chart shortcode
function xmpp_stats_s2s_in_weekly_chart_shortcode() {
	return '<div class="xmpp-stats-chart-title">'.__('Incoming S2S connections', 'xmpp-statistics').' - '.__('by week', 'xmpp-statistics').'</div><div data-action="s2s-in-weekly-chart" class="xmpp-stats-chart" style="max-width:'.get_option('xmpp_stats_chart_width', 440).'px; height:'.get_option('xmpp_stats_chart_height', 220).'px;"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('xmpp_s2s_in_weekly_chart', 'xmpp_stats_s2s_in_weekly_chart_shortcode');

// Route S2S incoming connections weekly chart
function xmpp_stats_route_s2s_in_weekly_chart() {
	register_rest_route('xmpp-statistics', 's2s-in-weekly-chart', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_s2s_in_weekly_chart',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_s2s_in_weekly_chart');

// S2S incoming connections weekly chart cache data
function xmpp_stats_s2s_in_weekly_cache() {
	// Datebase data
	global $wpdb;
	$table_name = $wpdb->prefix . 'xmpp_stats';
	// Get latest record
	$latest_record = $wpdb->get_row("SELECT * FROM $table_name WHERE type = '4' ORDER BY timestamp DESC");
	// Calculating oldest date
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
	// Get data from the last week
	$current_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '4' AND timestamp > '$oldest' ORDER BY timestamp ASC");
	// Auxiliary variables
	$lenght = count($current_rows);
	$prev_timestamp = 0;
	// Foreach data
	foreach($current_rows as $index=>$current_row) {
		if((($index+1)%6==0)||($index==$lenght-1)) {
			// Get current timestamp
			$timestamp = strtotime($current_row->timestamp);
			// Check previous timestamp
			if($prev_timestamp) {
				$prev_timestamp = $timestamp - $prev_timestamp;
				// Gaps in data
				if($prev_timestamp>3600) {
					$current_data[] = array($timestamp-1800, null);
				}
			}
			// Save current timestamp as previous
			$prev_timestamp = $timestamp;
			// Put data in array
			$current_data[] = array($timestamp, (int)$current_row->value);
		}
	}
	// Calculating previous older and oldest date
	$older = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(7*24*60*60));
	$oldest = date('Y-m-d H:i:s', strtotime($latest_record->timestamp)-(14*24*60*60));
	// Get data from the previous week
	$previous_rows = $wpdb->get_results("SELECT * FROM $table_name WHERE type = '4' AND timestamp > '$oldest' AND timestamp < '$older' ORDER BY timestamp ASC");
	// Auxiliary variables
	$lenght = count($previous_rows);
	$prev_timestamp = 0;
	// Foreach data
	foreach($previous_rows as $index=>$previous_row) {
		if((($index+1)%6==0)||($index==$lenght-1)) {
			// Get current timestamp
			$timestamp = strtotime($previous_row->timestamp);
			// Check previous timestamp
			if($prev_timestamp) {
				$prev_timestamp = $timestamp - $prev_timestamp;
				// Gaps in data
				if($prev_timestamp>3600) {
					$previous_data[] = array($timestamp+(7*24*60*60)-1800, null);
				}
			}
			// Save current timestamp as previous
			$prev_timestamp = $timestamp;
			// Put data in array
			$previous_data[] = array($timestamp+(7*24*60*60), (int)$previous_row->value);
		}
	}
	// Save cache
	set_transient('xmpp_stats_s2s_in_weekly_chart', array('current_data' => $current_data, 'previous_data' => $previous_data), 0);
}

// S2S incoming connections weekly chart data
function xmpp_stats_s2s_in_weekly_chart() {
	// Get data from cache
	$data = get_transient('xmpp_stats_s2s_in_weekly_chart');
	// Chart options
	$options = array(
		'xaxis' => array(
			'mode' => 'time',
			'timezone' => 'browser',
			'tickSize' => array(1, 'day'),
			'timeformat' => '%e.%m'
		),
		'yaxis' => array(
			'minTickSize' => 1,
			'tickDecimals' => 0
		),
		'series' => array(
			'lines' => array(
				'lineWidth' => 1
			),
			'shadowSize' => 0
		),
		'grid' => array(
			'clickable' => true,
			'hoverable' => true,
			'color' => get_option('xmpp_stats_chart_grid_color', '#eeeeee'),
			'borderWidth' => 1
		)
	);
	// Response
	$response = array(
		'data' => array(
			array('color' => get_option('xmpp_stats_chart_line_color', '#71c73e'), 'previously' => '', 'at' => ' '.__('outgoing connections at', 'xmpp-statistics').' ', 'data' => $data['current_data']),
			array('color' => get_option('xmpp_stats_chart_line_color2', 'rgba(0,102,179,0.3)'), 'previously' => __('Previously', 'xmpp-statistics').' ', 'at' => ' '.__('outgoing connections at', 'xmpp-statistics').' ', 'data' => $data['previous_data'])
		),
		'options' => $options
	);
	// Return response
	xmpp_stats_cache_header(120);
	return rest_ensure_response($response);
}
