<?php
/**
 * @NOTE: this is the logic to have your plugin only work for osdxp-powered websites
 */
// Network check for osdxp dash enabled.
if (!is_plugin_active_for_network('osdxp-dashboard/osdxp-dashboard.php') &&
    is_plugin_active_for_network(WPMM_PLUGIN_BASENAME)
) {
    add_action('network_admin_notices', 'wpmm_notice_missing_osdxp');
    return;
}

// Check if the OSDXP Dashboard is installed and activated for single site.
if (!is_plugin_active('osdxp-dashboard/osdxp-dashboard.php')) {
    add_action('admin_notices', 'wpmm_notice_missing_osdxp');
    return;
}

/**
 * @NOTE: convert plugin to module filters. these get added only on plugin activation.
 * To have your plugin show up as a module by default,
 * please contact us in the osdxp slack to add your plugin to the API endpoint queried by osdxp dashboard.
 */
add_filter('osdxp_get_modules','maintenance_mode_module');
add_filter('osdxp_get_available_modules','maintenance_mode_module');

add_filter('osdxp_dashboard_internal_licensed_plugins', 'wpmm_register_plugin');
add_filter('osdxp_dashboard_licensed_plugins', 'wpmm_register_plugin');
add_filter('osdxp_dashboard_plugin_update_checker_list', 'wpmm_register_plugin');
add_filter('osdxp_dashboard_plugin_file_' . WPMM_PLUGIN_SLUG, 'wpmm_register_plugin_file');
add_filter('osdxp_dashboard_plugin_name_' . WPMM_PLUGIN_SLUG, 'wpmm_get_plugin_name');

//setting up module information
function maintenance_mode_module($modules) {
	$slug = WPMM_PLUGIN_BASENAME;

	//plugin header info - second param is false to strip extra markup injected by WP
	$modules[$slug] = get_plugin_data(WPMM_PLUGIN_FILE, false);

	//optional - if not set will output a placeholder logo
	$modules[$slug]['logo'] = 'http://placekitten.com/150/150';

	//the 'Get Module' URL present in the Available Modules Page
	$modules[$slug]['url'] = 'http://mywebsite.com/get-my-module';

	/**
	 * @NOTE: pricing info can be left unset
	 */
	// $modules[$slug]['price'] = '150';
	// $modules[$slug]['before-price-text'] = 'From';
	// $modules[$slug]['after-price-text'] = 'Per Year';

	return $modules;
}

// notice and disable function
function wpmm_notice_missing_osdxp()
{
    if (isset($_GET['activate'])) {
        unset($_GET['activate']);
    }

    $message = sprintf(
        /* Translators: %1$s - Plugin name, %2$s - "OSDXP Dashboard". */
        esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'cf-conditional-content'),
        '<strong>' . esc_html(WPMM_PLUGIN_NAME) . '</strong>',
        '<strong>OSDXP Dashboard</strong>'
    );

    printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);

    if (is_multisite()) {
        $sites = get_sites();
        $active_sites = [];
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            if (is_plugin_active(WPMM_PLUGIN_BASENAME) &&
                is_plugin_active('osdxp-dashboard/osdxp-dashboard.php')
            ) {
                $active_sites[] = $site->blog_id;
            }
            restore_current_blog();
        }
        deactivate_plugins(WPMM_PLUGIN_BASENAME);
        foreach ($active_sites as $site_id) {
            switch_to_blog($site_id);
            activate_plugin(WPMM_PLUGIN_BASENAME);
            restore_current_blog();
        }
    } else {
        deactivate_plugins(WPMM_PLUGIN_BASENAME);
    }
}

/**
 * Method to get plugin name.
 *
 * @return string
 */
function wpmm_get_plugin_name()
{
    return esc_html(WPMM_PLUGIN_NAME);
}

/**
 * Method to register internal plugin.
 *
 * @param array $plugins An array of internal plugins slugs.
 *
 * @return array
 */
function wpmm_register_plugin($plugins)
{
    if (!is_array($plugins)) {
        $plugins = [];
    }

    if (!in_array(WPMM_PLUGIN_SLUG, $plugins, true)) {
        $plugins[] = WPMM_PLUGIN_SLUG;
    }

    return $plugins;
}

/**
 * Method to register plugin file.
 *
 * @return string
 */
function wpmm_register_plugin_file()
{
    return WPMM_PLUGIN_FILE;
}
