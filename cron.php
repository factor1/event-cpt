<?php

/**
 * Cron Job: Delete event posts after event has passed
 */

add_action('wp', 'event_cpt_setup_schedule'); // add cron job scheduling function

/**
 * Schedule next job, if not already scheduled
 */

function event_cpt_setup_schedule()
{
    if (!wp_next_scheduled('event_cpt_daily_event')) {
        wp_schedule_event(time(), 'daily', 'event_cpt_daily_event');
    }
}

add_action('event_cpt_daily_event', 'event_cpt_do_this_daily'); // apply function hook to cron job

/**
 * Delete past events
 */

function event_cpt_do_this_daily()
{
    global $wpdb;

    $currenttime = new DateTime();
    $currenttime_string = $currenttime->format('Ymd');

    $query = "
        SELECT ID FROM $wpdb->posts
        WHERE post_type = 'event'
        AND post_status = 'publish'
        ORDER BY post_modified DESC
    ";

    $results = $wpdb->get_results($query); // query database for events

    if (count($results)) {
        foreach ($results as $post) {
            $customfield = get_post_meta($post->ID, 'event_date');
            $customfield_object = new DateTime($customfield);
            $customfield_string = $customfield_object->format('Ymd');

            if ($customfield_string < $currenttime_string) { // if event_date has passed, delete event
                $purge = wp_delete_post($post->ID);
            }
        }
    }
}

?>