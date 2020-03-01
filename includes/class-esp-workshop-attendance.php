<?php
/**
 * WP Eventbrite Superpass - Workshop Attendance Record
 *
 * A record of what workshops users with a conference pass will attend
 *
 * @package     ESP
 * @subpackage  Classes/Workshop Attendance Record
 * @license     http://opensource.org/license/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * ESP Workshop Attendance Record
 *
 * @since 1.0
 */
class ESP_Workshop_Attendance
{

    /**
     * ID of Post Object
     *
     * @var int
     * @since 1.0
     * @access public
     */
    public $id;

    /**
     * Workshop object or post ID of the object
     *
     * @var int|WP_Post
     * @since 1.0
     * @access public
     */
    public $workshop;

    /**
     * Title of record
     *
     * @var string
     * @sicne 1.0
     * @access public
     */
    public $title;

    /**
     * User object or ID of user
     *
     * @var int|WP_User
     * @since 1.0
     * @access
     */
    public $user;

    public function __construct( $id, $workshop = false, $user = false ) {
        if ( $id !== false ) {
            $this->id = $id;
            $this->workshop = get_field( 'workshop', $id, false );
            $this->user = get_field( 'attendee', $id, false );
        } else if( $user && $workshop ) {
            $fullname = "";
            $userdata = get_userdata( $user );
            if( !isset( $userdata->first_name ) || !isset( $userdata->last_name ) ) {
                $first_name = get_user_meta( $user, 'first_name', true );
                $last_name = get_user_meta( $user, 'last_name', true );
                $fullname = $first_name . " " . $last_name;
            } else {
                $fullname = $userdata->first_name . " " . $userdata->last_name;
            }
            $workshop_title = get_the_title( $workshop );
            $this->title = "{$fullname} ({$userdata->user_email}) - {$workshop_title}";
            $postarr = [
                'post_title' => $this->title,
                'post_type' => 'workshop_attendance',
                'post_content' => '',
                'post_status' => 'publish'
            ];

            $this->id = wp_insert_post($postarr);
            $this->workshop = $workshop;
            $this->user = $user;
            update_field( 'attendee', $this->user, $this->id );
            update_field( 'workshop', $this->workshop, $this->id );
            $post = get_post( $this->id );
            do_action( 'save_post', $this->id, $post, false );
        }
    }
}