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
    $css_dir = ESP_PLUGIN_URL . 'assets/css/';

    // Get Vue, we're going to use the development version for now
    wp_enqueue_script('vue', 'https://cdn.jsdelivr.net/npm/vue/dist/vue.js', []);

    // Only load the following if we are on our admin management page
    if ( $hook === 'toplevel_page_eventbrite-superpass') {
        wp_enqueue_script('axios', 'https://unpkg.com/axios@0.19.0/dist/axios.min.js', [], '0.19.0');
        wp_register_style( 'bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css', [], '4.4.1' );
        wp_enqueue_style( 'bootstrap');
        wp_enqueue_style( 'extra', $css_dir . 'extra.css' );
        wp_style_add_data( 'bootstrap', array( 'integrity', 'crossorigin' ) , array( 'sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh', 'anonymous' ) );
        wp_register_script( 'bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js', ['jquery'], '4.4.1' );
        wp_enqueue_script( 'bootstrap');
        wp_script_add_data( 'bootstrap', array( 'integrity', 'crossorigin' ) , array( 'sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6', 'anonymous' ) );
        wp_register_script( 'esp-admin-scripts', $js_dir . 'admin.js', [], ESP_VERSION, false );
        wp_register_script( 'esp-misc-scripts', $js_dir . 'helpers.js', [], ESP_VERSION, false );
        wp_enqueue_script( 'esp-admin-scripts' );
        wp_enqueue_script( 'esp-misc-scripts' );
        wp_localize_script( 'esp-admin-scripts', 'ajax_object', array( 'ajax_url' => admin_url('admin-ajax.php', '') ) );
    }
}

add_action( 'admin_enqueue_scripts', 'load_scripts', 100 );