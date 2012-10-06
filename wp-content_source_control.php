<?php
/*
Plugin Name: WP Content Source Control
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Source Control For Your Theme Directory And Posts/Pages.
Version: 1.0
Author: Tom Skroza
Author URI: 
License: GPL2
*/

require_once('source_control_path.php'); 
require_once("source_control_template_diff.php");
require_once("source_control_post_diff.php");
add_action('admin_menu', 'register_source_control_page');

function register_source_control_page() {
   add_menu_page('WP Source Control', 'WP Source Control', 'update_themes', 'wp_content_source_control/source_control_list.php', '',  '', 1);
}

function wp_content_source_activate() {
   
   global $wpdb;
   $table_name = $wpdb->prefix . "version_controls";

   $sql = "CREATE TABLE $table_name (
id mediumint(9) NOT NULL AUTO_INCREMENT, 
job_no VARCHAR(50) DEFAULT '', 
description VARCHAR(255) DEFAULT '', 
theme_timestamp bigint,
PRIMARY KEY  (id)
);";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);


   $table_name = $wpdb->prefix . "version_control_templates";

    // Save current template file in version_control_template
    $sql = "CREATE TABLE $table_name (
id mediumint(9) NOT NULL AUTO_INCREMENT, 
job_id mediumint(9), 
orig_file_name VARCHAR(255) DEFAULT '',
file_name VARCHAR(255) DEFAULT '', 
template_timestamp bigint,
diff_file VARCHAR(255) NOT NULL,
PRIMARY KEY  (id)
);";

    dbDelta($sql);

    $table_name = $wpdb->prefix . "version_control_posts";

   $sql = "CREATE TABLE $table_name (
id mediumint(9) NOT NULL AUTO_INCREMENT, 
revision_id mediumint(9), 
job_id mediumint(9) DEFAULT 0,
job_deleted mediumint(9) DEFAULT 0,
diff_content longtext NOT NULL,
PRIMARY KEY  (id)
);";

    dbDelta($sql);

}
register_activation_hook( __FILE__, 'wp_content_source_activate' );

add_action( 'save_post', 'save_post_version' );
function save_post_version( $postid ) {
    global $wpdb;
    $table_name = $wpdb->prefix . "version_control_posts";  
    $rows_affected = $wpdb->insert( $table_name, array( 'revision_id' => $postid));
}
add_action( 'trashed_post', 'delete_post_version' );
function delete_post_version( $postid ) {
    global $wpdb;
    $table_name = $wpdb->prefix . "version_control_posts";  
    $rows_affected = $wpdb->insert( $table_name, array( 'revision_id' => $postid, 'job_deleted' => 1));
}


function create_theme_snapshot($templates, $post_ids, $job_no, $description) { 
    if (!($post_ids == null && $templates == null)) {
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone('UTC'));
        $date_time_stamp = $date->getTimestamp();
        //echo (Path::normalize(dirname(__FILE__).'/version_'.$date_time_stamp));
        mkdir(Path::normalize(dirname(__FILE__).'/version_'.$date_time_stamp));
        mkdir(Path::normalize(dirname(__FILE__).'/version_'.$date_time_stamp."/themes"));
        mkdir(Path::normalize(dirname(__FILE__).'/version_'.$date_time_stamp."/uploads"));
        
        $theme_file = Path::normalize(dirname(__FILE__)."../../../themes");
        // $upload_file = dirname(__FILE__)."../../../uploads";
    	  $new_theme_file = Path::normalize(dirname(__FILE__).'/version_'.$date_time_stamp."/themes");
        // $new_upload_file = dirname(__FILE__).'../version_'.$date_time_stamp."/uploads";

        global $wpdb;
        $table_name = $wpdb->prefix . "version_controls";
        // Save job no, timestamp to theme_version_control database.
        $rows_affected = $wpdb->insert( $table_name, array( 'job_no' => $job_no, 'description' => $description, 'theme_timestamp' => $date_time_stamp) );

        $table_name = $wpdb->prefix . "version_control_posts";
        
        if ($post_ids != null) {
            foreach ($post_ids as $post_id) {
              $wpdb->update($table_name, array('job_id' => $wpdb->insert_id, 'diff_content' => get_diff_post($post_id)), array( 'job_id' => 0, 'revision_id' => $post_id));
            }
        }
        if ($templates != null) {
        	if (!snapshot_directory($templates, $wpdb->insert_id, $theme_file, $new_theme_file)) {
        	    echo "failed to copy $theme_file...\n";
        	} 
            // if (!snapshot_directory($wpdb->insert_id, $upload_file, $new_upload_file)) {
            //     echo "failed to copy $upload_file...\n";
            // } 
        }
    }
}


function print_updated_template_files($src) {

    global $wpdb;
    $table_name = $wpdb->prefix . "version_control_templates";
    $dir = opendir($src); 
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) { 
                print_updated_template_files($src . '/' . $file); 
            } 
            else { 
                $formatted_src = str_replace("/", "::::", $src.'/'.$file);
                $my_template = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE orig_file_name = %s ORDER BY template_timestamp DESC", $src . '/' . $file) );
                if ($my_template != null){
                    if ($my_template->template_timestamp != filemtime($src.'/'.$file)) {
                        echo "<tr><td><input type=\"checkbox\" name=\"checkin_templates[]\" value=\"$formatted_src\"></td><td style='width: 800px;'>".$src.'/'.$file."</td><td>".date("l jS \of F Y h:i:s A", filemtime($src.'/'.$file))."</td></tr>";
                    }
                } else {
                    echo "<tr><td><input type=\"checkbox\" name=\"checkin_templates[]\" value=\"$formatted_src\"></td><td style='width: 800px;'>".$src.'/'.$file."</td><td>".date("l jS \of F Y h:i:s A", filemtime($src.'/'.$file))."</td></tr>";
                }
                 
            } 
        } 
    } 
    closedir($dir); 
    return $content;

}

function get_updated_template_files($src) {
    global $wpdb;
    $content = "";
    $table_name = $wpdb->prefix . "version_control_templates";
    $dir = opendir($src); 
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) { 
                $content .= get_updated_template_files($src . '/' . $file); 
            } 
            else { 
                $my_template = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE orig_file_name = %s ORDER BY template_timestamp DESC", $src . '/' . $file) );
                if ($my_template != null){
                    if ($my_template->template_timestamp != filemtime($src.'/'.$file)) {
                        $content .= $src.'/'.$file."</td><td>".date("l jS \of F Y h:i:s A", filemtime($src.'/'.$file))."::::";
                    }
                } else {
                    $content .= $src.'/'.$file."</td><td>".date("l jS \of F Y h:i:s A", filemtime($src.'/'.$file))."::::";
                }
                 
            } 
        } 
    } 
    closedir($dir); 
    return $content; 
}


function snapshot_directory($templates, $job_id,$src,$dst) { 
    global $wpdb;
    $table_name = $wpdb->prefix . "version_control_templates";
    $dir = opendir($src); 
    try{
        @mkdir($dst); 
        @mkdir($dst."/diff/");
        while(false !== ( $file = readdir($dir)) ) { 
            if (( $file != '.' ) && ( $file != '..' )) { 
                if ( is_dir($src . '/' . $file) ) { 
                    snapshot_directory($templates, $job_id, $src . '/' . $file,$dst . '/' . $file); 
                } 
                else { 
                    // Check if user wants to check the file in.
                    if (in_array(str_replace("/", "::::", $src.'/'.$file), $templates)) {
                        // User does want to check file in.
                        $my_template = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE orig_file_name = %s ORDER BY template_timestamp DESC", $src . '/' . $file) );
                        // Check if file exists
                        if ($my_template != null){
                            // File exists
                            if ($my_template->template_timestamp != filemtime($src.'/'.$file)) {
                                $rows_affected = $wpdb->insert( $table_name, array( 'job_id'=>$job_id, 'orig_file_name' => $src.'/'.$file, 'file_name' => $dst . '/' . $file, 'template_timestamp' => filemtime($src.'/'.$file), 'diff_file' => $dst . '/diff/' . $file) );
                                copy($src . '/' . $file,$dst . '/' . $file);
                                create_diff_file($my_template->file_name, $dst . '/' . $file, $dst . '/diff/' . $file);
                            }
                        } else if ($my_template == null) {
                            // File Does not exist, So add it in.
                            $rows_affected = $wpdb->insert( $table_name, array( 'job_id'=>$job_id, 'orig_file_name' => $src.'/'.$file, 'file_name' => $dst . '/' . $file, 'template_timestamp' => filemtime($src.'/'.$file)) );
                            copy($src . '/' . $file,$dst . '/' . $file);
                        } 
                    }
                     
                } 
            } 
        } 
        closedir($dir); 
    } catch(Exception $ex) {
        return false;
    }
    return true;
}


?>