<?php

/*
 *
 * This plugin provides a mechanism to mechanically translate Titles, Descriptions, and CMS content
 * of an object. Checkboxes are placed on the object's edit page for each of these
 * translatable properties. If the box is checked, the text associated with the user's locale (e.g. <code>%LOCALE%</code>)
 * will be translated into the other enabled languages. (You should carefully consider what languages
 * to enable as the translation process is resource intensive.)
 *
 * The plugin uses the Google Translation API.
 * It is based on the {@link https://statickidz.com/ Statickidz} GoogleTranslator class by
 * Adrián Barrio Andrés and Paris N. Baltazar Salguero.
 * Source language text is limited to 5000 characters. The
 * PHP <code>Curl</code> extension must be enabled.
 *
 * <b>Note:</b> Mechanical translations such as supplied by this plugin are intended as
 * a starting point. They may not accurately represent the content translated nor may they be
 * gramatically proper.
 *
 * In particular, HTML tags may be mishandled. HTML comments are especially prone to being broken. The successful rendering
 * of other HTML tags seems to be destination language dependent.
 *
 * @Copyright 2019 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics and derivatives}
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/googleTranslate
 * @pluginCategory development
 */

$plugin_is_filter = 5 | ADMIN_PLUGIN;
$plugin_description = gettext('Provides a Google translation facility.');
$plugin_disable = !function_exists('curl_version') ? gettext('The PHP <em>Curl</em> extension must be enabled for this plugin to function.') : false;

require_once(SERVERPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER . '/googleTranslate/GoogleTranslate.php');

zp_register_filter('edit_cms_utilities', 'translator::cms_utilities');
zp_register_filter('save_article_custom_data', 'translator::save');
zp_register_filter('save_page_custom_data', 'translator::save');
zp_register_filter('save_category_custom_data', 'translator::save');

zp_register_filter('edit_album_utilities', 'translator::media_utilities');
zp_register_filter('edit_image_utilities', 'translator::media_utilities');
zp_register_filter('save_album_utilities_data', 'translator::save');
zp_register_filter('save_image_utilities_data', 'translator::save');

use \Statickidz\GoogleTranslate;

$trans = new GoogleTranslate();

class translator {

	static function doTranslation($sourceLocale, $obj, $field) {
		global $trans;
		$active_languages = array_flip(i18n::generateLanguageList());
		unset($active_languages[$sourceLocale]);
		$getField = 'get' . $field;
		$source = $obj->$getField();
		$text = get_language_string($source, $sourceLocale);
		if ($text) { //	don' t bother if there is no text
			$translations = array($sourceLocale => $text);
			foreach ($active_languages as $target => $l) {
				$translations[$target] = $trans->translate($sourceLocale, $target, $text);
			}
			$setField = 'set' . $field;
			$obj->$setField(serialize($translations));
		}
	}

	static function cms_utilities($before, $object) {
		if ($before) {
			$before .= '<hr />';
		}
		$output = '<p class="checkbox">' . "\n" . '<label>' . "\n" .
						'<input type="checkbox" name="translateTitle' . '" id="translateTitle' .
						'" value="1" /> ' . gettext('Translate Title') . "\n</label>\n</p>\n";
		if (get_class($object) == 'Category') {
			$output .= '<p class="checkbox">' . "\n" . '<label>' . "\n" .
							'<input type="checkbox" name="translateDesc' . '" id="translateDesc' .
							'" value="1" /> ' . gettext('Translate Description') . "\n</label>\n</p>\n";
		} else {
			$output .= '<p class="checkbox">' . "\n" . '<label>' . "\n" .
							'<input type="checkbox" name="translateContent' . '" id="translateContent' .
							'" value="1" /> ' . gettext('Translate Content') . "\n</label>\n</p>\n";
			$fields = db_list_fields($object->table);
			if (isset($fields['extracontent'])) {
				$output .= '<p class="checkbox">' . "\n" . '<label>' . "\n" .
								'<input type="checkbox" name="translateExtraContent' . '" id="translateExtraContent' .
								'" value="1" /> ' . gettext('Translate Extra Content') . "\n</label>\n</p>\n";
			}
		}
		return $before . $output;
	}

	static function save($custom, $obj) {
		$sourceLocale = i18n::getUserLocale();
		if (isset($_POST['translateTitle'])) {
			translator::doTranslation($sourceLocale, $obj, 'title');
		}
		if (isset($_POST['translateContent'])) {
			translator::doTranslation($sourceLocale, $obj, 'Content');
		}
		if (isset($_POST['translateExtraContent'])) {
			translator::doTranslation($sourceLocale, $obj, 'ExtraContent');
		}
		if (isset($_POST['translateDesc'])) {
			translator::doTranslation($sourceLocale, $obj, 'Desc');
		}
	}

	static function media_utilities($before, $object, $prefix = NULL) {
		if ($before) {
			$before .= '<hr />';
		}
		$output = '<label>' . "\n" .
						'<input type="checkbox" name="translateTitle' . $prefix . '" id="translateTitle' . $prefix .
						'" value="1" /> ' . gettext('Translate Title') . "\n</label>\n";
		$output .= '<label><br />' . "\n" .
						'<input type="checkbox" name="translateDesc' . $prefix . '" id="translateDesc' . $prefix .
						'" value="1" /> ' . gettext('Translate Description') . "\n</label>\n";
		return $before . $output;
	}

}
