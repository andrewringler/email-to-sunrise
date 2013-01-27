<?php

require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');

function create_post_and_comments_from_db() {
  // create posts from database
  $new_posts = new_posts();
  foreach ($new_posts as $post) {
   $userdata = get_user_by('email', $post->author);
   if ( ! empty( $userdata ) ) {
     $author_id = $userdata->ID;
     
     $post_insert = array(
         'post_title'    => $post->subject,
         'post_content'  => $post->body,
         'post_status'   => 'publish',
         'post_author'   => $author_id
      );
      $post_id = wp_insert_post( $post_insert );
      add_post_meta( $post_id, 'emailtosunrise_email_id', $post->id );
      
      $image = get_image($post->id);
      echo '<p>image: '. esc_html(var_export($image, true)) ."</p>\n";
      $wp_filetype = wp_check_filetype(basename($image->filename), null);
      $attachment = array(
         'guid' => $image->image_url, 
         'post_mime_type' => $wp_filetype['type'],
         'post_title' => preg_replace('/\.[^.]+$/', '', basename($image->filename)),
         'post_content' => '',
         'post_status' => 'inherit'
      );
      $attach_id = wp_insert_attachment( $attachment, $image->filename, $post_id );
      echo '<p>attach id: '. esc_html(var_export($attach_id, true)) ."</p>\n";
      $attach_data = wp_generate_attachment_metadata( $attach_id, $image->filename );
      echo '<p>attach data: '. esc_html(var_export($attach_data, true)) ."</p>\n";
      if ( $attach_data ) {
        wp_update_attachment_metadata( $attach_id, $attach_data );         
        
        // pre-pend image to post body
        $image_post = wp_get_attachment_image( $attach_id, 'full' );
        
        $new_post_body = $image_post ."\n". $post->body;
        wp_update_post( array( 'ID' => $post_id, 'post_content' => $new_post_body) );
        
        // <a href="http://192.168.33.20/?attachment_id=1140" rel="attachment wp-att-1140">
        //   <img class="alignnone size-full wp-image-1140" alt="emailtosunrisezUyM1H" src="http://192.168.33.20/wp-content/uploads/2013/01/emailtosunrisezUyM1H.jpg" width="640" height="640" />
        //  </a>
      }
      
      update_message_status( $post->id, 'posted' );
   }		
  }
   
  // // create comments from database
  // $new_comments = new_comments();
  // foreach ($new_comments as $comment) {
  //   $time = current_time('mysql');
  //   
  //   $post_id = wordpress_post_id_for_email_id( $comment )
  //   
  //   $data = array(
  //       'comment_post_ID' => 1,
  //       'comment_author' => 'admin',
  //       'comment_author_email' => 'admin@admin.com',
  //       'comment_author_url' => 'http://',
  //       'comment_content' => 'content here',
  //       'comment_type' => '',
  //       'comment_parent' => 0,
  //       'user_id' => 1,
  //       'comment_author_IP' => '127.0.0.1',
  //       'comment_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)',
  //       'comment_date' => $time,
  //       'comment_approved' => 1,
  //   );
  // 
  //   wp_insert_comment($data);
  // }  
}

?>