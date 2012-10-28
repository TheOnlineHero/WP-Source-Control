<?php 
  require_once(dirname(__FILE__).'../../../../wp-admin/admin.php'); 

  require_once('source_control_path.php'); 

  global $wpdb;
  $table_name = $wpdb->prefix . "version_controls";
 
  wp_enqueue_script('jquery');
  wp_enqueue_script('jquery-ui-datepicker');
  wp_enqueue_style( 'jquery.ui.theme', "http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.1/themes/ui-lightness/jquery-ui.css");


  if ($_POST["submit"] == "Search") {
  	if ($_POST["search_start_date"] != null) {
  		$start_date = new DateTime();
  		$arr = explode("-", $_POST["search_start_date"]);
  		$start_date->setDate($arr[0],$arr[1],$arr[2]);
  		$start_timestamp = $start_date->getTimestamp();
  	}
  	if ($_POST["search_end_date"] != null) {
  		$end_date = new DateTime();
  		$arr = explode("-", $_POST["search_end_date"]);
  		$end_date->setDate($arr[0],$arr[1],$arr[2]);
  		$end_timestamp = $end_date->getTimestamp();
  	}

  	$job_no_sql = "";
  	$extra_sql = "";

  	if ($_POST["search_job_no"] != null) {
  		$job_no_sql = "job_no = '".$_POST["search_job_no"]."'";
  	}

  	if (($_POST["search_start_date"] != null) && ($_POST["search_end_date"] != null)) {
  		if ($_POST["search_job_no"] != null) {
  			$extra_sql = " AND ";
  		}
  		$extra_sql .= "(theme_timestamp BETWEEN $start_timestamp AND $end_timestamp)";
  	}

  	$searches = $wpdb->get_results("SELECT * FROM $table_name WHERE $job_no_sql $extra_sql ORDER BY theme_timestamp DESC");
  } else {
    $searches = $wpdb->get_results("SELECT * FROM $table_name ORDER BY theme_timestamp DESC LIMIT 10");
  }

  if ($_GET["vc_action"] == "template_diff") {
  
    require_once('source_control_template_diff.php');
    print_version_control_template_diff($_GET["id"]);

  } else if ($_GET["vc_action"] == "current_template_diff") {
  
    require_once('source_control_template_diff.php');  
    $table_name =  $wpdb->prefix . "version_control_templates";
    $my_template = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_GET["id"]) );
    print_current_version_control_template_diff($my_template->file_name,$my_template->orig_file_name);
  
  } else if ($_GET["vc_action"] == "template_view") {
  
    require_once('read_template_version.php');
  	print_version_control_template_file($_GET["id"]);

  } else if ($_GET["vc_action"] == "current_post_diff") {
  
    require_once('source_control_post_diff.php');
  	print_current_version_control_post_diff($_GET["id"]);
	
  } else if ($_GET["vc_action"] == "post_diff") {
  
    require_once('source_control_post_diff.php');
  	print_version_control_post_diff($_GET["id"]);
	
  } else if ($_GET["vc_action"] == "post_view") {
  
    require_once('read_post_version.php');
  	print_version_control_post($_GET["id"]);
	
  } else { ?>
    
    <script language="javascript">
  		jQuery(function() {
  			jQuery("table.data tr:odd").addClass("odd");
  			jQuery("table.data tr:even").addClass("even");

  			jQuery('.datepicker').datepicker({
  			  dateFormat : 'yy-m-d',
  			  showOn: "button",
  				buttonImage: "<?php echo(plugins_url( '/css/images/calendar.gif', __FILE__ )); ?>",
  				buttonImageOnly: true
  			});

  		});

  	</script>

  	<style>
  	.inside th {
  		text-align: left;
  	}
  	.inside table {
  		margin-left: 10px;
  	}
  	tbody tr.even td, tr.odd th {
  		background: #cac9c9;
  	}
  	tbody tr.odd td, tr.odd th {
  		background: #dfdfdf;
  	}
  	.inside table {
  		width: 100%;
  	}
  	</style>
    
    
    
  <h2>WordPress Source Control</h2>
	<div class="postbox " style="display: block; ">
	<div class="inside">
		<form action="" method="post">
			<h3>Search</h3>
		  <table class="form-table">
		  	<tbody>
		  		<tr valign="top">
		  			<th scope="row">
							Job No
						</th>
						<td>
							<input type="text" name="search_job_no" value="<?php echo($_POST['search_job_no']); ?>" />
						</td>
		  		</tr>
		  		<tr valign="top">
		  			<th scope="row">
							Start Date
						</th>
						<td>
							<input type="text" class="datepicker" name="search_start_date" value="<?php echo($_POST['search_start_date']); ?>" />
						</td>
		  		</tr>
		  		<tr valign="top">
		  			<th scope="row">
							End Date
						</th>
						<td>
							<input type="text" class="datepicker" name="search_end_date" value="<?php echo($_POST['search_end_date']); ?>" />
						</td>
		  		</tr>
		  	</tbody>
		  </table>
		  <p class="submit">
			<input type="submit" name="submit" value="Search">
			</p>
	  </form>
	  
	  <?php if ($searches) {
	  		foreach ($searches as $result) { ?>
					<?php $table_name = $wpdb->prefix . "version_control_templates"; ?>
	  			<?php $templates_changed = $wpdb->get_results("SELECT * FROM $table_name WHERE job_id = '".$result->id."' ORDER BY template_timestamp DESC"); ?>
	  			<h4><?php echo(date('l jS \of F Y h:i:s A', $result->theme_timestamp)); ?> UTC &#8211; <?php echo($result->job_no); ?> &#8211; <?php echo($result->description); ?></h4>
	 			  
	 			  <?php if ($templates_changed) { ?>
		 			  <h5>Template Changes</h5>
					  <table class="data">
					  	<thead>
					  		<tr>
					  			<th scope="col">Name</th>
					  			<th></th>
					  			<th></th>
					  		</tr>
					  	</thead>
					  	<tbody>
					  		<?php foreach ($templates_changed as $template_changed) { ?>
					  			<tr>
					  				<td  style='width: 800px;'><?php echo($template_changed->orig_file_name); ?></td>
					  				<?php if (preg_match("/.php$|.js$|.css$/", $template_changed->file_name)) { ?>
						  				<td><a href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=wp-source-control-2/source_control_list.php&vc_action=template_view&id=<?php echo($template_changed->id); ?>">View</a></td>
						  				<td><a href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=wp-source-control-2/source_control_list.php&vc_action=template_diff&id=<?php echo($template_changed->id); ?>">Diff</a></td>
						  			<?php } else { ?>
						  				<td colspan="2"></td>
						  			<?php } ?>
					  			</tr>
					  		<?php } ?>
					  	</tbody>
					  </table>
					<?php } ?>

				  <?php $post_table = $wpdb->prefix . "posts"; ?>
				  <?php $table_name = $wpdb->prefix . "version_control_posts"; ?>
				  <?php $posts_changed = $wpdb->get_results("SELECT * FROM $post_table as p, $table_name as v WHERE p.id = v.revision_id AND v.job_id = '".$result->id."' AND (p.post_type = 'revision') OR (p.post_type IN ('post', 'page') AND p.post_status = 'trash') ORDER BY p.post_modified ASC"); ?>

				  <?php if ($posts_changed) { ?>

					  <h5>Post/Page Changes</h5>
					  <table class="data">
					  	<thead>
					  		<tr>
					  			<th scope="col">Name</th>
					  			<th></th>
					  			<th></th>
					  		</tr>
					  	</thead>
					  	<tbody>
					  		<?php foreach ($posts_changed as $post_changed) { ?>
					  			<tr>
					  				<td style='width: 800px;'><?php echo($post_changed->post_title); ?></td>
					  				<?php if ($post_changed->job_deleted == 0) { ?>

					  					<td><a href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=wp-source-control-2/source_control_list.php&vc_action=post_view&id=<?php echo($post_changed->ID); ?>">View</a></td>
					  					<td><a href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=wp-source-control-2/source_control_list.php&vc_action=post_diff&id=<?php echo($post_changed->ID); ?>">Diff</a></td>
					  					
					  				<?php } else { ?>
					  					<td colspan="2">Deleted</td>
					  				<?php } ?>
					  			</tr>
					  		<?php } ?>
					  	</tbody>
					  </table>
					<?php } ?>

					<?php if (!$posts_changed && !$templates_changed) { ?>
						<h5>Empty commit.</h5>
					<?php } ?>

				  <hr/>

				<?php } ?>
		<?php } else { ?>
			<?php if ($_POST["submit"] == "Search") { ?>
				<h5>Sorry, job no not found.</h5>
			<?php } ?>
		<?php } ?>
	</div>
	</div>
	</div>
<?php } ?>