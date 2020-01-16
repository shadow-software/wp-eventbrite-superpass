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
            <div v-if="!this.settings.eventbrite_setup_required" class="card grey darken-4" style="margin:0 auto;margin-bottom: 1rem;">
                <div class="card-content white-text">
                    <span class="card-title">Congratulations!</span>
                    <span class="card-body">You have successfully connected to eventbrite.</span>
                </div>
            </div>
            <?php do_action( 'admin_setup_help' ); ?>
            <div v-if="this.settings.eventbrite_setup_required" class="card grey darken-4" style="margin:0 auto;margin-bottom: 1rem;">
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
                            <input v-model="eventbriteData.clientSecret" placeholder="Placeholder" id="client_secret" type="text" class="grey-text validate">
                            <label for="secret">Eventbrite Secret</label>
                        </div>
                    </div>
                    <div v-if="message.show && message.type === 'success'" class="row">
                        <div class="card green darken-4 white-text" style="margin:0 auto;">
                            <h6 style="margin: 0;">{{ message.content }}</h6>
                        </div>
                    </div>
                    <div class="card-action" style="padding-left:0;padding-right:0;">
                        <a v-on:click="eventbriteSetup" class="white-text waves-effect waves-light green darken-2 btn right" :class="{'disabled' : !checkEventbriteData(), 'pulse' : checkEventbriteData()}">Next</a>
                    </div>
                </div>
            </div>
            <div v-if="message.show && message.type === 'error'" class="row">
                <div class="card red darken-4 white-text" style="margin:0 auto;">
                    <h6 style="margin: 0;">{{ message.content }}</h6>
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

function setup_help() {
    ?>
        <ul v-if="this.settings.eventbrite_setup_required" class="collapsible">
            <li>
              <div class="collapsible-header"><i class="material-icons">help</i>Click here for help/instructions on connecting to Eventbrite.</div>
              <div class="collapsible-body">

              </div>
            </li>
        </ul>
    <?php
}
add_action( 'admin_setup_help', 'setup_help' );

function setup_menu() {
    add_menu_page( 'Eventbrite Superpass', 'Eventbrite Superpass', 'manage_options', 'eventbrite-superpass', 'init', 'dashicons-tickets', 10 );
}

function init() {
    render_admin_view();
}

add_action( 'admin_menu', 'setup_menu' );