<?php
/**
 * used in sorting the images within and album
 * @package admin
 *
 */
// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(dirname(__FILE__)) . '/admin-globals.php');

if (isset($_REQUEST['album'])) {
	$localrights = ALBUM_RIGHTS;
} else {
	$localrights = NULL;
}
admin_securityChecks($localrights, $return = currentRelativeURL());

if (isset($_GET['album'])) {
	$folder = sanitize($_GET['album']);
	$album = newAlbum($folder);
	if (!$album->isMyItem(ALBUM_RIGHTS)) {
		if (!npgFilters::apply('admin_managed_albums_access', false, $return)) {
			header('Location: ' . getAdminLink('admin.php'));
			exit();
		}
	}
	if (isset($_GET['saved'])) {
		XSRFdefender('save_sort');
		if (isset($_POST['ids'])) { //	process bulk actions, not individual image actions.
			$action = processImageBulkActions($album);
			if (!empty($action)) {
							$_GET['bulkmessage'] = $action;
			}
		}
		parse_str($_POST['sortableList'], $inputArray);
		if (isset($inputArray['id'])) {
			$orderArray = $inputArray['id'];
			if (!empty($orderArray)) {
				foreach ($orderArray as $key => $id) {
					$sql = 'UPDATE ' . prefix('images') . ' SET `sort_order`=' . db_quote(sprintf('%03u', $key)) . ' WHERE `id`=' . sanitize_numeric($id);
					query($sql);
				}
				$album->setSortType("manual");
				$album->setSortDirection(false, 'image');
				$album->save();
				$_GET['saved'] = 1;
			}
		}
	}
} else {
	$album = $_missing_album;
}

// Print the admin header
setAlbumSubtabs($album);
printAdminHeader('edit', 'sort');
?>
<script type="text/javascript">
	//<!-- <![CDATA[
	$(function () {
		$('#images').sortable({
			change: function (event, ui) {
				$('#sortableListForm').addClass('dirty');
			}
		});
	});
	function postSort(form) {
		$('#sortableList').val($('#images').sortable('serialize'));
		form.submit();
	}
	function cancelSort() {
		$('#images').sortable('cancel');
	}
	// ]]> -->
</script>
<?php
echo "\n</head>";
?>


<body>

	<?php
	$checkarray_images = array(
			gettext('*Bulk actions*') => 'noaction',
			gettext('Delete') => 'deleteall',
			gettext('Set to published') => 'showall',
			gettext('Set to unpublished') => 'hideall',
			gettext('Disable comments') => 'commentsoff',
			gettext('Enable comments') => 'commentson'
	);
	if (extensionEnabled('hitcounter')) {
		$checkarray_images[gettext('Reset hitcounter')] = 'resethitcounter';
	}
	$checkarray_images = npgFilters::apply('bulk_image_actions', $checkarray_images);

	// Layout the page
	printLogoAndLinks();
	?>

	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<?php
			if ($album->getParent()) {
				$link = getAlbumBreadcrumbAdmin($album);
			} else {
				$link = '';
			}
			$alb = removeParentAlbumNames($album);

			npgFilters::apply('admin_note', 'albums', 'sort');
			?>
			<h1><?php printf(gettext('Edit Album: <em>%1$s%2$s</em>'), $link, $alb); ?></h1>
			<?php
			$images = $album->getImages();
			$subtab = getCurrentTab();

			$parent = dirname($album->name);
			if ($parent == '/' || $parent == '.' || empty($parent)) {
				$parent = '';
			} else {
				$parent = '&amp;album=' . $parent . '&amp;tab=subalbuminfo';
			}
			?>
			<div id="container">

				<div class="tabbox">
					<?php
					if (isset($_GET['saved'])) {
						if (sanitize_numeric($_GET['saved'])) {
							?>
							<div class="messagebox fade-message">
								<h2><?php echo gettext("Image order saved"); ?></h2>
							</div>
							<?php
						} else {
							if (isset($_GET['bulkmessage'])) {
								$action = sanitize($_GET['bulkmessage']);
								switch ($action) {
									case 'deleteall':
										$messagebox = gettext('Selected items deleted');
										break;
									case 'showall':
										$messagebox = gettext('Selected items published');
										break;
									case 'hideall':
										$messagebox = gettext('Selected items unpublished');
										break;
									case 'commentson':
										$messagebox = gettext('Comments enabled for selected items');
										break;
									case 'commentsoff':
										$messagebox = gettext('Comments disabled for selected items');
										break;
									case 'resethitcounter':
										$messagebox = gettext('Hitcounter for selected items');
										break;
									case 'addtags':
										$messagebox = gettext('Tags added for selected items');
										break;
									case 'cleartags':
										$messagebox = gettext('Tags cleared for selected items');
										break;
									case 'alltags':
										$messagebox = gettext('Tags added for images of selected items');
										break;
									case 'clearalltags':
										$messagebox = gettext('Tags cleared for images of selected items');
										break;
									default:
										$messagebox = $action;
										break;
								}
							} else {
								$messagebox = gettext("Nothing changed");
							}
							?>
							<div class="messagebox fade-message">
								<h2><?php echo $messagebox; ?></h2>
							</div>
							<?php
						}
					}
					?>
					<form class="dirtylistening" onReset="setClean('sortableListForm');
							cancelSort();" action="?page=edit&amp;album=<?php echo $album->getFileName(); ?>&amp;saved&amp;tab=sort" method="post" name="sortableListForm" id="sortableListForm" >
								<?php XSRFToken('save_sort'); ?>
								<?php printBulkActions($checkarray_images, true); ?>

						<p class="buttons">
							<a href="<?php echo getAdminLink('admin-tabs/edit.php') . '?page=edit' . $parent; ?>">
								<?php echo BACK_ARROW_BLUE; ?>
								<strong><?php echo gettext("Back"); ?></strong>
							</a>
							<button type="submit" onclick="postSort(this.form);" >
								<?php echo CHECKMARK_GREEN; ?>
								<strong><?php echo gettext("Apply"); ?></strong>
							</button>
							<button type="reset">
								<?php echo CROSS_MARK_RED; ?>
								<strong><?php echo gettext("Reset"); ?></strong>
							</button>
							<a href="<?php echo WEBPATH . "/index.php?album=" . pathurlencode($album->getFileName()); ?>">
								<?php echo BULLSEYE_BLUE; ?>
								<strong><?php echo gettext('View Album'); ?></strong>
							</a>
						</p>
						<br class="clearall">
						<p><?php echo gettext("Set the image order by dragging them to the positions you desire."); ?></p>
						<ul id="images">
							<?php
							$images = $album->getImages();
							foreach ($images as $imagename) {
								$image = newImage($album, $imagename);
								if ($image->exists) {
									?>
									<li id="id_<?php echo $image->getID(); ?>">
										<div  class="images_publishstatus">
											<?php
											if (!$image->getShow()) {
												$publishstatus_text = gettext('Unpublished');
												$publishstatus_icon = '/images/action.png';
												?>
												<img src="<?php echo WEBPATH . '/' . CORE_FOLDER . $publishstatus_icon; ?>" alt="<?php echo $publishstatus_text; ?>" title="<?php echo $publishstatus_text; ?>">
												<?php
											}
											?>
										</div>
										<img class="imagethumb"
												 src="<?php echo getAdminThumb($image, 'large'); ?>"
												 alt="<?php echo html_encode($image->getTitle()); ?>"
												 title="<?php
												 echo html_encode($image->getTitle()) . ' (' . html_encode($album->name) . ')';
												 ?>"
												 width="<?php echo ADMIN_THUMB_LARGE; ?>" height="<?php echo ADMIN_THUMB_LARGE; ?>"  />
										<p>
											<input type="checkbox" name="ids[]" value="<?php echo $imagename; ?>">
											<a href="<?php echo getAdminLink('admin-tabs/edit.php'); ?>?page=edit&amp;album=<?php echo pathurlencode($album->name); ?>&amp;image=<?php echo urlencode($imagename); ?>&amp;tab=imageinfo#IT" title="<?php echo gettext('edit'); ?>">
												<?php echo PENCIL_ICON; ?>
											</a>
											<?php
											if (isImagePhoto($image)) {
												?>
												<a href="<?php echo html_encode($image->getFullImageURL()); ?>" class="colorbox" title="zoom">
													<img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/magnify.png" alt="">
												</a>
												<?php
											}
											linkPickerIcon($image);
											?>
										</p>
										</label>
									</li>
									<?php
								}
							}
							?>
						</ul>
						<br class="clearall">

						<div>
							<input type="hidden" id="sortableList" name="sortableList" value="" />
							<p class="buttons">
								<a href="<?php echo getAdminLink('admin-tabs/edit.php') . '?page=edit' . $parent; ?>">
									<?php echo BACK_ARROW_BLUE; ?>
									<strong><?php echo gettext("Back"); ?></strong>
								</a>
								<button type="submit" onclick="postSort(this.form);" >
									<?php echo CHECKMARK_GREEN; ?>
									<strong><?php echo gettext("Apply"); ?></strong>
								</button>
								<button type="reset">
									<?php echo CROSS_MARK_RED; ?>
									<strong><?php echo gettext("Reset"); ?></strong>
								</button>
								<a href="<?php echo WEBPATH . "/index.php?album=" . pathurlencode($album->getFileName()); ?>">
									<?php echo BULLSEYE_BLUE; ?>
									<strong><?php echo gettext('View Album'); ?></strong>
								</a>
							</p>
						</div>
					</form>
					<br class="clearall">
				</div>
			</div>
		</div>
		<?php
		printAdminFooter();
		?>
	</div>
</body>

<?php
echo "\n</html>";
?>
