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
    add_rewrite_endpoint( 'manage-agenda', EP_ROOT | EP_PAGES );
}

/**
 * Add custom query variable for our endpoint
 *
 * @since 1.0
 * @param $vars
 * @return array
 */
function add_query_vars( $vars ) {
    $vars[] = 'manage-agenda';

    return $vars;
}

/**
 * Register the endpoint title
 *
 * @since 1.0
 * @return mixed
 */
function wc_endpoint_title() {
    return __( 'Manage Conference Agenda', 'wc_custom_endpoint' );
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

    $items['manage-agenda'] = __( 'Manage Conference Agenda', 'wc_custom_endpoint' );

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
    add_filter( 'woocommerce_endpoint_manage-agenda_title', 'wc_endpoint_title' );
    add_filter( 'woocommerce_account_manage-agenda_endpoint', 'wc_endpoint_content' );
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

/**
 * Split the login and register page
 *
 * @since 1.0
 * @return void
 */
function woocommerce_login_split(){
    global $wp_query;
    if( $wp_query->query_vars['pagename'] === 'my-account' ):
    ?>
    <script type="text/javascript">
        (function($) {
            $(document).ready(function () {
                function getAllUrlParams(url) {

                    // get query string from url (optional) or window
                    var queryString = url ? url.split('?')[1] : window.location.search.slice(1);

                    // we'll store the parameters here
                    var obj = {};

                    // if query string exists
                    if (queryString) {

                        // stuff after # is not part of query string, so get rid of it
                        queryString = queryString.split('#')[0];

                        // split our query string into its component parts
                        var arr = queryString.split('&');

                        for (var i = 0; i < arr.length; i++) {
                            // separate the keys and the values
                            var a = arr[i].split('=');

                            // set parameter name and value (use 'true' if empty)
                            var paramName = a[0];
                            var paramValue = typeof (a[1]) === 'undefined' ? true : a[1];

                            // (optional) keep case consistent
                            paramName = paramName.toLowerCase();
                            if (typeof paramValue === 'string') paramValue = paramValue.toLowerCase();

                            // if the paramName ends with square brackets, e.g. colors[] or colors[2]
                            if (paramName.match(/\[(\d+)?\]$/)) {

                                // create key if it doesn't exist
                                var key = paramName.replace(/\[(\d+)?\]/, '');
                                if (!obj[key]) obj[key] = [];

                                // if it's an indexed array e.g. colors[2]
                                if (paramName.match(/\[\d+\]$/)) {
                                    // get the index value and add the entry at the appropriate position
                                    var index = /\[(\d+)\]/.exec(paramName)[1];
                                    obj[key][index] = paramValue;
                                } else {
                                    // otherwise add the value to the end of the array
                                    obj[key].push(paramValue);
                                }
                            } else {
                                // we're dealing with a string
                                if (!obj[paramName]) {
                                    // if it doesn't exist, create property
                                    obj[paramName] = paramValue;
                                } else if (obj[paramName] && typeof obj[paramName] === 'string'){
                                    // if property does exist and it's a string, convert it to an array
                                    obj[paramName] = [obj[paramName]];
                                    obj[paramName].push(paramValue);
                                } else {
                                    // otherwise add the property
                                    obj[paramName].push(paramValue);
                                }
                            }
                        }
                    }

                    return obj;
                }

                if (!getAllUrlParams().register) {
                    let elem = document.querySelector('.woocommerce #customer_login .u-column2');
                    elem.parentNode.removeChild(elem);
                    elem = document.querySelector('.col-1');
                    elem.style.float = 'none';
                    elem.style['margin-right'] = 'auto';
                    elem.style['margin-left'] = 'auto';
                } else {
                    let elem = document.querySelector('.woocommerce #customer_login .u-column1');
                    elem.parentNode.removeChild(elem);
                    elem = document.querySelector('.col-2');
                    elem.style.float = 'none';
                    elem.style['margin-right'] = 'auto';
                    elem.style['margin-left'] = 'auto';
                }
            });
        })(jQuery)
    </script>
    <?php
    endif;
}
add_action( 'wp_footer', 'woocommerce_login_split', 100 );

add_filter('woocommerce_login_redirect', 'custom_wc_login_redirect', 10, 3);
function custom_wc_login_redirect( $redirect, $user ) {
    $redirect = site_url() . '/schedule';
    return $redirect;
}

function custom_wc_register_redirect() {
    return site_url('/schedule/');
}
add_filter('woocommerce_registration_redirect', 'custom_wc_register_redirect', 2);