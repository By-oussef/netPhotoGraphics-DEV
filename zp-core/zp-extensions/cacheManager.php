<?php
/**
 *
 * This plugin is the centralized Cache manager for netPhotoGraphics.

  It provides:
 * <ul>
 * 		<li>Options to purge the HTML and RSS caches on publish state changes of:
 * 			<ul>
 * 				<li>albums</li>
 * 				<li>images</li>
 * 				<li>news articles</li>
 * 				<li>pages</li>
 * 			</ul>
 * 		</li>
 * 		<li><i>pre-creating</i> the Image cache images</li>
 * 		<li>utilities for purging Image, HTML, and RSS caches</li>
 * </ul>
 *
 * Image cache <i>pre-creating</i> will examine the gallery and make image references to any images which have not
 * already been cached. Your browser will then request these images causing the caching process to be
 * executed.
 *
 * The <i>distributed</i> themes have created <i>Caching</i> size options
 * for the images sizes they use.
 *
 *
 * <b>Notes:</b>
 * <ol>
 * 		<li>
 * 			Setting theme options or installing a new version of the software will re-create these caching sizes.
 * 			Use a different <i>theme name</i> for custom versions that you create. If you set image options that
 * 			impact the default caching you will need to re-create these caching sizes by one of the above methods.
 * 		</li>
 * 		<li>
 * 			The <i>pre-creating</i> process will cause your browser to display each and every image that has not
 * 			been previously cached. If your server does not do a good job of thread management this may swamp
 * 			it! You should probably also clear your browser cache before using this utility. Otherwise
 * 			your browser may fetch the images locally rendering the above process useless.
 * 		</li>
 * 		<li>
 * 			You may have to refresh the page multiple times until the report of the number of images cached is zero.
 * 			If some images seem to never be rendered you may be experiencing memory limit or other graphics processor
 * 			errors. You can click on the image that does not render to get the <var>i.php</var> debug screen for the
 * 			image. This may help in figuring out what has gone wrong.
 * 		</li>
 * 		<li>
 * 			Caching sizes shown on the <var>cache images</var> tab will be identified
 * 			with the same post-fixes as the image names in your cache folders. Some examples
 * 			are shown below:
 * 			<ol>
 * 					<li>
 * 					<var>_s595</var>: sized to 595 pixels
 * 				</li>
 * 				<li>
 * 					<var>_w180_cw180_ch80_thumb</var>: a size of 180px wide and 80px high
 * 							and it is a thumbnail (<var>thumb</var>)
 * 				</li>
 * 				<li>
 * 					<var>_s85_cw72_ch72_thumb_copyright_gray</var>: sized 85px cropped at about
 * 							7.6% (one half of 72/85) from the horizontal and vertical sides with a
 * 							watermark (<var>copyright</var>) and rendered in grayscale (<var>gray</var>)
 * 				</li>
 * 				<li>
 * 					<var>_w85_h85_cw350_ch350_cx43_cy169_thumb_copyright</var>: a custom cropped 85px
 * 						thumbnail with watermark.
 * 				</li>
 * 			</ol>
 *
 * 			If a field is not represented in the cache size, it is not applied.
 *
 * 			Custom crops (those with cx and cy)
 * 			really cannot be cached easily since each image has unique values. See the
 * 			<i>template-functions</i>::<var>getCustomImageURL()</var> comment block
 * 			for details on these fields.
 * 		</li>
 * </ol>
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/cacheManager
 * @pluginCategory admin
 */
$plugin_is_filter = defaultExtension(5 | ADMIN_PLUGIN);
$plugin_description = gettext("Provides cache management utilities for Image, HTML, and RSS caches.");

$option_interface = 'cacheManager';

require_once(CORE_SERVERPATH . 'class-feed.php');

npgFilters::register('admin_utilities_buttons', 'cacheManager::buttons');
npgFilters::register('admin_tabs', 'cacheManager::admin_tabs', -300);
npgFilters::register('edit_album_utilities', 'cacheManager::albumbutton', -9999);
npgFilters::register('show_change', 'cacheManager::published');

$_cached_feeds = array('RSS'); //	Add to this array any feed classes that need cache clearing

class cacheManagerFeed extends feed {

//fake feed descendent class so we can use the feed::clearCache()

	protected $feed = NULL;

	function __construct($feed) {
		$this->feed = $feed;
	}

}

/**
 *
 * Standard options interface
 * @author Stephen
 *
 */
class cacheManager {

	function __construct() {
		if (OFFSET_PATH == 2) {
			query('DELETE FROM ' . prefix('plugin_storage') . ' WHERE `type`="cacheManager" AND `subtype`!="_custom_"');
			self::addCacheSize('admin', ADMIN_THUMB_LARGE, NULL, NULL, ADMIN_THUMB_LARGE, ADMIN_THUMB_LARGE, NULL, NULL, -1);
			self::addCacheSize('admin', ADMIN_THUMB_MEDIUM, NULL, NULL, ADMIN_THUMB_MEDIUM, ADMIN_THUMB_MEDIUM, NULL, NULL, -1);
			self::addCacheSize('admin', ADMIN_THUMB_SMALL, NULL, NULL, ADMIN_THUMB_SMALL, ADMIN_THUMB_SMALL, NULL, NULL, -1);
		}
	}

	/**
	 *
	 * supported options
	 */
	function getOptionsSupported() {
		$options = array(gettext('Image caching sizes') => array('key' => 'cropImage_list', 'type' => OPTION_TYPE_CUSTOM,
						'order' => 1,
						'desc' => '<p>' .
						gettext('Cropped images will be made in these parameters if the <em>Create image</em> box is checked. Un-check to box to remove the settings. ' .
										'You can determine the values for these fields by examining your cached images. The file names will look something like these:') .
						'<ul>' .
						'<li>' . gettext('<code>photo_595.jpg</code>: sized to 595 pixels') . '</li>' .
						'<li>' . gettext('<code>photo_w180_cw180_ch80_thumb.jpg</code>: a size of 180px wide and 80px high and it is a thumbnail (<code>thumb</code>)') . '</li>' .
						'<li>' . gettext('<code>photo_85_cw72_ch72_thumb_copyright_gray.jpg</code>: sized 85px cropped at about 7.6% (one half of 72/85) from the horizontal and vertical sides with a watermark (<code>copyright</code>) and rendered in grayscale (<code>gray</code>).') . '</li>' .
						'<li>' . gettext('<code>photo_w85_h85_cw350_ch350_cx43_cy169_thumb_copyright.jpg</code>: a custom cropped 85px thumbnail with watermark.') . '</li>' .
						'</ul>' .
						'</p>' .
						'<p>' .
						gettext('If a field is not represented in the cached name, leave the field blank. Custom crops (those with cx and cy) really cannot be cached easily since each image has unique values. ' .
										'See the <em>template-functions</em>::<code>getCustomImageURL()</code> comment block for details on these fields.') .
						'</p>' .
						'<p>' .
						gettext('Some themes use <em>MaxSpace</em> image functions. To cache images referenced by these functions set the <em>width</em> and <em>height</em> parameters to the <em>MaxSpace</em> container size and check the <code>MaxSpace</code> checkbox.') .
						'</p>'
				)
		);
		$list = array('<em>' . gettext('Albums') . '</em>' => 'cacheManager_albums', '<em>' . gettext('Images') . '</em>' => 'cacheManager_images');
		if (extensionEnabled('zenpage')) {
			$list['<em>' . gettext('News') . '</em>'] = 'cacheManager_news';
			$list['<em>' . gettext('Pages') . '</em>'] = 'cacheManager_pages';
		} else {
			setOption('cacheManager_news', 0);
			setOption('cacheManager_pages', 0);
		}
		$options[gettext('Purge cache files')] = array('key' => 'cacheManager_items', 'type' => OPTION_TYPE_CHECKBOX_ARRAY,
				'order' => 0,
				'checkboxes' => $list,
				'desc' => gettext('If a <em>type</em> is checked, the HTML and RSS caches for the item will be purged when the published state of an item of <em>type</em> changes.') .
				'<div class="notebox">' . gettext('<strong>NOTE:</strong> The entire cache is cleared since there is no way to ascertain if a gallery page contains dependencies on the item.') . '</div>'
		);
		return $options;
	}

	/**
	 *
	 * custom option handler
	 * @param string $option
	 * @param mixed $currentValue
	 */
	function handleOption($option, $currentValue) {
		global $_gallery;
		$currenttheme = $_gallery->getCurrentTheme();
		$custom = array();
		$result = query('SELECT * FROM ' . prefix('plugin_storage') . ' WHERE `type`="cacheManager" ORDER BY `aux`');
		$key = 0;
		while ($row = db_fetch_assoc($result)) {
			$owner = $row['aux'];
			$data = getSerializedArray($row['data']);
			$index = $data['theme'];
			if (array_key_exists('album', $data) && $data['album']) {
				$index .= '__' . $data['album'];
			}
			$custom[$index][] = $data;
		}

		ksort($custom, SORT_LOCALE_STRING);
		$custom[] = array(array('theme' => NULL));
		$c = 0;
		self::printShowHide();

		foreach ($custom as $ownerdata) {
			$a = reset($ownerdata);
			$ownerid = $owner = $a['theme'];
			if (array_key_exists('class', $a)) {
				$type = $a['class'];
			} else {
				$type = 'legacy';
			}
			switch ($type) {
				default:
				case 'custom':
					break;
				case 'theme':
					if (is_dir(SERVERPATH . '/' . THEMEFOLDER . '/' . $owner)) {
						break;
					}
				case 'plugin':
					if (getPlugin($owner . '.php')) {
						break;
					}
					$type = 'deprecated'; //	owner no longer exists
			}

			if (array_key_exists('album', $a) && $a['album']) {
				$album = $a['album'];
				$ownerid = $owner . '__' . $album;
				$albumdisp = ' (' . $album . ')';
			} else {
				$albumdisp = $album = NULL;
			}
			$ownerid = preg_replace('/[^A-Za-z0-9\-_]/', '', $ownerid);

			$ownerdata = sortMultiArray($ownerdata, array('thumb', 'image_size', 'image_width', 'image_height'));
			if (!$owner) {
				echo '<br />';
			}
			?>
			<span class="icons upArrow" id="<?php echo $ownerid; ?>_arrow">
				<a onclick="showTheme('<?php echo $ownerid; ?>');" title="<?php echo gettext('Show'); ?>">
					<?php
					echo ARROW_DOWN_GREEN;
					if ($owner) {
						$inputclass = 'hidden';
						echo '<span class="' . $type . '"><em>' . $owner . $albumdisp . '</em> (' . count($ownerdata), ')</span>';
						$subtype = @$ownerdata['album'];
					} else {
						$inputclass = 'textbox';
						$subtype = '_custom_';
						$type = 'custom';
						echo gettext('add');
					}
					?>
				</a>
				<?php
				if ($owner && $owner != 'admin') {
					?>
					<span class="displayinlineright"><?php echo gettext('Delete'); ?> <input type="checkbox" onclick="$('.cacheManagerOwner_<?php echo $ownerid; ?>').prop('checked', $(this).prop('checked'))" value="1" /></span>
					<?php
				}
				?>
			</span>
			<br />
			<div id="<?php echo $ownerid; ?>_list" style="display:none">
				<br />
				<?php
				foreach ($ownerdata as $cache) {
					$key++;
					if ($c % 2) {
						$class = 'boxb';
					} else {
						$class = 'box';
					}
					?>
					<div>
						<?php
						$c++;
						if (isset($cache['enable']) && $cache['enable']) {
							$allow = ' checked="checked"';
						} else {
							$allow = '';
						}
						?>
						<div class="<?php echo $class; ?>">
							<input type="<?php echo $inputclass; ?>" size="25" name="cacheManager[<?php echo $key; ?>][theme]" value="<?php echo $owner; ?>" />
							<input type="hidden" name="cacheManager[<?php echo $key; ?>][subtype]" value="<?php echo $subtype; ?>" />
							<input type="hidden" name="cacheManager[<?php echo $key; ?>][class]" value="<?php echo $type; ?>" />
							<?php
							if ($owner) {
								?>
								<span class="displayinlineright"><?php echo gettext('Delete'); ?> <input type="checkbox" name="cacheManager[<?php echo $key; ?>][delete]" value="1" class="cacheManagerOwner_<?php echo $ownerid; ?>" /></span>
								<?php
							}
							?>
							<br />
							<?php
							foreach (array('image_size' => gettext('Size'), 'image_width' => gettext('Width'), 'image_height' => gettext('Height'),
					'crop_width' => gettext('Crop width'), 'crop_height' => gettext('Crop height'), 'crop_x' => gettext('Crop X axis'),
					'crop_y' => gettext('Crop Y axis')) as $what => $display) {
								if (isset($cache[$what])) {
									$v = $cache[$what];
								} else {
									$v = '';
								}
								?>
								<span class="nowrap"><?php echo $display; ?> <input type="textbox" size="2" name="cacheManager[<?php echo $key; ?>][<?php echo $what; ?>]" value="<?php echo $v; ?>" /></span>
								<?php
							}
							if (isset($cache['wmk'])) {
								$wmk = $cache['wmk'];
							} else {
								$wmk = '';
							}
							?>
							<span class="nowrap"><?php echo gettext('Watermark'); ?> <input type="textbox" size="20" name="cacheManager[<?php echo $key; ?>][wmk]" value="<?php echo $wmk; ?>" /></span>
							<br />
							<span class="nowrap"><?php echo gettext('MaxSpace'); ?> <input type="checkbox"  name="cacheManager[<?php echo $key; ?>][maxspace]" value="1"<?php if (isset($cache['maxspace']) && $cache['maxspace']) {
	echo ' checked="checked"';
}
?> /></span>
							<span class="nowrap"><?php echo gettext('Thumbnail'); ?> <input type="checkbox"  name="cacheManager[<?php echo $key; ?>][thumb]" value="1"<?php if (isset($cache['thumb']) && $cache['thumb']) {
	echo ' checked="checked"';
}
?> /></span>
							<span class="nowrap"><?php echo gettext('Grayscale'); ?> <input type="checkbox"  name="cacheManager[<?php echo $key; ?>][gray]" value="gray"<?php if (isset($cache['gray']) && $cache['gray']) {
	echo ' checked="checked"';
}
?> /></span>
						</div>
						<br />
					</div>
					<?php
				}
				?>
			</div><!-- <?php echo $owner . $album; ?>_list -->
			<?php
		}
	}

	/**
	 *
	 * process custom option saves
	 * @param string $ownername
	 * @param string $owneralbum
	 * @return string
	 */
	static function handleOptionSave($ownername, $themealbum) {
		query('DELETE FROM ' . prefix('plugin_storage') . ' WHERE `type`="cacheManager"');
		foreach ($_POST['cacheManager'] as $cacheimage) {
			if (!isset($cacheimage['delete']) && count($cacheimage) > 1) {
				$subtype = $cacheimage['subtype'];
				unset($cacheimage['subtype']);
				$cacheimage['theme'] = preg_replace("/[\s\"\']+/", "-", $cacheimage['theme']);
				if (!empty($cacheimage['theme'])) {
					$sql = 'INSERT INTO ' . prefix('plugin_storage') . ' (`type`, `subtype`, `aux`, `data`) VALUES ("cacheManager",' . db_quote($subtype) . ',' . db_quote($cacheimage['theme']) . ',' . db_quote(serialize($cacheimage)) . ')';
					query($sql);
				}
			}
		}
		return false;
	}

	/**
	 *
	 * @global type $_set_theme_album
	 * @global type $_gallery
	 * @param string $owner
	 * @param int $size	standard image parameters
	 * @param int $width
	 * @param int $height
	 * @param int $cw
	 * @param int $ch
	 * @param int $cx
	 * @param int $cy
	 * @param int $thumb
	 * @param int $watermark
	 * @param int $effects
	 * @param int $maxspace
	 */
	static function addCacheSize($owner, $size, $width, $height, $cw, $ch, $cx, $cy, $thumb, $watermark = NULL, $effects = NULL, $maxspace = NULL) {
		global $_set_theme_album, $_gallery;


		$albumName = '';
		if (getPlugin($owner . '.php')) {
			$class = 'plugin';
		} else {
			$ownerList = array_map('strtolower', array_keys($_gallery->getThemes()));
			if (in_array(strtolower($owner), $ownerList)) {
				$class = 'theme';
				//from a theme, so there are standard options
				if (is_null($watermark)) {
					$watermark = getThemeOption('image_watermark', $_set_theme_album, $owner);
				}
				if (is_null($effects)) {
					if ($thumb) {
						if (getThemeOption('thumb_gray', $_set_theme_album, $owner)) {
							$effects = 'gray';
						}
					} else {
						if (getThemeOption('image_gray', $_set_theme_album, $owner)) {
							$effects = 'gray';
						}
					}
				}
				if ($thumb) {
					if (getThemeOption('thumb_crop', $_set_theme_album, $owner)) {
						if (is_null($cw) && is_null($ch)) {
							$ch = getThemeOption('thumb_crop_height', $_set_theme_album, $owner);
							$cw = getThemeOption('thumb_crop_width', $_set_theme_album, $owner);
						}
					} else {
						$ch = $cw = NULL;
					}
				}
				if (!is_null($_set_theme_album)) {
					$albumName = $_set_theme_album->name;
				}
			} else {
				$class = 'custom';
			}
		}
		$cacheSize = serialize(array('theme' => $owner, 'album' => $albumName, 'apply' => false, 'class' => $class,
				'image_size' => $size, 'image_width' => $width, 'image_height' => $height,
				'crop_width' => $cw, 'crop_height' => $ch, 'crop_x' => $cx, 'crop_y' => $cy,
				'thumb' => $thumb, 'wmk' => $watermark, 'gray' => $effects, 'maxspace' => $maxspace));
		$sql = 'INSERT INTO ' . prefix('plugin_storage') . ' (`type`, `subtype`, `aux`,`data`) VALUES ("cacheManager",' . db_quote($albumName) . ',' . db_quote($owner) . ',' . db_quote($cacheSize) . ')';
		query($sql);
	}

	/**
	 *
	 * @global type $_set_theme_album
	 * @param string $owner
	 */
	static function deleteCacheSizes($owner) {
		global $_set_theme_album;
		$albumName = '';
		if (!is_null($_set_theme_album)) {
			$albumName = $_set_theme_album->name;
		}
		$sql = 'DELETE FROM ' . prefix('plugin_storage') . ' WHERE `type`="cacheManager" AND `subtype`=' . db_quote($albumName) . ' AND `aux`=' . db_quote($owner);
		query($sql);
	}

	/**
	 * @deprecated
	 * @since 1.8.0.11
	 */
	static function addThemeCacheSize($owner, $size, $width, $height, $cw, $ch, $cx, $cy, $thumb, $watermark = NULL, $effects = NULL, $maxspace = NULL) {
		cachemanager_internal_deprecations::addThemeCacheSize($owner, $size, $width, $height, $cw, $ch, $cx, $cy, $thumb, $watermark, $effects, $maxspace);
	}

	/**
	 * @deprecated
	 * @since 1.8.0.11
	 */
	static function deleteThemeCacheSizes($owner) {
		cachemanager_internal_deprecations::deleteThemeCacheSizes($owner);
	}

	/**
	 * javascript for show and hide of individual cache sizes
	 */
	static function printShowHide() {
		?>
		<script type="text/javascript">
			//<!-- <![CDATA[
			function checkTheme(theme) {
				$('.' + theme).prop('checked', $('#' + theme).prop('checked'));
			}
			function showTheme(theme) {
				html = $('#' + theme + '_arrow').html();
				if ($('#' + theme + '_arrow').hasClass('upArrow')) {
					$('#' + theme + '_arrow').removeClass('upArrow');
					html = html.replace(/<?php echo html_entity_decode(strip_tags(ARROW_DOWN_GREEN)); ?>/, '<?php echo html_entity_decode(strip_tags(ARROW_UP_GREEN)); ?>');
					html = html.replace(/<?php echo gettext('Show'); ?>/, '<?php echo gettext('Hide'); ?>');
					$('#' + theme + '_list').show();
				} else {
					$('#' + theme + '_arrow').addClass('upArrow');
					html = html.replace(/<?php echo html_entity_decode(strip_tags(ARROW_UP_GREEN)); ?>/, ' <?php echo html_entity_decode(strip_tags(ARROW_DOWN_GREEN)); ?>');
					html = html.replace(/<?php echo gettext('Hide'); ?>/, '<?php echo gettext('Show'); ?>');
					$('#' + theme + '_list').hide();
				}
				$('#' + theme + '_arrow').html(html);
			}
			//]]> -->
		</script>
		<?php
	}

	/**
	 *
	 * filter for the setShow() methods
	 * @param object $obj
	 */
	static function published($obj) {
		global $_HTML_cache, $_cached_feeds;

		if (getOption('cacheManager_' . $obj->table)) {
			$_HTML_cache->clearHTMLCache();
			foreach ($_cached_feeds as $feed) {
				$feeder = new cacheManagerFeed($feed);
				$feeder->clearCache();
			}
		}
		return $obj;
	}

	static function admin_tabs($tabs) {
		if (npg_loggedin(ADMIN_RIGHTS)) {
			$tabs['admin']['subtabs'][gettext('Cache images')] = PLUGIN_FOLDER . '/cacheManager/cacheImages.php?tab=images';
			$tabs['admin']['subtabs'][gettext('Cache stored images')] = PLUGIN_FOLDER . '/cacheManager/cacheDBImages.php?tab=DB&XSRFToken=' . getXSRFToken('cacheDBImages');
		}
		return $tabs;
	}

	static function buttons($buttons) {
		if (query_single_row('SELECT * FROM ' . prefix('plugin_storage') . ' WHERE `type`="cacheManager" LIMIT 1')) {
			$enable = true;
			$title = gettext('Finds images that have not been cached and creates the cached versions.');
		} else {
			$enable = false;
			$title = gettext('You must first set the plugin options for cached image parameters.');
		}

		if (class_exists('RSS')) {
			$buttons[] = array(
					'XSRFTag' => 'clear_cache',
					'category' => gettext('Cache'),
					'enable' => true,
					'button_text' => gettext('Purge RSS cache'),
					'formname' => 'purge_rss_cache.php',
					'action' => getAdminLink('admin.php') . '?action=clear_rss_cache',
					'icon' => WASTEBASKET,
					'alt' => '',
					'title' => gettext('Delete all files from the RSS cache'),
					'hidden' => '<input type="hidden" name="action" value="clear_rss_cache" />',
					'rights' => ADMIN_RIGHTS
			);
		}
		$buttons[] = array(
				'XSRFTag' => 'clear_cache',
				'category' => gettext('Cache'),
				'enable' => true,
				'button_text' => gettext('Purge Image cache'),
				'formname' => 'purge_image_cache.php',
				'action' => getAdminLink('admin.php') . '?action=action=clear_cache',
				'icon' => WASTEBASKET,
				'alt' => '',
				'title' => gettext('Delete all files from the Image cache'),
				'hidden' => '<input type="hidden" name="action" value="clear_cache" />',
				'rights' => ADMIN_RIGHTS
		);
		$buttons[] = array(
				'category' => gettext('Cache'),
				'enable' => true,
				'button_text' => gettext('Purge HTML cache'),
				'formname' => 'clearcache_button',
				'action' => getAdminLink('admin.php') . '?action=clear_html_cache',
				'icon' => WASTEBASKET,
				'title' => gettext('Clear the static HTML cache. HTML pages will be re-cached as they are viewed.'),
				'alt' => '',
				'hidden' => '<input type="hidden" name="action" value="clear_html_cache">',
				'rights' => ADMIN_RIGHTS,
				'XSRFTag' => 'ClearHTMLCache'
		);

		$buttons[] = array(
				'category' => gettext('Cache'),
				'enable' => true,
				'button_text' => gettext('Purge search cache'),
				'formname' => 'clearcache_button',
				'action' => getAdminLink('admin.php') . '?action=clear_search_cache',
				'icon' => WASTEBASKET,
				'title' => gettext('Clear the static search cache.'),
				'alt' => '',
				'hidden' => '<input type="hidden" name="action" value="clear_search_cache">',
				'rights' => ADMIN_RIGHTS,
				'XSRFTag' => 'ClearSearchCache'
		);
		return $buttons;
	}

	static function albumbutton($html, $object, $prefix) {
		$html .= '<hr />';
		if (query_single_row('SELECT * FROM ' . prefix('plugin_storage') . ' WHERE `type`="cacheManager" LIMIT 1')) {
			$disable = '';
			$title = gettext('Finds images that have not been cached and creates the cached versions.');
		} else {
			$disable = ' disabled="disabled"';
			$title = gettext("You must first set the plugin options for cached image parameters.");
		}
		$html .= '<div class="button buttons tooltip" title="' . $title . '"><a href="' . getAdminLink(PLUGIN_FOLDER . '/cacheManager/cacheImages.php') . '?album=' . html_encode($object->name) . '&amp;XSRFToken=' . getXSRFToken('cacheImages') . '"' . $disable . '>' . CIRCLED_BLUE_STAR . ' ' . gettext('Cache album images') . '</a><br class="clearall"></div>';
		return $html;
	}

	/**
	 * Catch redundant legacy zenphoto static functions if they happen to be used by a third party theme or plugin
	 *
	 * @param string $method
	 * @param misc $args
	 */
	public static function __callStatic($method, $args) {
		cachemanager_internal_deprecations::generalDeprecation($method, $args);
	}

}
?>
