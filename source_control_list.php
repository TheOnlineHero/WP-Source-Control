<?php

require_once(dirname(__FILE__).'../../../../wp-admin/admin.php'); 

require_once('source_control_path.php'); 

global $wpdb;
$table_name = $wpdb->prefix . "version_controls";
 
wp_enqueue_script('jquery');

if ($_POST["submit"] == "Commit") {
	create_theme_snapshot($_POST["checkin_templates"], $_POST["checkin_post_ids"], $_POST['job_no'], $_POST['description']);
}

if ($_GET["vc_action"] == "template_diff") {
  
  require_once('source_control_template_diff.php');
  print_version_control_template_diff($_GET["id"]);

} else if ($_GET["vc_action"] == "current_template_diff") {
  
  require_once('source_control_template_diff.php');  
  $table_name =  $wpdb->prefix . "version_control_templates";
  $my_template = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_GET["id"]) );
  $base_path = Path::normalize(dirname(__FILE__).'../../../');
  print_current_version_control_template_diff($base_path."/source_control/".$my_template->file_name,$base_path.$my_template->orig_file_name);
  
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

			jQuery("#all_templates").click(function() {
				jQuery("input[name='checkin_templates[]']").attr("checked", jQuery(this).attr("checked") == "checked");
			});

			jQuery("#all_posts").click(function() {
				jQuery("input[name='checkin_post_ids[]']").attr("checked", jQuery(this).attr("checked") == "checked");
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

	<div class="wrap">

	<?php if ($_POST["submit"] == "Commit") { ?>
	<div id="message" class="updated below-h2"><p>Job #<?php echo($_POST['job_no']); ?> has been committed.</p></div>
	<?php } ?>

	<h2>WordPress Source Control</h2>
	<div class="postbox " style="display: block; ">
	<div class="inside">
	<form action="" method="post">
		<h3>Commit Changes</h3>
		<?php $content = get_updated_template_files(Path::normalize(dirname(__FILE__)."../../../themes")); ?>
		<?php if ($content != "") { ?>
			<h5>Templates That Have Recently Been Edited</h5>
			<table class="data">
				<thead>
					<tr>
						<td style="width: 10px"><input type="checkbox" id="all_templates"></td>
						<td style="width: 800px"></td>
						<td></td>
					</tr>
				</thead>
				<tbody>	
						<?php print_updated_template_files(Path::normalize(dirname(__FILE__)."../../../themes")); ?>
				</tbody>
			<table>
		<?php } ?>

		<?php $post_table = $wpdb->prefix . "posts"; ?>
		<?php $version_post_name = $wpdb->prefix . "version_control_posts"; ?>
		<?php $posts_changed = $wpdb->get_results("SELECT DISTINCT(p.post_title), p.ID, p.post_date FROM $post_table as p, $version_post_name as v WHERE p.id = v.revision_id AND (p.post_type = 'revision' AND job_id = '0') OR (p.post_type IN ('post', 'page') AND p.post_status = 'trash' AND job_id = '0') ORDER BY p.post_modified DESC"); ?>

		<?php if ($posts_changed) { ?>
			<h5>Post/Page That Have Recently Been Edited</h5>
			<table class="data">
				<thead>
					<tr>
						<td style="width: 10px"><input type="checkbox" id="all_posts"></td>
						<td style="width: 800px"></td>
						<td></td>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($posts_changed as $post_changed) { ?>
						<tr>
							<td><input type="checkbox" name="checkin_post_ids[]" value="<?php echo($post_changed->ID); ?>"/></td>
							<td style='width: 800px;'><?php echo($post_changed->post_title); ?></td>
							<?php 
								$datetime = strtotime($post_changed->post_date);
								$mysqldate = date("l jS \of F Y h:i:s A", $datetime);
							?>
							<td><?php echo($mysqldate); ?> UTC</td>
							<td><a href="<?php echo(get_option('siteurl')); ?>/wp-admin/admin.php?page=wp-source-control/source_control_list.php&vc_action=current_post_diff&id=<?php echo($post_changed->ID); ?>">Diff</a></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		<?php } ?>
		<?php if (!$posts_changed && !$content) { ?>
			<h5>No files or posts to commit.</h5>
		<?php } ?>

	  <table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="job_no">Job No</label>
					</th>
					<td>
						<input type="text" name="job_no" maxlength="50" value="<?php echo($_POST['job_no']); ?>" >
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="description">Description</label>
					</th>
					<td>
					<textarea name="description" cols="100" rows="6"><?php echo($_POST['description']); ?></textarea>
					</td>
				</tr>

			</tbody>
		</table>
		<p class="submit">
			<input type="submit" name="submit" value="Commit">
		</p>
	</form>
	</div>
	</div>

<?php } ?>