<?php

function post_notes_get_notes()
{
	global $post;
	$notes = Post_notes::post_notes_get_existing_notes($post->ID);
	if(!is_array($notes))
	{
		$notes = array();
	}
	return $notes;
}

?>