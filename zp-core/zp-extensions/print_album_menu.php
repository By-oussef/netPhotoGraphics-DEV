<?php
/**
 * Prints a list of all albums context sensitive.
 *
 * Menu types:
 * 	<ul>
 * 			<li><var>list</var> for HTML list</li>
 * 			<li><var>list-top</var> for only the top level albums</li>
 * 			<li><var>omit-top</var> same as list, but the first level of albums is omitted</li>
 * 			<li><var>list-sub</var> lists the offspring level of subalbums for the current album</li>
 * 			<li><var>jump</var> dropdown menu of all albums(not context sensitive)</li>
 * 	</ul>
 *
 * Call the function <var>printAlbumMenu()</var> at the point where you want the menu to appear.
 *
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @package plugins/print_album_menu
 * @pluginCategory theme
 */
$plugin_description = gettext("Adds a theme function to print an album menu either as a nested list or as a dropdown menu.");

$option_interface = 'print_album_menu';

define('ALBUM_MENU_COUNT', getOption('print_album_menu_count'));
define('ALBUM_MENU_SHOWSUBS', getOption('print_album_menu_showsubs'));

$_albums_visited_albumMenu = array();

/**
 * Plugin option handling class
 *
 */
class print_album_menu {

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('print_album_menu_showsubs', 0);
			setOptionDefault('print_album_menu_count', 1);
		}
	}

	function getOptionsSupported() {
		$options = array(gettext('"List" subalbum level') => array('key' => 'print_album_menu_showsubs', 'type' => OPTION_TYPE_NUMBER,
						'order' => 0,
						'desc' => gettext('The depth of subalbum levels shown with the <code>printAlbumMenu</code> and <code>printAlbumMenuList</code> “List” option. Note: themes may override this default.')),
				gettext('Show counts') => array('key' => 'print_album_menu_count', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 1,
						'desc' => gettext('If checked, image and album counts will be included in the list. Note: Themes may override this option.')),
				gettext('Truncate titles*') => array('key' => 'menu_truncate_string', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 6,
						'desc' => gettext('Limit titles to this many characters. Zero means no limit.')),
				gettext('Truncate indicator*') => array('key' => 'menu_truncate_indicator', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 7,
						'desc' => gettext('Append this string to truncated titles.'))
		);

		$options['note'] = array('key' => 'menu_truncate_note',
				'type' => OPTION_TYPE_NOTE,
				'order' => 8,
				'desc' => gettext('<p class="notebox">*<strong>Note:</strong> These options are shared among <em>menu_manager</em>, <em>print_album_menu</em>, and <em>zenpage</em>.</p>'));

		return $options;
	}

	function handleOption($option, $currentValue) {

	}

}

/**
 * Prints a list of all albums context sensitive.
 * Since 1.4.3 this is a wrapper function for the separate functions printAlbumMenuList() and printAlbumMenuJump().
 * that was included to remain compatiblility with older installs of this menu.
 *
 * Usage: add the following to the php page where you wish to use these menus:
 * enable this extension on the admin plugins tab.
 * Call the function printAlbumMenu() at the point where you want the menu to appear.
 *
 * @param string $option
 * 									"list" for html list,
 * 									"list-top" for only the top level albums,
 * 									"omit-top" same as list, but the first level of albums is omitted
 * 									"list-sub" lists the offspring level of subalbums for the current album
 * 									"jump" dropdown menu of all albums(not context sensitive)
 *
 * @param bool $showcount true for a image counter or subalbum count in brackets behind the album name, false for no image numbers or leave blank
 * @param string $css_id insert css id for the main album list, leave empty if you don't use (only list mode)
 * @param string $css_class_topactive insert css class for the active link in the main album list (only list mode)
 * @param string $css_class insert css class for the sub album lists (only list mode)
 * @param string $css_class_active insert css class for the active link in the sub album lists (only list mode)
 * @param string $indexname insert the name how you want to call the link to the gallery index (insert "" if you don't use it, it is not printed then)
 * @param int C Set to depth of sublevels that should be shown always. 0 by default. To show all, set to a true! Only valid if option=="list".
 * @param int $showsubs Set to depth of sublevels that should be shown always. 0 by default. To show all, set to a true! Only valid if option=="list".
 * @param bool $firstimagelink If set to TRUE and if the album has images the link will point to page of the first image instead the album thumbnail page
 * @param bool $keeptopactive If set to TRUE the toplevel album entry will stay marked as active if within its subalbums ("list" only)
 * @param int $limit truncation of display text
 * @since 1.2
 */
function printAlbumMenu($option, $showcount = NULL, $css_id = '', $css_class_topactive = '', $css_class = '', $css_class_active = '', $indexname = "Gallery Index", $showsubs = NULL, $firstimagelink = false, $keeptopactive = false) {
	if ($option == "jump") {
		printAlbumMenuJump($showcount, $indexname, $firstimagelink, $showsubs);
	} else {
		printAlbumMenuList($option, $showcount, $css_id, $css_class_topactive, $css_class, $css_class_active, $indexname, $showsubs, $firstimagelink, $keeptopactive);
	}
}

/**
 * Prints a nested html list of all albums context sensitive.
 *
 * Usage: add the following to the php page where you wish to use these menus:
 * enable this extension on the admin plugins tab;
 * Call the function printAlbumMenuList() at the point where you want the menu to appear.
 *
 * @param string $option
 * 									"list" for html list,
 * 									"list-top" for only the top level albums,
 * 									"omit-top" same as list, but the first level of albums is omitted
 * 									"list-sub" lists the offspring level of subalbums for the current album
 * @param bool $showcount true for a image counter in brackets behind the album name, false for no image numbers or leave empty
 * @param string $css_id insert css id for the main album list, leave empty if you don't use (only list mode)
 * @param string $css_id_active insert css class for the active link in the main album list (only list mode)
 * @param string $css_class insert css class for the sub album lists (only list mode)
 * @param string $css_class_active insert css class for the active link in the sub album lists (only list mode)
 * @param string $indexname insert the name (default "Gallery Index") how you want to call the link to the gallery index, insert "" if you don't use it, it is not printed then.
 * @param int $showsubs Set to depth of sublevels that should be shown always. 0 by default. To show all, set to a true! Only valid if option=="list".
 * @param bool $firstimagelink If set to TRUE and if the album has images the link will point to page of the first image instead the album thumbnail page
 * @param bool $keeptopactive If set to TRUE the toplevel album entry will stay marked as active if within its subalbums ("list" only)
 * @param bool $startlist set to true to output the UL tab (false automatically if you use 'omit-top' or 'list-sub')
 * @param int $limit truncation of display text
 * @return html list of the albums
 */
function printAlbumMenuList($option, $showcount = NULL, $css_id = '', $css_class_topactive = '', $css_class = '', $css_class_active = '', $indexname = "Gallery Index", $showsubs = NULL, $firstimagelink = false, $keeptopactive = false, $startlist = true, $limit = NULL) {
	global $_gallery, $_current_album, $_gallery_page;
	// if in search mode don't use the foldout contextsensitiveness and show only toplevel albums
	if (in_context(SEARCH_LINKED)) {
		$option = "list-top";
	}

	$albumpath = rewrite_path("/", "/index.php?album=");
	if (empty($_current_album) || ($_gallery_page != 'album.php' && $_gallery_page != 'image.php')) {
		$currentfolder = "";
	} else {
		$currentfolder = $_current_album->name;
	}

	if (is_null($css_id)) {
		$css_id = 'menu_albums';
	}
	if (is_null($css_class_topactive)) {
		$css_class_topactive = 'menu_topactive';
	}
	if (is_null($css_class)) {
		$css_class = 'submenu';
	}
	if (is_null($css_class_active)) {
		$css_class_active = 'menu-active';
	}

	$startlist = $startlist && !($option == 'omit-top' || $option == 'list-sub');
	if ($startlist) {
			echo '<ul id="' . $css_id . '">' . "\n";
	}
	// top level list
		/*		 * ** Top level start with Index link  *** */
	if ($option === "list" OR $option === "list-top") {
		if (!empty($indexname)) {
			echo "<li><a href='" . html_encode(getGalleryIndexURL()) . "' title='" . html_encode($indexname) . "'>" . $indexname . "</a></li>";
		}
	}

	if ($option == 'list-sub' && in_context(NPG_ALBUM)) {
		$albums = $_current_album->getAlbums();
	} else {
		$albums = $_gallery->getAlbums();
	}

	printAlbumMenuListAlbum($albums, $currentfolder, $option, $showcount, $showsubs, $css_class, $css_class_topactive, $css_class_active, $firstimagelink, $keeptopactive, $limit);

	if ($startlist) {
			echo "</ul>\n";
	}
	}

/**
 * Handles an album for printAlbumMenuList
 *
 * @param array $albums albums array
 * @param string $folder
 * @param string $option see printAlbumMenuList
 * @param string $showcount see printAlbumMenuList
 * @param int $showsubs see printAlbumMenuList
 * @param string $css_class see printAlbumMenuList
 * @param string $css_class_topactive see printAlbumMenuList
 * @param string $css_class_active see printAlbumMenuList
 * @param bool $firstimagelink If set to TRUE and if the album has images the link will point to page of the first image instead the album thumbnail page
 * @param bool $keeptopactive If set to TRUE the toplevel album entry will stay marked as active if within its subalbums ("list" only)
 * @param int $limit truncation of display text
 */
function printAlbumMenuListAlbum($albums, $folder, $option, $showcount, $showsubs, $css_class, $css_class_topactive, $css_class_active, $firstimagelink, $keeptopactive, $limit = NULL) {
	global $_gallery, $_current_album, $_current_search, $_albums_visited_albumMenu;
	if (is_null($limit)) {
		$limit = MENU_TRUNCATE_STRING;
	}
	if (is_null($showcount)) {
		$showcount = ALBUM_MENU_COUNT;
	}
	if (is_null($showsubs)) {
		$showsubs = ALBUM_MENU_SHOWSUBS;
	}
	if ($showsubs && !is_numeric($showsubs)) {
		$showsubs = 9999999999;
	}
	$pagelevel = count(explode('/', $folder));
	$currenturalbumname = "";

	foreach ($albums as $album) {

		$level = count(explode('/', $album));
		$process = (($level < $showsubs && $option == "list") // user wants all the pages whose level is <= to the parameter
						|| ($option != 'list-top' // not top only
						&& strpos($folder, $album) === 0 // within the family
						&& $level <= $pagelevel) // but not too deep\
						);

		if ($process && hasDynamicAlbumSuffix($album) && !is_dir(ALBUM_FOLDER_SERVERPATH . $album)) {
			if (in_array($album, $_albums_visited_albumMenu)) {
							$process = false;
			}
			// skip already seen dynamic albums
		}
		$albumobj = newAlbum($album, true);
		$has_password = '';
		if ($albumobj->isProtected()) {
			$has_password = ' has_password';
		}
		if ($level > 1 || ($option != 'omit-top')) { // listing current level album
			if ($level == 1) {
				$css_class_t = $css_class_topactive . $has_password;
			} else {
				$css_class_t = $css_class_active . $has_password;
			}
			if ($keeptopactive) {
				if (isset($_current_album) && is_object($_current_album)) {
					$currenturalbum = getUrAlbum($_current_album);
					$currenturalbumname = $currenturalbum->name;
				}
			}
			$count = "";
			if ($showcount) {
				$toplevelsubalbums = $albumobj->getAlbums();
				$toplevelsubalbums = count($toplevelsubalbums);
				$topalbumnumimages = $albumobj->getNumImages();
				if ($topalbumnumimages + $toplevelsubalbums > 0) {
					$count = ' <span style="white-space:nowrap;"><small>(';
					if ($toplevelsubalbums > 0) {
						$count .= sprintf(ngettext('%u album', '%u albums', $toplevelsubalbums), $toplevelsubalbums);
					}
					if ($topalbumnumimages > 0) {
						if ($toplevelsubalbums) {
							$count .= ' ';
						}
						$count .= sprintf(ngettext('%u image', '%u images', $topalbumnumimages), $topalbumnumimages);
					}
					$count .= ')</small></span>';
				}
			}

			if ((in_context(NPG_ALBUM) && !in_context(SEARCH_LINKED) && (@$_current_album->getID() == $albumobj->getID() ||
							$albumobj->name == $currenturalbumname)) ||
							(in_context(SEARCH_LINKED)) && ($a = $_current_search->getDynamicAlbum()) && $a->name == $albumobj->name) {
				$current = $css_class_t;
			} else {
				$current = "";
			}
			$title = $albumobj->getTitle();
			if ($limit) {
				$display = shortenContent($title, $limit, MENU_TRUNCATE_INDICATOR);
			} else {
				$display = $title;
			}
			if ($firstimagelink && $albumobj->getNumImages() != 0) {
				$link = '<li><a class="' . $current . '" href="' . html_encode($albumobj->getImage(0)->getLink()) . '" title="' . html_encode($title) . '">' . html_encode($display) . '</a>' . $count;
			} else {
				$link = '<li><a class="' . $current . '" href="' . html_encode($albumobj->getLink(1)) . '" title="' . html_encode($title) . '">' . html_encode($display) . '</a>' . $count;
			}
			echo $link;
		}
		if ($process) { // listing subalbums
			$subalbums = $albumobj->getAlbums();
			if (!empty($subalbums)) {
				echo "\n" . '<ul class="' . $css_class . '">' . "\n";
				array_push($_albums_visited_albumMenu, $album);
				printAlbumMenuListAlbum($subalbums, $folder, $option, $showcount, $showsubs, $css_class, $css_class_topactive, $css_class_active, $firstimagelink, false, $limit);
				array_pop($_albums_visited_albumMenu);
				echo "\n</ul>\n";
			}
		}
		if ($option == 'list' || $option == 'list-top' || $level > 1) { // close the LI
			echo "\n</li>\n";
		}
	}
}

/**
 * Prints a dropdown menu of all albums(not context sensitive)
 * Is used by the wrapper function printAlbumMenu() if the options "jump" is choosen. For standalone use, too.
 *
 * Usage: add the following to the php page where you wish to use these menus:
 * enable this extension on the admin plugins tab;
 * Call the function printAlbumMenuJump() at the point where you want the menu to appear.
 *
 * @param string $option "count" for a image counter in brackets behind the album name, "" = for no image numbers
 * @param string $indexname insert the name (default "Gallery Index") how you want to call the link to the gallery index, insert "" if you don't use it, it is not printed then.
 * @param bool $firstimagelink If set to TRUE and if the album has images the link will point to page of the first image instead the album thumbnail page
 * @param string $css_class see printAlbumMenuList
 * @param bool $skipform If set to false this prints a full form option select list (default), if set to true it will only print the options
 */
function printAlbumMenuJump($option = "count", $indexname = "Gallery Index", $firstimagelink = false, $showsubs = NULL, $skipform = false) {
	global $_gallery, $_current_album, $_gallery_page;
	if (!is_null($_current_album) || $_gallery_page == 'album.php') {
		$currentfolder = $_current_album->name;
	}
	if (is_null($showsubs)) {
		$showsubs = ALBUM_MENU_SHOWSUBS;
	}
	if ($showsubs && !is_numeric($showsubs)) {
		$showsubs = 9999999999;
	}
	$albums = getNestedAlbumList(null, $showsubs);

	if (!$skipform) {
		?>
		<form name="AutoListBox" action="#">
			<p>
				<select name="ListBoxURL" size="1" onchange="npg_gotoLink(this.form);">
					<?php
					if (!empty($indexname)) {
						$selected = checkSelectedAlbum("", "index");
						?>
						<option <?php echo $selected; ?> value="<?php echo html_encode(getGalleryIndexURL()); ?>"><?php echo $indexname; ?></option>
						<?php
					}
				}

				foreach ($albums as $album) {
					$albumobj = newAlbum($album['name'], true);
					$count = '';
					if ($option == "count") {
						$numimages = $albumobj->getNumImages();
						if ($numimages != 0) {
							$count = " (" . $numimages . ")";
						}
					}
					$sortorder = count($album['sort_order']);
					$arrow = '';
					if ($sortorder > 1) {
						for ($c = 1; $c != $sortorder; $c++) {
							$arrow .= '» ';
						}
					}
					$selected = checkSelectedAlbum($albumobj->name, "album");
					if ($firstimagelink && $numimages != 0) {
						$link = "<option $selected value='" . html_encode($albumobj->getImage(0)->getLink()) . "'>" . $arrow . getBare($albumobj->getTitle()) . $count . "</option>";
					} else {
						$link = "<option $selected value='" . html_encode($albumobj->getLink(1)) . "'>" . $arrow . getBare($albumobj->getTitle()) . $count . "</option>";
					}
					echo $link;
				}
				if (!$skipform) {
					?>
				</select>
			</p>
		</form>
		<?php
	}
}

/**
 * A printAlbumMenu() helper function for the jump menu mode of printAlbumMenu() that only
 * checks which the current album so that the entry in the in the dropdown jump menu can be selected
 * Not for standalone use.
 *
 * @param string $checkalbum The album folder name to check
 * @param string $option "index" for index level, "album" for album level
 * @return string returns nothing or "selected"
 */
function checkSelectedAlbum($checkalbum, $option) {
	global $_current_album, $_gallery_page;
	if (is_object($_current_album)) {
		$currentalbumname = $_current_album->name;
	} else {
		$currentalbumname = "";
	}
	$selected = "";
	switch ($option) {
		case "index":
			if ($_gallery_page === "index.php") {
				$selected = "selected";
			}
			break;
		case "album":
			if ($currentalbumname === $checkalbum) {
				$selected = "selected";
			}
			break;
	}
	return $selected;
}
?>