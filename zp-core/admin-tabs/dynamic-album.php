<?php
/**
 * This script is used to create dynamic albums from a search.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package core
 */
// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(dirname(__FILE__)) . '/admin-globals.php');
require_once(CORE_SERVERPATH . 'template-functions.php');

admin_securityChecks(ALBUM_RIGHTS, $return = currentRelativeURL());

$imagelist = array();

function getSubalbumImages($folder) {
	global $imagelist, $_gallery;
	$album = newAlbum($folder);
	if ($album->isDynamic()) {
			return;
	}
	$images = $album->getImages();
	foreach ($images as $image) {
		$imagelist[] = '/' . $folder . '/' . $image;
	}
	$albums = $album->getAlbums();
	foreach ($albums as $folder) {
		getSubalbumImages($folder);
	}
}

$search = new SearchEngine(true);
if (isset($_GET['action']) && $_GET['action'] == 'savealbum') {
	XSRFdefender('savealbum');
	$msg = gettext("Failed to save the album file");
	$_GET['name'] = $albumname = sanitize($_POST['album']);

	if (trim($_POST['words'])) {
		if ($album = sanitize($_POST['albumselect'])) {
			$albumobj = newAlbum($album);
			$allow = $albumobj->isMyItem(ALBUM_RIGHTS);
		} else {
			$allow = npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS);
		}
		$allow = npgFilters::apply('admin_managed_albums_access', $allow, $return);

		if ($allow) {
			if ($_POST['create_tagged'] == 'static') {
				//	create the tag
				$words = sanitize($_POST['album_tag']);
				$success = query('INSERT INTO ' . prefix('tags') . ' (`name`,`private`) VALUES (' . db_quote($words) . ', "1")', false);

				$unpublished = isset($_POST['return_unpublished']);
				$_POST['return_unpublished'] = true; //	state is frozen at this point, so unpublishing should not impact

				$searchfields[] = 'tags_exact';
				// now tag each element
				if (isset($_POST['return_albums'])) {
					$subalbums = $search->getAlbums(0);
					foreach ($subalbums as $analbum) {
						$albumobj = newAlbum($analbum);
						if ($unpublished || $albumobj->getShow()) {
							$tags = array_unique(array_merge($albumobj->getTags(false), array($words)));
							$albumobj->setTags($tags);
							$albumobj->save();
						}
					}
				}
				if (isset($_POST['return_images'])) {
					$images = $search->getImages();
					foreach ($images as $animage) {
						$image = newImage(newAlbum($animage['folder']), $animage['filename']);
						if ($unpublished || $image->getShow()) {
							$tags = array_unique(array_merge($image->getTags(false), array($words)));
							$image->setTags($tags);
							$image->save();
						}
					}
				}
			} else {
				$searchfields = array();
				foreach ($_POST as $key => $v) {
					if (strpos($key, 'SEARCH_') === 0) {
						$searchfields[] = $v;
					}
				}
				$criteria = explode('::', sanitize($_POST['words']));
				$words = @$criteria[0];
			}
			if (isset($_POST['thumb'])) {
				$thumb = sanitize($_POST['thumb']);
			} else {
				$thumb = '';
			}
			$inalbums = (int) (isset($_POST['return_albums']));
			$inAlbumlist = sanitize($_POST['albumlist']);
			if ($inAlbumlist) {
				$inalbums .= ':' . $inAlbumlist;
			}

			$constraints = "\nCONSTRAINTS=" . 'inalbums=' . $inalbums . '&inimages=' . ((int) (isset($_POST['return_images']))) . '&unpublished=' . ((int) (isset($_POST['return_unpublished'])));
			$redirect = $album . '/' . $albumname . '.alb';

			if (!empty($albumname)) {
				$f = fopen(internalToFilesystem(ALBUM_FOLDER_SERVERPATH . $redirect), 'w');
				if ($f !== false) {
					fwrite($f, "WORDS=$words\nTHUMB=$thumb\nFIELDS=" . implode(',', $searchfields) . $constraints . "\n");
					fclose($f);
					clearstatcache();
					// redirct to edit of this album
					header("Location: " . getAdminLink('admin-tabs/edit.php') . '?page=edit&album=' . pathurlencode($redirect));
					exit();
				}
			}
		} else {
			$msg = gettext("You do not have edit rights on this album.");
		}
	} else {
		$msg = gettext('Your search criteria is empty.');
	}
}

$_GET['page'] = 'edit'; // pretend to be the edit page.
printAdminHeader('edit', gettext('dynamic'));
echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
echo "\n" . '<div id="main">';
printTabs();
echo "\n" . '<div id="content">';
npgFilters::apply('admin_note', 'albums', 'dynamic');
echo "<h1>" . gettext("Create Dynamic Album") . "</h1>\n";
?>
<div class="tabbox">
	<?php
	if (isset($_POST['savealbum'])) { // we fell through, some kind of error
		echo "<div class=\"errorbox space\">";
		echo "<h2>" . $msg . "</h2>";
		echo "</div>\n";
	}

	$albumlist = array();
	genAlbumList($albumlist);
	$fields = $search->fieldList;
	$words = $search->codifySearchString();
	$inAlbumlist = $search->getAlbumList();

	if (empty($inAlbumlist)) {
		$inalbums = '';
	} else {
		$inalbums = implode(',', $inAlbumlist);
	}

	if (isset($_GET['name'])) {
		$albumname = sanitize($_GET['name']);
	} else {
		$albumname = seoFriendly(sanitize_path($words));
		$old = '';
		while ($old != $albumname) {
			$old = $albumname;
			$albumname = str_replace('--', '-', $albumname);
		}
	}

	$images = $search->getImages(0);
	foreach ($images as $image) {
		$folder = $image['folder'];
		$filename = $image['filename'];
		$imagelist[] = '/' . $folder . '/' . $filename;
	}
	$subalbums = $search->getAlbums(0);
	foreach ($subalbums as $folder) {
		getSubalbumImages($folder);
	}
	?>
	<form class="dirtylistening" onReset="setClean('savealbun_form');" id="savealbun_form" action="?action=savealbum" method="post" autocomplete="off" >
		<?php XSRFToken('savealbum'); ?>
		<input type="hidden" name="savealbum" value="yes" />
		<table>
			<tr>
				<td><?php echo gettext("Album name:"); ?></td>
				<td>
					<input type="text" size="40" name="album" value="<?php echo html_encode($albumname) ?>" />
				</td>
			</tr>
			<tr>
				<td><?php echo gettext("Create in:"); ?></td>
				<td>
					<select id="albumselectmenu" name="albumselect">
						<?php
						if (accessAllAlbums(UPLOAD_RIGHTS)) {
							?>
							<option value="" style="font-weight: bold;">/</option>
							<?php
						}
						$parentalbum = sanitize(@$_GET['folder']);
						$bglevels = array('#fff', '#f8f8f8', '#efefef', '#e8e8e8', '#dfdfdf', '#d8d8d8', '#cfcfcf', '#c8c8c8');
						foreach ($albumlist as $fullfolder => $albumtitle) {
							$singlefolder = $fullfolder;
							$saprefix = "";
							$salevel = 0;
							// Get rid of the slashes in the subalbum, while also making a subalbum prefix for the menu.
							while (strstr($singlefolder, '/') !== false) {
								$singlefolder = substr(strstr($singlefolder, '/'), 1);
								$saprefix = "&nbsp; &nbsp;&raquo;&nbsp;" . $saprefix;
								$salevel++;
							}
							$selected = '';
							if ($parentalbum == $fullfolder) {
								$selected = ' selected="selected"';
							}
							echo '<option value="' . $fullfolder . '"' . $selected . '>' . $saprefix . $singlefolder . ' (' . $albumtitle . ')' . '</option>\n';
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td><?php echo gettext("Thumbnail:"); ?></td>
				<td>
					<select id="thumb" name="thumb">
						<?php
						$selections = array();
						foreach ($_albumthumb_selector as $key => $selection) {
							$selections[$selection['desc']] = $key;
						}
						generateListFromArray(array(getOption('AlbumThumbSelect')), $selections, false, true);
						$showThumb = $_gallery->getThumbSelectImages();
						foreach ($imagelist as $imagepath) {
							$pieces = explode('/', $imagepath);
							$filename = array_pop($pieces);
							$folder = implode('/', $pieces);
							$albumx = newAlbum($folder);
							$image = newImage($albumx, $filename);
							if (isImagePhoto($image) || !is_null($image->objectsThumb)) {
								echo "\n<option class=\"thumboption\"";
								if ($showThumb) {
									echo " style=\"background-image: url(" . html_encode($image->getSizedImage(ADMIN_THUMB_MEDIUM)) .
									"); background-repeat: no-repeat;\"";
								}
								echo " value=\"" . $imagepath . "\"";
								echo ">" . $image->getTitle();
								echo " ($imagepath)";
								echo "</option>";
							}
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td><?php echo gettext("Search criteria:"); ?></td>
				<td>
					<input type="text" size="60" name="words" value="<?php echo html_encode($words); ?>" />
					<label>
						<input type="checkbox" name="return_albums" value="1"<?php if (!getOption('search_no_albums')) {
	echo ' checked="checked"' ?> />
						<?php echo gettext('Return albums found') ?>
					</label>
					<label>
						<input type="checkbox" name="return_images" value="1"<?php if (!getOption('search_no_images')) echo ' checked="checked"' ?> />
						<?php echo gettext('Return images found') ?>
					</label>
					<label>
						<input type="checkbox" name="return_unpublished" value="1" />
						<?php echo gettext('Return unpublished items') ?>
					</label>
				</td>
			</tr>
			<tr>
				<td></td>
				<td>
					<?php echo gettext('within');
}
?>
					<input type="text" size="60" name="albumlist" value="<?php echo html_encode($inalbums); ?>" />
				</td>
			</tr>

			<script type="text/javascript">
				// <!-- <![CDATA[
				function setTagged(state) {
					if (state) {
						$('#album_tag').prop('disabled', false);
						$('.searchchecklist').prop('disabled', true);
					} else {
						$('.searchchecklist').prop('disabled', false);
						$('#album_tag').prop('disabled', true);
					}
				}
				// ]]> -->
			</script>

			<tr>
				<td>
					<label>
						<input type="radio" name="create_tagged" value="dynamic" onchange="setTagged(false)" checked="checked" />
						<?php echo gettext('dynamic'); ?>
					</label>
					<label>
						<input type="radio" name="create_tagged" value="static" onchange="setTagged(true)"/>
						<?php echo gettext('tagged'); ?>
					</label>
				</td>
				<td>
				</td>
			</tr>
			<tr>
				<td><?php echo gettext('Album <em>Tag</em>'); ?></td>
				<td>
					<input type="text" size="40" name="album_tag" id="album_tag" value="<?php echo html_encode($albumname) . '.' . time(); ?>" disabled="disabled" />
					<?php echo gettext('Select <em>tagged</em> to tag the search results with this <em>tag</em> and use as the album criteria.'); ?>
				</td>
			</tr>
			<tr>
				<td><?php echo gettext("Search fields:"); ?></td>
				<td>
					<?php
					echo '<ul class="searchchecklist">' . "\n";
					$selected_fields = array();
					$engine = new SearchEngine(true);
					$available_fields = $engine->allowedSearchFields();
					if (count($fields) == 0) {
						$selected_fields = $available_fields;
					} else {
						foreach ($available_fields as $display => $key) {
							if (in_array($key, $fields)) {
								$selected_fields[$display] = $key;
							}
						}
					}
					generateUnorderedListFromArray($selected_fields, $available_fields, 'SEARCH_', false, true, true, true);
					echo '</ul>';
					?>
				</td>
			</tr>

		</table>

		<?php
		if (empty($albumlist)) {
			?>
			<p class="errorbox">
				<?php echo gettext('There is no place you are allowed to put this album.'); ?>
			</p>
			<p>
				<?php echo gettext('You must have <em>upload</em> rights to at least one album to have a place to store this album.'); ?>
			</p>
			<?php
		} else {
			?>
			<input type="submit" value="<?php echo gettext('Create the album'); ?>" class="button" />
			<?php
		}
		?></form>
</div>
<?php
echo "\n" . '</div>';
printAdminFooter();
echo "\n" . '</div>';
echo "\n</body>";
echo "\n</html>";
?>

