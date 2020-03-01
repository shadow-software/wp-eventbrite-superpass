<?php
/**
 * Actions
 *
 * @package     ESP
 * @subpackage  Functions
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Require Woocommerce as a dependency. If not installed, disable the plugin and flash a message
 *
 * @since 1.0
 * @return void
 */
function woocommerce_dependency() {
    if ( is_admin() && current_user_can( 'activate_plugins' ) && ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        add_action( 'admin_notices', 'dependency_notice' );

        deactivate_plugins( ESP_PLUGIN_FILE );

        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}
add_action( 'admin_init', 'woocommerce_dependency' );

/**
 * For flashing dependency message
 *
 * @since 1.0
 * @return void
 */
function dependency_notice(){
    ?><div class="error"><p>Sorry, WP Eventbrite Superpass requires Woocommerce in order to be used.</p></div><?php
}


/**
 * Remove Eventbrite page on plugin deactivation
 *
 * @since 1.0
 * @return void
 */
function remove_eb_page() {
    $post = get_page_by_title( 'Eventbrite Checkout', OBJECT, 'page' );
    wp_delete_post( $post->ID );
}
register_deactivation_hook( ESP_PLUGIN_FILE, 'remove_eb_page' );


/**
 * Get a list of Eventbrite events that the current user is attending.
 *
 * @since 1.0
 * @return array
 */
/*
function esp_get_extended_attendance_record() {
    $user_id = get_current_user_id();
    $esp = ESP();
    $customer = $esp->get_customer_by_id( $user_id );

    $events = [];
    foreach( $customer->attending as $record ) {
        if ( isset( $record->coupon ) ) {
            // Check to see if the attendance record still exists.
            $res = [];
            if( isset( $record->order_id ) ) {
                $res = $esp->eb_sdk->client->get(
                    "/orders/{$record->order_id}/",
                    array()
                );
            }

            if( isset( $res['status'] ) && $res['status'] === "refunded") {
                $result = $record->delete_coupon();
                if ( $result['quantity_sold'] === 0 ) {
                    // Only delete the record if the coupon is no longer in use.
                    // Theoretically, someone could generate a coupon, use it, refund it and then have someone else use it
                    // and generate another one. This is to prevent that. This way they will not be able to generate
                    // another coupon for this Time Slot.
                    $record->delete();
                }
            } else {
                $event = $esp->get_event_by_id( $record->event_id );
                $event['super_pass_id'] = (int)$record->super_pass_id;
                $event['order_id'] = $record->order_id;
                $event['confirmed'] = $record->confirmed;
                $event['record_id'] = $record->id;
                $event['debug'] = $res;
                $events[] = $event;
            }
        }
    }

    return $events;
}
add_filter( 'esp_get_extended_attendance_record', 'esp_get_extended_attendance_record', 1000 );
*/

/**
 * Get the events that a customer is allowed to buy tickets for.
 *
 * @since 1.0
 * @return array
 */
/*
function esp_get_allowed_events() {
    //$user_id = get_current_user_id();
    $esp = ESP();
    $events = $esp->get_events( true );
    $esp->get_super_passes();
    $customer = $user_id !== 0 ? $esp->get_customer_by_id( $user_id ) : null;
    $allowed_events = array();
    // Just use the first super pass for now.
    $super_pass = $esp->super_passes[0];
    if ( isset( $super_pass->events[0] ) ) {
        if ( ! isset( $super_pass->events[0]['id'] ) ) {
            $super_pass->gather_event_data();
        }
    }
    foreach ( $events as $key => $event ) {
        $found = array_search( $event['id'], array_column( $super_pass->events, 'id' ) );

        if( $found !== false ) {
            unset( $event );
        } else {
            // Check if event is set as an add on
            $found = array_search( $event['id'], array_column( $super_pass->add_on_events, 'id' ) );
            if ( $found !== false ) {
                if ( $customer !== null ) {
                    $check = array_search( $super_pass->id, array_column( $customer->super_passes, 'id' ) );
                    if ( $check === false ) {
                        unset( $event );
                    }
                } else {
                    unset( $event );
                }

            }
        }

        if( isset( $event ) ) {
            $allowed_events[] = $event;
        }
    }

    return $allowed_events;
}
add_filter( 'esp_get_allowed_events', 'esp_get_allowed_events', 1000 );
*/

/**
 * Add columns for custom post type Workshops
 *
 * @since 1.0
 * @param $columns
 * @return mixed
 */
function set_custom_edit_workshops_columns($columns) {
    $date = $columns['date'];
    unset( $columns['date'] );
    $columns['date_of_event'] = 'Date of Workshop';
    $columns['start_time'] = 'Start Time';
    $columns['end_time'] = 'End Time';
    $columns['date'] = $date;
    return $columns;
}
add_filter( 'manage_workshops_posts_columns', 'set_custom_edit_workshops_columns' );

/**
 * Add data to custom workshop columns
 *
 * @since 1.0
 * @param $column
 * @param $post_id
 * @return void
 */
function custom_workshops_column( $column, $post_id ) {
    switch ( $column ) {
        case 'date_of_event' :
            echo get_field( 'date', $post_id, true );
            break;
        case 'start_time':
            echo get_post_meta( $post_id, 'start_time', true );
            break;
        case 'end_time':
            echo get_post_meta( $post_id, 'end_time', true );
            break;
    }
}
add_action( 'manage_workshops_posts_custom_column' , 'custom_workshops_column', 10, 2 );


/**
 * Add columns for custom post type eb_orders
 *
 * @since 1.0
 * @param $columns
 * @return mixed
 */
function set_custom_edit_eb_orders_columns($columns) {
    $date = $columns['date'];
    unset( $columns['date'] );
    $columns['user'] = 'User';
    $columns['eb_ticket'] = 'Eventbrite Ticket';
    $columns['date'] = $date;
    return $columns;
}
add_filter( 'manage_eb_orders_posts_columns', 'set_custom_edit_eb_orders_columns' );

/**
 * Add data to custom eb_orders columns
 *
 * @since 1.0
 * @param $column
 * @param $post_id
 * @return void
 */
function custom_eb_orders_column( $column, $post_id ) {
    switch ( $column ) {
        case 'user' :
            $user_id = get_post_meta( $post_id, 'user', true );
            $user = get_userdata( $user_id );
            echo "<a href='" . get_edit_user_link( $user_id ) . "'> {$user->first_name} {$user->last_name} </a>";
            break;
        case 'eb_ticket':
            $esp = ESP();
            $event_id = get_post_meta( $post_id, 'event_id', true );
            $event = $esp->get_event_by_id( $event_id );
            echo "<a href='{$event['url']}'>{$event['name']['text']}</a>";
            break;
    }
}
add_action( 'manage_eb_orders_posts_custom_column' , 'custom_eb_orders_column', 10, 2 );

/**
 * Add columns for custom post type workshop_attendance
 *
 * @since 1.0
 * @param $columns
 * @return mixed
 */
function set_custom_edit_workshop_attendance_columns($columns) {
    $date = $columns['date'];
    unset( $columns['date'] );
    $columns['user'] = 'User';
    $columns['workshop'] = 'Workshop';
    $columns['date'] = $date;
    return $columns;
}
add_filter( 'manage_workshop_attendance_posts_columns', 'set_custom_edit_workshop_attendance_columns' );

/**
 * Add data to custom workshop_attendance columns
 *
 * @since 1.0
 * @param $column
 * @param $post_id
 * @return void
 */
function custom_workshop_attendance_column( $column, $post_id ) {
    switch ( $column ) {
        case 'user' :
            $user = get_field( 'attendee', $post_id, true );
            echo "<a href='" . get_edit_user_link( $user->ID ) . "'> {$user->first_name} {$user->last_name} </a>";
            break;
        case 'workshop':
            $workshop = get_field( 'workshop', $post_id, true );
            $link = get_edit_post_link($workshop->ID);
            echo "<a href='{$link}'>{$workshop->post_title}</a>";
            break;
    }
}
add_action( 'manage_workshop_attendance_posts_custom_column' , 'custom_workshop_attendance_column', 10, 2 );

/**
 * Remove any attendance records that are no longer associated with this workshop
 *
 * @since 1.0
 * @param $post_ID
 * @param $post
 * @param $update
 * @return void
 */
function save_workshop_helper( $post_ID, $post, $update ) {
    if ( $post->post_type === 'workshops' ) {
        $attendees = get_field( 'attendees', $post_ID, true );
        $args = array(
            'numberposts'      => -1,
            'meta_key'         => '',
            'meta_value'       => '',
            'post_type'        => 'workshop_attendance',
        );
        $existing_records = get_posts( $args );
        // Synchronize Attendance Records by deleting any attendance records with this event that aren't in the list
        foreach ( $existing_records as $record ) {
            $found = array_search( $record->ID, array_column( (array) $attendees, 'ID') );
            if ( $found === false ) {
                wp_delete_post( $record->ID, true );
            }
        }
    }
}
add_action( 'save_post', 'save_workshop_helper', 10, 3 );

/**
 * Add attendance record to list for respective workshop upon creation
 *
 * @since 1.0
 * @param $post_ID
 * @param $post
 * @param $update
 * @return void
 */
function save_workshop_attendance_helper( $post_ID, $post, $update ) {
    if( $post->post_type === 'workshop_attendance' ) {
        $workshop = get_field( 'workshop', $post_ID );
        $attendee_list = get_field( 'attendees', $workshop, true );
        $attendee_list = empty($attendee_list) ? array() : $attendee_list;
        array_push( $attendee_list, $post );
        update_field( 'attendees', $attendee_list, $workshop );
    }
}
add_action( 'save_post', 'save_workshop_attendance_helper', 10, 3 );

/**
 * Add button to allow admins to get a PDF list of attendees for each workshop
 *
 * @since 1.0
 * @return void
 */
function add_csv_button() {
    add_meta_box(
        'csv_button',
        'Download Attendee List as CSV',
        'csv_button_content'
    );
}

function csv_button_content() {
    echo "<div class='button button-primary button-large' onclick='getCSV()'>Download as CSV</div>";
    add_action('admin_footer', 'download_csv_script');
}

function download_csv_script() {
    global $post;
    $id = $post->ID;
    $attendees = get_field( 'attendees', $id, true );
    $list = [];
    foreach ( $attendees as $attendee ) {
        $user = get_field( 'attendee', $attendee->ID, true );
        unset( $user->data->user_pass );
        $list[] = array(
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->user_email,
        );
    }
    $last_names = array_column( $list, 'last_name' );
    array_multisort($last_names, SORT_ASC, $list);
    $list = json_encode( $list );
    $txt = "'data:text/csv;charset=utf-8,' + outPut.map(e => e.join(',')).join('\\n')";
    echo
    "<script type='text/javascript'>
        let attendees = {$list};
        let title = '{$post->post_title}';
        let outPut = undefined;
        console.log(attendees);
        
        function getCSV() {
            outPut = [];
            outPut.push(['Attendee', 'Email']);
            attendees.forEach( attendee => {
                let row = [];
                row.push( attendee.last_name + ' ' + attendee.first_name);
                row.push( attendee.email );
                outPut.push(row);
            })
            let csvContent = {$txt}
            this.doDownload(csvContent, title + ' Attendees.csv');
        }
        
        function doDownload(csvContent, file_name){
            var hiddenElement = document.createElement('a');
            hiddenElement.href = encodeURI(csvContent);
            hiddenElement.target = '_blank';
            hiddenElement.download = file_name;
            hiddenElement.click();
            // Remove this
            hiddenElement = null;
          }
    </script>";
}

add_action( 'add_meta_boxes_workshops', 'add_csv_button' );

/**
 * Get all available workshops
 *
 * @since 1.0
 * @return array
 */
function esp_get_workshops() {
    $workshops = [];
    $args = array(
        'post_type' => 'workshops',
        'meta_key' => 'date',
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
        'numberposts' => -1,
    );
    $results = get_posts( $args );
    foreach( $results as $post ) {
        $workshop = new ESP_Workshop( $post->ID );
        $workshops[] = $workshop;
    }

    return $workshops;
}
add_action( 'esp_get_workshops', 'esp_get_workshops' );

function esp_get_workshop_attendance( $user_id ) {
    $workshop_attendances = [];
    global $wpdb;
    $results = $wpdb->get_results("SELECT ID FROM {$wpdb->posts} LEFT JOIN {$wpdb->postmeta} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID 
    WHERE post_type = 'workshop_attendance' AND post_status = 'publish' AND {$wpdb->postmeta}.meta_key = 'attendee' AND {$wpdb->postmeta}.meta_value = {$user_id}", OBJECT);
    foreach( $results as $post ) {
        $workshop_attendances[] = new ESP_Workshop_Attendance( $post->ID );
    }
    return $workshop_attendances;
}
add_filter( 'esp_get_workshop_attendance', 'esp_get_workshop_attendance', 10, 1 );

/**
 * Check if the customer has any attending events that conflict with the event requested
 *
 * @param $user_id - WP User
 * @param $workshop_id - ID of Workshop.
 * @return array
 * @since 1.0
 */
function esp_check_event_overlap( $user_id, $workshop_id ) {
    // Search for event.
    $workshop = new ESP_Workshop( $workshop_id );

    if ( $workshop === false ) {
        return array( 'result' => false, 'message' => 'Workshop not found.' );
    }

    $event_start_time = strtotime( "{$workshop->date} {$workshop->start_time}" );
    $event_end_time = strtotime( "{$workshop->date} {$workshop->end_time}" );
    $overlap = false;

    $attendance_records = apply_filters( 'esp_get_workshop_attendance', $user_id );
    foreach( $attendance_records as $record ) {
        // Compare the dates of each record and make sure there's no overlap.
        $cWorkshop = new ESP_Workshop( $record->workshop );
        $start_time = strtotime( "{$cWorkshop->date} {$cWorkshop->start_time}" );
        $end_time = strtotime( "{$cWorkshop->date} {$cWorkshop->end_time}" );

        if ( (int)$record->workshop === (int)$workshop_id ) {
            // The user already has an attendance record for this workshop
            return array( 'result' => false, 'message' => 'Already attending this event.' );
        }

        if ( $event_start_time < $end_time && $start_time < $event_end_time ) {
            $overlap = true;
        }
    }

    if( $overlap ) {
        return array( 'result' => false, 'message' => 'Already attending an overlapping event' );
    } else {
        return array( 'result' => true, 'message' => 'Success' );
    }
}
add_filter( 'esp_check_event_overlap', 'esp_check_event_overlap', 1, 2 );