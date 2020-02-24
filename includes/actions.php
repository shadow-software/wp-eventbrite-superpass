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
 * Check if the customer has any attending events that conflict with the event requested
 *
 * @param $customer - ESP_Customer Object
 * @param $event_id - ID of Eventbrite event.
 * @param $super_pass_id - Superpass being used to attend this event
 * @return array
 * @since 1.0
 */
function esp_can_customer_attend( $customer, $event_id, $super_pass_id ) {
    // Search for event.
    $esp = ESP();
    $event = $esp->get_event_by_id( $event_id );
    if ( $event === false ) {
        return array( 'result' => false, 'message' => 'Event not found.' );
    }
    $event_start_time = strtotime( $event['start']['local'] );
    $event_end_time = strtotime( $event['end']['local'] );
    $overlap = false;

    foreach( $customer->attending as $record ) {
        // Compare the dates of each record and make sure there's no overlap.
        $cEvent = $esp->get_event_by_id( $record->event_id );
        $start_time = strtotime( $cEvent['start']['local'] );
        $end_time = strtotime( $cEvent['end']['local'] );

        if ( $event_start_time < $end_time && $start_time < $event_end_time && ! (!$record->confirmed && (int)$record->event_id === (int)$event_id ) && (int) $super_pass_id === (int) $record->super_pass_id ) {
            $overlap = true;
        }

        if ( (int)$event_id === (int)$record->event_id && (int)$super_pass_id === (int)$record->super_pass_id && $record->confirmed ) {
            // The user already has an attendance record for this event using this superpass and has purchased the eventbrite ticket.
            return array( 'result' => false, 'message' => 'Already attending this event.' );
        }
    }

    if( $overlap ) {
        return array( 'result' => false, 'message' => 'Already attending an overlapping event' );
    } else {
        return array( 'result' => true, 'message' => '' );
    }
}
add_filter( 'esp_can_customer_attend', 'esp_can_customer_attend', 10, 3 );

/**
 * Get a list of Eventbrite events that the current user is attending.
 *
 * @since 1.0
 * @return array
 */
/*
function esp_get_extended_attendance_record() {
    $user_id = get_current_user_id();
    $esp = ESP();
    $customer = $esp->get_customer_by_id( $user_id );

    $events = [];
    foreach( $customer->attending as $record ) {
        if ( isset( $record->coupon ) ) {
            // Check to see if the attendance record still exists.
            $res = [];
            if( isset( $record->order_id ) ) {
                $res = $esp->eb_sdk->client->get(
                    "/orders/{$record->order_id}/",
                    array()
                );
            }

            if( isset( $res['status'] ) && $res['status'] === "refunded") {
                $result = $record->delete_coupon();
                if ( $result['quantity_sold'] === 0 ) {
                    // Only delete the record if the coupon is no longer in use.
                    // Theoretically, someone could generate a coupon, use it, refund it and then have someone else use it
                    // and generate another one. This is to prevent that. This way they will not be able to generate
                    // another coupon for this Time Slot.
                    $record->delete();
                }
            } else {
                $event = $esp->get_event_by_id( $record->event_id );
                $event['super_pass_id'] = (int)$record->super_pass_id;
                $event['order_id'] = $record->order_id;
                $event['confirmed'] = $record->confirmed;
                $event['record_id'] = $record->id;
                $event['debug'] = $res;
                $events[] = $event;
            }
        }
    }

    return $events;
}
add_filter( 'esp_get_extended_attendance_record', 'esp_get_extended_attendance_record', 1000 );
*/

/**
 * Get the events that a customer is allowed to buy tickets for.
 *
 * @since 1.0
 * @return array
 */
/*
function esp_get_allowed_events() {
    //$user_id = get_current_user_id();
    $esp = ESP();
    $events = $esp->get_events( true );
    $esp->get_super_passes();
    $customer = $user_id !== 0 ? $esp->get_customer_by_id( $user_id ) : null;
    $allowed_events = array();
    // Just use the first super pass for now.
    $super_pass = $esp->super_passes[0];
    if ( isset( $super_pass->events[0] ) ) {
        if ( ! isset( $super_pass->events[0]['id'] ) ) {
            $super_pass->gather_event_data();
        }
    }
    foreach ( $events as $key => $event ) {
        $found = array_search( $event['id'], array_column( $super_pass->events, 'id' ) );

        if( $found !== false ) {
            unset( $event );
        } else {
            // Check if event is set as an add on
            $found = array_search( $event['id'], array_column( $super_pass->add_on_events, 'id' ) );
            if ( $found !== false ) {
                if ( $customer !== null ) {
                    $check = array_search( $super_pass->id, array_column( $customer->super_passes, 'id' ) );
                    if ( $check === false ) {
                        unset( $event );
                    }
                } else {
                    unset( $event );
                }

            }
        }

        if( isset( $event ) ) {
            $allowed_events[] = $event;
        }
    }

    return $allowed_events;
}
add_filter( 'esp_get_allowed_events', 'esp_get_allowed_events', 1000 );
*/