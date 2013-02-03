<?php

require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');

function create_post_and_comments_from_db() {
  // get or create categories
  $sunsetsCategory = get_category_by_slug('sunsets');
  if(!$sunsetsCategory){
    $sunsetsCategory = wp_insert_category(
      array('cat_name' => 'sunsets', 'category_description' => 'Sunset pictures', 'category_nicename' => 'Sunsets')
    );
    if(!$sunsetsCategory || is_wp_error($sunsetsCategory)){
      $sunsetsCategory = false;
    }
  }else{
    $sunsetsCategory = $sunsetsCategory->term_id;
  }

  $sunrisesCategory = get_category_by_slug('sunrises');
  if(!$sunrisesCategory){
    $sunrisesCategory = wp_insert_category(
      array('cat_name' => 'sunrises', 'category_description' => 'Sunrise pictures', 'category_nicename' => 'Sunrises')
    );
    if(!$sunrisesCategory || is_wp_error($sunrisesCategory)){
      $sunrisesCategory = false;
    }
  }else{
    $sunrisesCategory = $sunrisesCategory->term_id;
  }
  echo "Using {$sunrisesCategory} and {$sunsetsCategory}<br>\n";
  
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
         'post_author'   => $author_id,
         'post_date'     => mysql2date('Y-m-d H:i:s', $post->send_date),
      );
      $post_id = wp_insert_post( $post_insert );
      add_post_meta( $post_id, 'emailtosunrise_email_id', $post->id );
      
      // Add categories
      if($post->category === 'sunrises'){
        $category = $sunrisesCategory;
      }else if($post->category === 'sunsets'){
        $category = $sunsetsCategory;      
      }
      
      if($post_id && $category){
        wp_set_post_terms($post_id, $category, 'category');
      }
      
      $image = get_image($post->id);
      $wp_filetype = wp_check_filetype(basename($image->filename), null);
      $attachment = array(
         'guid' => $image->image_url, 
         'post_mime_type' => $wp_filetype['type'],
         'post_title' => preg_replace('/\.[^.]+$/', '', basename($image->filename)),
         'post_content' => '',
         'post_status' => 'inherit',
         'post_date'     => mysql2date('Y-m-d H:i:s', $post->send_date)
      );
      $attach_id = wp_insert_attachment( $attachment, $image->filename, $post_id );
      $attach_data = wp_generate_attachment_metadata( $attach_id, $image->filename );
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
   
  // create comments from database
  $new_comments = new_comments();
  foreach ($new_comments as $comment) {
     $time = current_time('mysql');
     $post_id = blog_post_id_from_email_id( $comment->ref_id );
     
     if( $post_id ) {
       if ( is_email($comment->author) ) {
    			$userdata = get_user_by('email', $comment->author);
    			if ( ! empty( $userdata ) ) {
    				$author_id = $userdata->ID;
            $comment_data = array(
                'comment_post_ID' => $post_id,
                'comment_content' => $comment->body,
                'comment_date' => mysql2date('Y-m-d H:i:s', $post->send_date),
                'user_id' => $author_id
            );       
    			}
    		}
       
       wp_insert_comment($comment_data);
     }
  }
}

?>