<?php
/**
 * Woocommerce Actions
 *
 * @package     ESP
 * @subpackage  Woocommerce/Functions
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register our custom endpoint
 *
 * @since 1.0
 * @return void
 */
function add_wc_endpoint() {
    add_rewrite_endpoint( 'superpass', EP_ROOT | EP_PAGES );
}

/**
 * Add custom query variable for our endpoint
 *
 * @since 1.0
 * @param $vars
 * @return array
 */
function add_query_vars( $vars ) {
    $vars[] = 'superpass';

    return $vars;
}

/**
 * Register the endpoint title
 *
 * @since 1.0
 * @return mixed
 */
function wc_endpoint_title() {
    return __( 'Eventbrite Super Passes', 'wc_custom_endpoint' );
}

/**
 * Add our endpoint to the account page menu
 *
 * @since 1.0
 * @param $items
 * @return mixed
 */
function wc_add_menu_item( $items ) {
    $logout = $items['customer-logout'];
    unset( $items['customer-logout'] );

    $items['superpass'] = __( 'Eventbrite Super Passes', 'wc_custom_endpoint' );

    $items['customer-logout'] = $logout;

    return $items;
}

/**
 * Load the content for our custom endpoint
 *
 * @since 1.0
 * @return void
 */
function wc_endpoint_content() {
    ?>
        <p>Hello World 2!</p>
    <?php
}

/**
 * Run our woocommerce actions after woocommerce is loaded.
 *
 * @since 1.0
 * @return void
 */
function mount_custom_wc() {
    add_action( 'init', 'add_wc_endpoint' );
    add_filter( 'query_vars', 'add_query_vars' );
    add_filter( 'woocommerce_account_menu_items', 'wc_add_menu_item' );
    add_filter( 'woocommerce_endpoint_superpass_title', 'wc_endpoint_title' );
    add_filter( 'woocommerce_account_superpass_endpoint', 'wc_endpoint_content' );
}
add_action( 'woocommerce_loaded', 'mount_custom_wc' );

/**
 * Mount our endpoint and flush the rewrite rules
 *
 * @since 1.0
 * @return void
 */
function mount_custom_wc_endpoint() {
    add_wc_endpoint();
    flush_rewrite_rules();
}

/**
 * Flush out our custom endpoint
 *
 * @since 1.0
 * @return void
 */
function unmount_custom_wc_endpoint() {
    flush_rewrite_rules();
}
register_activation_hook( ESP_PLUGIN_FILE, 'mount_custom_wc_endpoint' );
register_deactivation_hook( ESP_PLUGIN_FILE, 'unmount_custom_wc_endpoint' );