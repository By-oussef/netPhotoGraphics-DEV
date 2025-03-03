<?php

/**
 * @package plugins/uploader_http
 */
function upload_head() {
	$myfolder = CORE_SERVERPATH . PLUGIN_FOLDER . '/uploader_http';
	scriptLoader($myfolder . '/httpupload.css');
	scriptLoader($myfolder . '/httpupload.js');
	return getAdminLink(PLUGIN_FOLDER . '/uploader_http/uploader.php');
}

function upload_extra($uploadlimit, $passedalbum) {

}

function upload_form($uploadlimit, $passedalbum) {
	global $_current_admin_obj;

	XSRFToken('upload');
	?>
	<script type="text/javascript">
		// <!-- <![CDATA[
		window.totalinputs = 5;
		function addUploadBoxes(placeholderid, copyfromid, num) {
			for (i = 0; i < num; i++) {
				jQuery('#' + copyfromid).clone().insertBefore('#' + placeholderid);
				window.totalinputs++;
				if (window.totalinputs >= 50) {
					jQuery('#addUploadBoxes').toggle('slow');
					return;
				}
			}
		}
		function resetBoxes() {
			window.totalinputs = 5;
			$('#uploadboxes').html('<div id="place" style="display: none;"></div>');
			addUploadBoxes('place', 'filetemplate', 5);
		}
		// ]]> -->
	</script>

	<input type="hidden" name="existingfolder" id="existingfolder" value="false" />
	<input type="hidden" name="auth" id="auth" value="<?php echo $_current_admin_obj->getPass(); ?>" />
	<input type="hidden" name="id" id="id" value="<?php echo $_current_admin_obj->getID(); ?>" />
	<input type="hidden" name="processed" id="processed" value="1" />
	<input type="hidden" name="folder" id="folderslot" value="<?php echo html_encode($passedalbum); ?>" />
	<input type="hidden" name="albumtitle" id="albumtitleslot" value="" />
	<input type="hidden" name="publishalbum" id="publishalbumslot" value="" />
	<div id="uploadboxes">
		<div class="fileuploadbox"><input type="file" size="40" name="files[]" /></div>
		<div class="fileuploadbox"><input type="file" size="40" name="files[]" /></div>
		<div class="fileuploadbox"><input type="file" size="40" name="files[]" /></div>
		<div class="fileuploadbox"><input type="file" size="40" name="files[]" /></div>
		<div class="fileuploadbox"><input type="file" size="40" name="files[]" /></div>

		<div id="place" style="display: none;"></div>
		<!-- New boxes get inserted before this -->

	</div>
	<div style="display:none">
		<!-- This is the template that others are copied from -->
		<div class="fileuploadbox" id="filetemplate" ><input type="file" size="40" name="files[]" value="x" /></div>
	</div>
	<p id="addUploadBoxes"><a href="javascript:addUploadBoxes('place','filetemplate',5)" title="<?php echo gettext("Does not reload!"); ?>">+ <?php echo gettext("Add more upload boxes"); ?></a> <small>
			<?php echo gettext("(will not reload the page, but remember your upload limits!)"); ?></small></p>

	<p class="fileUploadActions" class="buttons" style="display: none;">
		<button type="submit" value="<?php echo gettext('Upload'); ?>" onclick="$('#folderslot').val($('#folderdisplay').val());" class="button">
			<?php echo CHECKMARK_GREEN; ?>
			<?php echo gettext('Upload'); ?>
		</button>
		<button type="button" value="<?php echo gettext('Cancel'); ?>" onclick="resetBoxes();" class="button">
			<?php echo CHECKMARK_GREEN; ?>
			<?php echo gettext('Cancel'); ?>
		</button>
	</p>


	<?php
}
?>
