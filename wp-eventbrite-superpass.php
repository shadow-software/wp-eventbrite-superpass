<?php
/**
 * Plugin Name: WP Eventbrite Superpass
 * Plugin URI: https://shadowsoftware.solutions
 * Description: WP Event Superpass allows event organizers using eventbrite to sell all inclusive passes that gives users access to any event that they are managing.
 * Author: Simon Chawla
 * Author URI: https://shadowsoftware.solutions
 * Version: 0.0.1
 * Text Domain: wp-eventbrite-superpass
 * Domain Path: languages
 *
 * WP Eventbrite Superpass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WP Eventbrite Superpass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WP Eventbrite Superpass. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package ESP
 * @category Core
 * @author Simon Chawla
 * @version 0.0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_Eventbrite_Superpass' ) ) :

    /**
     *  Main Class
     *
     * @since 1.0
     */
    final class WP_Eventbrite_Superpass {
        // Modeling this class based on the Singleton method.

        /**
         * @var WP_Eventbrite_Superpass The one instance of WP_Eventbrite_Superpass
         *
         * @since 1.0
         */
        private static $instance;

        /**
         * Post ID for ESP Settings and Data. Used for writing & reading from DB
         *
         * @var int
         * @since 1.0
         */
        private $post_id;

        /**
         * Eventbrite API Key
         *
         * @var string
         * @since 1.0
         */
        public $api_key;

        /**
         * Eventbrite Client Secret
         *
         * @var string
         * @since 1.0
         */
        public $client_secret;

        /**
         * Eventbrite Access Code
         *
         * @var string
         * @since 1.0
         */
        public $access_code;

        /**
         * Instance of the Eventbrite SDK Wrapper
         *
         * @var object|Eventbrite
         * @since 1.0
         */
        public $eb_sdk;

        /**
         * Main Class Instance
         *
         * Insures that only once instance of the main class exists in memory at one time.
         *
         * @since 1.0
         * @static
         * @staticvar array $instance
         * @uses WP_Eventbrite_Superpass::setup_constants() Setup constants needed
         * @uses WP_Eventbrite_Superpass::includes() Include needed files
         * @return object|WP_Eventbrite_Superpass
         */
        public static function instance() {
            if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_Eventbrite_Superpass ) ) {
                self::$instance = new WP_Eventbrite_Superpass();
                self::$instance->setup_constants();
                self::$instance->init_db();
                self::$instance->get_eventbrite_keys();

                self::$instance->includes();
                self::$instance->eb_sdk = new ESP_Eventbrite_SDK_Wrapper();
                self::$instance->compile_settings();
            }

            return self::$instance;
        }

        /**
         * Prevent object cloning
         *
         * @since 1.0
         * @access protected
         * @return void
         */
        public function __clone() {
            _doing_it_wrong( __FUNCTION__, "Don't clone me man!", "1.0");
        }

        /**
         * Prevent unserializing of this class
         *
         * @since 1.0
         * @access protected
         * @return void
         */
        public function __wakeup() {
            _doing_it_wrong( __FUNCTION__, "No unpacking this for later!", "1.0" );
        }

        /**
         * Set eventbrite keys
         *
         * @return void
         * @since 1.0
         * @access public
         * @param $eb_keys - Array of Keys
         */
        public function set_eventbrite_keys( $eb_keys ) {
            if ( isset( $eb_keys[ 'api_key' ] ) ) {
                self::$instance->api_key        = $eb_keys[ 'api_key' ];
            }
            if ( isset( $eb_keys[ 'access_code' ] ) ) {
                self::$instance->access_code    = $eb_keys[ 'access_code' ];
            }
            if ( isset( $eb_keys[ 'access_code' ] ) ) {
                self::$instance->client_secret  = $eb_keys[ 'client_secret' ];
            }

            // When we set the event keys we want to save them to the database, overwriting any old info
            update_post_meta( self::$instance->post_id, 'EVENTBRITE_API_KEY', self::$instance->api_key );
            update_post_meta( self::$instance->post_id, 'EVENTBRITE_ACCESS_CODE', self::$instance->access_code );
            update_post_meta( self::$instance->post_id, 'EVENTBRITE_CLIENT_SECRET', self::$instance->client_secret );
        }

        /**
         * Include required files.
         *
         * @access private
         * @since 1.0
         * @return void
         */
        private function includes() {
            // Third Party Stuff
            require_once ESP_PLUGIN_DIR . 'includes/class-esp-eventbrite-sdk-wrapper.php';

            // Admin
            require_once ESP_PLUGIN_DIR . 'includes/admin/admin_page.php';

            // Functions
            require_once ESP_PLUGIN_DIR . 'includes/actions.php';

            // Misc
            require_once  ESP_PLUGIN_DIR . 'includes/scripts.php';
        }

        /**
         * Setup plugin constants
         *
         * @access private
         * @since 1.0
         * @return void
         */
        private function setup_constants() {

            // Plugin version
            if ( ! defined( 'ESP_VERSION' ) ) {
                define( 'ESP_VERSION', '1.0' );
            }

            // Plugin Folder Path
            if ( ! defined ( 'ESP_PLUGIN_DIR' ) ) {
                define( 'ESP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
            }

            // Plugin Folder URL
            if ( ! defined ( 'ESP_PLUGIN_URL' ) ) {
                define( 'ESP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
            }

            // Plugin Root File
            if ( ! defined ( 'ESP_PLUGIN_FILE' ) ) {
                define( 'ESP_PLUGIN_FILE', __FILE__ );
            }

            // Shadow Website, for plugs n' stuff
            if ( ! defined ( 'SHADOW_SITE_URL' ) ) {
                define( 'SHADOW_SITE_URL', "https://shadowsoftware.solutions");
            }
        }

        /**
         * Collect Eventbrite, API Key, Secret and Access Code from the DB, if no entry exists, set it up
         *
         * @access private
         * @since 1.0
         * @return void
         */
        private function get_eventbrite_keys() {
            // Get values from the DB if they exist
            $values = get_post_meta( self::$instance->post_id );

            if ( isset( $values[ 'EVENTBRITE_API_KEY' ] ) ) {
                self::$instance->api_key = $values[ 'EVENTBRITE_API_KEY' ];
            }

            if ( isset( $values[ 'EVENTBRITE_CLIENT_SECRET' ] ) ) {
                self::$instance->client_secret =  $values[ 'EVENTBRITE_CLIENT_SECRET' ];
            }

            if ( isset( $values [ 'EVENTBRITE_ACCESS_CODE' ] ) ) {
                self::$instance->access_code = $values [ 'EVENTBRITE_ACCESS_CODE' ];
            }
        }

        /**
         * Create blank entries for the Eventbrite key in the database if the keys don't exist
         *
         * @access private
         * @since 1.0
         * @return mixed
         */
        private function init_db() {

            $post = get_page_by_title( 'ESP', OBJECT, 'ESP' );

            if ( !$post ){
                $postarr = [
                    'post_title' => 'ESP',
                    'post_type' => 'ESP',
                    'post_content' => '',
                ];

                $post_id = wp_insert_post( $postarr );

                if ( $post_id != 0 ) {
                    // Post successfully created
                    self::$instance->post_id = $post_id;
                } else {
                    // Creation failed.
                    self::$instance->post_id = false;
                }
            } else {
                self::$instance->post_id = $post->ID;
            }

        }

        /**
         * Check if the Eventbrite keys we have are valid by attempting to connect
         *
         * @access public
         * @since 1.0
         * @return boolean
         */
        public function eventbrite_keys_valid() {
            // If none of the keys have been set, obviously it's not valid
            if ( ! isset( self::$instance->api_key ) && ! isset( self::$instance->client_secret ) ) {
                return false;
            }

            // Let's check if we can connect to Eventbrite.
            return self::$instance->eb_sdk->connect( self::$instance->access_code, self::$instance->client_secret, self::$instance->api_key );
        }

        /**
         * Setup WP Eventbrite Superpass' global settings
         *
         * @access private
         * @since 1.0
         * @return void
         */
        private function compile_settings() {
            global $esp_settings;

            $connection_result = self::$instance->eventbrite_keys_valid();
            $esp_settings = array(
                'eventbrite_setup_required' => true,
            );
        }
    }

endif; // End class check

/**
 * The main function that returns the one instance of our main class
 *
 * Usage: <?php $esp = ESP(); ?>
 *
 * @since 1.0
 * @return object|WP_Eventbrite_Superpass
 */
function ESP() {
    return WP_Eventbrite_Superpass::instance();
}

// Start the main class
ESP();