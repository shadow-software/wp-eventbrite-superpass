<?php
/**
 * WP Eventbrite Superpass - Workshop
 *
 * Workshop Object
 *
 * @package     ESP
 * @subpackage  Classes/Workshop
 * @license     http://opensource.org/license/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 *
 * Workshop Class
 *
 * @since 1.0
 */
class ESP_Workshop {

    public $id;

    public $title;

    public $description;

    public $date;

    public $start_time;

    public $end_time;

    public $capacity;

    public $image;

    public function __construct( $id ) {
        $this->id           = $id;
        $this->title        = get_the_title( $id );
        $this->description  = get_field( 'description', $id, true );
        $this->date         = get_field( 'date', $id, true );
        $this->start_time   = get_field( 'start_time', $id, true );
        $this->end_time     = get_field( 'end_time', $id, true );
        $this->capacity     = get_field( 'capacity', $id, true );
        $this->image        = get_the_post_thumbnail_url( $id );
    }
}