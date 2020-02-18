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
     * ID of Woocommerce Product
     *
     * @var int
     * @since 1.0
     */
    public $wc_id;

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

            // Create a WC Product to represent this Pass
            $this->create_super_pass_as_wc_product();
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
        $key = array_search( $event_id, array_column( $this->events, 'id' ) );
        if ( $key ) {
            // Remove from meta
            delete_post_meta( $this->id, "ESP_SUPER_PASS_EVENT", $event_id );
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
        $events = $esp->get_events();
        foreach( $this->events as &$event ) {
            if ( ! is_array( $event ) || ! is_object( $event )) {
                foreach( $events as $event_data ) {
                    if ( $event_data[ 'id' ] === $event ) {
                        $event = $event_data;
                        break;
                    }
                }
            }
        }
    }

    /**
     * Create a product in Woocommerce to represent our super pass.
     *
     * @since 1.0
     * @access public
     * @return void
     */
    public function create_super_pass_as_wc_product() {
        $will_manage_stock  = false;
        $is_virtual         = true;
        $price              = $this->cost;
        $product            = new \WC_Product();
        $image_id           = 0; // Attachment ID
        $product->set_props( array(
            'name'               => $this->name,
            'featured'           => false,
            'catalog_visibility' => 'visible',
            'description'        => 'My awesome product description',
            'short_description'  => 'My short description',
            'sku'                => sanitize_title( $this->name ) . '-' . rand(0, 100),
            'regular_price'      => $price,
            'sale_price'         => '',
            'date_on_sale_from'  => '',
            'date_on_sale_to'    => '',
            'total_sales'        => 0,
            'tax_status'         => 'taxable',
            'tax_class'          => '',
            'manage_stock'       => $will_manage_stock,
            'stock_quantity'     => $will_manage_stock ? 100 : null, // Stock quantity or null
            'stock_status'       => 'instock',
            'backorders'         => 'no',
            'sold_individually'  => true,
            'weight'             => $is_virtual ? '' : 15,
            'length'             => $is_virtual ? '' : 15,
            'width'              => $is_virtual ? '' : 15,
            'height'             => $is_virtual ? '' : 15,
            'upsell_ids'         => '',
            'cross_sell_ids'     => '',
            'parent_id'          => 0,
            'reviews_allowed'    => true,
            'purchase_note'      => '',
            'menu_order'         => 10,
            'virtual'            => $is_virtual,
            'downloadable'       => false,
            'category_ids'       => '',
            'tag_ids'            => '',
            'shipping_class_id'  => 0,
            'image_id'           => $image_id,
        ) );

        $product->save();
        $this->wc_id = $product->get_id();
        update_post_meta( $this->id, 'ESP_SUPER_PASS_WC_ID', $this->wc_id );
    }

    /**
     * Update main post meta data (excluding events)
     *
     * @since 1.0
     * @access public
     * @return boolean
     */
    public function update() {
        $postarr = [
            'ID' => (int)$this->id,
            'post_title' => $this->name,
            'post_type' => 'ESP_SUPER_PASS',
            'post_content' => '',
        ];
        $result = wp_update_post( $postarr, true );
        update_post_meta( $this->id, 'ESP_SUPER_PASS_COST', $this->cost );
        return $result !== 0;
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
        // Remove WC Product
        $wc_result = wp_delete_post( $this->wc_id );

        return ( $result !== false && $result !== null) && ( $wc_result !== false && $wc_result !== null );
    }
}