#!/bin/sh

E_BADARGS=85
if [ $# != 2 ]
	then
	  PROG_NAME=`basename $0`
	  echo "$PROG_NAME: requires 2 parameters"
	  echo "usage: $PROG_NAME <hostname> <remote-path-to-wordpress-dir>"
	  exit $E_BADARGS
	fi
	
HOST=$1
WP_DIR=$2
PLUGIN_LOCAL=emailtosunrise-plugin/
THEME_LOCAL=emailtosunrise-theme/
PLUGIN_REMOTE="$WP_DIR/wp-content/plugins/"
THEME_REMOTE="$WP_DIR/wp-content/themes/"
FAVICON_LOCAL="emailtosunrise-theme/favicon.ico"
FAVICON_REMOTE="$WP_DIR/"

SCP_PLUGIN="scp -r $PLUGIN_LOCAL $HOST:$PLUGIN_REMOTE"
SCP_THEME="scp -r $THEME_LOCAL $HOST:$THEME_REMOTE"
SCP_FAVICON="scp $FAVICON_LOCAL $HOST:$FAVICON_REMOTE"

echo $SCP_PLUGIN
echo $SCP_THEME
echo $SCP_FAVICON

read -p "Run above commands <y/n>? " -n 1 -r
if [[ $REPLY =~ ^[Yy]$ ]]
then
	echo
	`$SCP_PLUGIN`
	`$SCP_THEME`
	`$SCP_FAVICON`
	echo "Success"
else
	echo
	echo "Canceled"
fi