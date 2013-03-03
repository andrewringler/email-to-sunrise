<?php

add_action('admin_menu', 'plugin_admin_add_page');
function plugin_admin_add_page() {
  add_options_page('Email to Sunrise', 'Email to Sunrise', 'manage_options', 'email_to_sunrise_plugin', 'plugin_options_page');
}

function plugin_options_page() {
?>
<div class="wrap">
  <?php screen_icon(); ?>
  <h2>Email to Sunrise Settings</h2>
  <form action="options.php" method="post">
    <?php settings_fields('email_to_sunrise_plugin_options'); ?>
    <?php do_settings_sections('email_to_sunrise_plugin'); ?>
    <?php submit_button(); ?>
  </form>  
</div> 
<?php
}

add_action('admin_init', 'plugin_admin_init');
function plugin_admin_init(){
  register_setting( 'email_to_sunrise_plugin_options', 'plugin_options', 'plugin_options_validate' );
  add_settings_section('plugin_main', 'Mail Settings', 'plugin_section_text', 'email_to_sunrise_plugin');
  add_settings_field('mail_server_string', 'Mail Server', 'the_mail_server_input', 'email_to_sunrise_plugin', 'plugin_main');
}
function plugin_section_text() {
  echo '<p>Email account to poll mail from.</p>';
}
function the_mail_server_input() {
  $options = get_option('plugin_options');
  echo "<input id='mail_server_string' name='plugin_options[mail_server_string]' size='40' type='text' value='{$options['mail_server_string']}' />";
}
function plugin_options_validate($input) {
  $newinput['mail_server_string'] = trim($input['mail_server_string']);
  // if(!preg_match('/^[a-z0-9]{32}$/i', $newinput['text_string'])) {
  //   $newinput['text_string'] = '';
  // }
  return $newinput;
}

?>