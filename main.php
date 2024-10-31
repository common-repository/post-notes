<?php
/*
Plugin Name: Post Notes
Version: 1.0.1
Plugin URI: http://mondaybynoon.com/wordpress-post-notes/
Author: Jonathan Christopher
Author URI: http://mondaybynoon.com
Description: Provides ability to add any number of WYSIWYG formatted notes to each post

Compatible with WordPress 2.7.1


================ 
INSTALLATION   
================ 

1. Download the file http://mondaybynoon.com/download/plugins/wordpress/post-notes/post-notes-1.0b.zip 
   and unzip it into your /wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' admin menu in WordPress


========= 
USAGE   
========= 

To retrieve an associative array containing all Notes for a post, use the following within The Loop:	

	<?php $post_notes = post_notes_get_notes(); ?>

You will be provided an associative array with which to work in your template:

	<?php $total_notes = sizeof($post_notes); ?>
	<?php if($total_notes>0) : ?>
		<?php for ($i = 0; $i < $total_notes; $i++) : ?>
			<div class="post-note">
				<div><?php echo $total_notes[$i]['text']; ?></div>
			</div>
		<?php endfor ?>
	<?php endif ?>

*/

/*  Copyright 2009 Jonathan Christopher (email: jonathan@mondaybynoon.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    For a copy of the GNU General Public License, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


global $post_notes_plugin_url;
$post_notes_plugin_url = WP_PLUGIN_URL;

require_once 'post-notes.php';
require_once 'post-notes-getter.php';

register_activation_hook( __FILE__, array('Post_notes','post_notes_install'));

add_action('admin_head', array('Post_notes','post_notes_javascript'));
add_action('admin_head', array('Post_notes','post_notes_css'));
add_action('admin_footer', array('Post_notes','post_notes_load_existing_notes_javascript'));

add_action('admin_menu', array('Post_notes','post_notes_init_notes'));

add_action('save_post', array('Post_notes','post_notes_save_notes'));

?>