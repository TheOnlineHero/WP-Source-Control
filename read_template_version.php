<?php

  function print_version_control_template_file($id) {
  	try{
  	  global $wpdb;
  		$table_name = $wpdb->prefix . "version_control_templates";
  		$my_template = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id) );
  		$lines = file($my_template->file_name);

  		echo "<pre>";
  		foreach ($lines as $line_num => $line) {
  		    echo htmlspecialchars($line);
  		}
  		echo "</pre>";
  	} catch (Exception $ex) {
  		echo "Sorry, version file no longer exists.";
  	}    
  }

?>