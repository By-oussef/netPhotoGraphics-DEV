<?php

require_once(dirname(__FILE__) . '/functions.php');

class ThemeOptions {

	function __construct($setDefaultOptions) {

		$me = basename(dirname(__FILE__));
		setThemeOptionDefault('Allow_search', true);
		setThemeOptionDefault('Allow_cloud', true);
		setThemeOptionDefault('albums_per_page', 6);
		setThemeOptionDefault('albums_per_row', 2);
		setThemeOptionDefault('images_per_page', 20);
		setThemeOptionDefault('images_per_row', 5);
		setThemeOption('image_size', 520, NULL);
		setThemeOption('image_use_side', 'longest', NULL);
		setThemeOptionDefault('thumb_transition', 1);
		setThemeOption('thumb_size', 85, NULL);
		setThemeOptionDefault('thumb_crop_width', 85);
		setThemeOptionDefault('thumb_crop_height', 85);
		setThemeOptionDefault('thumb_crop', 1);
		setThemeOptionDefault('garland_customHome', '');
		setThemeOptionDefault('garland_personality', 'image_page');
		setThemeOptionDefault('garland_transition', 'slide-hori');
		setThemeOptionDefault('garland_caption_location', 'image');
		setThemeOptionDefault('garland_menu', '');
		if (class_exists('cacheManager')) {
			$me = basename(dirname(__FILE__));
			cacheManager::deleteCacheSizes($me);
			cacheManager::addCacheSize($me, 520, NULL, NULL, NULL, NULL, NULL, NULL, false, NULL, NULL, NULL);
			cacheManager::addCacheSize($me, 85, NULL, NULL, getThemeOption('thumb_crop_width'), getThemeOption('thumb_crop_height'), NULL, NULL, true, NULL, NULL, NULL);
		}
		if (function_exists('menuExists') && !menuExists('garland')) {
			$menuitems = array(
					array('type' => 'menulabel', 'title' => getOption('zenpage_news_label'), 'link' => '', 'show' => 1, 'nesting' => 0),
					array('type' => 'menufunction', 'title' => getAllTranslations('All'),
							'link' => 'printAllNewsCategories("All",TRUE,"","menu-active",false,"inner_ul",false,"list",false,getOption("menu_manager_truncate_string"));',
							'show' => 1, 'include_li' => 0, 'nesting' => 1),
					array('type' => 'html', 'title' => getAllTranslations('Articles Rule'), 'link' => '<li class="menu_rule menu_menulabel"></li>', 'show' => 1, 'include_li' => 0, 'nesting' => 0),
					array('type' => 'albumindex', 'title' => getAllTranslations('Gallery'), 'link' => NULL, 'show' => 1, 'nesting' => 0),
					array('type' => 'menufunction', 'title' => getAllTranslations('All'), 'link' => 'printAlbumMenuList("list",NULL,"","menu-active","inner_ul","menu-active","",false,false,false,false,getOption("menu_manager_truncate_string"));', 'show' => 1, 'include_li' => 0, 'nesting' => 1),
					array('type' => 'html', 'title' => getAllTranslations('Gallery Rule'), 'link' => '<li class="menu_rule menu_menulabel"></li>', 'show' => 1, 'include_li' => 0, 'nesting' => 0),
					array('type' => 'menulabel', 'title' => getAllTranslations('Pages'), 'link' => '', 'show' => 1, 'nesting' => 0),
					array('type' => 'menufunction', 'title' => getAllTranslations('All'), 'link' => 'printPageMenu("list","","menu-active","inner_ul","menu-active","",0,false,getOption("menu_manager_truncate_string"));', 'show' => 1, 'include_li' => 0, 'nesting' => 1, getOption("menu_manager_truncate_string")),
					array('type' => 'html', 'title' => getAllTranslations('Pages Rule'), 'link' => '<li class="menu_rule menu_menulabel"></li>', 'show' => 1, 'include_li' => 0, 'nesting' => 0),
			);
			if (class_exists('RSS')) {
				$rssItems = array(
						array('type' => 'menulabel', 'title' => getAllTranslations('RSS'), 'link' => '', 'show' => 1, 'nesting' => 0),
						array('type' => 'dynamiclink', 'title' => getAllTranslations('Gallery'), 'link' => "html_encode(getRSSLink(''))", 'show' => 1, 'nesting' => 1),
						array('type' => 'dynamiclink', 'title' => getOption('zenpage_news_label'), 'link' => "html_encode(getRSSLink('news'))", 'show' => 1, 'nesting' => 1),
						array('type' => 'dynamiclink', 'title' => getAllTranslations('Both'), 'link' => "html_encode(getRSSLink('news')).'&amp;withimages'", 'show' => 1, 'nesting' => 1),
				);
				$menuitems = array_merge($menuitems, $rssItems);
			}
			createMenu($menuitems, 'garland');
		}
	}

	function getOptionsDisabled() {
		return array('thumb_size', 'image_size');
	}

	function getOptionsSupported() {
		global $personalities;
		if (!extensionEnabled('print_album_menu') && (($m = getOption('garland_menu')) == 'garland' || $m == 'zenpage' || $m == 'garland')) {
			$note = '<p class="notebox">' . sprintf(gettext('<strong>Note:</strong> The <em>%s</em> custom menu makes use of the <em>print_album_menu</em> plugin.'), $m) . '</p>';
		} else {
			$note = '';
		}
		$options = array(
				gettext('Theme personality') => array('key' => 'garland_personality', 'type' => OPTION_TYPE_SELECTOR,
						'selections' => $personalities,
						'desc' => gettext('Select the theme personality')),
				gettext('Allow search') => array('key' => 'Allow_search', 'type' => OPTION_TYPE_CHECKBOX, 'desc' => gettext('Set to enable search form.')),
				gettext('Allow cloud') => array('key' => 'Allow_cloud', 'type' => OPTION_TYPE_CHECKBOX, 'desc' => gettext('Set to enable tag cloud for album page.')),
				gettext('Custom menu') => array('key' => 'garland_menu', 'type' => OPTION_TYPE_CUSTOM, 'desc' => gettext('Set this to the <em>menu_manager</em> menu you wish to use.') . $note)
		);
		if (extensionEnabled('zenpage')) {
			global $_CMS;
			$pages = $_CMS->getPages(false);

			$list = array();
			foreach ($pages as $page) {
				$pageObj = newPage($page['titlelink']);
				if (!$pageObj->getShow()) {
					$list[getBare(get_language_string($page['title']))] = $page['titlelink'];
				}
			}
			$options[gettext('Custom Homepage')] = array('key' => 'garland_customHome', 'type' => OPTION_TYPE_SELECTOR,
					'selections' => $list,
					'null_selection' => gettext('none'),
					'desc' => gettext('Select the <em>pages</em> titlelink for the home page. Only unpublished pages are offered for selection.'));
		}
		if (getOption('garland_personality') == 'image_gallery') {
			$options[gettext('Image gallery transition')] = array('key' => 'garland_transition', 'type' => OPTION_TYPE_SELECTOR,
					'selections' => array(gettext('None') => '', gettext('Fade') => 'fade', gettext('Shrink/grow') => 'resize', gettext('Horizontal') => 'slide-hori', gettext('Vertical') => 'slide-vert'),
					'order' => 10,
					'desc' => gettext('Transition effect for Image gallery'));
			$options[gettext('Image gallery caption')] = array('key' => 'garland_caption_location', 'type' => OPTION_TYPE_RADIO,
					'buttons' => array(gettext('On image') => 'image', gettext('Separate') => 'separate', gettext('Omit') => 'none'),
					'order' => 10.5,
					'desc' => gettext('Location for Image gallery picture caption'));
		}
		return $options;
	}

	function handleOption($option, $currentValue) {
		switch ($option) {
			case 'garland_menu':
				$menusets = array($currentValue => $currentValue);
				echo '<select id="garland_menuset" name="garland_menu"';
				if (function_exists('printCustomMenu') && extensionEnabled('zenpage')) {
					$result = query_full_array("SELECT DISTINCT menuset FROM " . prefix('menu') . " ORDER BY menuset");
					foreach ($result as $set) {
						$menusets[$set['menuset']] = $set['menuset'];
					}
				} else {
					echo ' disabled="disabled"';
				}
				echo ">\n";
				echo '<option value="" style="background-color:LightGray">' . gettext('*standard menu') . '</option>';
				generateListFromArray(array($currentValue), $menusets, false, false);
				echo "</select>\n";
				break;
		}
	}

}

?>