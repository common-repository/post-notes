// ================================
// = GLOBAL VARIABLE DECLARATIONS =
// ================================
var post_notes_count 		= 1;
var post_notes_max 			= 1;
var post_notes_index		= 0;
var controls 				= '';
var existing_notes_count 	= 0;






/**
 * Generates unique markup for the Order field as well as the 'New' and 'Delete' links
 * @param {Number} count Current index of the Post Note
 */
function post_notes_generate_controls_markup(count)
{
	controls = '<div class="post_notes_controls"><div class="post_notes_textfield"><label for="post_notes_note_order_' + count + '">Order</label><input type="text" class="post_notes_order" name="post_notes_note_order_' + count + '" id="post_notes_note_order_' + count + '" value="' + count + '" /></div><p><a class="post_note_duplicate" href="#">New Note</a> <a class="post_note_delete" href="#">Delete This Note</a></p></div>';
}






/**
 * Injects the markup provided by WordPress' add_meta_box, including the textarea
 */
function post_notes_create_note_duplicate()
{
	post_notes_count++;
	post_notes_max++;
	post_notes_index++;
	jQuery('.post_note:last').after('<div id="post_notes_sectionid'+post_notes_index+'" class="postbox post_note"><div class="handlediv" title="Click to toggle"><br /></div><h3 class="hndle"><span>Post Note</span></h3><div class="inside"><textarea name="post_notes_copy'+post_notes_index+'" class="post_notes_copy" id="post_notes_copy'+post_notes_index+'" rows="3" size="25"></textarea></div></div>');
	post_notes_generate_controls_markup(post_notes_index);
	jQuery('#post_notes_copy'+post_notes_index).after(controls);
	post_notes_update_notes_count();
	tinyMCE.execCommand("mceAddControl", false, "post_notes_copy"+post_notes_index);
	post_notes_unhook_controls();
	post_notes_hook_controls();
}






/**
 * Removes an existing Notes entry by disabling TinyMCE and removing the parent-most element from the DOM
 */
function post_notes_create_note_delete()
{
	editor_id = jQuery('#post_note_delete_hook').parent().parent().parent().find('textarea').attr('id');
	tinyMCE.execCommand("mceRemoveControl", false, editor_id);
	jQuery('#post_note_delete_hook').parent().parent().parent().parent().remove();
	post_notes_update_notes_count();
	post_notes_unhook_controls();
	post_notes_hook_controls();
}






/**
 * Removes click events from 'duplicate' and 'delete' anchors
 */
function post_notes_unhook_controls()
{
	jQuery("a.post_note_duplicate").unbind('click');
	jQuery("a.post_note_delete").unbind('click');
}






/**
 * Binds the click events for 'duplicate' and 'delete' anchors
 */
function post_notes_hook_controls()
{
	jQuery('a.post_note_duplicate').click(function()
	{
		post_notes_create_note_duplicate();
		return false;
	});
	jQuery('a.post_note_delete').click(function()
	{
		jQuery(this).attr('id','post_note_delete_hook');
		post_notes_create_note_delete();
		return false;
	});
	
	// if there's only one note field, we want to prevent deleting it
	if(post_notes_count==1)
	{
		jQuery('.post_note_delete').hide();
	}
	else
	{
		jQuery('.post_note_delete').show();
	}
}






/**
 * Loads the existing notes from the WordPress database via JSON
 * @param {String} target_url The URL to the plugins of the current WordPress installation
 * @param {Number} post_id The WordPress post ID for which you would like to retrieve notes
 */
function post_notes_load_existing_notes(target_url,post_id)
{
	// first we need to get an array of Note IDs
	jQuery.getJSON(target_url+'/post-notes/post-notes-get-notes-for-post.php', { id: post_id },
		function(json){
			existing_notes_count = json.length;
			
			if(existing_notes_count>0)
			{
				// we know the total number of notes, let's prep the textareas
				for (var i=1; i <= existing_notes_count; i++) {
					post_notes_index++;
					jQuery('#post_notes_section'+i).addClass('post_note');
					jQuery('#post_notes_section'+i+' .inside').append('<textarea name="post_notes_copy'+i+'" class="post_notes_copy" id="post_notes_copy'+i+'" rows="5" size="25">' + json[i-1].text + '</textarea>');
					post_notes_generate_controls_markup(i);
					jQuery('#post_notes_copy'+i).after(controls);
				};
				
				// finally, we'll invoke TinyMCE and call it a damn day...
				jQuery('.post_note').each(function() {
					current_id = jQuery(this).attr('id').replace(new RegExp('post_notes_section'), '');
					tinyMCE.execCommand("mceAddControl", false, "post_notes_copy"+current_id);
				});
				
				// we MUST update this count, or nothing will get saved
				post_notes_update_notes_count();
				
				// need to hook all of our newly created controls...
				post_notes_hook_controls();
				
				// finally we'll take care of the input tracking we need
				post_notes_prep_input_trackers();
			}
		});
}






/**
 * Counts the number of Notes on the page and sets the proper input values in the DOM
 */
function post_notes_update_notes_count()
{
	// we're just checking to see how many of OUR textareas have been added...
	post_notes_count = parseInt(jQuery('.post_notes_copy').length);
	jQuery('#post_notes_note_count').attr('value',post_notes_count);
	if(post_notes_max<post_notes_count)
	{
		post_notes_max = post_notes_count;
	}
	jQuery('#post_notes_max').attr('value',post_notes_max);
}






/**
 * Injects fields we need for tracking stats about existing Post Notes 
 */
function post_notes_prep_input_trackers()
{
	// we need to track how many Notes we've got
	jQuery('#post').append('<input type="hidden" name="post_notes_note_count" id="post_notes_note_count" value="'+post_notes_count+'" />');
	jQuery('#post').append('<input type="hidden" name="post_notes_max" id="post_notes_max" value="'+post_notes_count+'" />');
}






/**
 * Initializes functionality. Invokes TinyMCE on proper textareas and sets initial Notes counts
 */
function post_notes_init_notes()
{
	if(jQuery('#post').length!=0)
	{
		for (var i=1; i <= post_notes_count; i++)
		{
			jQuery('#post_notes_section'+i+'').addClass('post_note');
			post_notes_generate_controls_markup(i);
			jQuery('#post_notes_copy'+i+'').after(controls);
			tinyMCE.execCommand('mceAddControl', false, 'post_notes_copy'+i+'');
			jQuery('#post_notes_sectionid'+i+'').addClass('post_note');
		};
		post_notes_index = post_notes_count;
		post_notes_update_notes_count();	
		post_notes_max = post_notes_count;
		post_notes_prep_input_trackers();
		post_notes_hook_controls();
	}
}