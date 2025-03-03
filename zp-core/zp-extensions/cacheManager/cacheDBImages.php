<?php
/**
 * This template is used to generate cache images. Running it will process the entire gallery,
 * supplying an album name (ex: loadAlbums.php?album=newalbum) will only process the album named.
 * Passing clear=on will purge the designated cache before generating cache images
 * @package plugins/cacheManager
 */
// force UTF-8 Ø
define('OFFSET_PATH', 3);
require_once("../../admin-globals.php");
require_once(CORE_SERVERPATH . 'template-functions.php');
require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/cacheManager/functions.php');

admin_securityChecks(ADMIN_RIGHTS, $return = currentRelativeURL());

XSRFdefender('cacheDBImages');


printAdminHeader('admin', 'DB');
echo "\n</head>";
echo "\n<body>";

printLogoAndLinks();
echo "\n" . '<div id="main">';
printTabs();
echo "\n" . '<div id="content">';

npgFilters::apply('admin_note', 'cache', '');
echo '<h1>' . gettext('Cache images stored in the database') . '</h1>';

$tables = array(
		'albums' => array('desc'),
		'images' => array('desc'),
		'pages' => array('content'),
		'news' => array('content')
);

// "extracontent" is optional
foreach (array('albums', 'images', 'pages', 'news') as $table) {
	$fields = db_list_fields($table);
	if (array_key_exists('extracontent', $fields)) {
		$tables[$table][] = 'extracontent';
	}
	if (array_key_exists('codeblock', $fields)) {
		$tables[$table][] = 'codeblock';
	}
}
?>
<script type="text/javascript">
	$(function () {
		$('img').on("error", function () {
			var link = $(this).attr('src');
			var title = $(this).attr('title');
			$(this).parent().html('<a href="' + link + '&debug" target="_blank" title="' + title + '"><?php echo CROSS_MARK_RED; ?></a>');
		});
	});
</script>

<div class="tabbox">
	<p>
		<?php
		echo gettext('This utility scans the database for images references that have been stored in <em>codeblocks</em><sup>†</sup>, in the album and image <em>description</em> fields, and in the news<sup>†</sup> and pages<sup>†</sup> <em>content</em> and <em>extracontent</em><sup>†</sup> fields.') . ' ';
		echo gettext('If an image processor URI is discovered it will be converted to a cache file URI.') . ' ';
		echo gettext('If the cache file for the image does not exist, a caching image reference will be made for the image.');
		?>
	</p>
	<p>
		<sup>†</sup><?php echo gettext('If these are enabled.'); ?>
	</p>

	<?php
	$refresh = $imageprocessor = $found = $fixed = $fixedFolder = 0;
	XSRFToken('cacheDBImages');
	$watermarks = getWatermarks();
	$missingImages = NULL;
	foreach ($tables as $table => $fields) {
		@set_time_limit(200);
		foreach ($fields as $field) {
			$sql = 'SELECT * FROM ' . prefix($table) . ' WHERE `' . $field . '` REGEXP "<img.*src\s*=\s*\".*i.php((\\.|[^\"])*)"';
			$result = query($sql);
			if ($result) {
				while ($row = db_fetch_assoc($result)) {
					$update = false;
					preg_match_all('|\<\s*img.*\ssrc\s*=\s*"(.*i\.php\?.*)\"|U', npgFunctions::unTagURLs($row[$field]), $matches);
					foreach ($matches[1] as $uri) {
						$imageprocessor++;
						$params = mb_parse_url(html_decode($uri));
						if (array_key_exists('query', $params)) {
							parse_str($params['query'], $query);
							if (!file_exists(getAlbumFolder() . $query['a'] . '/' . $query['i'])) {
								recordMissing($table, $row, $query['a'] . '/' . $query['i']);
							} else {
								$update = true;

								if (strpos($uri, 'i.php') !== false) {
									$url = '<span><img src="' . npgFunctions::updateImageProcessorLink($uri) . '" height="20" width="20" alt="X" /></span>';
									$title = getTitle($table, $row) . ' ' . gettext('image processor reference');
									?>
									<a href="<?php echo $uri; ?>&amp;debug" title="<?php echo $title; ?>">
										<?php echo $url . "\n"; ?>
									</a>
									<?php
								}
							}
						}
					}
					if ($update) {
						$text = npgFunctions::updateImageProcessorLink($row[$field], true);
						if ($text != $row[$field]) {
							$sql = 'UPDATE ' . prefix($table) . ' SET `' . $field . '`=' . db_quote($text) . ' WHERE `id`=' . $row['id'];
							query($sql);
						} else {
							$refresh++;
						}
					}
				}
			}

			$sql = 'SELECT * FROM ' . prefix($table) . ' WHERE `' . $field . '` REGEXP "<img.*src\s*=\s*\".*' . CACHEFOLDER . '((\\.|[^\"])*)"';
			$result = query($sql);
			if ($result) {
				while ($row = db_fetch_assoc($result)) {
					$updated = false;
					preg_match_all('~\<\s*img.*\ssrc\s*=\s*"(.*)".*/\>~U', $row[$field], $matches);
					foreach ($matches[1] as $key => $match) {
						if (preg_match('~/' . CACHEFOLDER . '/~', $match)) {
							$match = urldecode($match);
							$found++;
							list($image_uri, $args) = getImageProcessorURIFromCacheName($match, $watermarks);
							if (preg_match('~(.*)/' . CORE_FOLDER . '_images_(.*)\.(.*)$~i', $image_uri, $matches)) {
								$cachefile = SERVERPATH . '/' . CACHEFOLDER . getImageCacheFilename($matches[1], CORE_FOLDER . '_images_' . $matches[2] . '.' . $matches[3], $args);
								if (!file_exists($cachefile)) {
									$uri = getImageProcessorURI($args, $matches[1], CORE_FOLDER . '_images_' . $matches[2] . '.' . $matches[3]) . '&z=' . CORE_FOLDER . '/images/' . $matches[2] . '.png';
									$fixed++;
									$title = getTitle($table, $row);
									?>
									<a href="<?php echo html_encode($uri); ?>&amp;debug" title="<?php echo $title; ?>">
										<?php
										echo '<img class="iplink" src="' . html_encode($uri) . '&returncheckmark" height="16" width="16" alt="x" />' . "\n";
										?>
									</a>
									<?php
								}
								continue;
							}
							$try = $_supported_images;
							$base = stripSuffix($image = $image_uri);
							$prime = getSuffix($image);
							array_unshift($try, $prime);
							$try = array_unique($try);
							$missing = true;
							//see if we can match the cache name to an image in the album.
							//Note that the cache suffix may not match the image suffix
							foreach ($try as $suffix) {
								if (file_exists(getAlbumFolder() . $base . '.' . $suffix)) {
									$missing = false;
									$image = $base . '.' . $suffix;
									$uri = getImageURI($args, dirname($image), basename($image), NULL);
									if (strpos($uri, 'i.php?') !== false) {
										$fixed++;
										$title = getTitle($table, $row);
										?>
										<a href="<?php echo html_encode($uri); ?>&amp;debug" title="<?php echo $title; ?>">
											<?php
											echo '<img class="iplink" src="' . html_encode($uri) . '&returncheckmark" height="16" width="16" alt="x" />' . "\n";
											?>
										</a>
										<?php
									}
									break;
								}
							}
							if ($missing) {
								recordMissing($table, $row, $image_uri);
							}
							$cache_file = '{*WEBPATH*}/' . CACHEFOLDER . getImageCacheFilename(dirname($image), basename($image), $args);
							if ($match != $cache_file) {
								//need to update the record.
								$row[$field] = updateCacheName($row[$field], $match, $cache_file);
								$updated = true;
							}
						}
					}
					if ($updated) {
						$sql = 'UPDATE ' . prefix($table) . ' SET `' . $field . '`=' . db_quote($row[$field]) . ' WHERE `id`=' . $row['id'];
						query($sql);
					}
				}
			}
		}
	}
	if (!empty($missingImages)) {
		?>
		<div class="errorbox">
			<p>
				<?php
				echo gettext('<strong>Note:</strong> the following objects have images that appear to no longer exist.');
				?>
			</p>
			<?php
			foreach ($missingImages as $missing) {
				echo $missing;
			}
			?>
		</div>
		<?php
	}
	?>
	<p>
		<?php
		printf(ngettext('%u image processor reference found.', '%u image processor references found.', $imageprocessor), $imageprocessor);
		if ($refresh) {
			echo ' ' . gettext('You should use the refresh button to convert these to cached image references');
		}
		?>
		<br />
		<?php
		printf(ngettext('%u cached image reference found.', '%u cached image references found.', $found), $found);
		?>
		<br />
		<?php
		printf(ngettext('%s reference re-cached.', '%s references re-cached.', $fixed), $fixed);
		?>
		<br />
		<?php
		if ($fixedFolder) {
			printf(ngettext('%s cache folder reference fixed.', '%s cache folder references fixed.', $fixedFolder), $fixedFolder);
		}
		?>
	</p>


	<p class="buttons">
		<button class="tooltip" type="button" title="<?php echo gettext('Refresh the caching of the images stored in the database if some images did not render.'); ?>" onclick="location.reload();" >
			<?php echo CURVED_UPWARDS_AND_RIGHTWARDS_ARROW_BLUE; ?>
			<?php echo gettext("Refresh"); ?>
		</button>
	</p>

	<br class="clearall">

	<?php
	echo "\n" . '</div>';
	echo "\n" . '</div>';
	printAdminFooter();
	echo "\n" . '</div>';
	echo "\n</body>";
	?>
