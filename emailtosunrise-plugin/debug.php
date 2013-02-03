<?php

function clear_db_posts_media_and_uploads() {
  global $wpdb;
  $message_table = $wpdb->prefix . "emailtosunrise_email";
  $image_table = $wpdb->prefix . "emailtosunrise_images";
  
  $num_rows = $wpdb->get_var( "DELETE FROM $message_table" );
  $num_rows = $wpdb->get_var( "DELETE FROM $image_table" );
  $num_rows = $wpdb->get_var( "DELETE FROM $wpdb->posts" );
  
  $posts = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'emailtosunrise_email_id';" );
  foreach($posts as $post_id) {
   wp_delete_post( $post_id, true);
  }
}

?>