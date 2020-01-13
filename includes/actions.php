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

    // Setup api key && client_secret
    if ( ! empty( $_POST[ 'api_key' ] ) && ! empty( $_POST[ 'client_secret'] ) ) {
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
        }
    }

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        header( 'Content-type: application/json' );
        echo json_encode( $result );
        wp_die();
    }
}
add_action( 'wp_ajax_setup_esp_eventbrite_keys', 'setup_esp_eventbrite_keys', 10 );