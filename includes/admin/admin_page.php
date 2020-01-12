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
    <div class="container" id="esp-admin-app" style="display:none">
        <div v-if="!ready" class="row col s12 m6" style="display:flex;padding-top: 2rem;justify-content: center;">
            <div class="preloader-wrapper big active">
                <div class="spinner-layer spinner-green-only">
                    <div class="circle-clipper left">
                        <div class="circle"></div>
                    </div><div class="gap-patch">
                        <div class="circle"></div>
                    </div><div class="circle-clipper right">
                        <div class="circle"></div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="ready" class="col s12 m6" style="margin-top: 2rem;">
            <div class="card grey darken-4" style="margin:0 auto;margin-bottom: 1rem;">
                <div class="card-content white-text">
                    <span class="card-title">First Time Setup</span>
                    <div class="row">
                        <div class="input-field col s12">
                            <input v-model="eventbriteData.appKey" placeholder="Placeholder" id="api_key" type="text" class="grey-text validate">
                            <label for="api_key">Eventbrite API Key</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field col s12">
                            <input v-model="eventbriteData.accessCode" placeholder="Placeholder" id="access_code" type="text" class="grey-text validate">
                            <label for="access_code">Eventbrite Access Code</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field col s12">
                            <input v-model="eventbriteData.clientSecret" placeholder="Placeholder" id="client_secret" type="text" class="grey-text validate">
                            <label for="secret">Eventbrite Secret</label>
                        </div>
                    </div>
                    <div class="card-action" style="padding-left:0;padding-right:0;">
                        <a :click="eventbriteSetup" class="white-text waves-effect waves-light green darken-2 btn right" :class="{'disabled' : !checkEventbriteData(), 'pulse' : checkEventbriteData()}">Next</a>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="card red darken-4 white-text" style="margin:0 auto;">
                    <h6 style="margin: 0;">Test!</h6>
                </div>
            </div>
            <br />
            <div class="divider"></div>
            <div class="section">
                <span class="right">Created by <a href="<?php echo SHADOW_SITE_URL; ?>">Shadow Software Solutions.</a></span>
            </div>
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