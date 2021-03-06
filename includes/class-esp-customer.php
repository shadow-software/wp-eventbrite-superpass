<?php
/**
 * WP Eventbrite Superpass - Customer
 *
 * This is to keep track of our Woocommerce customers who have purchased super passes, as well as which events they
 * have selected to attend.
 *
 * @package     ESP
 * @subpackage  Classes/Customer
 * @license     http://opensource.org/license/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

/**
* ESP Customer Class
 *
 * @since 1.0
*/
class ESP_Customer {

    /**
     * Wordpress account associated with this Customer
     *
     * @since 1.0
     * @access public
     * @var integer - WP_User::ID
     */
    public $wp_user_id;

    /**
     * Event Passes purchased
     *
     * @since 1.0
     * @access public
     * @var array
     */
    public $super_passes = array();

    /**
     * A record of which events a user is attending and what pass they are using to attend
     *
     * @since 1.0
     * @access public
     * @var array
     */
    public $attending = array();

    /**
     * ESP_Customer constructor.
     *
     * @since 1.0
     * @access public
     * @param $wp_user_id
     * @return void
     */
    public function __construct( $wp_user_id ) {
        $this->wp_user_id = $wp_user_id;
    }

    /**
     * Gather the attendance records for this customer
     *
     * @since 1.0
     * @access public
     * @return void
     */
    public function gather_attendance_records() {
        $args = array(
            'post_type' => 'ESP_RECORD',
            'post_status' => 'draft',
            'numberposts' => -1,
            'metaquery' => array(
                array(
                    'key' => 'ESP_RECORD_USER_ID',
                    'value' => $this->wp_user_id,
                    'compare' => '=',
                )
            )
        );

        $posts = get_posts( $args );

        foreach( $posts as $post ) {
            $record = new ESP_Attendance_Record( null, null, null, $post->ID );
            $this->attending[] = $record;
        }
    }

    /**
     * Add a Super Pass to this user's collection
     *
     * @since 1.0
     * @param $super_pass
     * @access public
     */
    public function add_super_pass( $super_pass ) {
        array_push( $this->super_passes, $super_pass );
    }

    /**
     * Create an attendance record for this user
     *
     * @param $event_id
     * @param $super_pass_id
     * @return ESP_Attendance_Record
     * @throws Exception
     * @since 1.0
     * @access public
     */
    public function attend_event( $event_id, $super_pass_id ) {
        // Let's add this user to Eventbrite's system.
        $esp = ESP();
        // First let's check if we are already attending this event.
        $attendance_record = null;
        foreach ( $this->attending as $record ) {
            if ( $record->event_id === $event_id && $record->super_pass_id === $super_pass_id ) {
                $attendance_record = $record;
            }
        }
        if ( $attendance_record === null ) {
            $attendance_record = new ESP_Attendance_Record( $super_pass_id, $event_id, $this->wp_user_id );
            array_push( $this->attending, $attendance_record );
        }
        return $attendance_record;
    }

    /**
     * Remove an event from the attendance record
     *
     * @since 1.0
     * @access public
     * @param $event_id
     * @param $super_pass_id
     * @return void
     */
    public function leave_event( $event_id, $super_pass_id ) {
        foreach ( $this->attending as &$attendance_record ) {
            if ( $attendance_record->event_id === $event_id && $super_pass_id === $attendance_record->super_pass_id ) {
                $attendance_record->delete();
                unset($attendance_record);
                break;
            }
        }
    }
}
