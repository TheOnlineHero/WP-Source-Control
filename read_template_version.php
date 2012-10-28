<?php

  function print_version_control_template_file($id) {
    
    // Javascript Back To Search Result.
  	echo "<p><a href='javascript: history.go(-1)'>Back</a></p>";
    
  	try{
  	  global $wpdb;
  		$table_name = $wpdb->prefix . "version_control_templates";
  		$my_template = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id) );
  		$lines = file(Path::normalize(dirname(__FILE__).'../../../source_control/').$my_template->file_name);

  		echo "<pre>";
  		foreach ($lines as $line_num => $line) {
  		    echo htmlspecialchars($line);
  		}
  		echo "</pre>";
  	} catch (Exception $ex) {
  		echo "Sorry, version file no longer exists.";
  	}    
  	
  	// Javascript Back To Search Result.
  	echo "<p><a href='javascript: history.go(-1)'>Back</a></p>";
  	
  }

?>