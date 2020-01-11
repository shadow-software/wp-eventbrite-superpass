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

    // Get Vue
    wp_enqueue_script('vue', 'https://cdn.jsdelivr.net/npm/vue@2.6.11', [], '2.6.11');
    
    // Only load the following if we are on our admin management page
    if ( $hook === 'toplevel_page_eventbrite-superpass') {
        wp_enqueue_style( 'materialize', 'https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css', [], '1.0.0' );
        wp_enqueue_script( 'materialize', 'https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js', [], '1.0.0' );
        wp_localize_script( 'esp-admin-scripts', 'ajax_object', array( 'ajax_url' => admin_url('admin-ajax.php', '') ) );
    }
}

add_action( 'admin_enqueue_scripts', 'load_scripts', 100 );