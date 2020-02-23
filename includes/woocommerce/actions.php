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
    include_once ESP_PLUGIN_DIR . 'includes/woocommerce/templates/event_selection_table.php';
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

/**
 * Gather all completed orders and generate our WC data
 *
 * @return void
 * @since 1.0
 */
function setup_esp_customer() {
    $user_id = get_current_user_id();
    if( !$user_id ) {
        return;
    }
    $orders = wc_get_orders( array(
        'status' => 'wc-completed',
        'customer_id' => $user_id,
    ) );

    $esp = ESP();
    $customer = null;
    $esp->get_super_passes();
    foreach( $orders as $order ) {
        $status = $order->get_status();
        if( $status === 'completed' ) {
            $items = $order->get_items();
            foreach ($items as $item) {
                $data = $item->get_data();
                // Compare this item's ID to our Superpass WC ID
                $found = array_search($data['product_id'], array_column((array)$esp->super_passes, "wc_id"));
                if ($found !== false) {
                    // Order found, let's make an object instance of our ESP Customer
                    $customer = $esp->get_customer_by_id($user_id);
                    $esp->super_passes[$found]->gather_event_data();
                    $customer->add_super_pass($esp->super_passes[$found]);
                }
            }
        }
    }

    if ( $customer ) {
        $customer->gather_attendance_records();
    }
}
add_action( 'woocommerce_after_register_post_type', 'setup_esp_customer' );

/**
 * Redirect after purchase
 *
 * @return void
 * @since 1.0
 */
function esp_wc_redirect() {
    global $wp;

    if ( is_checkout() && ! empty( $wp->query_vars['order-received'] ) ) {

        $redirect_url = get_site_url(null, '/schedule/', 'https');

        wp_redirect($redirect_url);

        exit;
    }
}
add_action( 'template_redirect', 'esp_wc_redirect' );