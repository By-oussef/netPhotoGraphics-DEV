<?php

/**
 *
 * Use to overlay thumbnail images with icons depending on the state of the image.
 *
 *
 * Thumbnails may be flagged with the following icons:
 * <ul>
 * 		<li><img src="%WEBPATH%/%CORE_FOLDER%/%PLUGIN_FOLDER%/flag_thumbnail/new.png" />: <i>New</i>—images whose <var>date</var> (or <var>mtime</var>) are within the selected "range" of the current day.</li>
 * 		<li><img src="%WEBPATH%/%CORE_FOLDER%/%PLUGIN_FOLDER%/flag_thumbnail/lock.png" />: <i>Protected</i>—images which are in a password protected album or because
 * 							 a parent album is password protected.</li>
 * 		<li><img src="%WEBPATH%/%CORE_FOLDER%/%PLUGIN_FOLDER%/flag_thumbnail/action.png" />: <i>Un-published</i>—images that are marked as not visible.</li>
 * 		<li><img src="%WEBPATH%/%CORE_FOLDER%/%PLUGIN_FOLDER%/flag_thumbnail/GPS.png" />: <i>Geotagged</i>—images which have latitude/longitude information in their metadata.</li>
 * </ul>
 *
 * The icon with which the thumbnail is flagged is selectable by option. The above standard icons are provided as defaults.
 * Additional icons can be used by placing them in the <var>%USER_PLUGIN_FOLDER%/flag_thumbnail</var> folder.
 *
 * @author Stephen Billard (sbillard) and Malte Müller (acrylian)
 *
 * @package plugins/flag_thumbnail
 * @pluginCategory media
 */
$plugin_description = gettext('Overlay icons over thumbnails to indicate image status.');

$option_interface = 'flag_thumbnail';

npgFilters::register('standard_image_thumb_html', 'flag_thumbnail::std_image_thumbs');
npgFilters::register('standard_album_thumb_html', 'flag_thumbnail::std_album_thumbs', 99);
npgFilters::register('custom_album_thumb_html', 'flag_thumbnail::custom_album_thumbs', 99);
npgFilters::register('custom_image_html', 'flag_thumbnail::custom_images', 99);

/**
 * Plugin option handling class
 *
 */
class flag_thumbnail {

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('flag_thumbnail_date', 'date');
			setOptionDefault('flag_thumbnail_range', '3');
			setOptionDefault('flag_thumbnail_new_text', 'NEW');
			setOptionDefault('flag_thumbnail_unpublished_text', 'unpub');
			setOptionDefault('flag_thumbnail_locked_text', 'locked');
			setOptionDefault('flag_thumbnail_geodata_text', 'GPS');
			setOptionDefault('flag_thumbnail_use_text', '');
			setOptionDefault('flag_thumbnail_flag_new', 1);
			setOptionDefault('flag_thumbnail_flag_locked', 1);
			setOptionDefault('flag_thumbnail_flag_unpublished', 1);
			setOptionDefault('flag_thumbnail_flag_geodata', 1);
			setOptionDefault('flag_thumbnail_new_icon', 'new.png');
			setOptionDefault('flag_thumbnail_unpublished_icon', 'action.png');
			setOptionDefault('flag_thumbnail_locked_icon', 'lock.png');
			setOptionDefault('flag_thumbnail_geodata_icon', 'GPS.png');
		}
	}

	function getOptionsSupported() {
		$buttons = array();
		$icons = getPluginFiles('*.png', 'flag_thumbnail');
		foreach ($icons as $icon) {
			$icon = str_replace(SERVERPATH, WEBPATH, $icon);
			$buttons['  <img src="' . $icon . '" />'] = basename($icon);
		}
		return array('» ' . gettext('Criteria') => array('key' => 'flag_thumbnail_date', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 3.6,
						'selections' => array(gettext('date') => "date", gettext('mtime') => "mtime"),
						'desc' => gettext("Select the basis for considering if an image is new.")),
				'» ' . gettext('Icon') . chr(0) . '3' => array('key' => 'flag_thumbnail_new_icon', 'type' => OPTION_TYPE_RADIO,
						'order' => 3.1,
						'buttons' => $buttons, 'behind' => true,
						'desc' => gettext('Select the icon that will show for “new” images.')),
				'» ' . gettext('Icon') . chr(0) . '2' => array('key' => 'flag_thumbnail_unpublished_icon', 'type' => OPTION_TYPE_RADIO,
						'order' => 2.1,
						'buttons' => $buttons, 'behind' => true,
						'desc' => gettext('Select the icon that will show for “un-published” images.')),
				'» ' . gettext('Icon') . chr(0) . '4' => array('key' => 'flag_thumbnail_locked_icon', 'type' => OPTION_TYPE_RADIO,
						'order' => 4.1,
						'buttons' => $buttons, 'behind' => true,
						'desc' => gettext('Select the icon that will show for “Protected” images.')),
				'» ' . gettext('Icon') . chr(0) . '5' => array('key' => 'flag_thumbnail_geodata_icon', 'type' => OPTION_TYPE_RADIO,
						'order' => 5.1,
						'buttons' => $buttons, 'behind' => true,
						'desc' => gettext('Select the icon that will show for images tagged with geodata.')),
				gettext('Un-published') => array('key' => 'flag_thumbnail_flag_unpublished', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 2,
						'desc' => gettext('Thumbnails for images which are not <em>published</em> will be marked.')),
				gettext('Protected') => array('key' => 'flag_thumbnail_flag_locked', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 4,
						'desc' => gettext('Thumbnails for images which are password protected or in password protected albums will be marked.')),
				gettext('New') => array('key' => 'flag_thumbnail_flag_new', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 3,
						'desc' => gettext('Thumbnails for images which have recently been added to the gallery will be marked.')),
				gettext('Geotagged') => array('key' => 'flag_thumbnail_flag_geodata', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 5,
						'desc' => gettext('Thumbnails for images which are geodata tagged will be marked.')),
				'» ' . gettext('Text') . chr(0) . '5' => array('key' => 'flag_thumbnail_geodata_text', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 5.5,
						'desc' => gettext("Text flag for <em>geodata tagged</em> images.")),
				5 => array('type' => OPTION_TYPE_NOTE,
						'order' => 5.9,
						'desc' => '<hr />'),
				'» ' . gettext('Aging') => array('key' => 'flag_thumbnail_range', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 3.7,
						'desc' => gettext("The range in days until images are no longer flagged as new.")),
				'» ' . gettext('Text') . chr(0) . '3' => array('key' => 'flag_thumbnail_new_text', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 3.5,
						'desc' => gettext("Text flag for <em>new</em> images.")),
				3 => array('type' => OPTION_TYPE_NOTE,
						'order' => 3.9,
						'desc' => '<hr />'),
				'» ' . gettext('Text') . chr(0) . '2' => array('key' => 'flag_thumbnail_unpublished_text', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 2.5,
						'desc' => gettext("Text flag for <em>un-published</em> images.")),
				2 => array('type' => OPTION_TYPE_NOTE,
						'order' => 2.9,
						'desc' => '<hr />'),
				'» ' . gettext('Text') . chr(0) . '4' => array('key' => 'flag_thumbnail_locked_text', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 4.5,
						'desc' => gettext("Text flag for <em>protected</em> images.")),
				4 => array('type' => OPTION_TYPE_NOTE,
						'order' => 4.9,
						'desc' => '<hr />'),
				gettext('Use text') => array('key' => 'flag_thumbnail_use_text', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 8,
						'desc' => gettext('If checked, the defined <em>text</em> will be used in place of the icon. (Use the class <code>textasnewflag</code> for styling "text" overlays.)'))
		);
	}

	protected static function image($html, $which, $where) {
		$img = getPlugin($which);
		$size = gl_imageDims($img);
		$wide = $size['width'];
		$high = $size['height'];
		$img = str_replace(SERVERPATH, WEBPATH, $img);
		$html .= '<img src="' . $img . '" class="imageasflag" width="' . $wide . 'px" height="' . $high . 'px" alt="" style="max-width:' . $wide . 'px; position: ' . $where . '" />' . "\n";
		return $html;
	}

	protected static function insert_class($html_original) {
		global $_current_album, $_current_image;

		$html = $html_original;
		if (getOption('flag_thumbnail_flag_new')) {
			if (isset($_current_image)) {
				$obj = $_current_image;
			} else {
				$obj = $_current_album;
			}
			switch (getOption('flag_thumbnail_date')) {
				case "date":
					$imagedatestamp = strtotime($obj->getDateTime());
					break;
				case "mtime":
					$imagedatestamp = $obj->get('mtime');
					break;
			}
			$not_older_as = (60 * 60 * 24 * getOption('flag_thumbnail_range'));
			$age = (time() - $imagedatestamp);
			if ($age <= $not_older_as) {
				if (getOption('flag_thumbnail_use_text')) {
					$html .= '<span class="textasnewflag" style="position: absolute;top: 10px;right: 6px;">' . getOption('flag_thumbnail_new_text') . "</span>\n";
				} else {
					$html = self::image($html, get_class() . '/' . getOption('flag_thumbnail_new_icon'), 'absolute;top: 4px;right: 4px;');
				}
			}
		}
		if (getOption('flag_thumbnail_flag_geodata')) {
			if (isAlbumClass($obj)) {
				$obj = $obj->getAlbumThumbImage();
			}
			if (is_object($obj) && isImageClass($obj)) {
				if ($obj->get('GPSLatitude') && $obj->get('GPSLongitude')) {
					if (getOption('flag_thumbnail_use_text')) {
						$html .= '<span class="textasnewflag" style="position: absolute;bottom: 10px;right: 6px;">' . getOption('flag_thumbnail_use_text') . "</span>\n";
					} else {
						$html = self::image($html, get_class() . '/' . getOption('flag_thumbnail_geodata_icon'), 'absolute;bottom: 4px;right: 4px;');
					}
				}
			}
		}
		$i = strpos($html, 'class=');
		if ($i !== false) {
			$locked = strpos($html, 'password_protected', $i + 7) !== false;
			$unpublished = strpos($html, 'not_visible', $i + 7) !== false;

			if ($locked && getOption('flag_thumbnail_flag_locked')) {
				if (getOption('flag_thumbnail_use_text')) {
					$html .= '<span class="textasnewflag" style="position: absolute;bottom: 10px;left: 4px;">' . getOption('flag_thumbnail_locked_text') . "</span>\n";
				} else {
					$html = self::image($html, get_class() . '/' . getOption('flag_thumbnail_locked_icon'), 'absolute;bottom: 4px;left: 4px;');
				}
			}
			if ($unpublished && getOption('flag_thumbnail_flag_unpublished')) {
				if (getOption('flag_thumbnail_use_text')) {
					$html .= '<span class="textasnewflag" style="position: absolute;top: 10px;left: 4px;">' . getOption('flag_thumbnail_unpublished_text') . "</span>\n";
				} else {
					$html = self::image($html, get_class() . '/' . getOption('flag_thumbnail_unpublished_icon'), 'absolute;top: 4px;left: 4px;');
				}
			}
		}
		$html = '<span class="flag_thumbnail" style="position:relative; display:block;">' . "\n" . trim($html) . "\n</span>\n";

		return $html;
	}

	static function custom_images($html, $thumbstandin) {
		if ($thumbstandin) {
			$html = static::insert_class($html);
		}
		return $html;
	}

	static function std_image_thumbs($html) {
		$html = static::insert_class($html);
		return $html;
	}

	static function std_album_thumbs($html) {
		$html = static::insert_class($html);
		return $html;
	}

	static function custom_album_thumbs($html) {
		$html = static::insert_class($html);
		return $html;
	}

}

?>