<?php include("source_control_diff.php"); 
  function print_version_control_template_diff($id) { 
    global $wpdb;
  	$table_name = $wpdb->prefix . "version_control_templates";
  	$my_template = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id) );
  	print_r("<pre>".htmlspecialchars_decode(htmlspecialchars(file_get_contents(Path::normalize($my_template->diff_file))))."</pre>");
  }
	
	function create_diff_file($orig_file, $new_file, $diff_file) {
	  $current_content = file_get_contents(Path::normalize($orig_file));
	  $version_content = file_get_contents(Path::normalize($new_file));
	  
	  // write diff file
	  $myFile = $diff_file;
    $fh = fopen($myFile, 'w') or die("can't write diff file");
    $stringData = (htmlDiff(htmlspecialchars($current_content), htmlspecialchars($version_content)));
    fwrite($fh, $stringData);
    fclose($fh);
	}

?>