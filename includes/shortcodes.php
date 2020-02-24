<?php
/**
 * Shortcodes
 *
 * @package     ESP
 * @subpackage  Shortcodes
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function event_list() {
    $js_dir = ESP_PLUGIN_URL . 'assets/js/';
    $css_dir = ESP_PLUGIN_URL . 'assets/css/';

    wp_enqueue_script('axios', 'https://unpkg.com/axios@0.19.0/dist/axios.min.js', [], '0.19.0');
    wp_register_script( 'esp-event-list-scripts', $js_dir . 'eventListing.js', ['axios'], ESP_VERSION, false );
    $esp = ESP();
    $esp->get_super_passes();
    $customer = array();
    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $customer = new ESP_Customer( $user_id );
        $customer->get_eb_orders();
    }

    // For now just use the first super pass
    $esp->get_events( true );
    $super_pass = $esp->super_passes[0];
    $super_pass->gather_event_data();
    wp_localize_script( 'esp-event-list-scripts', 'esp_data',
        array(
            'ajax_url' => admin_url('admin-ajax.php', ''),
            'is_logged_in' => is_user_logged_in(),
            'redirect' => get_permalink( get_option('woocommerce_myaccount_page_id') ),
            'customer' => $customer,
            'super_pass' => $super_pass,
        )
    );
    wp_enqueue_script( 'esp-event-list-scripts' );
    wp_enqueue_style( 'extra', $css_dir . 'extra.css' );
    wp_enqueue_script('eb_widgets', "https://www.eventbrite.com/static/widgets/eb_widgets.js");

    ob_start();
    ?>
        <div id="esp-event-list">
        </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'esp_event_list', 'event_list', 1000 );