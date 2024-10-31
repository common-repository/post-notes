<?php
	require( dirname(__FILE__) . '/../../../wp-config.php' );
	require 'thirdparty/jsonwrapper/jsonwrapper.php';
	require_once 'post-notes.php';
	
	if(isset($_GET['id']))
	{
		$post_id = $_GET['id'];
		$notes = json_encode(Post_notes::post_notes_get_existing_notes($post_id));
		echo $notes;
	}
	else
	{
		echo 'An unexpected error has occurred.';
	}
?>