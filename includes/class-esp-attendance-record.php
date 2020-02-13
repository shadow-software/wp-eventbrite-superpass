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
     * Unique one time discount code usage
     *
     * @since 1.0
     * @access public
     * @var string
     */
    public $coupon;

    /**
     * Eventbrite Object's ID for the coupon
     *
     * @since 1.0
     * @access public
     * @var string
     */
    public $coupon_eb_id;

    /**
     * Eventbrite Object's ID for the order that was placed
     *
     * @since 1.0
     * @access public
     * @var string
     */
    public $order_id;

    /**
     * Has this ticket purchase been confirmed? (via Eventbrite's checkout order complete callback)
     *
     * @since 1.0
     * @access public
     * @var boolean
     */
    public $confirmed;

    /**
     * ESP_Attendance_Record constructor.
     *
     * @param integer $id
     * @param integer $super_pass_id
     * @param integer $event_id
     * @param integer $user_id
     * @throws Exception
     * @since 1.0
     * @access public
     */
    public function __construct( $super_pass_id = null, $event_id = null, $user_id = null, $id = null ) {
        if ( $super_pass_id && $event_id && $user_id) {
            $this->super_pass_id    = $super_pass_id;
            $this->event_id         = $event_id;
            $this->user_id          = $user_id;
            // If the record is being constructed this way than it means that it has not reached the purchasing stage.
            $this->confirmed        = false;
        }

        $this->save( $id );
    }

    /**
     * Save this to the wordpress database
     *
     * @param $id
     * @return void
     * @throws Exception
     * @since 1.0
     * @access public
     */
    public function save( $id ) {
        if( $id === null ) {
            // Save to DB
            $postarr = array(
                'post_title' => 'Eventbrite Attendance Record',
                'post_type' => 'ESP_RECORD',
                'post_content' => '',
            );

            $this->id = wp_insert_post( $postarr, true );

            // Save meta data
            add_post_meta( $this->id, 'ESP_RECORD_SP_ID', $this->super_pass_id );
            add_post_meta( $this->id, 'ESP_RECORD_USER_ID', $this->user_id );
            add_post_meta( $this->id, 'ESP_RECORD_EVENT_ID', $this->event_id );
            $this->create_coupon();
        } else {
            $this->id = $id;
            $this->unpack();
        }
    }

    /**
     * Get all related meta data from DB
     *
     * @since 1.0
     * @access public
     * @return void
     */
    public function unpack() {
        $values = get_post_meta( $this->id );

        if ( isset( $values['ESP_RECORD_SP_ID'] ) ) {
            $this->super_pass_id = $values['ESP_RECORD_SP_ID'][0];
        }

        if ( isset( $values['ESP_RECORD_USER_ID'] ) ) {
            $this->user_id = $values['ESP_RECORD_USER_ID'][0];
        }

        if ( isset( $values['ESP_RECORD_EVENT_ID'] ) ) {
            $this->event_id = $values['ESP_RECORD_EVENT_ID'][0];
        }

        if ( isset( $values['ESP_RECORD_COUPON'] ) ) {
            $this->coupon = $values['ESP_RECORD_COUPON'][0];
        }

        if ( isset( $values['ESP_RECORD_CONFIRMED'] ) ) {
            $this->confirmed = $values['ESP_RECORD_CONFIRMED'][0];
        }

        if ( isset( $values['ESP_EB_COUPON_ID'] ) ) {
            $this->coupon_eb_id = $values['ESP_EB_COUPON_ID'][0];
        }

        if( isset( $values['ESP_EB_ORDER_ID'] ) ) {
            $this->order_id = $values['ESP_EB_ORDER_ID'][0];
        }

    }

    /**
     * Generate secure key for coupon
     *
     * @return void
     * @throws Exception
     * @since 1.0
     * @access public
     */
    public function create_coupon() {
        $current_user = wp_get_current_user();
        $this->coupon = $current_user->first_name . '-' . $current_user->last_name . '-' . sha1( random_bytes( 24 ) );
        add_post_meta( $this->id, 'ESP_RECORD_COUPON', $this->coupon );
        // Create coupon in Eventbrite's system
        $esp = ESP();
        $id = $esp->eb_user["id"];
        $result = $esp->eb_sdk->client->post(
            "/organizations/$id/discounts/",
            array(
                "discount" => array(
                    "type" => "coded",
                    "code" => $this->coupon,
                    "percent_off" => "100",
                    "quantity_available" => 1,
                    "event_id" => $this->event_id,
                ),
            )
        );
        // Save the Eventbrite ID for later use.
        $this->coupon_eb_id = $result["id"];
        add_post_meta( $this->id, 'ESP_EB_COUPON_ID', $this->coupon_eb_id );
    }

    /**
     * Delete the coupon (used for removing attendance record before purchase)
     *
     * @return void
     * @since 1.0
     * @access public
     */
    public function delete_coupon() {
        $esp = ESP();
        $result = $esp->eb_sdk->client->delete(
            "/discounts/{$this->coupon_eb_id}/",
            array()
        );
    }

    /**
     * Ticket purchase has been confirmed
     *
     * @since 1.0
     * @param $order_id
     * @param $confirmed
     * @access public
     * @return void
     */
    public function set_confirmed( $order_id, $confirmed = true ) {
        $this->order_id = $order_id;
        add_post_meta( $this->id, 'ESP_EB_ORDER_ID', $this->order_id );
        $this->confirmed = $confirmed;
        add_post_meta( $this->id, 'ESP_RECORD_CONFIRMED', $this->confirmed );
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