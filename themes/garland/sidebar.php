<?php
// force UTF-8 Ø

if (getOption('Allow_search')) {
	switch ($_gallery_page) {
		case 'album.php':
		case 'image.php':
			$list = array('albums' => array($_current_album->name), 'pages' => '0', 'news' => '0');
			$text = gettext('Search within album');
			break;
		case 'gallery.php':
			$list = array('albums' => '1', 'pages' => '0', 'news' => '0');
			$text = gettext('Search albums');
			break;
		case 'pages.php':
			$list = array('albums' => '0', 'pages' => '1', 'news' => '0');
			$text = gettext('Search pages');
			break;
		case 'news.php':
			if (is_NewsCategory()) {
				$list = array('news' => array($_CMS_current_category->getTitlelink()), 'albums' => '0', 'images' => '0', 'pages' => '0');
				$text = gettext('Search category');
			} else {
				$list = array('news' => '1', 'albums' => '0', 'images' => '0', 'pages' => '0');
				$text = gettext("Search");
			}
			break;
		case 'search.php':
			$categorylist = $_current_search->getCategoryList();
			if (is_array($categorylist)) {
				$list = array('news' => $categorylist, 'albums' => '0', 'images' => '0', 'pages' => '0');
				$text = gettext('Search within category');
			} else {
				$albumlist = $_current_search->getAlbumList();
				if (is_array($albumlist)) {
					$list = array('albums' => $albumlist, 'pages' => '0', 'news' => '0');
					$text = gettext('Search within album');
				} else {
					$list = NULL;
					$text = gettext('Search gallery');
				}
			}
			break;
		default:
			$list = NULL;
			$text = gettext('Search gallery');
			break;
	}
	printSearchForm(NULL, 'search', $_themeroot . '/images/search.png', $text, NULL, NULL, $list);
	?>
	<br class="clearall">
	<?php
}

if (function_exists('printCustomMenu') && ($menu = getOption('garland_menu'))) {
	?>
	<!-- custom menu -->
	<div class="menu">
		<?php
		printCustomMenu($menu, 'list', '', "menu-active", "submenu", "menu-active", 2);
		?>
	</div>
	<?php
} else { //	"standard zenpage sidebar menus
	?>
	<!-- standard menu -->	<?php
	if (extensionEnabled('zenpage')) {
		if (hasNews()) {
			?>
			<div class="menu">
				<h3><?php echo NEWS_LABEL; ?></h3>
				<?php
				printAllNewsCategories(gettext("All"), TRUE, "news_menu", "menu", true, "menu_sub", "menu_sub_active");
				?>
			</div>
			<?php
		}
	}
	?>

	<?php
	if (function_exists("printAlbumMenu")) {
		?>
		<div class="menu">
			<?php
			if (extensionEnabled('zenpage') && $_gallery_page != 'gallery.php') {
				?>
				<h3>
					<a href="<?php echo html_encode(getCustomPageURL('gallery')); ?>" title="<?php echo gettext('Album index'); ?>"><?php echo gettext("Gallery"); ?></a>
				</h3>
				<?php
			} else {
				?>
				<h3><?php echo gettext("Gallery"); ?></h3>
				<?php
			}
			printAlbumMenu("list", "count", "album_menu", "menu", "menu_sub", "menu_sub_active", '');
			?>
		</div>
		<?php
	} else {
		if (extensionEnabled('zenpage')) {
			?>
			<div class="menu">
				<h3><?php echo gettext("Albums"); ?></h3>
				<ul id="album_menu">
					<li>
						<a href="<?php echo html_encode(getCustomPageURL('gallery')); ?>" title="<?php echo gettext('Album index'); ?>"><?php echo gettext('Gallery'); ?></a>
					</li>
				</ul>
			</div>
			<?php
		}
	}
	?>

	<?php
	if (extensionEnabled('zenpage')) {
		if (hasPages()) {
			?>
			<div class="menu">
				<h3><?php echo gettext("Pages"); ?></h3>
				<?php
				printPageMenu("list", "page_menu", "menu-active", "submenu", "menu-active");
				?>
			</div>
			<?php
		}
	}
}
?>