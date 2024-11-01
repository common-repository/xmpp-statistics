<?php
/**
 * Plugin Name: XMPP Statistics
 * Plugin URI: https://beherit.pl/en/wordpress/xmpp-statistics/
 * Description: Displays the statistics from ejabberd XMPP server through REST API.
 * Version: 1.12
 * Requires at least: 4.4
 * Requires PHP: 7.0
 * Author: Krzysztof Grochocki
 * Author URI: https://beherit.pl/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: xmpp-statistics
 */

if(!defined('ABSPATH')) {
	exit;
}

// Define variables
define('XMPP_STATS_VERSION', '1.12');
define('XMPP_STATS_BASENAME', plugin_basename(__FILE__));
define('XMPP_STATS_DIR_PATH', plugin_dir_path(__FILE__));
define('XMPP_STATS_DIR_URL', plugin_dir_url(__FILE__));

// Load necessary files
include_once XMPP_STATS_DIR_PATH.'includes/settings.php';
include_once XMPP_STATS_DIR_PATH.'includes/functions.php';
include_once XMPP_STATS_DIR_PATH.'includes/cron.php';
include_once XMPP_STATS_DIR_PATH.'includes/simple.php';
include_once XMPP_STATS_DIR_PATH.'includes/charts.php';