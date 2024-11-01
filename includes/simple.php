<?php
/**
 * Simple shortcodes
 */

if(!defined('ABSPATH')) {
	exit;
}

// Enqueue shortcodes styles & jQuery scripts
function xmpp_stats_enqueue_shortcodes_scripts() {
	global $post;
	if(is_a($post, 'WP_Post') && (
		has_shortcode($post->post_content, 'xmpp_onlineusers')
		|| has_shortcode($post->post_content, 'xmpp_registeredusers')
		|| has_shortcode($post->post_content, 'xmpp_s2s_out')
		|| has_shortcode($post->post_content, 'xmpp_s2s_in')
		|| has_shortcode($post->post_content, 'xmpp_uptime')
		|| has_shortcode($post->post_content, 'xmpp_version')
		|| has_shortcode($post->post_content, 'system_disk_usage')
		|| has_shortcode($post->post_content, 'system_version')
		|| has_shortcode($post->post_content, 'system_uptime')
		|| has_shortcode($post->post_content, 'system_memory_usage')
	)) {
		wp_enqueue_style('xmpp-stats', XMPP_STATS_DIR_URL.'css/style.min.css', array(), XMPP_STATS_VERSION, 'all');
		wp_enqueue_script('xmpp-stats', XMPP_STATS_DIR_URL.'js/js.xmpp-stats.min.js', array(), XMPP_STATS_VERSION, true);
		wp_localize_script('xmpp-stats', 'xmpp_stats', array(
			'rest_api' => esc_url_raw(rest_url().'xmpp-statistics/')
		));
	}
}
add_action('wp_enqueue_scripts', 'xmpp_stats_enqueue_shortcodes_scripts');

// Online users stats shortcode
function xmpp_stats_online_users_shortcode() {
	// Return loading information
	return '<span data-action="online-users" class="xmpp-stats"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('xmpp_onlineusers', 'xmpp_stats_online_users_shortcode');

// Route online users stats
function xmpp_stats_route_online_users() {
	register_rest_route('xmpp-statistics', 'online-users', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_online_users',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_online_users');

// Online users stats
function xmpp_stats_online_users() {
	return rest_ensure_response(array('stat' => xmpp_stats_restapi_data('stats', array('name' => 'onlineusers'))));
}

// Registered users shortcode
function xmpp_stats_registered_users_shortcode() {
	// Return loading information
	return '<span data-action="registered-users" class="xmpp-stats"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('xmpp_registeredusers', 'xmpp_stats_registered_users_shortcode');

// Route registered users stats
function xmpp_stats_route_registered_users() {
	register_rest_route('xmpp-statistics', 'registered-users', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_registered_users',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_registered_users');

// Registered users stats
function xmpp_stats_registered_users() {
	return rest_ensure_response(array('stat' => xmpp_stats_restapi_data('stats', array('name' => 'registeredusers'))));
}

// Outgoing s2s connections shortcode
function xmpp_stats_s2s_out_shortcode() {
	// Return loading information
	return '<span data-action="s2s-out" class="xmpp-stats"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('xmpp_s2s_out', 'xmpp_stats_s2s_out_shortcode');

// Route outgoing s2s connections stats
function xmpp_stats_route_s2s_out() {
	register_rest_route('xmpp-statistics', 's2s-out', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_s2s_out',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_s2s_out');

// Outgoing s2s connections stats
function xmpp_stats_s2s_out() {
	return rest_ensure_response(array('stat' => xmpp_stats_restapi_data('outgoing_s2s_number')));
}

// Incoming s2s connections shortcode
function xmpp_stats_s2s_in_shortcode() {
	// Return loading information
	return '<span data-action="s2s-in" class="xmpp-stats"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('xmpp_s2s_in', 'xmpp_stats_s2s_in_shortcode');

// Route incoming s2s connections stats
function xmpp_stats_route_s2s_in() {
	register_rest_route('xmpp-statistics', 's2s-in', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_s2s_in',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_s2s_in');

// Incoming s2s connections stats
function xmpp_stats_s2s_in() {
	return rest_ensure_response(array('stat' => xmpp_stats_restapi_data('incoming_s2s_number')));
}

// XMPP uptime shortcode
function xmpp_stats_xmpp_uptime_shortcode() {
	// Return loading information
	return '<span data-action="xmpp-uptime" class="xmpp-stats"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('xmpp_uptime', 'xmpp_stats_xmpp_uptime_shortcode');

// Route XMPP uptime stats
function xmpp_stats_route_xmpp_uptime() {
	register_rest_route('xmpp-statistics', 'xmpp-uptime', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_xmpp_uptime',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_xmpp_uptime');

// XMPP uptime stats
function xmpp_stats_xmpp_uptime() {
	return rest_ensure_response(array('stat' => xmpp_stats_seconds_to_datestamp(xmpp_stats_restapi_data('stats', array('name' => 'uptimeseconds')))));
}

// XMPP version shortcode
function xmpp_stats_xmpp_version_shortcode() {
	// Return loading information
	return '<span data-action="xmpp-version" class="xmpp-stats"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('xmpp_version', 'xmpp_stats_xmpp_version_shortcode');

// Route XMPP version stats
function xmpp_stats_route_xmpp_version() {
	register_rest_route('xmpp-statistics', 'xmpp-version', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_xmpp_version',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_xmpp_version');

// XMPP version stats
function xmpp_stats_xmpp_version() {
	preg_match('/ejabberd (.*) is running in that node/i', xmpp_stats_restapi_data('status'), $version);
	return rest_ensure_response(array('stat' => $version[1]));
}

// System disk usage shortcode
function xmpp_stats_system_disk_shortcode() {
	// Return loading information
	return '<span data-action="system-disk" class="xmpp-stats"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('system_disk_usage', 'xmpp_stats_system_disk_shortcode');

// Route system disk usage stats
function xmpp_stats_route_system_disk() {
	register_rest_route('xmpp-statistics', 'system-disk', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_system_disk',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_system_disk');

// System disk usage stats
function xmpp_stats_system_disk() {
	// Get data
	$data = xmpp_stats_restapi_data('system_disk');
	// Return response
	if(is_null($data)) return rest_ensure_response(array('stat' => null));
	else return rest_ensure_response(array('stat' => json_decode($data)->used . ' / ' . json_decode($data)->size));
}

// System version shortcode
function xmpp_stats_system_version_shortcode() {
	// Return loading information
	return '<span data-action="system-version" class="xmpp-stats"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('system_version', 'xmpp_stats_system_version_shortcode');

// Route system version stats
function xmpp_stats_route_system_version() {
	register_rest_route('xmpp-statistics', 'system-version', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_system_version',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_system_version');

// System version stats
function xmpp_stats_system_version() {
	return rest_ensure_response(array('stat' => json_decode(xmpp_stats_restapi_data('system_info'))->release));
}

// System uptime shortcode
function xmpp_stats_system_uptime_shortcode() {
	// Return loading information
	return '<span data-action="system-uptime" class="xmpp-stats"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('system_uptime', 'xmpp_stats_system_uptime_shortcode');

// Route system uptime stats
function xmpp_stats_route_system_uptime() {
	register_rest_route('xmpp-statistics', 'system-uptime', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_system_uptime',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_system_uptime');

// System uptime stats
function xmpp_stats_system_uptime() {
	return rest_ensure_response(array('stat' => xmpp_stats_seconds_to_datestamp(json_decode(xmpp_stats_restapi_data('system_uptime'))->stat)));
}

// System memory usage shortcode
function xmpp_stats_system_memory_shortcode() {
	// Return loading information
	return '<span data-action="system-memory" class="xmpp-stats"><span class="loader" title="'.__('Loading', 'xmpp-statistics').'..."></span></div>';
}
add_shortcode('system_memory_usage', 'xmpp_stats_system_memory_shortcode');

// Route system memory usage stats
function xmpp_stats_route_system_memory() {
	register_rest_route('xmpp-statistics', 'system-memory', array(
		'methods' => 'GET',
		'callback' => 'xmpp_stats_system_memory',
		'permission_callback' => '__return_true'
	));
}
add_action('rest_api_init', 'xmpp_stats_route_system_memory');

// System memory usage stats
function xmpp_stats_system_memory() {
	// Get data
	$data = xmpp_stats_restapi_data('system_memory');
	// Return response
	if(is_null($data)) return rest_ensure_response(array('stat' => null));
	else return rest_ensure_response(array('stat' => json_decode($data)->used . ' / ' . json_decode($data)->size));
}
