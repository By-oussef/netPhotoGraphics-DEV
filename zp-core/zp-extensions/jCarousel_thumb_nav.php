<?php
/**
 * JavaScript thumb nav plugin with dynamic loading of thumbs on request via JavaScript.
 * Place <var>printThumbNav()</var> on your theme's <i>image.php</i> where you want it to appear.
 *
 * Supports theme based custom css files (place <var>jcarousel.css</var> and needed images in your theme's <var>jCarousel_thumb_nav</var> folder).
 *
 * @author Malte Müller (acrylian)
 * @package plugins/jCarousel_thumb_nav
 * @pluginCategory theme
 */
$plugin_description = gettext("jQuery jCarousel thumb nav plugin with dynamic loading of thumbs on request via JavaScript.");
$plugin_disable = (extensionEnabled('bxslider_thumb_nav')) ? sprintf(gettext('Only one Carousel plugin may be enabled. <a href="#%1$s"><code>%1$s</code></a> is already enabled.'), 'bxslider_thumb_nav') : '';

$option_interface = 'jcarousel';
if (!getOption('jQuery_Migrate_theme')) { //	until such time as jquery.jcarousel works with jQuery 3.3
	setOption('jQuery_Migrate_theme', 1, false);
}

/**
 * Plugin option handling class
 *
 */
class jcarousel {

	function __construct() {
		if (OFFSET_PATH == 2) {

			$found = array();
			$result = getOptionsLike('jcarousel_');
			foreach ($result as $option => $value) {
				preg_match('/jcarousel_(.*)_(.*)/', $option, $matches);
				if (count($matches) == 3 && $matches[2] != 'scripts') {
					if ($value) {
						$found[$matches[1]][] = $matches[2];
					}
					purgeOption('jcarousel_' . $matches[1] . '_' . $matches[2]);
				}
			}
			foreach ($found as $theme => $scripts) {
				setOptionDefault('jcarousel_' . $theme . '_scripts', serialize($scripts));
			}

			setOptionDefault('jcarousel_scroll', '3');
			setOptionDefault('jcarousel_width', '50');
			setOptionDefault('jcarousel_height', '50');
			setOptionDefault('jcarousel_croph', '50');
			setOptionDefault('jcarousel_cropw', '50');
			setOptionDefault('jcarousel_fullimagelink', '');
			setOptionDefault('jcarousel_vertical', 0);
			if (class_exists('cacheManager')) {
				cacheManager::deleteCacheSizes('jcarousel_thumb_nav');
				cacheManager::addCacheSize('jcarousel_thumb_nav', NULL, getOption('jcarousel_width'), getOption('jcarousel_height'), getOption('jcarousel_cropw'), getOption('jcarousel_croph'), NULL, NULL, true, NULL, NULL, NULL);
			}
		}
	}

	function getOptionsSupported() {
		global $_gallery;
		$options = array(gettext('Thumbs number') => array('key' => 'jcarousel_scroll', 'type' => OPTION_TYPE_NUMBER,
						'order' => 0,
						'desc' => gettext("The number of thumbs to scroll by. Note that the CSS might need to be adjusted.")),
				gettext('width') => array('key' => 'jcarousel_width', 'type' => OPTION_TYPE_NUMBER,
						'order' => 1,
						'desc' => gettext("Width of the carousel. Note that the CSS might need to be adjusted.")),
				gettext('height') => array('key' => 'jcarousel_height', 'type' => OPTION_TYPE_NUMBER,
						'order' => 2,
						'desc' => gettext("Height of the carousel. Note that the CSS might need to be adjusted.")),
				gettext('Crop width') => array('key' => 'jcarousel_cropw', 'type' => OPTION_TYPE_NUMBER,
						'order' => 3,
						'desc' => ""),
				gettext('Crop height') => array('key' => 'jcarousel_croph', 'type' => OPTION_TYPE_NUMBER,
						'order' => 4,
						'desc' => ""),
				gettext('Full image link') => array('key' => 'jcarousel_fullimagelink', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 5,
						'desc' => gettext("If checked the thumbs link to the full image instead of the image page.")),
				gettext('Vertical') => array('key' => 'jcarousel_vertical', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 6,
						'desc' => gettext("If checked the carousel will flow vertically instead of the default horizontal. Changing this may require theme changes!"))
		);
		foreach (getThemeFiles(array('404.php', 'themeoptions.php', 'theme_description.php', 'functions.php', 'password.php', 'sidebar.php', 'register.php', 'contact.php')) as $theme => $scripts) {
			$list = array();
			foreach ($scripts as $script) {
				$list[$script] = 'jcarousel_' . $theme . '_' . stripSuffix($script);
			}
			$options[$theme] = array('key' => 'jcarousel_' . $theme . '_scripts', 'type' => OPTION_TYPE_CHECKBOX_ARRAY,
					'order' => 99,
					'checkboxes' => $list,
					'desc' => gettext('The scripts for which jCarousel is enabled. {If themes require it they might set this, otherwise you need to do it manually!}')
			);
		}
		return $options;
	}

	static function themeJS() {
		?>
		<script>
			(function ($) {
				var userAgent = navigator.userAgent.toLowerCase();

				$.browser = {
					version: (userAgent.match(/.+(?:rv|it|ra|ie)[\/: ]([\d.]+)/) || [0, '0'])[1],
					safari: /webkit/.test(userAgent),
					opera: /opera/.test(userAgent),
					msie: /msie/.test(userAgent) && !/opera/.test(userAgent),
					mozilla: /mozilla/.test(userAgent) && !/(compatible|webkit)/.test(userAgent)
				};

			})(jQuery);
		</script>
		<?php
		scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/jCarousel_thumb_nav/jquery.jcarousel.pack.js');
		scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/jCarousel_thumb_nav/jquery.jcarousel.css');
		$theme = getCurrentTheme();
		if (file_exists(SERVERPATH . '/' . THEMEFOLDER . '/' . internalToFilesystem($theme) . '/jcarousel.css')) {
			// this should comply with the standard!
			$css = SERVERPATH . '/' . THEMEFOLDER . '/' . $theme . '/jcarousel.css';
			require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/deprecated-functions.php');
			deprecated_functions::notify_handler(gettext('The jCarousel css files should be placed in the theme subfolder "jCarousel_thumb_nav"'), NULL);
		} else {
			$css = getPlugin('jCarousel_thumb_nav/jcarousel.css', $theme);
		}
		scriptLoader($css);
	}

}

if (!$plugin_disable && !OFFSET_PATH && getOption('jcarousel_' . $_gallery->getCurrentTheme() . '_' . stripSuffix($_gallery_page))) {
	npgFilters::register('theme_body_close', 'jcarousel::themeJS');

	/** Prints the jQuery jCarousel HTML setup to be replaced by JS
	 *
	 * @param int $minitems The minimum number of thumbs to be visible always if resized regarding responsiveness.
	 * @param int $maxitems not supported
	 * @param int $width Width Set to NULL if you want to use the backend plugin options.
	 * @param int $height Height Set to NULL if you want to use the backend plugin options.
	 * @param int $cropw Crop width Set to NULL if you want to use the backend plugin options.
	 * @param int $croph Crop heigth Set to NULL if you want to use the backend plugin options.
	 * @param bool $crop TRUE for cropped thumbs, FALSE for un-cropped thumbs. $width and $height then will be used as maxspace. Set to NULL if you want to use the backend plugin options.
	 * @param bool $fullimagelink Set to TRUE if you want the thumb link to link to the full image instead of the image page. Set to NULL if you want to use the backend plugin options.
	 * @param string $vertical 'horizontal','vertical', 'fade'
	 * @param int $speed not supported
	 */
	function printThumbNav($minitems = NULL, $maxitems = NULL, $width = NULL, $height = NULL, $cropw = NULL, $croph = NULL, $fullimagelink = NULL, $vertical = NULL, $speed = NULL, $thumbscroll = NULL) {
		global $_gallery, $_current_album, $_current_image, $_current_search, $_gallery_page;
		//	Just incase the theme has not set the option, at least second try will work!
		setOptionDefault('jcarousel_' . $_gallery->getCurrentTheme() . '_' . stripSuffix($_gallery_page), 1);
		$items = "";
		if (is_object($_current_album) && $_current_album->getNumImages() >= 2) {
			if (is_null($thumbscroll)) {
				$thumbscroll = getOption('jcarousel_scroll');
			} else {
				$thumbscroll = sanitize_numeric($thumbscroll);
			}
			if (is_null($width)) {
				$width = getOption('jcarousel_width');
			} else {
				$width = sanitize_numeric($width);
			}
			if (is_null($height)) {
				$height = getOption('jcarousel_height');
			} else {
				$height = sanitize_numeric($height);
			}
			if (is_null($cropw)) {
				$cropw = getOption('jcarousel_cropw');
			} else {
				$cropw = sanitize_numeric($cropw);
			}
			if (is_null($croph)) {
				$croph = getOption('jcarousel_croph');
			} else {
				$croph = sanitize_numeric($croph);
			}
			if (is_null($fullimagelink)) {
				$fullimagelink = getOption('jcarousel_fullimagelink');
			}
			if (is_null($vertical)) {
				$vertical = getOption('jcarousel_vertical');
			}
			if ($vertical) {
				$vertical = 'true';
			} else {
				$vertical = 'false';
			}
			if (in_context(SEARCH_LINKED)) {
				if ($_current_search->getNumImages() === 0) {
					$searchimages = false;
				} else {
					$searchimages = true;
				}
			} else {
				$searchimages = false;
			}
			if (in_context(SEARCH_LINKED) && $searchimages) {
				$jcarousel_items = $_current_search->getImages();
			} else {
				$jcarousel_items = $_current_album->getImages();
			}
			if (count($jcarousel_items) >= 2) {
				foreach ($jcarousel_items as $item) {
					if (is_array($item)) {
						$imgobj = newImage($_current_album, $item['filename']);
					} else {
						$imgobj = newImage($_current_album, $item);
					}
					if ($fullimagelink) {
						$link = $imgobj->getFullImageURL();
					} else {
						$link = $imgobj->getLink();
					}
					if (!is_null($_current_image)) {
						if ($_current_album->isDynamic()) {
							if ($_current_image->filename == $imgobj->filename && $_current_image->getAlbum()->name == $imgobj->getAlbum()->name) {
								$active = 'active';
							} else {
								$active = '';
							}
						} else {
							if ($_current_image->filename == $imgobj->filename) {
								$active = 'active';
							} else {
								$active = '';
							}
						}
					} else {
						$active = '';
					}
					$imageurl = $imgobj->getCustomImage(NULL, $width, $height, $cropw, $croph, NULL, NULL, true);
					$items .= ' {url: "' . html_encode($imageurl) . '", title: "' . html_encode($imgobj->getTitle()) . '", link: "' . html_encode($link) . '", active: "' . $active . '"},';
					$items .= "\n";
				}
			}
			$items = substr($items, 0, -2);
			$numimages = getNumImages();
			if (!is_null($_current_image)) {
				$imgnumber = imageNumber();
			} else {
				$imgnumber = 1;
			}
			?>
			<script type="text/javascript">
				// <!-- <![CDATA[
				var mycarousel_itemList = [
			<?php echo $items; ?>
				];

				function mycarousel_itemLoadCallback(carousel, state) {
					for (var i = carousel.first; i <= carousel.last; i++) {
						if (carousel.has(i)) {
							continue;
						}
						if (i > mycarousel_itemList.length) {
							break;
						}
						carousel.add(i, mycarousel_getItemHTML(mycarousel_itemList[i - 1]));
					}
				}

				function mycarousel_getItemHTML(item) {
					if (item.active === "") {
						html = '<a href="' + item.link + '" title="' + item.title + '"><img src="' + item.url + '" width="<?php echo $width; ?>" height="<?php echo $height; ?>" alt="' + item.url + '" /></a>';
					} else {
						html = '<a href="' + item.link + '" title="' + item.title + '"><img class="activecarouselimage" src="' + item.url + '" width="<?php echo $width; ?>" height="<?php echo $height; ?>" alt="' + item.url + '" /></a>';
					}
					return html;
				}

				jQuery(document).ready(function () {
					jQuery("#mycarousel").jcarousel({
						vertical: <?php echo $vertical; ?>,
						size: mycarousel_itemList.length,
						start: <?php echo $imgnumber; ?>,
						scroll: <?php echo $thumbscroll; ?>,
						itemLoadCallback: {onBeforeAnimation: mycarousel_itemLoadCallback}
					});
				});
				// ]]> -->
			</script>
			<ul id="mycarousel">
				<!-- The content will be dynamically loaded in here -->
			</ul>
			<?php
		}
	}

}
