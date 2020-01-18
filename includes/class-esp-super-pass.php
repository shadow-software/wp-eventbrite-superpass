<?php
/**
 * WP Eventbrite Superpass - Super Pass
 *
 * This is the meat of ESP, this is our object that connects to the users Eventbrite events and allows users to be
 * able to select the events the want to use the super pass for.
 *
 * @package     ESP
 * @subpackage  Classes/Super-Pass
 * @license     http://opensource.org/license/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 *
 * Super Pass Class
 *
 * @since 1.0
 */
class ESP_Super_Pass {

    /**
     * ID of DB Entry
     *
     * @var int
     * @since 1.0
     */
    public $id;

    /**
     * Cost of Super Pass
     *
     * @var decimal
     * @since 1.0
     */
    public $cost;

    /**
     * Name of the Super Pass
     *
     * @var string
     * @since 1.0
     */
    public $name;

    /**
     * Array of connected Eventbrite events
     *
     * @var array
     * @since 1.0
     */
    public $events = array();

    /**
     * ESP_Super_Pass constructor.
     *
     * @param $cost
     * @param $name
     * @param int $id - If this has already been saved to the DB we don't need to setup again.
     * @since 1.0
     */
    public function __construct( $cost, $name, $id = null) {
        $this->cost = $cost;
        $this->name = $name;

        if ( ! $id ) {
            // Save to DB
            $postarr = [
                'post_title' => $name,
                'post_type' => 'ESP_SUPER_PASS',
                'post_content' => '',
            ];

            $this->id = wp_insert_post( $postarr );

            // Save cost as meta data.
            add_post_meta( $this->id, 'ESP_SUPER_PASS_COST', $cost );
        } else {
            $this->id = $id;
        }
    }

    /**
     * Add specific event
     *
     * @param $event_id
     * @since 1.0
     * @access public
     * @return boolean
     */
    public function add_event( $event_id ) {
        array_push( $this->events, $event_id );
        // Add as meta
        add_post_meta( $this->id, 'ESP_SUPER_PASS_EVENT', $event_id );
        $this->gather_event_data();
        return true;
    }

    /**
     * Remove specific event
     *
     * @param $event_id
     * @since 1.0
     * @access public
     * @return boolean
     */
    public function remove_event( $event_id ) {
        $key = array_search( $event_id, $this->events );
        // Remove from meta
        delete_post_meta( $this->id, "ESP_SUPER_PASS_EVENT", $event_id );
        if ( $key ) {
            array_splice( $this->events, $key, 1 );
        }
        return $key !== false;
    }

    /**
     * Replace the array of event ID's with actual data from Eventbrite's API
     *
     * @since 1.0
     * @access public
     * @return void
     */
    public function gather_event_data() {
        $esp = ESP();
        foreach( $this->events as &$event ) {
            if ( ! is_array( $event ) || ! is_object( $event )) {
                foreach( $esp->events as $event_data ) {
                    if ( $event_data[ 'id' ] === $event ) {
                        $event = $event_data;
                        break;
                    }
                }
            }
        }
    }

    /**
     * Goodbye :( (delete this class and remove it from the database)
     *
     * @since 1.0
     * @access public
     * @return boolean
     */
    public function self_destruct() {
        // Remove it from esp's collection.
        $esp = ESP();
        foreach( $esp->super_passes as &$super_pass ) {
            if ( $super_pass->id === $this->id ) {
                unset( $super_pass );
            }
        }
        // Remove from DB
        $result = wp_delete_post( $this->id );
        return $result !== false && $result !== null;
    }
}