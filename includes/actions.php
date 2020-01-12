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
 * Get the settings that the front end will use
 *
 * @since 1.0
 * @return void
 */
function get_esp_settings() {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        global $esp_settings;
        header( "Content-type: application/json" );
        echo json_encode( $esp_settings );
        wp_die();
    }
}
add_action( 'wp_ajax_get_esp_settings', 'get_esp_settings', 10 );

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

    if ( !empty( $_POST[ 'api_key' ] ) && !empty( $_POST[ 'client_secret'] ) && !empty( $_POST[ 'access_code' ] ) ) {
        $esp = ESP();

        $esp->set_eventbrite_keys( $_POST );

        $result = $esp->eventbrite_keys_valid();
        header( 'Content-type: application/json' );
        echo json_encode( $result );
        wp_die();
        if ( $esp->eventbrite_keys_valid() ){
            $result[ 'success' ] = true;
            $result[ 'message' ] = "Successfully connected to Eventbrite!";
        } else {
            $result[ 'success' ] = false;
            $result[ 'message' ] = "One or more fields were invalid, please check to make sure you have the right information!";
        }
    } else {
        $result[ 'success' ] = false;
        $result[ 'message' ] = "Please fill out all 3 fields";
    }

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        header( 'Content-type: application/json' );
        echo json_encode( $result );
        wp_die();
    }
}
add_action( 'wp_ajax_setup_esp_eventbrite_keys', 'setup_esp_eventbrite_keys', 10 );