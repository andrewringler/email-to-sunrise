email-to-sunrise
================

WIP - email-to-sunrise. My family sends around sunrise and sunset photos via email,
this wordpress plugin parses those emails and converts them into Wordress posts maintaining 
threaded conversations by converting all replies to comments on the original post.

### Production
   * Install wordpress somewhere
      * and plugins:
         * [Force Strong Passwords](http://bit.ly/ForceStrongPasswordsPlugin)
         * [Limit Login Attempts](http://bit.ly/LimitLoginAttemptsPlugin)
   * Setup users (be sure to set display name), only registered authors will be allowed to post
   * Set site title
   * Set 15 posts max
   * Settings -> Permalinks -> /%postname%-%year%-%monthnum%-%day%/
   * Widgets -> Archives & Categories
   * ./pushToProduction.sh
   * active theme
   * active plugin
   * set Settings -> Writing -> Post via e-mail
   * visit sitename/wp-mail.php to populate emails
   * Add categories to menu
   
### Developer - Getting Started
   * Clone repo, cd email-to-sunrise
   * Install [rvm](https://rvm.io/) latest
      * rvm use 1.9.3
   * Setup chef 10.14.2
      * gem install librarian
      * mkdir cookbooks
      * librarian-chef install
   * Install Composer [composer](http://getcomposer.org/)
       * curl -s https://getcomposer.org/installer | php
       * Run Composer
          * ./afterPull 
   * Install virtualbox 4.2.0
   * Install [Vagrant](http://docs.vagrantup.com/) v1.0.6
   * vagrant up
   * enable debug
      * vagrant ssh
      * sudo -u www-data vi /var/www/wordpress/wp-config.php
      * add the following to wp-config.php and save 
         * define('WP_DEBUG', true);
   * visit site: http://192.168.33.20/
   
#### Run the Tests
   * Run the Tests
      * ./vendor/bin/phpunit test

### References
   * [Post to your blog using email](http://codex.wordpress.org/Post_to_your_blog_using_email)
   * [composer wordpress tutorial](http://www.andrewmeredith.info/tutorials/2012/10/26/wordpress-plugins-with-composer-tutorial/)
   * [wordpress data sanitization and validation](http://wp.tutsplus.com/tutorials/creative-coding/data-sanitization-and-validation-with-wordpress/)
   * [remote debugging](http://bogdan-albei.blogspot.com/2010/06/php-remote-debugging-with-xdebug-and.html?m=1)
      
### Notes
   * vagrant debug
      * VAGRANT_LOG=INFO vagrant up
   * vagrant ssh   (login to vagrant instance)
      * less /var/log/apache2/wordpress-error.log
      * mysql -u root -p           use password 0JR1qLXJkztAbgOBGNBoLzimU
   
### Todo
   * multiple images for a single email
   * images in comments
   * more robust email checking
      * don't blow about all emails when checking
      * should only check unseen emails
   * menu is being clobbered on load
   * post thumbnails
   * user avatars
   * custom params for plugin (instead of using wp-email endpoint)
   * more intelligent about which posts are originals
      * capture image md5 don't show again?
   * rewrite url endpoint for email checking: http://wordpress.stackexchange.com/questions/9870/how-do-you-create-a-virtual-page-in-wordpress