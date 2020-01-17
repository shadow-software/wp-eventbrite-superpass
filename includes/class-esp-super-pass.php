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
     * @since 1.0
     */
    public function __construct( $cost, $name ) {
        $this->cost = $cost;
        $this->name = $name;
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
        if ( $key ) {
            array_splice( $this->events, $key, 1 );
        }
        return $key !== false;
    }
}