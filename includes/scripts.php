<?php
/**
 * Scripts
 *
 * @package ESP
 * @subpackage Functions
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined ( 'ABSPATH' ) ) exit;

/**
 * Load Scripts
 *
 * @since 1.0
 * @param string $hook Page hook
 * @return void
 */
function load_scripts( $hook ) {
    $js_dir = ESP_PLUGIN_URL . 'assets/js/';
    // Get Vue, we're going to use the development version for now
    wp_enqueue_script('vue', 'https://cdn.jsdelivr.net/npm/vue/dist/vue.js', []);
    
    // Only load the following if we are on our admin management page
    if ( $hook === 'toplevel_page_eventbrite-superpass') {
        wp_enqueue_script('axios', 'https://unpkg.com/axios@0.19.0/dist/axios.min.js', [], '0.19.0');
        wp_enqueue_style( 'materialize', 'https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css', [], '1.0.0' );
        wp_enqueue_style( '', 'https://fonts.googleapis.com/icon?family=Material+Icons' );
        wp_enqueue_script( 'materialize', 'https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js', [], '1.0.0' );
        wp_register_script( 'esp-admin-scripts', $js_dir . 'admin.js', [], ESP_VERSION, false );
        wp_enqueue_script( 'esp-admin-scripts' );
        wp_localize_script( 'esp-admin-scripts', 'ajax_object', array( 'ajax_url' => admin_url('admin-ajax.php', '') ) );
    }
}

add_action( 'admin_enqueue_scripts', 'load_scripts', 100 );