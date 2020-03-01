<?php
/**
 * Ajax Actions
 *
 * @package     ESP
 * @subpackage  Functions
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get the settings that the front end will use
 *
 * @since 1.0
 * @return void
 */
function get_esp_settings() {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        $esp = ESP();
        $esp->compile_settings();
        global $esp_settings;
        // Add events to the settings
        $esp_settings['eventbrite']['events'] = $esp->get_events();
        header( "Content-type: application/json" );
        echo json_encode( $esp_settings );
        wp_die();
    }
}
add_action( 'wp_ajax_get_esp_settings', 'get_esp_settings', 10 );

/**
 * Get current super passes
 *
 * @since 1.0
 * @return void
 */
function esp_get_super_passes() {
    if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        $esp = ESP();
        $esp->get_super_passes();
        header( "Content-type: application/json" );
        foreach( $esp->super_passes as $super_pass ) {
            $super_pass->gather_event_data();
        }
        echo json_encode( $esp->super_passes );
        wp_die();
    }
}
add_action( 'wp_ajax_esp_get_super_passes', 'esp_get_super_passes', 10 );

/**
 * Get the Eventbrite Events associated with this account
 *
 * @since 1.0
 * @return void
 */
function esp_get_events() {
    if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        $esp = ESP();
        $events = $esp->get_events();
        header( "Content-type: application/json" );
        echo json_encode( $events );
        wp_die();
    }
}
add_action( 'wp_ajax_esp_get_events', 'esp_get_events', 10 );

/**
 * Set Eventbrite keys and test if they are working
 *
 * @since 1.0
 * @return void
 */
function setup_esp_eventbrite_keys() {
    $result = array(
        "success" => null,
        "message" => "",
    );

    // Setup api key && client_secret
    if ( ! empty( $_POST[ 'api_key' ] ) && ! empty( $_POST[ 'client_secret' ] ) ) {
        $esp = ESP();

        $esp->set_eventbrite_keys( $_POST );

        // First let's see if these keys are valid
        $check = $esp->eventbrite_keys_valid();
        if ( strpos( $check[ 'error_description' ], 'client_id' ) !== false && strpos( $check[ 'error_description' ], 'client_secret' ) !== false ){
            $result[ 'success' ] = false;
            $result[ 'message' ] = $check[ 'error_description' ];
        } else {
            $link = $esp->eb_sdk->createAuthLink($esp->api_key);
            $result[ 'link' ] = $link;
            $result[ 'success' ] = true;
        }
    }

    // Check if we're getting an access code
    if ( ! empty( $_POST[ 'access_code' ] ) ) {
        $esp = ESP();

        $esp->set_eventbrite_keys( $_POST );

        $check = $esp->eventbrite_keys_valid();
        if ( strpos( $check[ 'error_description' ], 'code' ) !== false ) {
            $result[ 'success' ] = false;
            $result[ 'message' ] = $check[ 'error_description' ];
        } else {
            $result[ 'success' ] = true;
            $esp->set_eventbrite_keys( [ 'token' => $check['access_token'] ] );
        }
    }

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        header( 'Content-type: application/json' );
        echo json_encode( $result );
        wp_die();
    }
}
add_action( 'wp_ajax_setup_esp_eventbrite_keys', 'setup_esp_eventbrite_keys', 10 );

function esp_create_super_pass() {
    $result = array(
        "success" => null,
        "message" => "",
    );

    if ( ! empty( $_POST[ 'name' ] && ! empty( $_POST[ 'cost' ] ) && ! empty( $_POST[ 'event' ] ) ) ) {
        $name = sanitize_text_field( $_POST[ 'name' ] );
        $cost = sanitize_text_field( $_POST[ 'cost' ] );
        $event = sanitize_text_field( $_POST[ 'event' ] );

        $add_ons = isset( $_POST['add_ons'] ) ? (array) $_POST['add_ons'] : array();
        $add_ons = array_map( 'esc_attr', $add_ons );

        $super_pass = new ESP_Super_Pass( $cost, $name, $event );

        // We want to make sure each event is existing and is okay to add.

        foreach( $add_ons as $event ) {
            $super_pass->add_event( $event );
        }

        $esp = ESP();
        $esp->add_super_pass( $super_pass );
        $result[ 'success' ] = true;
    } else {
        $result[ 'success' ] = false;
        $result[ 'message' ] = "Please make sure to create a name and cost for your Super Pass and select at least one event";
    }

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        header( 'Content-type: application/json' );
        echo json_encode( $result );
        wp_die();
    }
}
add_action( 'wp_ajax_esp_create_super_pass', 'esp_create_super_pass' );

function esp_update_super_pass() {
    $result = array(
        'success' => false,
    );

    if ( isset( $_POST['id'] ) && isset( $_POST['cost'] ) && isset( $_POST['name'] ) && isset( $_POST['event'] ) ) {
        $id = sanitize_text_field( $_POST['id'] );
        $cost = sanitize_text_field( $_POST['cost'] );
        $name = sanitize_text_field( $_POST['name'] );
        $event = sanitize_text_field( $_POST['event'] );
        $add_ons = $_POST['add_ons'];

        $esp = ESP();
        $esp->get_super_passes();
        $super_pass = $esp->get_super_pass_by_id( $id );

        // Remove all events for this super pass
        delete_post_meta( $super_pass->id, 'ESP_SUPER_PASS_EVENT' );
        delete_post_meta( $super_pass->id, 'ESP_SUPER_PASS_ADDONS' );

        $super_pass->name = $name;
        $super_pass->cost = $cost;
        $super_pass->event = $event;

        update_post_meta( $super_pass->id, 'ESP_SUPER_PASS_ADDON', $add_ons );
        $updated = $super_pass->update();
        $result['success'] = $updated;
        $result['message'] = $updated ? 'Superpass successfully updated' : 'Something went wrong';
    }

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        header( "Content-type: application/json" );
        echo json_encode( $result );
        wp_die();
    }
}
add_action( 'wp_ajax_esp_update_super_pass', 'esp_update_super_pass' );

function esp_delete_super_pass() {
    $result = array(
        'success' => false,
    );

    if ( isset( $_POST[ 'id' ] ) ) {
        $esp = ESP();
        $id = (int) $_POST[ 'id' ];
        foreach( $esp->super_passes as $super_pass ) {
            if ( $super_pass->id === $id ) {
                $super_pass->self_destruct();
                $result = array(
                    'success' => true,
                    'message' => "Super Pass deleted."
                );
            }
        }
    }

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        header( "Content-type: application/json" );
        echo json_encode( $result );
        wp_die();
    }
}
add_action( 'wp_ajax_esp_delete_super_pass', 'esp_delete_super_pass' );

function esp_confirm_eb_order() {
    $result = array(
        'success' => false,
        'message' => ''
    );
    /*
    if( isset( $_POST['attendance_id'] )) {
        $attendance_id = $_POST['attendance_id'];
        $order_id = $_POST['order_id'];
        $attendance_record = new ESP_Attendance_Record( null, null, null,$attendance_id );
        if ( isset( $attendance_record->event_id ) ) {
            $attendance_record->set_confirmed( $order_id );
            $result['success'] = true;
            $result['message'] = "Ticket purchase successful";
            $result['redirect'] = get_site_url( null, '/my-account/superpass' );
        }
    }
    */
    if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        header( 'Content-type: application/json' );
        echo json_encode( $result );
        wp_die();
    }
}
add_action( 'wp_ajax_esp_confirm_eb_order', 'esp_confirm_eb_order' );

function esp_cancel_eb_order() {
    $esp = ESP();
    $result = [
        'success' => false,
        'message' => "Something went wrong. Please try again."
    ];
    /*
    $current_user_id = get_current_user_id();
    $customer = $esp->get_customer_by_id( $current_user_id );
    $attendance_id = $_POST['attendance_id'];

    foreach( $customer->attending as $record ) {
        if( $record->id === (integer)$attendance_id ) {
            // Check on order.
            $res = $esp->eb_sdk->client->get(
                "/discounts/{$record->coupon_eb_id}/",
                array()
            );
            if ( $res['quantity_sold'] === 1 ) {
                // An order has already been made using the user's one time generated discount code.
                // They must cancel the order through Eventbrite in order to change event slots.
                $result = array(
                    'success' => false,
                    'message' => "You have already received your tickets from Eventbrite, please cancel your ticket" .
                        " through Eventbrite. <a target='blank' href='https://www.eventbrite.ca/support/articles/en_US/How_To/how-to-cancel-your-free-registration?lg=en_CA'>How to cancel my ticket</a>"
                );
            } else {
                // The order hasn't been placed yet. Cancel the one time coupon code.
                $record->delete_coupon();
                if ( isset( $res['id'] ) ) {
                    $result = array(
                        'success' => true,
                        'message' => "You are no longer set to attend this event. Feel free to choose another event in this timeslot.",
                    );
                }
            }
        }
    }
    */
    if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        header( 'Content-type: application/json' );
        echo json_encode( $result );
        wp_die();
    }
}
add_action( 'wp_ajax_esp_cancel_eb_order', 'esp_cancel_eb_order' );

function esp_get_extended_attendance() {
    $attending_events = apply_filters( 'esp_get_extended_attendance_record', '' );

    if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        header( 'Content-type: application/json' );
        echo json_encode( array( 'attending_events' => $attending_events ) );
        wp_die();
    }
}
add_action( 'wp_ajax_esp_get_extended_attendance', 'esp_get_extended_attendance_record' );

/**
 * Register an eventbrite order after checkout
 *
 * @since 1.0
 * @return void
 */
function esp_register_eb_order() {
    $result = array(
        'success' => false,
        'message' => ''
    );

    $user_id = get_current_user_id();
    if ( $user_id !== 0  && $_POST['event_id'] && $_POST['order_id'] ) {
        $customer = new ESP_Customer( $user_id );
        $order_id = sanitize_text_field( $_POST['order_id'] );
        $event_id = sanitize_text_field( $_POST['event_id'] );

        $result['success'] = $customer->register_eb_order( $order_id, $event_id );
        $customer->get_eb_orders();
        $result['customer'] = $customer;
    }

    if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        header( 'Content-type: application/json' );
        echo json_encode( $result );
        wp_die();
    }
}
add_action( 'wp_ajax_esp_register_eb_order', 'esp_register_eb_order' );

function esp_customer_attend_event() {
    $result = array(
        'success' => false,
        'message' => ""
    );

    if ( isset( $_POST['workshop'] ) ) {
        $id = get_current_user_id();
        $workshop = sanitize_text_field( $_POST['workshop'] );

        if ( $id ) {
            $customer = new ESP_Customer( $id );
            $pass_purchased = $customer->check_if_pass_purchased();

            if( $pass_purchased ) {
                $check = apply_filters( 'esp_check_event_overlap', $id, $workshop );
                if( $check['result'] === true ) {
                    $record = new ESP_Workshop_Attendance( false, $workshop, $id );
                    if ( $record->id ) {
                        $result = array(
                            'success' => true,
                            'message' => "You are now participating in this workshop",
                            'result' => $record,
                        );
                    }
                } else {
                    $result['message'] = $check["message"];
                }
            } else {
                $result['message'] = "Pass not yet purchased";
            }
        }
    } else {
        $result['message'] = "Workshop not selected.";
    }

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        header( "Content-type: application/json" );
        echo json_encode( $result );
        wp_die();
    }
}
add_action( 'wp_ajax_esp_customer_attend_event', 'esp_customer_attend_event' );


function esp_get_main_pass_data() {
    $esp = ESP();
    $esp->get_super_passes();

    // For now just use the first super pass
    $esp->get_events( true );
    $super_pass = $esp->super_passes[0];
    $super_pass->gather_event_data();

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        header( "Content-type: application/json" );
        echo json_encode( $super_pass );
        wp_die();
    }
}
add_action( 'wp_ajax_esp_get_main_pass_data', 'esp_get_main_pass_data' );
add_action( 'wp_ajax_nopriv_esp_get_main_pass_data', 'esp_get_main_pass_data' );

function esp_get_customer() {
    $customer = array();

    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $customer = new ESP_Customer( $user_id );
        $customer->get_eb_orders();
        if( isset( $_POST['attendance'] ) ) {
            $customer->gather_attendance_records();
        }
    }


    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        header( "Content-type: application/json" );
        echo json_encode( $customer );
        wp_die();
    }
}
add_action( 'wp_ajax_esp_get_customer', 'esp_get_customer' );
add_action( 'wp_ajax_nopriv_esp_get_customer', 'esp_get_customer' );