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
         * Eventbrite Token
         *
         * Token from Eventbrite's API, gets reset when the token is invalid
         * @var string
         * @since 1.0
         */
        public $token;

        /**
         * Instance of the Eventbrite SDK Wrapper
         *
         * @var object|Eventbrite
         * @since 1.0
         */
        public $eb_sdk;

        /**
         * Collection of exisiting Super Passes
         *
         * @access public
         * @var array|ESP_Super_Pass
         * @since 1.0
         */
        public $super_passes = array();

        /**
         * Collection of Events. Instead of making multiple calls, call once and then associate by ID.
         *
         * @access public
         * @var array
         * @since 1.0
         */
        public $events = array();

        /**
         * Collection of ESP Customers
         *
         * @access public
         * @var array
         * @since 1.0
         */
        public $customers = array();

        /**
         * Eventbrite user object
         *
         * @access public
         * @var array
         * @since 1.0
         */
        public $eb_user;

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
                if ( isset( self::$instance->token ) ) {
                    // Setup client if one time token exists.
                    self::$instance->eb_sdk->setup_client( self::$instance->token );
                }

                self::$instance->get_super_passes();
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
                update_post_meta( self::$instance->post_id, 'EVENTBRITE_API_KEY', self::$instance->api_key );
            }
            if ( isset( $eb_keys[ 'access_code' ] ) ) {
                self::$instance->access_code    = $eb_keys[ 'access_code' ];
                update_post_meta( self::$instance->post_id, 'EVENTBRITE_ACCESS_CODE', self::$instance->access_code );
            }
            if ( isset( $eb_keys[ 'client_secret' ] ) ) {
                self::$instance->client_secret  = $eb_keys[ 'client_secret' ];
                update_post_meta( self::$instance->post_id, 'EVENTBRITE_CLIENT_SECRET', self::$instance->client_secret );
            }
            if( isset( $eb_keys[ 'token' ] ) ) {
                self::$instance->token = $eb_keys[ 'token' ];
                update_post_meta( self::$instance->post_id, 'EVENTBRITE_TOKEN', self::$instance->token );
            }
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
            require_once ESP_PLUGIN_DIR . 'includes/woocommerce/actions.php';

            // Admin
            require_once ESP_PLUGIN_DIR . 'includes/admin/admin_page.php';

            // Functions
            require_once ESP_PLUGIN_DIR . 'includes/actions.php';
            require_once ESP_PLUGIN_DIR . 'includes/ajax-actions.php';

            // Classes
            require_once ESP_PLUGIN_DIR . 'includes/class-esp-super-pass.php';
            require_once ESP_PLUGIN_DIR . 'includes/class-esp-customer.php';
            require_once ESP_PLUGIN_DIR . 'includes/class-esp-attendance-record.php';

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
                self::$instance->api_key = $values[ 'EVENTBRITE_API_KEY' ][0];
            }

            if ( isset( $values[ 'EVENTBRITE_CLIENT_SECRET' ] ) ) {
                self::$instance->client_secret =  $values[ 'EVENTBRITE_CLIENT_SECRET' ][0];
            }

            if ( isset( $values [ 'EVENTBRITE_ACCESS_CODE' ] ) ) {
                self::$instance->access_code = $values [ 'EVENTBRITE_ACCESS_CODE' ][0];
            }

            if ( isset( $values[ 'EVENTBRITE_TOKEN' ] ) ) {
                self::$instance->token = $values[ 'EVENTBRITE_TOKEN' ][0];
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
         * Retrieve the serialized array of passes from DB
         *
         * @access public
         * @since 1.0
         * @return void
         */
        public function get_super_passes() {
            $args = array(
                'post_type' => 'ESP_SUPER_PASS',
                'post_status' => 'draft',
                'numberposts' => -1,
            );
            $results = get_posts( $args );
            foreach ( $results as $result ) {
                $values = get_post_meta( $result->ID );
                $super_pass = new ESP_Super_Pass( $values[ 'ESP_SUPER_PASS_COST' ][0], $result->post_title, $result->ID );
                $super_pass->wc_id = $values[ 'ESP_SUPER_PASS_WC_ID' ][0];
                if ( isset( $values['ESP_SUPER_PASS_EVENT'] ) ){
                    $events = explode( ',', $values[ 'ESP_SUPER_PASS_EVENT'][0] );
                    foreach ( $events as $event ) {
                        $super_pass->events[] = $event;
                    }
                }
                self::$instance->super_passes[] = $super_pass;
            }
        }

        /**
         * Get Super Pass by ID
         *
         * @access public
         * @param $id
         * @return mixed
         * @since 1.0
         */
        public function get_super_pass_by_id( $id ) {
            $found = array_search( $id, array_column( self::$instance->super_passes, 'id' ) );
            if ( $found !== false ) {
                return self::$instance->super_passes[$found];
            } else {
                return false;
            }
        }

        /**
         * Add super pass and save to DB
         *
         * @access public
         * @param ESP_Super_Pass $super_pass
         * @return boolean
         * @since 1.0
         */
        public function add_super_pass( ESP_Super_Pass $super_pass ) {
            array_push( self::$instance->super_passes, $super_pass );
            return true;
        }

        /**
         * Check if the Eventbrite keys we have are valid by attempting to connect
         *
         * @access public
         * @since 1.0
         * @return boolean
         */
        public function eventbrite_keys_valid() {
            if ( isset( self::$instance->token ) ) {
                $user = self::$instance->eb_sdk->client->get('/users/me');
                self::$instance->eb_user = $user;
                return isset( $user['id'] );
            }
            // If none of the keys have been set, obviously it's not valid
            if ( ! isset( self::$instance->api_key ) && ! isset( self::$instance->client_secret ) ) {
                return false;
            }

            // Let's check if we can connect to Eventbrite.
            return self::$instance->eb_sdk->connect( self::$instance->access_code, self::$instance->client_secret, self::$instance->api_key );
        }

        /**
         * Get event from the list of events by id
         *
         * @access public
         * @param $id
         * @return array
         * @since 1.0
         */
        public function get_event_by_id( $id ) {
            if ( ! count( self::$instance->events ) > 0 ) {
                return false;
            }
            if ( ! isset( self::$instance->events[0]['id'] ) ) {
                self::$instance->get_events();
            }
            $found = array_search( $id, array_column( (array) self::$instance->events, 'id' ) );
            return self::$instance->events[$found];
        }

        /**
         * Gather event data from Eventbrite, we don't want to be making this call every time, only when it's
         * needed, doing so every time will lead to performance issues.
         *
         * @access public
         * @since 1.0
         * @return array
         */
        public function get_events() {
            if ( empty( self::$instance->events ) && isset ( self::$instance->eb_sdk->client ) ) {
                self::$instance->events = self::$instance->eb_sdk->client->get('/users/me/events');
                self::$instance->events = self::$instance->events[ 'events' ];
            }

            return self::$instance->events;
        }

        /**
         * If a ESP Customer instance does not exist for this Customer create it and return it. Otherwise return
         * from existing array.
         *
         * @access public
         * @param $id - ID of WP User
         * @since 1.0
         * @return ESP_Customer
         */
        public function get_customer_by_id( $id ) {
            foreach ( self::$instance->customers as $customer ) {
                if ( $customer->wp_user_id === $id ) {
                    return $customer;
                }
            }

            // Customer not found, create an instance.
            $wp_user = get_user_by( "ID", $id );
            $customer = new ESP_Customer( $wp_user->ID );
            array_push( self::$instance->customers, $customer );
            return $customer;
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

            $connection_result  = self::$instance->eventbrite_keys_valid();
            $setup_required     = isset( $connection_result[ 'error' ] ) || $connection_result === false;
            $esp_settings = array(
                'eventbrite_setup_required' => $setup_required,
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