<?php
  function print_version_control_post_diff($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . "version_control_posts";
    $post = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE revision_id= %d", $id) );
    
    // Javascript Back To Search Result.
  	echo "<p><a href='javascript: history.go(-1)'>Back</a></p>";
    print_r("<pre>".htmlspecialchars_decode(htmlspecialchars($post->diff_content))."</pre>");
    
    // Javascript Back To Search Result.
  	echo "<p><a href='javascript: history.go(-1)'>Back</a></p>";
    
  }

  function print_current_version_control_post_diff($id) {
    // TODO - Print Diff
    global $wpdb;
    $table_name = $wpdb->prefix . "posts";
    $my_revision = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE post_type='revision' AND ID= %d", $id) );
    $my_post = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE post_type IN ('page', 'post') AND ID=%d", $my_revision->post_parent) );

    $vc_post = $wpdb->prefix . "version_control_posts";
    $my_previous_commit = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name as p, $vc_post as v WHERE post_id=%d AND p.id = v.post_id", $my_post->ID) );
    
    // Javascript Back To Search Result.
  	echo "<p><a href='javascript: history.go(-1)'>Back</a></p>";
    
    print_r("<pre>".
    htmlspecialchars_decode(
      htmlspecialchars(
        (htmlDiff(htmlspecialchars($my_previous_commit->current_content), htmlspecialchars($my_post->post_content)))
      )."</pre>"));
      
    // Javascript Back To Search Result.
  	echo "<p><a href='javascript: history.go(-1)'>Back</a></p>";
    
  }

  function get_diff_post($revision_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . "posts";
    $my_revision = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE post_type='revision' AND id= %d", $revision_id) );
    $my_post = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE post_type IN ('page', 'post') AND id=%d", $my_revision->post_parent) );

    $current_content = $my_post->post_content;
    $version_content = $my_revision->post_content;

    return htmlDiff(htmlspecialchars($version_content), htmlspecialchars($current_content));
  }

?>