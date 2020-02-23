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

/**
 * HTML content and JS script for Eventbrite Checkout. (Taken from Eventbrite)
 *
 * @since 1.0
 * @throws Exception
 * @return void
 */
function eventbrite_checkout_content() {
    $page_name = get_query_var('pagename');
    if ( $page_name === 'eventbrite-checkout' ) {
        $attendance_id =  $_GET['attendance'];
        $record = new ESP_Attendance_Record(null, null, null, $attendance_id);
        // Make sure that the owner of the attendance record belongs to the current logged in user.
        $user = wp_get_current_user();
        if( $user->ID === (int)$record->user_id ) {
            ?>
            <div id="eventbrite-widget-container-<?php echo $record->event_id; ?>"></div>
            <div id="esp-overlay" class="esp-overlay"></div>
            <script src="https://www.eventbrite.com/static/widgets/eb_widgets.js"></script>

            <script type="text/javascript">
                var markComplete = function(obj) {
                    var orderId = obj.orderId;
                    var overlay = document.getElementById("esp-overlay");
                    overlay.style.display = "block";
                    var ajaxurl = ajax_object.ajax_url;
                    var attendance_id = getAllUrlParams().attendance;
                    var data = new FormData();
                    data.append('action', 'esp_confirm_eb_order');
                    data.append('attendance_id', attendance_id);
                    data.append('order_id', orderId);

                    axios.post(ajaxurl, data)
                        .then(function(response) {
                            overlay.style.display = "none";
                            window.location.href = response.data.redirect;
                        })
                };

                window.EBWidgets.createWidget({
                    // Required
                    widgetType: 'checkout',
                    eventId: '<?php echo $record->event_id; ?>',
                    promoCode: '<?php echo $record->coupon; ?>',
                    iframeContainerId: 'eventbrite-widget-container-<?php echo $record->event_id; ?>',

                    // Optional
                    iframeContainerHeight: 425,  // Widget height in pixels. Defaults to a minimum of 425px if not provided
                    onOrderComplete: markComplete  // Method called when an order has successfully completed
                });
            </script>
            <?php
        } else {
            ?>
            <div>
                We're sorry, this URL is invalid.
            </div>
            <?php
        }

    }
}
add_shortcode( 'esp_eventbrite_checkout', 'eventbrite_checkout_content' );

function event_list() {
    $js_dir = ESP_PLUGIN_URL . 'assets/js/';
    $css_dir = ESP_PLUGIN_URL . 'assets/css/';

    wp_enqueue_script('axios', 'https://unpkg.com/axios@0.19.0/dist/axios.min.js', [], '0.19.0');
    wp_register_script( 'esp-event-list-scripts', $js_dir . 'eventListing.js', ['axios'], ESP_VERSION, false );
    $esp = ESP();
    $events = apply_filters( 'esp_get_allowed_events', '' );
    $esp->get_super_passes();
    $customer = array();
    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $customer = $esp->get_customer_by_id( $user_id );
    }

    wp_localize_script( 'esp-event-list-scripts', 'esp_data',
        array(
            'events' => $events,
            'ajax_url' => admin_url('admin-ajax.php', ''),
            'is_logged_in' => is_user_logged_in(),
            'redirect' => get_permalink( get_option('woocommerce_myaccount_page_id') ),
            'customer' => $customer,
            'super_passes' => $esp->super_passes,
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