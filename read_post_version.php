<?php
	
	function print_version_control_post($id) {
	  global $wpdb;
  	$table_name = $wpdb->prefix . "version_control_posts";
  	$post_record = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE revision_id=%d", $id));

  	// Javascript Back To Search Result.
  	echo "<p><a href='javascript: history.go(-1)'>Back</a></p>";

  	echo "<pre>";
  	echo htmlspecialchars($post_record->current_content);
  	echo "</pre>";	  
  	
  	// Javascript Back To Search Result.
  	echo "<p><a href='javascript: history.go(-1)'>Back</a></p>";
	}

?>