<?php
/*
Plugin Name: TWOSEVENTWO events
Description: Custom Post Type for Tour page.
Author: Jeff Tribble
Author URI: http://twoseventwo.us/
Version 0.2
*/

$prefix = 'event'; // name of post type

require_once dirname( __FILE__ ) . '/cron.php';


/**
 * Custom Meta Box
 */

function add_custom_meta_box()
{  
    add_meta_box(
        'custom_meta_box', // $id
        'Event Details', // $title
        'show_custom_meta_box', // $callback
        'event', // $post_type
        'normal', // $context
        'high' // $priority
    );
}

add_action('add_meta_boxes', 'add_custom_meta_box'); 


/**
 * Custom Fields
 */

$custom_meta_fields = array(
    array(
        'label'    => 'Date*',
        'desc'     => 'The date (or start date) of the event.',
        'id'       => $prefix . '_date',
        'required' => true,
        'type'     => 'date'
    ),
    array(
        'label'    => 'City*',
        'desc'     => 'The city of the event.',
        'id'       => $prefix . '_city',
        'required' => true,
        'type'     => 'text'
    ),
    array(
        'label'    => 'State or Country (if outside U.S.)*',
        'desc'     => 'i.e. CA, TX, UK. If the event is located in the United States, this field represents the state. Otherwise, enter the country of the event.',
        'id'       => $prefix . '_state',
        'required' => true,
        'type'     => 'text'
    ),
    array(
        'label'    => 'Doors Time*',
        'desc'     => 'The doors time of the event. i.e. 6PM, 8:30PM, 11AM.',
        'id'       => $prefix . '_time_doors',
        'required' => true,
        'type'     => 'text'
    ),
    array(
        'label'    => 'Start Time*',
        'desc'     => 'The start time of the event. i.e. 6PM, 8:30PM, 11AM.',
        'id'       => $prefix . '_time_start',
        'required' => true,
        'type'     => 'text'
    ),
    array(
        'label'    => 'Tickets Text*',
        'desc'     => 'The text that shows up in the far right button.',
        'id'       => $prefix . '_tickets_text',
        'required' => true,
        'type'     => 'text'
    ),
    array(
        'label'    => 'Tickets Link*',
        'desc'     => 'A link to allow users to purchase tickets.',
        'id'       => $prefix . '_tickets_url',
        'required' => true,
        'type'     => 'text'
    ),
    array(
        'label'    => 'VIP Tickets Link',
        'desc'     => 'A link to allow users to purchase VIP tickets.',
        'id'       => $prefix . '_tickets_url_vip',
        'required' => false,
        'type'     => 'text'
    ),
    array(
        'label'    => 'Facebook Event ID',
        'desc'     => 'The Facebook Event ID will link users to the Facebook event. Ex: For facebook.com/events/433212686809974, the ID is 433212686809974.',
        'id'       => $prefix . '_facebook_id',
        'required' => false,
        'type'     => 'text'
    ),
);


/**
 * Show Custom Fields
 */

function show_custom_meta_box()
{  
    global $custom_meta_fields, $post;  
    
    echo '<input type="hidden" name="custom_meta_box_nonce" value="' . wp_create_nonce(basename(__FILE__)) . '" />'; // use nonce for verification
      
    echo '<table class="form-table">';  

    foreach ($custom_meta_fields as $field) {  
        
        $meta = get_post_meta($post->ID, $field['id'], true); // get value of this field if it exists for this post  
        
        echo '<tr>';
        echo '  <th><label for="' . $field['id'] . '">' . $field['label'] . '</label></th>';
        echo '  <td>';

        if ($field['id'] == 'event_tickets_text') {
            echo '<input type="text" name="' . $field['id'] . '" id="' . $field['id'] . '" value="' . $meta . '" size="30" class="event-cpt-' . $field['type'] . '" placeholder="Defaults to \'Purchase Tickets\'" /><br />';
        } else {
            echo '<input type="text" name="' . $field['id'] . '" id="' . $field['id'] . '" value="' . $meta . '" size="30" class="event-cpt-' . $field['type'] . '" /><br />';
        }


        echo '    <span class="description">' . $field['desc'] . '</span>';
        echo '  </td>';
        echo '</tr>';
    }

    echo '</table>';
}


/**
 * Save Custom Field Data
 */

function save_custom_meta($post_id)
{  
    global $custom_meta_fields;      
    
    if (!wp_verify_nonce($_POST['custom_meta_box_nonce'], basename(__FILE__))) { // verify nonce
        return $post_id;
    }        
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { // check autosave
        return $post_id;
    }
        
    /* Check Permissions */

    if ('page' == $_POST['post_type']) {  
        if (!current_user_can('edit_page', $post_id)) {
            return $post_id;
        } else if (!current_user_can('edit_post', $post_id)) {  
            return $post_id;
        }
    }

    /* Loop Through Fields & Save Data */

    foreach ($custom_meta_fields as $field) {  

        $old = get_post_meta($post_id, $field['id'], true);  
        $new = $_POST[$field['id']];  

        if ($new && $new != $old) {  
            update_post_meta($post_id, $field['id'], $new);  
        } elseif ('' == $new && $old) {  
            delete_post_meta($post_id, $field['id'], $old);  
        }  
    }
}

add_action('save_post', 'save_custom_meta');


/**
 * Register Event CPT
 */

function event_cpt()
{
    $arrLabels = array(
        'name'                => _x('Events', 'Post Type General Name', 'text_domain'),
        'singular_name'       => _x('Event', 'Post Type Singular Name', 'text_domain'),
        'menu_name'           => __('Events', 'text_domain'),
        'parent_item_colon'   => __('Parent Event:', 'text_domain'),
        'all_items'           => __('All Events', 'text_domain'),
        'view_item'           => __('View Event', 'text_domain'),
        'add_new_item'        => __('Add New Event', 'text_domain'),
        'add_new'             => __('Add New', 'text_domain'),
        'edit_item'           => __('Edit Event', 'text_domain'),
        'update_item'         => __('Update Event', 'text_domain'),
        'search_items'        => __('Search Event', 'text_domain'),
        'not_found'           => __('Event not found', 'text_domain'),
        'not_found_in_trash'  => __('Event not found in Trash', 'text_domain'),
    );

    $arrArgs = array(
        'label'               => __('event', 'text_domain'),
        'description'         => __('Events will show up on the Tour page.', 'text_domain'),
        'labels'              => $arrLabels,
        'supports'            => array('title', 'editor',),
        'taxonomies'          => array(),
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'menu_position'       => 5,
        'menu_icon'           => plugins_url('/event-cpt/images/1day.png'),
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => true,
        'publicly_queryable'  => true,
        'rewrite'             => false,
        'capability_type'     => 'page',
    );

    register_post_type('event', $arrArgs); // register custom post type
}

add_action('init', 'event_cpt', 0); // Hook into the 'init' action


/**
 * Include Plugin CSS & JS
 */

function event_cpt_scripts()
{
    wp_register_style('event-cpt', plugins_url('/event-cpt/css/event-cpt.css'));
    wp_enqueue_style('event-cpt');
}

function event_cpt_admin_scripts()
{
    wp_register_style('jquery-ui', plugins_url('/event-cpt/css/lib/jquery-ui-1.10.4.custom.min.css'));
    wp_enqueue_style('jquery-ui');

    wp_register_script('event-cpt', plugins_url('/js/event-cpt.js', __FILE__ ), array('jquery-ui-datepicker'));
    wp_enqueue_script('event-cpt');
}

add_action('init', 'event_cpt_scripts');
add_action('admin_init', 'event_cpt_admin_scripts');


/**
 * Shortcode
 */

function event_cpt_shortcode($atts)
{
    // Collect Attributes
    $a = shortcode_atts(array(
        'limit' => -1,
        'list' => false,
    ), $atts);

    $intLimit = $a['limit'];
    $boolList = $a['list'];

    // Array of month abbreviations (utility)
    $arrMonths = array(1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec');

    // Check for single
    $intId = $_GET['id'];

    if ($intId != 0) {
        $args = array('post_type' => 'event', 'p' => $intId);
    } else {
        $strToday = date('Y-m-d');
        $args = array(
            'post_type' => 'event',
            'posts_per_page' => $intLimit,
            'orderby' => 'meta_value',
            'meta_key' => 'event_date',
            'order' => 'ASC',            
            'meta_query' => array(
                array(
                    'key' => 'event_date',
                    'value' => $strToday,
                    'compare' => '>='
                ),
            )
        );
    }

    $query = new WP_Query($args);

    if ($query->have_posts() && !$boolList) {

        $strPageURL = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        echo '<ul class="shows container">';

        while ($query->have_posts()) : $query->the_post();

            // Get custom field meta (event details)
            $arrEventDetails = get_post_meta(get_the_ID());

            /**
             * Echo Event
             */

            echo '<li class="show">';

            echo '  <div class="top row">';

            echo '    <div class="info">';

            echo '      <div class="col-md-2">';
            echo '        <span class="date">';
            echo '          <span class="month">' . $arrMonths[date('n', strtotime($arrEventDetails['event_date'][0]))] . '</span>';
            echo '          <span class="day">' . date('d', strtotime($arrEventDetails['event_date'][0])) . '</span>';
            echo '        </span>';
            echo '      </div>';

            echo '      <div class="col-md-4">';
            echo '        <ul class="details">';
            echo '          <li class="location brand">' . $arrEventDetails['event_city'][0] . ', ' . $arrEventDetails['event_state'][0] . '</li>';
            echo '          <li class="doors time">Doors open @ ' . $arrEventDetails['event_time_doors'][0] . '</li>';
            echo '          <li class="start time">Show @ ' . $arrEventDetails['event_time_start'][0] . '</li>';
            echo '          <li class="sharing">';
            echo '            <!-- AddThis Button BEGIN -->';
            echo '            <div class="addthis_toolbox addthis_default_style addthis_16x16_style">';
            echo '              <a class="addthis_button_facebook" addthis:url="' . $strPageURL . '?id=' . get_the_ID() . '"></a>';
            echo '              <a class="addthis_button_twitter" addthis:url="' . $strPageURL . '?id=' . get_the_ID() . '"></a>';
            echo '              <a class="addthis_button_google_plusone_share" addthis:url="' . $strPageURL . '?id=' . get_the_ID() . '"></a>';
            echo '              <a class="addthis_button_compact" addthis:url="' . $strPageURL . '?id=' . get_the_ID() . '"></a><a class="addthis_counter addthis_bubble_style" addthis:url="' . $strPageURL . '?id=' . get_the_ID() . '"></a>';
            echo '            </div>';
            echo '            <script type="text/javascript">var addthis_config = {"data_track_addressbar":true};</script>';
            echo '            <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-52ed57365b4ef019"></script>';
            echo '            <!-- AddThis Button END -->';
            echo '          </li>';
            echo '        </ul>';
            echo '      </div>';

            echo '    </div>';

            echo '    <div class="content col-md-6">';

            if ($intId != 0) {
                echo '<h3 class="brand">' . get_the_title() . '</h3>';
            } else {
                echo '<h3><a href="?id=' . get_the_ID() . '" class="brand">' . get_the_title() . '</a></h3>';
            }

            echo '      <div class="desc">' . wp_trim_words(get_the_content(), 80) . '</div>';
            echo '    </div>';

            echo '  </div>';

            echo '  <div class="bottom row">';

            /**
             * Format Links
             */
    
            $boolVIP       = true;
            $strTicketsVIP = $arrEventDetails['event_tickets_url_vip'][0];
            $strTickets    = $arrEventDetails['event_tickets_url'][0];

            if ($strTicketsVIP != '') {
                if (strpos($strTicketsVIP, 'http://') === false){
                    $strTicketsVIP = 'http://' . $strTicketsVIP;
                }
            } else {
                $boolVIP = false;
            }
            
            if (strpos($strTickets, 'http://') === false){
                $strTickets = 'http://' . $strTickets;
            }

            echo '    <ul>';

            if ($arrEventDetails['event_facebook_id'][0] != '') {
                echo '  <li class="facebook col-md-3 col-sm-6"><a href="http://www.facebook.com/events/' . $arrEventDetails['event_facebook_id'][0] . '">RSVP on <img src="' . plugins_url('/event-cpt/images/facebook.png') . '" alt="Facebook" /></a></li>';
            } else {
                echo '  <li class="facebook col-md-3 col-sm-6"><a class="inactive">RSVP on <img src="' . plugins_url('/event-cpt/images/facebook.png') . '" alt="Facebook" /></a></li>';
            }

            echo '      <li class="invite col-md-3 col-sm-6">';
            echo '        <!-- AddThis Button BEGIN -->';
            echo '        <a href="#" class="addthis_button_email" addthis:url="' . $strPageURL . '?id=' . get_the_ID() . '">Invite Friends</a>';
            echo '        <script type="text/javascript">var addthis_config = {"data_track_addressbar":true};</script>';
            echo '        <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-52ed57365b4ef019"></script>';
            echo '        <!-- AddThis Button END -->';
            echo '      </li>';

            if ($boolVIP) {
                echo '  <li class="tickets-vip col-md-3 col-sm-6"><a href="' . $strTicketsVIP . '">VIP Tickets</a></li>';
            } else {
                echo '  <li class="tickets-vip col-md-3 col-sm-6"><a class="inactive">VIP Tickets</a></li>';
            }

            echo '      <li class="tickets-reg col-md-3 col-sm-6">';
            echo '        <a href="' . $strTickets . '" class="tickets">';
            echo ($arrEventDetails['event_tickets_text'][0]) ? $arrEventDetails['event_tickets_text'][0] : 'Purchase Tickets';
            echo '        </a>';
            echo '      </li>';
            echo '    </ul>';

            echo '  </div>';

            echo '</li>';


        endwhile;

        echo '</ul>';
    }

    else if ($query->have_posts() && $boolList) {

        echo '<ul class="shows-list">';

        while ($query->have_posts()) : $query->the_post();

            // Get custom field meta (event details)
            $arrEventDetails = get_post_meta(get_the_ID());

            /**
             * Echo Event
             */

            echo '<li class="show">';

            echo '  <span class="date">';
            echo '    <span class="month">' . $arrMonths[date('n', strtotime($arrEventDetails['event_date'][0]))] . '</span>';
            echo '    <span class="day">' . date('d', strtotime($arrEventDetails['event_date'][0])) . '</span>';
            echo '  </span>';
            echo '  <strong class="title">' . get_the_title() . '</strong>';
            echo '  <span class="location">' . $arrEventDetails['event_city'][0] . ', ' . $arrEventDetails['event_state'][0] . '</span>';

            echo '</li>';

        endwhile;

        echo '</ul>';
    }

    else {}


}

add_shortcode('event-cpt', 'event_cpt_shortcode');


?>