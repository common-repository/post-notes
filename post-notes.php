<?php

/**
 * Post_notes
 *
 * @package Post Notes
 * @author Jonathan Christopher
 */





class Post_notes
{




	/**
	 * Runs only when the plugin is activated, sets up the database table for Post Notes
	 *
	 * @return void
	 * @author Jonathan Christopher
	 */
	function post_notes_install()
	{
		global $wpdb, $table_prefix, $post;
		$table_name = $wpdb->prefix . "postnotes";
		if($wpdb->get_var("show tables like '$table_name'") != $table_name)
		{
			$sql = "CREATE TABLE " . $table_name . " (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				postid bigint(20) NOT NULL,
				text text NOT NULL,
				status varchar(10),
				noteorder int(4) NOT NULL,
				UNIQUE KEY id (id)
				);";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
	}






	/**
	 * Recursive function to strip slashes from an associative array
	 *
	 * @param array $value The array from which you want to strip slashes
	 * @return void
	 * @author Jonathan Christopher
	 */
	function post_notes_stripslashes_deep($value)
	{
		$value = is_array($value) ?
			array_map('stripslashes_deep', $value) :
		stripslashes($value);
		return $value;
	}






	/**
	 * Injects the markup to include the required Post Notes JavaScript
	 *
	 * @return void
	 * @author Jonathan Christopher
	 */
	function post_notes_javascript()
	{
		echo '<script type="text/javascript" src="' . plugins_url("/post-notes/js/post-notes.js") .'"></script>';
	}






	/**
	 * Injects the markup to include the required Post Notes CSS
	 *
	 * @return void
	 * @author Jonathan Christopher
	 */
	function post_notes_css()
	{
		echo '<link rel="stylesheet" href="' . plugins_url("/post-notes/css/post-notes.css") .'" type="text/css" media="screen" />';
	}






	/**
	 * Calls WordPress' function for injecting markup into the post screen
	 * Only adds the injection request to a queue within WordPress, does not actually insert at this time
	 *
	 * @return void
	 * @author Jonathan Christopher
	 */
	function post_notes_add_note_entry()
	{
		add_meta_box( 'post_notes_section1', __( 'Post Note', 'post_notes_textdomain' ), 
				array('Post_notes','post_notes_inner_custom_box'), 'post', 'advanced' );
	}






	/**
	 * Callback that injects the markup within the meta box
	 *
	 * @return void
	 * @author Jonathan Christopher
	 */
	function post_notes_inner_custom_box()
	{
		// we can force 1 because this function is called only when no Notes exist
		echo '<textarea class="post_notes_copy" name="post_notes_copy1" id="post_notes_copy1" rows="3" size="25"></textarea>';
	}






	/**
	 * Repeated call to WordPress' add_meta_box() in order to prep an area for an existing Post Note
	 *
	 * @param int $tmp_index The index of the current note
	 * @param int $tmp_post_note_id The id of the current Post Note
	 * @return void
	 * @author Jonathan Christopher
	 */
	function post_notes_insert_note($tmp_index,$tmp_post_note_id)
	{
		global $post_note_id, $post_note_index, $count;
		$post_note_id = $tmp_post_note_id;
		$count = $tmp_index;
		
		add_meta_box( 'post_notes_section'.$tmp_index, __( 'Post Note', 'post_notes_textdomain' ), 
				array('Post_notes','post_notes_inner_custom_box_populated'), 'post', 'advanced' );
		do_meta_boxes('post_notes_section','advanced',null);
	}






	/**
	 * Empty callback for post_notes_insert_note()
	 *
	 * @return void
	 * @author Jonathan Christopher
	 */
	function post_notes_inner_custom_box_populated()
	{
		return;
	}






	/**
	 * Injects JavaScript to set up retrieval of existing Post Note copy
	 *
	 * @return void
	 * @author Jonathan Christopher
	 */
	function post_notes_load_existing_notes_javascript()
	{
		echo "\n\n" . '<script type="text/javascript">' . "\n";
		if(isset($_GET['post']))
		{
			if(Post_notes::post_notes_get_notes_count($_GET['post'])>0)
			{
				echo '	post_notes_load_existing_notes("' . plugins_url() . '",' . $_GET['post'] . ')' . "\n";
			}
			else
			{
				echo '	post_notes_init_notes()' . "\n";
			}
		}
		else
		{
			echo '	post_notes_init_notes()' . "\n";
		}
		echo '</script>' . "\n\n";
	}






	/**
	 * Deletes all 'final' notes for a single post
	 *
	 * @param int $post_id The id of the post for which you want to remove all existing Post Notes
	 * @return void
	 * @author Jonathan Christopher
	 */
	function flush_existing_post_notes($post_id)
	{
		global $wpdb, $table_prefix, $post;
		$table_name = $wpdb->prefix . "postnotes";
		
		// make sure we're dealing with a post not a page
		if ( 'page' != $_POST['post_type'] )
		{
			// check to make sure the sure can indeed edit the post
			if ( !current_user_can( 'edit_post', $post_id ))
				return $post_id;
		}
		
		$delete = "DELETE FROM " . $table_name . " WHERE status='final' and postid = " . $post_id;
		$results = $wpdb->query($delete);
		return $post_id;
	}






	/**
	 * Parses POST data and saves any Post Notes to the database
	 *
	 * @param int $post_id The id of the current post
	 * @return void
	 * @author Jonathan Christopher
	 */
	function post_notes_save_notes($post_id)
	{
		global $wpdb, $table_prefix, $post;
		$table_name = $wpdb->prefix . "postnotes";
				
		// make sure we're dealing with a post not a page
		if ('page' != $_POST['post_type'])
		{
			// check to make sure the sure can indeed edit the post
			if (!current_user_can( 'edit_post', $post_id ))
			{
				return $post_id;
			}	
			
			if(!isset($_POST['post_notes_note_count']))
			{
				return $post_id;
			}
		}

		// OK, we're authenticated: we need to find and save the data
		
		// We need to track whether or not this is a post revision or final
		if(wp_is_post_revision($post_id))
		{
			$note_status = "revision";
		}
		else
		{
			$note_status = "final";
			// Let's remove any old final versions...
			Post_notes::flush_existing_post_notes($post_id);
		}

		for ($i=1; $i <= $_POST['post_notes_max']; $i++)
		{
			$note_copy = $_POST['post_notes_copy'.$i];
			if(trim($note_copy)!="")
			{
				// let's set the order
				$order = $_POST['post_notes_max'];
				if(isset($_POST['post_notes_note_order_'.$i]))
				{
					$order = intval($_POST['post_notes_note_order_'.$i]);
				}
				
				if(!is_int($order))
				{
					$order = $_POST['post_notes_max'];
				}
				
				// we need to wpautop because TinyMCE doesn't pass paragraphs...
				$note_copy = wpautop($note_copy);
				
				$insert = "INSERT INTO " . $table_name .
						  " (postid, text, status, noteorder) " .
						  "VALUES (" . $wpdb->escape($post_id) . ",'" . $wpdb->escape($note_copy) . "','" . $wpdb->escape($note_status) . "'," . $order . ")";

				$results = $wpdb->query($insert);
			}
		}
		
		return $post_id;
	}






	/**
	 * Retrieve the existing Post Notes for a single post
	 *
	 * @param int $post_id The ID of the post for which you would like to retrieve Post Notes
	 * @return void
	 * @author Jonathan Christopher
	 */
	function post_notes_load_existing_notes($post_id)
	{
		global $wpdb, $table_prefix, $post, $post_note_index;
		$table_name = $wpdb->prefix . "postnotes";
		
		if ( !current_user_can( 'edit_post', $post_id ))
		{
			return $post_id;
		}
		
		// Authenticated
		
		// Prep query
		$sql = "SELECT * FROM " . $table_name . " WHERE postid = " . $post_id;
		$results = $wpdb->get_results($sql,ARRAY_A);
		$results = Post_notes::post_notes_stripslashes_deep($results);
		
		// Set notes count
		$post_note_count = sizeof($results);
		
		if($post_note_count>0)
		{
			// Inject pre-populated notes
			for ($i=1; $i <= $post_note_count; $i++)
			{
				$post_note_index=$i;
				Post_notes::post_notes_insert_note($i,$results[$i-1]['id']);
			}
		}
	}






	/**
	 * Retrieve the existing notes for a single post
	 *
	 * @param int $post_id The ID of the post for which you would like to retrieve Post Notes
	 * @return array $results Associative array containing all Notes
	 * @author Jonathan Christopher
	 */
	function post_notes_get_existing_notes($post_id)
	{
		global $wpdb, $table_prefix, $post, $post_note_index;
		$table_name = $wpdb->prefix . "postnotes";
		
		// Prep query
		$sql = "SELECT text FROM " . $table_name . " WHERE status = 'final' AND postid = " . $post_id . " and text != '' ORDER BY noteorder";
		$results = $wpdb->get_results($sql,ARRAY_A);
		$results = Post_notes::post_notes_stripslashes_deep($results);
		
		return $results;
	}






	/**
	 * Retrieve the number of notes for a particular post
	 *
	 * @param int $post_id The post for which you would like the Post Note count
	 * @return int $result The number of Post Notes for a single post
	 * @author Jonathan Christopher
	 */
	function post_notes_get_notes_count($post_id)
	{
		global $wpdb, $table_prefix, $post;
		$table_name = $wpdb->prefix . "postnotes";
		
		$sql = "SELECT count(postid) as cnt FROM " . $table_name . " WHERE postid = " . $post_id;
		$result = $wpdb->get_results($sql,ARRAY_A);
		return intval($result[0]['cnt']);
	}






	/**
	 * Initializes functionality for Post Notes
	 *
	 * @return void
	 * @author Jonathan Christopher
	 */
	function post_notes_init_notes()
	{
		global $post_note_index;
		
		$post_note_index = 1;
		
		if(isset($_GET['post']))
		{
			$post_id = $_GET['post'];
			if(!is_array($post_id))
			{
				$post_count = intval(Post_notes::post_notes_get_notes_count($post_id));
				if($post_count>0)
				{
					// We're editing, and we have existing notes, so let's load all existing
					Post_notes::post_notes_load_existing_notes($post_id);
				}
				else
				{
					// No existing notes, let's just set up a blank...
					Post_notes::post_notes_add_note_entry();
				}
			}
		}
		else
		{
			// This is a new note, only need to prep empty note
			Post_notes::post_notes_add_note_entry();
		}
	}
}

?>