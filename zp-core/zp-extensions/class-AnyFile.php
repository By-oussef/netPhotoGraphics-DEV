<?php
/**
 *
 * Use this plugin to handle filetypes as "images" that are not otherwise provided for by zenphoto.
 *
 * Default thumbnail images may be created in the <var>%USER_PLUGIN_FOLDER%/class-AnyFile</var> folder. The naming convention is
 * <i>suffix</i><var>Default.png</var>. If no such file is found, the class object default thumbnail will be used.
 *
 * The plugin is an extension of <var>TextObject</var>. For more details see the <i>class-textobject</i> plugin.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/class-AnyFile
 * @pluginCategory media
 *
 */
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_is_filter = 990 | CLASS_PLUGIN;
	$plugin_description = gettext('Provides a means for handling arbitrary file types. (No rendering provided!)');
}

foreach (get_AnyFile_suffixes() as $suffix) {
	Gallery::addImageHandler($suffix, 'AnyFile');
}
$option_interface = 'AnyFile_Options';

/**
 * Option class for textobjects objects
 *
 */
class AnyFile_Options {

	/**
	 * Standard option interface
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(gettext('Watermark default images') => array('key' => 'AnyFile_watermark_default_images', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Check to place watermark image on default thumbnail images.')),
				gettext('Handled files') => array('key' => 'AnyFile_file_list', 'type' => OPTION_TYPE_CUSTOM,
						'desc' => gettext('File suffixes to be handled.')),
				gettext('Add file suffix') => array('key' => 'AnyFile_file_new', 'type' => OPTION_TYPE_TEXTBOX,
						'desc' => gettext('Add a file suffix to be handled by the plugin'))
		);
	}

	function handleOption($option, $currentValue) {
		$list = get_AnyFile_suffixes();
		?>
		<ul class="customchecklist">
			<?php
			generateUnorderedListFromArray($list, $list, 'AnyFile_file_', false, false, false, NULL, NULL, true);
			?>
		</ul>
		<?php
	}

	function handleOptionSave($themename, $themealbum) {
		if (isset($_POST['AnyFile_file_list'])) {
			$mysetoptions = sanitize($_POST['AnyFile_file_list']);
		} else {
			$mysetoptions = array();
		}
		if ($new = getOption('AnyFile_file_new')) {
			$mysetoptions[] = $new;
		}
		purgeOption('AnyFile_file_new');
		setOption('AnyFileSuffixList', serialize($mysetoptions));
		return false;
	}

}

function get_AnyFile_suffixes() {
	return getSerializedArray(getOption('AnyFileSuffixList'));
}

require_once(dirname(__FILE__) . '/class-textobject/class-textobject_core.php');

class AnyFile extends TextObject {

	/**
	 * creates a WEBdocs (image standin)
	 *
	 * @param object $album the owner album
	 * @param string $filename the filename of the text file
	 * @return TextObject
	 */
	function __construct($album, $filename, $quiet = false) {

		$this->watermark = getOption('AnyFile_watermark');
		$this->watermarkDefault = getOption('AnyFile_watermark_default_images');

		$this->common_instantiate($album, $filename, $quiet);
	}

	/**
	 * Returns the image file name for the thumbnail image.
	 *
	 * @param string $path override path
	 *
	 * @return s
	 */
	function getThumbImageFile($path = NULL) {
		global $_gallery;
		if (is_null($path)) {
			$path = SERVERPATH;
		}
		if (is_null($this->objectsThumb)) {
			$img = '/' . getSuffix($this->filename) . 'Default.png';
			$imgfile = $path . '/' . THEMEFOLDER . '/' . internalToFilesystem($_gallery->getCurrentTheme()) . '/images/' . $img;
			if (!file_exists($imgfile)) {
				$imgfile = $path . "/" . USER_PLUGIN_FOLDER . '/' . substr(basename(__FILE__), 0, -4) . $img;
				if (!file_exists($imgfile)) {
					$imgfile = $path . "/" . CORE_FOLDER . '/' . PLUGIN_FOLDER . '/' . substr(basename(__FILE__), 0, -4) . '/anyFileDefault.png';
				}
			}
		} else {
			$imgfile = ALBUM_FOLDER_SERVERPATH . internalToFilesystem($this->imagefolder) . '/' . $this->objectsThumb;
		}
		return $imgfile;
	}

	/**
	 * Returns the content of the text file
	 *
	 * @param int $w optional width
	 * @param int $h optional height
	 * @return string
	 */
	function getContent($w = NULL, $h = NULL) {
		$this->updateDimensions();
		if (is_null($w)) {
					$w = $this->getWidth();
		}
		if (is_null($h)) {
					$h = $this->getHeight();
		}
		/*
		 * just return the thumbnail as we do not know how to
		 * render the file.
		 */
		return '<img src="' . html_encode($this->getThumb()) . '">';
	}

}
?>