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
        <div v-if="!ready" class="row" style="display:flex;padding-top: 2rem;justify-content: center;">
            <div class="text-center">
                <div class="spinner-border text-success" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </div>
        <?php do_action( 'esp_admin_setup' ); ?>
        <?php do_action( 'esp_admin_main' );?>
        <br />
        <hr />
        <div class="section text-right">
            <span>Created by <a href="<?php echo SHADOW_SITE_URL; ?>">Shadow Software Solutions.</a></span>
        </div>
    </div>
    <?php
}

function setup() {
    ?>
    <div v-if="ready" class="" style="margin-top: 2rem;">
        <div v-if="!this.settings.eventbrite_setup_required" class="card bg-dark text-white" style="margin:0 auto;margin-bottom: 1rem;">
            <div class="card-body">
                <span class="card-title">Congratulations!</span>
                <span class="card-text">You have successfully connected to eventbrite.</span>
            </div>
        </div>
        <?php do_action( 'esp_admin_setup_help' ); ?>
        <div v-if="this.settings.eventbrite_setup_required" class="card m-auto bg-dark text-white">
            <span class="card-title">
                <div style="font-size:2em;">First Time Setup</div>
            </span>
            <div class="card-body">
                <div class="row">
                    <div class="form-group w-100">
                        <label for="api_key">Eventbrite API Key</label>
                        <input v-model="eventbriteData.appKey" placeholder="Placeholder" id="api_key" type="text" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group w-100">
                        <label for="secret">Eventbrite Secret</label>
                        <input v-model="eventbriteData.clientSecret" placeholder="Placeholder" id="client_secret" type="text" class="form-control">
                    </div>
                </div>
                <div v-if="message.show && message.type === 'success'" class="row">
                    <div class="card green darken-4 white-text" style="margin:0 auto;">
                        <h6 style="margin: 0;">{{ message.content }}</h6>
                    </div>
                </div>
                <div class="row float-right">
                    <a v-on:click="eventbriteSetup" class="btn btn-primary text-white px-4" :class="{'disabled' : !checkEventbriteData()}">Next</a>
                </div>
            </div>
        </div>
        <div v-if="message.show && message.type === 'error'" class="row">
            <div class="card red darken-4 white-text" style="margin:0 auto;">
                <h6 style="margin: 0;">{{ message.content }}</h6>
            </div>
        </div>
    </div>
    <?php
}
add_action( 'esp_admin_setup', 'setup' );

function main() {
    ?>
        <div v-if="!this.settings.eventbrite_setup_required && ready" class="row">
            <div class="card border-primary mb-2 w-100 p-0 m-auto" style="max-width:unset">
                <div class="card-header">Manage Super Passes</div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-dark">
                        <tr>
                            <th>Name</th>
                            <th>Cost</th>
                            <th>Included Events</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="superpass in superPasses">
                            <td>{{superpass.name}}</td>
                            <td>${{superpass.cost}}</td>
                            <td style="position: relative;">
                                <ul>
                                    <li v-for="event in superpass.events"><a :href="event.url" target="_blank">{{ event.name.text }}</a></li>
                                </ul>
                                <div style="position: absolute;top: calc(30%);right: 20px;">
                                    <a class="btn btn-danger text-white">DELETE</a>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <div v-if="! creatingPass"  class="row">
                       <div v-on:click="creatingPass = true" class="btn btn-primary m-auto">Create a new Super Pass</div>
                    </div>
                    <div v-if="creatingPass" class="card w-100 bg-light" style="max-width: unset;">
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="name">Super Pass Name:</label>
                                    <input v-model="superPass.name" placeholder="Name" id="name" type="text" class="form-control" />
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="cost">Cost of Pass:</label>
                                    <div class="input-group mb-2">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text">$</div>
                                        </div>
                                        <input v-model="superPass.cost" type="text" class="form-control" id="cost" placeholder="0.00">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <ul class="list-group col">
                                <button v-for="event in settings.eventbrite.events" v-on:click="toggleEvent" :value="event.id" type="button" class="list-group-item list-group-item-action" :class="{active : superPass.events.includes(event.id)}">
                                    {{event.name.text}}
                                </button>
                            </ul>
                        </div>
                        <div class="row mt-3">
                            <div class="m-auto">
                                <div v-on:click="createSuperPass" class="btn btn-primary m-auto" :class="{disabled : !superPassValid()}">
                                    <span v-if="!updating">
                                        Create
                                    </span>
                                    <span v-if="updating">
                                        <div class="text-center">
                                            <div class="spinner-border text-light" role="status">
                                                <span class="sr-only">Loading...</span>
                                            </div>
                                        </div>
                                    </span>
                                </div>
                                <div v-on:click="creatingPass = false" class="btn btn-warning m-auto" v-if="!updating">Cancel</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
}
add_action( 'esp_admin_main', 'main' );

function setup_help() {
    ?>
        <div class="accordion" id="accordionExample">
        </div>
    <?php
}
add_action( 'esp_admin_setup_help', 'setup_help' );

function setup_menu() {
    add_menu_page( 'Eventbrite Superpass', 'Eventbrite Superpass', 'manage_options', 'eventbrite-superpass', 'init', 'dashicons-tickets', 10 );
}

function init() {
    render_admin_view();
}

add_action( 'admin_menu', 'setup_menu' );