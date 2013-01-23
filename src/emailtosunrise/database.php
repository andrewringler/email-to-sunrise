<?php

global $emailtosunrise_db_version;
$emailtosunrise_db_version = "0.1";

function email_to_sunrise_install_db() {
   global $wpdb;
   global $emailtosunrise_db_version;

   $table_name = $wpdb->prefix . "emailtosunrise_email";
      
   $sql = "CREATE TABLE $table_name (id VARCHAR(256) NOT NULL,
     UNIQUE KEY id (id)
     );";

   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   dbDelta($sql);
 
   add_option("emailtosunrise_db_version", $emailtosunrise_db_version);
}

function set_message_seen($message_id) {
   global $wpdb;
   $table_name = $wpdb->prefix . "emailtosunrise_email";
   // $rows_affected = $wpdb->insert( $table_name, array( 'id' => sha1($message_id, $raw_output = true) ) );
   $rows_affected = $wpdb->insert( $table_name, array( 'id' => esc_sql($message_id) ) );
}

function get_message_seen($message_id) {
   global $wpdb;
   $table_name = $wpdb->prefix . "emailtosunrise_email";
   $message_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE id = '" .esc_sql($message_id) ."'");
   return $message_count == 1;
}

?>