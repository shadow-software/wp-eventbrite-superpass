<?php
/**
 * Admin Actions
 *
 * @package     ESP
 * @subpackage  Admin/Page
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function render_admin_view() {
    ?>
    <div class="container">
        <div class="row col s12 m6" style="margin-top: 2rem;">
            <div class="card grey darken-4" style="margin:0 auto;">
                <div class="card-content white-text">
                    <span class="card-title">First Time Setup</span>
                    <div class="row">
                        <div class="input-field col s12">
                            <input placeholder="Placeholder" id="api_key" type="text" class="grey-text validate">
                            <label for="api_key">Eventbrite API Key</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field col s12">
                            <input placeholder="Placeholder" id="access_code" type="text" class="grey-text validate">
                            <label for="access_code">Eventbrite Access Code</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field col s12">
                            <input placeholder="Placeholder" id="secret" type="text" class="grey-text validate">
                            <label for="secret">Eventbrite Secret</label>
                        </div>
                    </div>
                <div class="card-action" style="padding-left:0;padding-right:0;">
                    <a class="white-text waves-effect waves-light green darken-2 btn right disabled">Next</a>
                </div>
            </div>
        </div>
        <br />
        <div class="divider"></div>
        <div class="section">
            <span class="right">Created by <a href="<?php echo SHADOW_SITE_URL; ?>">Shadow Software Solutions.</a></span>
        </div>
    </div>
    <?php
}

function setup_menu() {
    add_menu_page( 'Eventbrite Superpass', 'Eventbrite Superpass', 'manage_options', 'eventbrite-superpass', 'init', 'dashicons-tickets', 10 );
}

function init() {
    render_admin_view();
}

add_action( 'admin_menu', 'setup_menu' );