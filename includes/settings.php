<?php
/**
 * General settings
 */

if(!defined('ABSPATH')) {
	exit;
}

// Register settings
function xmpp_stats_register_settings() {
	register_setting('xmpp_stats_settings', 'xmpp_stats_rest_url', array('type' => 'string', 'sanitize_callback' => 'trim'));
	register_setting('xmpp_stats_settings', 'xmpp_stats_login', array('type' => 'string', 'sanitize_email' => 'trim'));
	register_setting('xmpp_stats_settings', 'xmpp_stats_password', array('type' => 'string', 'sanitize_callback' => 'trim'));
	register_setting('xmpp_stats_settings', 'xmpp_stats_oauth_token', array('type' => 'string', 'sanitize_callback' => 'trim'));
	register_setting('xmpp_stats_settings', 'xmpp_stats_save_data', array('type' => 'boolean', 'sanitize_callback' => 'boolval'));
	register_setting('xmpp_stats_settings', 'xmpp_stats_delete_old_data', array('type' => 'boolean', 'sanitize_callback' => 'boolval'));
	register_setting('xmpp_stats_settings', 'xmpp_stats_chart_width', array('type' => 'integer', 'sanitize_callback' => 'intval'));
	register_setting('xmpp_stats_settings', 'xmpp_stats_chart_height', array('type' => 'integer', 'sanitize_callback' => 'intval'));
	register_setting('xmpp_stats_settings', 'xmpp_stats_chart_line_color', array('type' => 'string', 'sanitize_callback' => 'sanitize_hex_color'));
	register_setting('xmpp_stats_settings', 'xmpp_stats_chart_line_color2', array('type' => 'string', 'sanitize_callback' => 'trim'));
	register_setting('xmpp_stats_settings', 'xmpp_stats_chart_grid_color', array('type' => 'string', 'sanitize_callback' => 'sanitize_hex_color'));
	register_setting('xmpp_stats_settings', 'xmpp_stats_daily_chart_mode', array('type' => 'integer', 'sanitize_callback' => 'intval'));
}
add_action('admin_init', 'xmpp_stats_register_settings');

// Add link to the settings on plugins page
function xmpp_stats_plugin_action_links($links) {
	$links[] = '<a href="'.esc_url(admin_url('options-general.php?page=xmpp-statistics')).'">'.__('Settings', 'xmpp-statistics').'</a>';
	return $links;
}
add_filter('plugin_action_links_'.XMPP_STATS_BASENAME, 'xmpp_stats_plugin_action_links');

// Create options menu
function xmpp_stats_add_admin_menu() {
	// Add options page
	if($page_hook = add_options_page('XMPP Statistics', 'XMPP Statistics', 'manage_options', 'xmpp-statistics', 'xmpp_stats_settings_page')) {
		// Add the needed CSS & JavaScript
		add_action('admin_enqueue_scripts', 'xmpp_stats_settings_enqueue_scripts');
		// Add page CSS style
		add_action('admin_head-'.$page_hook, 'xmpp_stats_settings_css');
		// Add JS script
		add_action('admin_footer-'.$page_hook, 'xmpp_stats_settings_js');
	}
}
add_action('admin_menu', 'xmpp_stats_add_admin_menu');

// Add the needed CSS & JavaScript
function xmpp_stats_settings_enqueue_scripts($hook_suffix) {
	// Get global variable
	if($hook_suffix == 'settings_page_xmpp-statistics') {
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker-alpha', XMPP_STATS_DIR_URL.'js/wp-color-picker-alpha.min.js', array('wp-color-picker'), '3.0.4', true);
		wp_add_inline_script(
			'wp-color-picker-alpha',
			'jQuery( function() { jQuery( ".color-picker" ).wpColorPicker(); } );'
		);
	}
}

// Add CSS style
function xmpp_stats_settings_css() { ?>
	<style>
		.metabox-holder .postbox .hndle{
		cursor:default;
		}
		.postbox.opened .hndle, .postbox.closed .hndle{
		cursor:pointer;
		}
		.wp-picker-container{
		display:inline;
		}
		.wp-picker-container .iris-picker{
		margin-bottom:6px;
		}
		.rating-stars{
		text-align:center;
		}
		.rating-stars a{
		color:#ffb900;
		text-decoration:none;
		}
	</style>
<?php }

// Add JS script
function xmpp_stats_settings_js() { ?>
	<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			// Add toggles to postboxes
			document.querySelectorAll('.postbox.closed .handlediv').forEach(function(item) {
				item.addEventListener('click', function() {
					this.parentElement.parentElement.parentElement.classList.toggle('closed');
					this.parentElement.parentElement.parentElement.classList.toggle('opened');
				});
			});
			document.querySelectorAll('.postbox.closed .hndle').forEach(function(item) {
				item.addEventListener('click', function() {
					this.parentElement.parentElement.classList.toggle('closed');
					this.parentElement.parentElement.classList.toggle('opened');
				});
			});
		});
	</script>
<?php }

// Display settings page
function xmpp_stats_settings_page() {
	// Create new graphs cache after settings update
	if(($_GET['page']=='xmpp-statistics') && isset($_GET['settings-updated'])) {
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
	} ?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php _e('XMPP server statistics', 'xmpp-statistics'); ?></h1>
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="postbox-container-2" class="postbox-container">
					<div id="normal-sortables" class="meta-box-sortables">
						<form id="xmpp-stats-form" method="post" action="options.php">
							<?php settings_fields('xmpp_stats_settings'); ?>
							<div class="postbox">
								<div class="postbox-header">
									<h2 class="hndle"><?php _e('ReST API', 'xmpp-statistics'); ?></h2>
								</div>
								<div class="inside">
									<table class="form-table"><tbody>
										<tr>
											<th><?php _e('API address', 'xmpp-statistics'); ?></th>
											<td>
												<input name="xmpp_stats_rest_url" id="xmpp_stats_rest_url" type="text" size="40" value="<?php echo get_option('xmpp_stats_rest_url'); ?>" />
												<p class="description"><?php _e('Enter URL address defined for module mod_http_api in ejabberd settings.', 'xmpp-statistics'); ?></p>
											</td>
										</tr>
										<tr>
											<th><?php _e('Login', 'xmpp-statistics'); ?></th>
											<td>
												<input name="xmpp_stats_login" id="xmpp_stats_login" type="text" size="40" value="<?php echo get_option('xmpp_stats_login'); ?>" />
											</td>
										</tr>
										<tr>
											<th><?php _e('Password', 'xmpp-statistics'); ?></th>
											<td>
												<input name="xmpp_stats_password" id="xmpp_stats_password" type="password" size="40" autocomplete="new-password" value="<?php echo get_option('xmpp_stats_password'); ?>" />
											</td>
										</tr>
										<tr>
											<th><?php _e('OAuth token', 'xmpp-statistics'); ?></th>
											<td>
												<input name="xmpp_stats_oauth_token" id="xmpp_stats_oauth_token" type="text" size="40" value="<?php echo get_option('xmpp_stats_oauth_token'); ?>" />
												<p class="description"><?php printf(__('Use instead of classic login and password access. The plugin doesn\'t generate and check the expiration date of OAuth tokens, so you need to generate OAuth token for your own with long expiration date. To generate a token use the oauth_issue_token command with the ejabberdctl shell script e.g.: %s', 'xmpp-statistics'), '<kbd>ejabberdctl oauth_issue_token bot@'.preg_replace('/^www\./','',$_SERVER['SERVER_NAME']).' 315360000 ejabberd:admin</kbd>'); ?></p>
											</td>
										</tr>
									</tbody></table>	
								</div>
							</div>	
							<div class="postbox">
								<div class="postbox-header">
									<h2 class="hndle"><?php _e('Charts with statistics', 'xmpp-statistics'); ?></h2>
								</div>
								<div class="inside">
									<table class="form-table"><tbody>			
										<tr>
											<th><?php _e('Statistics', 'xmpp-statistics'); ?></th>
											<td>
												<label for="xmpp_stats_save_data"><input name="xmpp_stats_save_data" id="xmpp_stats_save_data" type="checkbox" value="1" <?php checked(1, get_option('xmpp_stats_save_data', false)); ?> /><?php _e('Save statistics', 'xmpp-statistics'); ?></label>
												<p class="description"><?php printf(__('Automatically retrieves server statistics every 5 minutes and stores them in a database. WP Cron fires only on the page visit, so plugin may work incorrectly - to prevent such situations, you must disable WP Cron by adding %s to wp-config.php and adding a task to the system cron, for example: %s.', 'xmpp-statistics'), '<kbd>define(\'DISABLE_WP_CRON\', true);</kbd>', '<kbd>*/1 * * * * /usr/bin/php '.get_home_path().'wp-cron.php</kbd>'); ?></p>
											</td>
										</tr>
										<tr>
											<th><?php _e('Cleaning', 'xmpp-statistics'); ?></th>
											<td>
												<label for="xmpp_stats_delete_old_data"><input name="xmpp_stats_delete_old_data" id="xmpp_stats_delete_old_data" type="checkbox" value="1" <?php checked(1, get_option('xmpp_stats_delete_old_data', false)); ?> /><?php _e('Automatically delete unnecessary data from the database', 'xmpp-statistics'); ?></label>
												<p class="description"><?php _e('Use this option with caution - it irrevocably removes data older than 2 weeks!', 'xmpp-statistics'); ?></p>
											</td>
										</tr>
										<tr>
											<th><?php _e('Charts width', 'xmpp-statistics'); ?></th>
											<td>
												<input name="xmpp_stats_chart_width" id="xmpp_stats_chart_width" type="number" value="<?php echo get_option('xmpp_stats_chart_width', 440); ?>" />&nbsp;px
											</td>
										</tr>
										<tr>
											<th><?php _e('Charts height', 'xmpp-statistics'); ?></th>
											<td>
												<input name="xmpp_stats_chart_height" id="xmpp_stats_chart_height" type="number" value="<?php echo get_option('xmpp_stats_chart_height', 220); ?>" />&nbsp;px
											</td>
										</tr>
										<tr>
											<th><?php _e('Charts lines color', 'xmpp-statistics'); ?></th>
											<td>
												<fieldset>
													<input name="xmpp_stats_chart_line_color" id="xmpp_stats_chart_line_color" type="text" value="<?php echo get_option('xmpp_stats_chart_line_color', '#71c73e'); ?>" class="color-picker" data-alpha-enabled="true" data-alpha-color-type="hex" data-default-color="#71c73e" />
													<input type="text" name="xmpp_stats_chart_line_color2" id="xmpp_stats_chart_line_color2" value="<?php echo get_option('xmpp_stats_chart_line_color2', 'rgba(0,102,179,0.3)'); ?>" class="color-picker" data-alpha-enabled="true" data-alpha-color-type="hex" data-default-color="rgba(0,102,179,0.3)" />
												</fieldset>
											</td>
										</tr>
										<tr>
											<th><?php _e('Charts grid color', 'xmpp-statistics'); ?></th>
											<td>
												<input type="text" name="xmpp_stats_chart_grid_color" id="xmpp_stats_chart_grid_color" value="<?php echo get_option('xmpp_stats_chart_grid_color', '#eeeeee'); ?>" class="color-picker" data-alpha-enabled="true" data-alpha-color-type="hex" data-default-color="#eeeeee" />
											</td>
										</tr>
										<tr>
											<th><?php _e('Previous data in charts', 'xmpp-statistics'); ?></th>
											<td>
												<?php $xmpp_stats_daily_chart_mode = get_option('xmpp_stats_daily_chart_mode', 0); ?>
												<select name="xmpp_stats_daily_chart_mode" id="xmpp_stats_daily_chart_mode"><option value="0" <?php selected($xmpp_stats_daily_chart_mode, 0); ?>><?php _e('from yesterday', 'xmpp-statistics'); ?></option><option value="1" <?php selected($xmpp_stats_daily_chart_mode, 1); ?>><?php _e('from last week', 'xmpp-statistics'); ?></option></select>
												<p class="description"><?php _e('Specifies the data range for daily charts.', 'xmpp-statistics'); ?><p>
											</td>
										</tr>
									</tbody></table>
								</div>						
							</div>
							<input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save settings', 'xmpp-statistics'); ?>" />
						</form>
					</div>
				</div>
				<div id="postbox-container-1" class="postbox-container">
					<div id="side-sortables" class="meta-box-sortables">
						<div class="postbox">
							<div class="inside">
								<p><?php _e('If you like this plugin please give a review at WordPress.org.', 'xmpp-statistics'); ?></p>
								<p class="rating-stars"><a href="https://wordpress.org/support/plugin/xmpp-statistics/reviews/?rate=5#new-post" target="_blank"><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span></a></p>
							</div>
						</div>
						<div class="postbox closed">
							<div class="postbox-header">
								<h2 class="hndle"><?php _e('Usage information', 'xmpp-statistics'); ?></h2>
								<div class="handle-actions hide-if-no-js">
									<button type="button" class="handlediv" aria-expanded="true"><span class="toggle-indicator" aria-hidden="true"></span></button>
								</div>
							</div>
							<div class="inside">
								<p><?php printf(__('Make sure that you have the latest version of ejabberd - plugin requires at least ejabberd %s.', 'xmpp-statistics'), '24.02'); ?></p>
								<p><?php printf(__('Check that module mod_http_api in ejabberd is properly configured. Example configuration (more information <a href="%s" target="_blank">here</a>):', 'xmpp-statistics'), 'https://docs.ejabberd.im/developer/ejabberd-api/'); ?></p>
<pre style="overflow-x:auto;">
listen:
  - ip: "::"
    port: 5285
    module: ejabberd_http
    request_handlers:
      api: mod_http_api

api_permissions:
  "rest api":
    who:
      - user: "bot@<?php echo preg_replace('/^www\./','',$_SERVER['SERVER_NAME']); ?>"
    what:
      - incoming_s2s_number
      - outgoing_s2s_number
      - set_last
      - stats
      - status</pre>
								<p><?php _e('Then configure ReST API url and authorization data, finally put shortcodes on some page.', 'xmpp-statistics'); ?></p>
								<p><?php _e('For information about the system, use e.g. Nginx and Lua module. Plugin will be connect to ReST API url with endpoint with name same as shortcode. Example configuration:', 'xmpp-statistics'); ?></p>
<pre style="overflow-x:auto;">
server {
	listen 443 ssl http2;
	listen [::]:443 ssl http2;
	server_name xmpp.<?php echo preg_replace('/^www\./','',$_SERVER['SERVER_NAME']); ?>;

	location /api/system_disk{
		default_type "application/json; charset=UTF-8";
		content_by_lua_block {
			local handle = io.popen("df -h / | grep '/dev/sda1' | awk '{ print $2 }'")
			local size = handle:read()
			handle:close()
			handle = io.popen("df -h / | grep '/dev/sda1' | awk '{ print $3 }'")
			local used = handle:read()
			handle:close()
			handle = io.popen("df -BM / | grep '/dev/sda1' | awk '{ print $3 }'")
			local stat = handle:read():gsub("%D+", "")
			handle:close()
			local json = require("json")
			ngx.say(json.encode({size = size .. "B", used = used .. "B", stat = stat}))
		}
	}
	location /api/system_info {
		default_type "application/json; charset=UTF-8";
		content_by_lua_block {
			local handle = io.popen("lsb_release -r | awk '{ print $2 }'")
			local release = handle:read()
			handle:close()
			handle = io.popen("lsb_release -i | awk '{ print $3 }'")
			local distribution = handle:read()
			handle:close()
			local json = require("json")
			ngx.say(json.encode({distribution = distribution, release = release}))
		}
	}
	location /api/system_uptime {
		default_type "application/json; charset=UTF-8";
		content_by_lua_block {
			local handle = io.popen("awk -F. '{print $1}' /proc/uptime")
			local stat = handle:read()
			handle:close()
			local json = require("json")
			ngx.say(json.encode({stat = stat}))
		}
	}
	location /api/system_memory {
		default_type "application/json; charset=UTF-8";
		content_by_lua_block {
			local handle = io.popen("free -h --si | grep 'Mem' | awk '{ print $2 }'")
			local size = handle:read()
			handle:close()
			handle = io.popen("free -h --si | grep 'Mem' | awk '{ print $3 }'")
			local used = handle:read()
			handle:close()
			handle = io.popen("free -m --si | grep 'Mem' | awk '{ print $3 }'")
			local stat = handle:read()
			handle:close()
			local json = require("json")
			ngx.say(json.encode({size = size .. "B", used = used .. "B", stat = stat}))
		}
	}
}</pre>
							</div>
						</div>
						<div class="postbox closed">
							<div class="postbox-header">
								<h2 class="hndle"><?php _e('Simple shortcodes', 'xmpp-statistics'); ?></h2>
								<div class="handle-actions hide-if-no-js">
									<button type="button" class="handlediv" aria-expanded="true"><span class="toggle-indicator" aria-hidden="true"></span></button>
								</div>
							</div>
							<div class="inside">
								<ul>
									<li><b>[xmpp_onlineusers]</b></br><?php _e('Online users count', 'xmpp-statistics'); ?></br><small><?php _e('Command', 'xmpp-statistics'); ?>:&nbsp;ejabberdctl stats onlineusers</small></li>
									<li><b>[xmpp_registeredusers]</b></br><?php _e('Registered users count', 'xmpp-statistics'); ?></br><small><?php _e('Command', 'xmpp-statistics'); ?>:&nbsp;ejabberdctl stats registeredusers</small></li>
									<li><b>[xmpp_s2s_out]</b></br><?php _e('Outgoing s2s connections count', 'xmpp-statistics'); ?></br><small><?php _e('Command', 'xmpp-statistics'); ?>:&nbsp;ejabberdctl outgoing_s2s_number</small></li>
									<li><b>[xmpp_s2s_in]</b></br><?php _e('Incoming s2s connections count', 'xmpp-statistics'); ?></br><small><?php _e('Command', 'xmpp-statistics'); ?>:&nbsp;ejabberdctl incoming_s2s_number</small></li>
									<li><b>[xmpp_uptime]</b></br><?php _e('XMPP server uptime', 'xmpp-statistics'); ?></br><small><?php _e('Command', 'xmpp-statistics'); ?>:&nbsp;ejabberdctl stats uptimeseconds</small></li>
									<li><b>[xmpp_version]</b></br><?php _e('XMPP server version', 'xmpp-statistics'); ?></br><small><?php _e('Command', 'xmpp-statistics'); ?>:&nbsp;ejabberdctl status</small></li>
									<li><b>[system_uptime]</b></br><?php _e('System uptime', 'xmpp-statistics'); ?></br></li>
									<li><b>[system_memory]</b></br><?php _e('RAM memory usage', 'xmpp-statistics'); ?></br></li>
									<li><b>[system_version]</b></br><?php _e('System version', 'xmpp-statistics'); ?></br></li>
								</ul>
							</div>
						</div>
						<div class="postbox closed">
							<div class="postbox-header">
								<h2 class="hndle"><?php _e('Shortcodes for charts', 'xmpp-statistics'); ?></h2>
								<div class="handle-actions hide-if-no-js">
									<button type="button" class="handlediv" aria-expanded="true"><span class="toggle-indicator" aria-hidden="true"></span></button>
								</div>
							</div>
							<div class="inside">
								<ul>
									<li><b>[xmpp_online_users_daily_chart]</b></br><?php _e('Logged in users', 'xmpp-statistics').' - '.__('by day', 'xmpp-statistics'); ?></li>
									<li><b>[xmpp_online_users_weekly_chart]</b></br><?php _e('Logged in users', 'xmpp-statistics').' - '.__('by week', 'xmpp-statistics'); ?></li>
									<li><b>[xmpp_registered_users_daily_chart]</b></br><?php _e('Registered users', 'xmpp-statistics').' - '.__('by day', 'xmpp-statistics'); ?></li>
									<li><b>[xmpp_registered_users_weekly_chart]</b></br><?php _e('Registered users', 'xmpp-statistics').' - '.__('by week', 'xmpp-statistics'); ?></li>
									<li><b>[xmpp_uptime_daily_chart]</b></br><?php _e('XMPP server uptime', 'xmpp-statistics').' - '.__('by day', 'xmpp-statistics'); ?></li>
									<li><b>[xmpp_uptime_weekly_chart]</b></br><?php _e('XMPP server uptime', 'xmpp-statistics').' - '.__('by week', 'xmpp-statistics'); ?></li>
									<li><b>[system_uptime_daily_chart]</b></br><?php _e('System uptime', 'xmpp-statistics').' - '.__('by day', 'xmpp-statistics'); ?></li>
									<li><b>[system_uptime_weekly_chart]</b></br><?php _e('System uptime', 'xmpp-statistics').' - '.__('by week', 'xmpp-statistics'); ?></li>
									<li><b>[memory_usage_daily_chart]</b></br><?php _e('RAM memory usage', 'xmpp-statistics').' - '.__('by day', 'xmpp-statistics'); ?></li>
									<li><b>[memory_usage_weekly_chart]</b></br><?php _e('RAM memory usage', 'xmpp-statistics').' - '.__('by week', 'xmpp-statistics'); ?></li>
									<li><b>[xmpp_s2s_out_daily_chart]</b></br><?php _e('Outgoing S2S connections', 'xmpp-statistics').' - '.__('by day', 'xmpp-statistics'); ?></li>
									<li><b>[xmpp_s2s_out_weekly_chart]</b></br><?php _e('Outgoing S2S connections', 'xmpp-statistics').' - '.__('by week', 'xmpp-statistics'); ?></li>
									<li><b>[xmpp_s2s_in_daily_chart]</b></br><?php _e('Incoming S2S connections', 'xmpp-statistics').' - '.__('by day', 'xmpp-statistics'); ?></li>
									<li><b>[xmpp_s2s_in_weekly_chart]</b></br><?php _e('Incoming S2S connections', 'xmpp-statistics').' - '.__('by week', 'xmpp-statistics'); ?></li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php }
