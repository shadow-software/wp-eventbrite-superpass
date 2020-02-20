<?php
/**
 * WP Eventbrite Superpass - Cart
 *
 * Cart object for holding eventbrite tickets that a user has selected
 *
 * @package     ESP
 * @subpackage  Classes/Cart
 * @license     http://opensource.org/license/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class ESP Cart
 *
 * @since 1.0
 */
class ESP_Cart {

    /**
     * Array of items
     *
     * @since 1.0
     * @access public
     * @var array
     */
    public $items;

    /**
     * ESP_Cart constructor
     *
     * @since 1.0
     * @return void
     */
    public function __construct() {
        if ( isset( $_SESSION['esp-cart'] ) && !empty( $_SESSION['esp-cart'] ) ) {
            $this->items = $_SESSION['esp-cart'];
        } else {
            $this->items = array();
        }
    }

    /**
     * Add item to this cart unless already included.
     *
     * @since 1.0
     * @access public
     * @param $item - string, Eventbrite Event ID
     * @return boolean - if the item was added
     */
    public function add_item( $item ) {
        if (!in_array( $item, $this->items ) ) {
            $this->items[] = $item;
            $_SESSION['esp-cart'] = $this->items;
            return true;
        }

        return false;
    }

    /**
     * Remove item from this cart
     *
     * @since 1.0
     * @access public
     * @param $item - string, Eventbrite Event ID
     * @return boolean - if the item was removed
     */
    public function remove_item( $item ) {
        $index = array_search( $item, $this->items );

        if ( $index ) {
            unset( $this->items[$index] );
            return true;
        }

        return false;
    }

    /**
     * Empty the cart
     *
     * @since 1.0
     * @access public
     * @return void
     */
    public function dump() {
        $this->items = array();
        $_SESSION['esp-cart'] = $this->items;
    }
}