<?php
/*
Plugin Name: Events Calendar
Description: A custom plugin to create and display events using a custom post type.
Version: 1.7.1
Author: Riley Litchfield
Author URI: https://rileylitchfield.com
License: GPL2
*/

function load_admin_script()
{
  wp_enqueue_script('jquery');
  wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
  wp_enqueue_script('jquery-ui-js', 'https://code.jquery.com/ui/1.12.1/jquery-ui.js', array('jquery'));
}
add_action('admin_enqueue_scripts', 'load_admin_script');

// Add CSS
function events_cal_enqueue_styles()
{
  wp_enqueue_style('my-plugin-css', plugin_dir_url(__FILE__) . 'events-calendar.css');
}
add_action('wp_enqueue_scripts', 'events_cal_enqueue_styles');

// Add a custom meta box for the event date
function events_cal_add_meta_box()
{
  add_meta_box('event-date-meta-box', 'Event Date', 'events_cal_meta_box_callback', 'event', 'side');
}
add_action('add_meta_boxes', 'events_cal_add_meta_box');

// Callback function to display the meta box contents
function events_cal_meta_box_callback($post)
{
  // Add a security nonce
  wp_nonce_field('save_event_date', 'event_date_nonce');

  // Get the current event date, if any
  $event_date = get_post_meta($post->ID, 'event_date', true);

  // Display the date selector field
?>
<label for="event-date-input">Event Date:</label>
<input type="text" id="event-date-input" name="event-date" value="<?php echo esc_attr($event_date); ?>" />
<script>
  console.log("Datepicker initialized on #event-date-input element");
  jQuery(document).ready(function ($) {
    $('#event-date-input').datepicker({
      dateFormat: 'mm-dd-yy'
    });
  });
</script>
<?php
}

// Save the event date when the post is saved
function events_cal_save_event_date($post_id)
{
  // Check if the nonce is valid
  if (!isset($_POST['event_date_nonce']) || !wp_verify_nonce($_POST['event_date_nonce'], 'save_event_date')) {
    return;
  }

  // Check if the current user has permission to edit the post
  if (!current_user_can('edit_post', $post_id)) {
    return;
  }

  // Check if the event date was submitted
  if (isset($_POST['event-date'])) {
    // Save the event date
    update_post_meta($post_id, 'event_date', sanitize_text_field($_POST['event-date']));
  }
}
add_action('save_post', 'events_cal_save_event_date');


// Custom post type function
function my_custom_post_type()
{
  $labels = array(
    'name' => 'Events',
    'singular_name' => 'Event',
    'menu_name' => 'Events',
    'name_admin_bar' => 'Event',
    'add_new' => 'Add New',
    'add_new_item' => 'Add New Event',
    'new_item' => 'New Event',
    'edit_item' => 'Edit Event',
    'view_item' => 'View Event',
    'all_items' => 'All Events',
    'search_items' => 'Search Events',
    'parent_item_colon' => 'Parent Events:',
    'not_found' => 'No events found.',
    'not_found_in_trash' => 'No events found in Trash.',
  );

  $args = array(
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'query_var' => true,
    'rewrite' => array('slug' => 'event'),
    'capability_type' => 'post',
    'has_archive' => true,
    'hierarchical' => false,
    'menu_position' => null,
    'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments')
  );

  register_post_type('event', $args);
}
add_action('init', 'my_custom_post_type');

// Function to truncate the content
function truncate_the_content($content)
{
  // Truncate the content to 200 characters
  $truncated_content = substr($content, 0, 200);
  // Return the truncated content
  return $truncated_content;
}

// Hook the truncate function to the the_content filter
add_filter('the_content', 'truncate_the_content');

// Query and display function
function display_events()
{
  $args = array(
    'post_type' => 'event',
    // The name of the custom post type
    'posts_per_page' => -1,
    // Display all events
    'orderby' => 'title',
    // Order the events by title
    'order' => 'ASC' // Sort the events in ascending order
  );

  // Create a new instance of WP_Query
  $events = new WP_Query($args);

  // Check if the query has any posts
  if ($events->have_posts()) {
?>
<div class="events-grid">
  <?php
    // Start the loop
    while ($events->have_posts()) {
      $events->the_post();
      // Get the event date
      $event_date = get_post_meta(get_the_ID(), 'event_date', true);
  ?>
  <div class="event-card">
    <!-- Access the current post's data using template functions -->
    <h2 class="event-title"><?php the_title(); ?></h2>
    <p class="event-date"><?php echo esc_html($event_date); ?></p>
    <p class="event-content"><?php the_content(); ?></p>
  </div>
  <?php
    }
  ?>
</div>
<?php
    // Reset the post data
    wp_reset_postdata();
  } else {
    // Display a message if there are no events
    echo 'No events found';
  }
}

// Create the shortcode
add_shortcode('display_events', 'display_events');