<?php
/**
 * Most important functions used in other files
 */

if(!defined('ABSPATH')) {
	exit;
}

// Get data by ReST API
function xmpp_stats_restapi_data($command, $arguments = array()) {
	// Perform request
	$response = wp_remote_post(get_option('xmpp_stats_rest_url').'/'.$command, array(
		'headers' => array(
			'Authorization' => empty(get_option('xmpp_stats_oauth_token')) ? 'Basic '.base64_encode(get_option('xmpp_stats_login').':'.get_option('xmpp_stats_password')) : 'Bearer '.get_option('xmpp_stats_oauth_token'),
			'Content-Type' => 'application/json'
		),
		'body' => json_encode($arguments, JSON_FORCE_OBJECT),
		'redirection' => 0,
		'httpversion' => '1.1'
	));
	// Server unavailable
	if(is_wp_error($response)) {
		return null;
	}
	// Verify response
	else if($response['response']['code'] == 200) {
		// Return response body
		return $response['body'];
	}
	// Unexpected error
	return null;
}

// Change seconds to friendly view
function xmpp_stats_seconds_to_datestamp($seconds) {
	if($seconds == 0) return null;
	$output = '';
    $divs = array(86400, 3600, 60, 1);
    for($div=0; $div<4; $div++) {
        $res = (int)($seconds / $divs[$div]);
        $rem = $seconds % $divs[$div];
        if($res != 0) $output .= sprintf('%d%s ', $res, substr('dhms', $div, 1));
        $seconds = $rem;
    }
    return trim($output);
}

// Caching Headers
function xmpp_stats_cache_header($seconds) {
	$ts = gmdate('d M Y H:i:s', time() + $seconds) . ' GMT';
	header("Expires: $ts");
	header('Pragma: cache');
	header("Cache-Control: max-age=$seconds");;
}
