<?php
  function print_version_control_post_diff($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . "version_control_posts";
    $post = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE revision_id= %d", $id) );
    print_r("<pre>".htmlspecialchars_decode(htmlspecialchars($post->diff_content))."</pre>");
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