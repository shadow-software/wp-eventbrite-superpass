<?php
/**
 * Actions
 *
 * @package     ESP
 * @subpackage  Functions
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Require Woocommerce as a dependency. If not installed, disable the plugin and flash a message
 *
 * @since 1.0
 * @return void
 */
function woocommerce_dependency() {
    if ( is_admin() && current_user_can( 'activate_plugins' ) && ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        add_action( 'admin_notices', 'dependency_notice' );

        deactivate_plugins( ESP_PLUGIN_FILE );

        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}
add_action( 'admin_init', 'woocommerce_dependency' );

/**
 * For flashing dependency message
 *
 * @since 1.0
 * @return void
 */
function dependency_notice(){
    ?><div class="error"><p>Sorry, WP Eventbrite Superpass requires Woocommerce in order to be used.</p></div><?php
}

/**
 * Create Page for Eventbrite Checkout
 *
 * @since 1.0
 * @return void
 */
function create_eb_page() {
    $post = get_page_by_title( 'Eventbrite Checkout', OBJECT, 'page' );

    if ( ! $post ) {
        $postarr = [
            'post_title' => 'Eventbrite Checkout',
            'post_type' => 'page',
            'post_content' => '<!-- wp:shortcode -->[esp_eventbrite_checkout]<!-- /wp:shortcode -->',
            'post_status' =>'publish',
        ];

        wp_insert_post( $postarr );
    }
}
register_activation_hook( ESP_PLUGIN_FILE, 'create_eb_page' );

/**
 * Remove Eventbrite page on plugin deactivation
 *
 * @since 1.0
 * @return void
 */
function remove_eb_page() {
    $post = get_page_by_title( 'Eventbrite Checkout', OBJECT, 'page' );
    wp_delete_post( $post->ID );
}
register_deactivation_hook( ESP_PLUGIN_FILE, 'remove_eb_page' );

/**
 * Get the ESP customer for the current user
 *
 * @since 1.0
 * @return bool|ESP_Customer
 */
function esp_get_customer_data() {
    $id = get_current_user_id();
    if ( $id ) {
        $esp = ESP();
        return $esp->get_customer_by_id( $id );
    } else {
        return false;
    }

}
add_filter( 'esp_get_customer_data', 'esp_get_customer_data', 1000 );

/**
 * Check if the customer has any attending events that conflict with the event requested
 *
 * @since 1.0
 * @param $customer
 * @param $event_id
 * @return array
 */
function esp_can_customer_attend( $customer, $event_id ) {
    // Search for event.
    $esp = ESP();
    $event = $esp->get_event_by_id( $event_id );
    if ( $event === false ) {
        return array( 'result' => false, 'message' => 'Event not found.' );
    }
    $event_start_time = strtotime( $event->start );
    $event_end_time = strtotime( $event->end );
    $overlap = false;
    foreach( $customer->attendace_record as $record ) {
        // Compare the dates of each record and make sure there's no overlap.
        $cEvent = $esp->get_event_by_id( $record->event_id );
        $start_time = strtotime( $cEvent->start );
        $end_time = strtotime( $cEvent->end );
        if ( $start_time >= $event_start_time && $end_time <= $event_end_time ) {
            $overlap = true;
            break;
        }
    }

    if( $overlap ) {
        return array( 'result' => false, 'message' => 'Already attending an overlapping event' );
    } else {
        return array( 'result' => true, 'message' => '' );
    }
}
add_filter( 'esp_can_customer_attend', 'esp_can_customer_attend', 10, 2 );

function eventbrite_checkout_content() {
    $page_name = get_query_var('pagename');
    if ( $page_name === 'eventbrite-checkout' ) {
        $attendance_id =  $_GET['attendance'];
        $record = new ESP_Attendance_Record(null, null, null, $attendance_id);
        ?>
        <div id="eventbrite-widget-container-90798034365"></div>

        <script src="https://www.eventbrite.com/static/widgets/eb_widgets.js"></script>

        <script type="text/javascript">
            var exampleCallback = function() {
                console.log('Order complete!');
            };

            window.EBWidgets.createWidget({
                // Required
                widgetType: 'checkout',
                eventId: '<?php echo $record->event_id; ?>',
                promoCode: '<?php echo $record->coupon; ?>',
                iframeContainerId: 'eventbrite-widget-container-90798034365',

                // Optional
                iframeContainerHeight: 425,  // Widget height in pixels. Defaults to a minimum of 425px if not provided
                onOrderComplete: exampleCallback  // Method called when an order has successfully completed
            });
        </script>
        <?php
    }
}
add_shortcode( 'esp_eventbrite_checkout', 'eventbrite_checkout_content' );