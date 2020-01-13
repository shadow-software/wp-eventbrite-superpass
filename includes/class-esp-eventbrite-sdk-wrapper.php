<?php
/**
 * WP Eventbrite Super Pass - Eventbrite SDK Wrapper
 *
 * Bundles up some classes from the SDK to make everything self contained for this plugin
 *
 * @package     ESP
 * @subpackage  Classes/Eventbrite-SDK-Wrapper
 * @license     http://opensource.org/license/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 *
 * Bundle SDK package in one place for easy access
 *
 * @since 1.0
 */
class ESP_Eventbrite_SDK_Wrapper {

    /**
     * Eventbrite SDK HTTP Client
     *
     * @var object|HttpClient
     * @since 1.0
     */
    public $client;

    /**
     * ESP_Eventbrite_SDK_Wrapper constructor.
     *
     * @since 1.0
     */
    public function __construct() {
        require_once ESP_PLUGIN_DIR . 'includes/libraries/eventbrite-sdk-php/HttpClient.php';
        require_once ESP_PLUGIN_DIR . 'includes/libraries/eventbrite-sdk-php/Authenticate.php';
    }

    /**
     * Test the connection to Eventbrite
     *
     * @since 1.0
     * @access public
     * @param $code
     * @param $client_secret
     * @param $app_key
     * @return mixed
     */
    public function connect( $code, $client_secret, $app_key ) {
        return handshake($code, $client_secret, $app_key);
    }

    /**
     * Get a link to authorize from Eventbrite
     *
     * @since 1.0
     * @access public
     * @param $app_key
     * @return string
     */
    public function createAuthLink( $app_key ) {
        return createAuthorizeUrl( $app_key ) . '&redirect_uri=' . get_admin_url() . 'admin.php?page=eventbrite-superpass';
    }
}
