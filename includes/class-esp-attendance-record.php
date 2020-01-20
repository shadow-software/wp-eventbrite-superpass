<?php
/**
 * WP Eventbrite Superpass - Attendance Record
 *
 * A record of which events a customer selects to attend, and which pass they are using for it.
 *
 * @package     ESP
 * @subpackage  Classes/Attendance Record
 * @license     http://opensource.org/license/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * ESP Attendance Record
 *
 * @since 1.0
 */
class ESP_Attendance_Record {

    /**
     * ID of record
     *
     * @since 1.0
     * @access public
     * @var integer
     */
    public $id;

    /**
     * ID of Super Pass being used
     *
     * @since 1.0
     * @access public
     * @var integer
     */
    public $super_pass_id;

    /**
     * ID of Event the user wants to attend
     *
     * @since 1.0
     * @access public
     * @var integer
     */
    public $event_id;

    /**
     * ID of Customer
     *
     * @since 1.0
     * @access public
     * @var integer
     */
    public $user_id;

    /**
     * ESP_Attendance_Record constructor.
     *
     * @param integer $id
     * @param integer $super_pass_id
     * @param integer $event_id
     * @param integer $user_id
     * @since 1.0
     * @access public
     */
    public function __construct( $super_pass_id = null, $event_id = null, $user_id = null, $id = null ) {
        $this->super_pass_id    = $super_pass_id;
        $this->event_id         = $event_id;
        $this->user_id          = $user_id;

        $this->save( $id );
    }

    /**
     * Save this to the wordpress database
     *
     * @since 1.0
     * @access public
     * @param $id
     * @return void
     */
    public function save( $id ) {
        if( $id !== null ) {
            // Save to DB
            $postarr = [
                'post_title' => 'Eventbrite Attendance Record',
                'post_type' => 'ESP_ATTENDANCE_RECORD',
                'post_content' => '',
            ];

            $this->id = wp_insert_post( $postarr );

            // Save meta data
            add_post_meta( $this->id, 'ESP_ATTENDANCE_RECORD_SP_ID', $super_pass_id );
            add_post_meta( $this->id, 'ESP_ATTENDANCE_RECORD_USER_ID', $user_id );
            add_post_meta( $this->id, 'ESP_ATTENDANCE_RECORD_EVENT_ID', $event_id );
        } else {
            $this->id = $id;
        }
    }

    /**
     * Delete this record from DB
     *
     * @since 1.0
     * @access public
     * @return boolean
     */
    public function delete() {
        $result = wp_delete_post( $this->id );

        return $result !== false && $result !== null;
    }
}