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
 * @return voids
 */
function dependency_notice(){
    ?><div class="error"><p>Sorry, WP Eventbrite Superpass requires Woocommerce in order to be used.</p></div><?php
}

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
 * Get current super passes
 *
 * @since 1.0
 * @return void
 */
function esp_get_super_passes() {
    if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        $esp = ESP();
        header( "Content-type: application/json" );
        echo json_encode( $esp->super_passes );
        wp_die();
    }
}
add_action( 'wp_ajax_esp_get_super_passes', 'esp_get_super_passes', 10 );

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

    if ( ! empty( $_POST[ 'name' ] && ! empty( $_POST[ 'cost' ] ) && ! empty( $_POST[ 'events' ] ) ) ) {
        $name = sanitize_text_field( $_POST[ 'name' ] );
        $cost = sanitize_text_field( $_POST[ 'cost' ] );

        $events = isset( $_POST['events'] ) ? (array) $_POST['events'] : array();
        $events = array_map( 'esc_attr', $events );
        $super_pass = new ESP_Super_Pass( $cost, $name );

        // We want to make sure each event is existing and is okay to add.
        foreach( $events as $event ) {
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