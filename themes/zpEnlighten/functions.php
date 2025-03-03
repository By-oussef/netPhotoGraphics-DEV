<?php

class Utils {

	private function __construct() {

	}

	static function getLatestImages($limit = 2) {
		if (!isset($limit) || !is_numeric($limit)) {
					$limit = 3;
		}
		$t_images = prefix("images");
		$t_albums = prefix("albums");
		$query = "SELECT i.id, i.filename, i.title, a.folder FROM $t_images i " .
						"LEFT JOIN $t_albums a ON i.albumid=a.id " .
						"ORDER BY i.date DESC";
		$result = query($query);
		return filterImageQueryList($result, NULL, $limit, false);
	}

}

/**
 * Prints jQuery JS to enable the toggling of search results of Zenpage  items
 *
 */
function printZDSearchToggleJS() {
	?>
	<script type="text/javascript">
		// <!-- <![CDATA[
		function toggleExtraElements(category, show) {
			if (show) {
				jQuery('.' + category + '_showless').show();
				jQuery('.' + category + '_showmore').hide();
				jQuery('.' + category + '_extrashow').show();
			} else {
				jQuery('.' + category + '_showless').hide();
				jQuery('.' + category + '_showmore').show();
				jQuery('.' + category + '_extrashow').hide();
			}
		}
		// ]]> -->
	</script>
	<?php
}

/**
 * Prints the "Show more results link" for search results for Zenpage items
 *
 * @param string $option "news" or "pages"
 * @param int $number_to_show how many search results should be shown initially
 */
function printZDSearchShowMoreLink($option, $number_to_show) {
	$option = strtolower($option);
	$number_to_show = sanitize_numeric($number_to_show);
	switch ($option) {
		case "news":
			$num = getNumNews();
			break;
		case "pages":
			$num = getNumPages();
			break;
	}
	if ($num > $number_to_show) {
		?>
		<a class="<?php echo $option; ?>_showmore" href="javascript:toggleExtraElements('<?php echo $option; ?>',true);"><?php echo gettext('Show more results'); ?></a>
		<a class="<?php echo $option; ?>_showless" style="display: none;"	href="javascript:toggleExtraElements('<?php echo $option; ?>',false);"><?php echo gettext('Show fewer results'); ?></a>
		<?php
	}
}

/**
 * Adds the css class necessary for toggling of Zenpage items search results
 *
 * @param string $option "news" or "pages"
 * @param string $c After which result item the toggling should begin. Here to be passed from the results loop.
 */
function printZDToggleClass($option, $c, $number_to_show) {
	$option = strtolower($option);
	$c = sanitize_numeric($c);
	$number_to_show = sanitize_numeric($number_to_show);
	if ($c > $number_to_show) {
		echo ' class="' . $option . '_extrashow" style="display:none;"';
	}
}

function printZDRoundedCornerJS() {
	global $_themeroot;
	scriptLoader($_themeroot . '/js/jquery.corner.js');
	?>

	<script type="text/javascript">
		//<!-- <![CDATA[
		window.addEventListener('load', function () {
			$(".album,#slideshowlink a,textarea,#exif_link a").corner("keep 5px");
		}, false);
		// ]]> -->
	</script>
	<?php
}

function my_checkPageValidity($request, $gallery_page, $page) {
	switch ($gallery_page) {
		case 'gallery.php':
			$gallery_page = 'index.php'; //	same as an album gallery index
			break;
		case 'index.php':
			if (extensionEnabled('zenpage')) {
				if (getOption('zenpage_zp_index_news')) {
					$gallery_page = 'news.php'; //	really a news page
					break;
				}
				if (checkForPage(getOption('zenpage_homepage'))) {
					return $page == 1; // only one page if zenpage enabled.
				}
			}
			break;
		case 'news.php':
		case 'album.php':
		case 'favorites.php';
		case 'search.php':
			break;
		default:
			if ($page != 1) {
				return false;
			}
	}
	return checkPageValidity($request, $gallery_page, $page);
}

/**
 * makex news page 1 link go to the index page
 * @param type $link
 * @param type $obj
 * @param type $page
 */
function newsOnIndex($link, $obj, $page) {
	if (is_string($obj) && $obj == 'news.php') {
		if (MOD_REWRITE) {
			if (preg_match('~' . _NEWS_ . '[/\d/]*$~', $link)) {
				$link = WEBPATH;
				if ($page > 1) {
									$link .= '/' . _PAGE_ . '/' . $page;
				}
			}
		} else {
			if (strpos($link, 'category=') === false && strpos($link, 'title=') === false) {
				$link = str_replace('?&', '?', rtrim(str_replace('p=news', '', $link), '?'));
			}
		}
	}
	return $link;
}

//because the theme does not check!
if (!function_exists('getCommentCount')) {

	function getCommentCount() {
		return 0;
	}

}

if (!OFFSET_PATH) {
	enableExtension('print_album_menu', 1 | THEME_PLUGIN, false);
	setOption('user_logout_login_form', 2, false);
	$_current_page_check = 'my_checkPageValidity';
	if (extensionEnabled('zenpage') && getOption('zenpage_zp_index_news')) { // only one index page if zenpage plugin is enabled & displaying
		npgFilters::register('getLink', 'newsOnIndex');
	}
}
?>