email-to-sunrise
================

WIP - email-to-sunrise. My family sends around sunrise and sunset photos via email,
this wordpress plugin parses those emails and converts them into Wordress posts maintaining 
threaded conversations by converting all replies to comments on the original post.

### Get Started
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
   
### Notes
   * vagrant debug
      * VAGRANT_LOG=INFO vagrant up
      
### Todo
   * rewrite url endpoint for email checking:
      * http://wordpress.stackexchange.com/questions/9870/how-do-you-create-a-virtual-page-in-wordpress