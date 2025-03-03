<?php

/**
 * This plugin is an example demonstrating the use of the various <i>image_html</i> filters.
 * Each effect is defined by a text file these sections (no individual section is required).
 *
 * <var><source>...</source></var><br>
 *  Documents the source of the effect
 *
 * <var><head>...</head></var><br>
 * 	Each effect will have a head section that contains the HTML to be emitted in the theme head.
 *
 * <var><class>...</class></var><br>
 * 	Elements from this section will be added to the class element of the <var><img src... /></var> tag.
 *
 * <var><extra>..</extra></var><br>
 * 	It is also possible to create a extra section for html that will be inserted into the
 * 	<var><img src... /></var> just befor the <var>/></var>. This can be used to insert <var>style="..."</var> or other elements.
 *
 * <var><validate><i>file path</i></validate></var><br>
 * 	Used specially for Effenberger effects, but applicable to similar situations. Causes the
 * 	named file to have its presence tested. If it is not present, the "effect" is not made available.
 * 	Multiple files are separated by semicolons. Each is tested.
 *
 * The following tokens are available to represent paths:
 * 	<ul>
 * 		<li><var>&#37;WEBPATH&#37;</v> to represent the WEB path to the installation.</li>
 * 		<li><var>&#37;SERVERPATH&#37;</var> to represent the server path to the installation.</li>
 * 		<li><var>&#37;PLUGIN_FOLDER&#37;</var> to represent the "extensions" folder.</li>
 * 		<li><var>&#37;USER_PLUGIN_FOLDER&#37;</var> to represent the root "plugin" folder.</li>
 * 		<li><var>&#37;CORE_FOLDER&#37;</var> to represent the "core" folder.</li>
 * </ul>
 *
 * Pixastic effects:<br>
 * 	Included standard are several effects from {@link http://www.pixastic.com/ Pixastic}. Please visit their site for
 * 	detailed documentation.
 *
 * Effenberger effects::<br>
 * 	Some effects which can be used are the javascript effects created by Christian Effenberger
 * 	which may be downloaded from his {@link http://www.netzgesta.de/cvi/ site}:
 *
 * 	<b>Note:</b> No effenberger effects are distributed with this plugin to conform with CVI licensing constraints.
 * 	To add an effect to the plugin, download the zip file from the site. Extract the effect
 * 	files and place in the image_effects folder (in the global plugins folder.)
 *
 * 	For instance, to add the <i>Reflex effect</i>, download <var>reflex.zip</var>, unzip it, and place <var>reflex.js</var>
 * 	in the folder. Check the image_effects foder in the extensions to see if there is already
 * 	header file. If not create one in the <var>plugins/image_effects</var> folder. Use as a model one of the other
 * 	Effenberger effects header files.
 *
 * 	Included are some typical Effenberger files as examples. Simply provide the appropriate js script as above
 * 	to activate them.
 *
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/image_effects
 * @pluginCategory media
 */
$plugin_description = gettext('Attaches “Image effects” to images and thumbnails.');

$option_interface = 'image_effects';

npgFilters::register('standard_image_html', 'image_effects::std_images');
npgFilters::register('custom_image_html', 'image_effects::custom_images');
npgFilters::register('standard_album_thumb_html', 'image_effects::std_album_thumbs');
npgFilters::register('standard_image_thumb_html', 'image_effects::std_image_thumbs');
npgFilters::register('custom_album_thumb_html', 'image_effects::custom_album_thumbs');

if (defined('OFFSET_PATH') && OFFSET_PATH == 0) {
	npgFilters::register('theme_body_close', 'image_effects::effectsJS');
}

class image_effects {

	var $effects = array();

	function __construct() {
		$effect = getPluginFiles('*.txt', 'image_effects');
		$this->effects = array_keys($effect);
		if (OFFSET_PATH == 2) {
			$list = array();
			$options = getOptionsLike('image_effects_random_');
			foreach ($options as $option => $value) {
				if ($value) {
					$effect = str_replace('image_effects_random_', '', $option);
					$list[$effect] = $effect;
				}
				purgeOption($option);
			}
			setOptionDefault('image_effects_random', serialize($list));
		}
	}

	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {


		$list = array('random' => '!');
		$rand = $docs = array();
		$effenberger = array('bevel', 'corner', 'crippleedge', 'curl', 'filmed', 'glossy', 'instant', 'reflex', 'slided', 'sphere');
		foreach ($this->effects as $effect) {
			$rand[$effect] = $effect;
			$effectdata = image_effects::getEffect($effect);
			$list[$effect] = $effect;
			$docs[chr(0) . $effect] = array('key' => 'image_effect_' . $effect, 'order' => $effect, 'type' => OPTION_TYPE_CUSTOM);
			if (array_key_exists('source', $effectdata)) {
				$docs[chr(0) . $effect]['desc'] = $effectdata['source'];
			} else {
				$docs[chr(0) . $effect]['desc'] = gettext('No acknowledgement');
			}
			if (array_key_exists('error', $effectdata)) {
				$docs[chr(0) . $effect]['desc'] .= '<p class="notebox">' . $effectdata['error'] . '</p>';
				query('DELETE FROM ' . prefix('options') . ' WHERE `name`="image_custom_random_' . $effect . '"');
				if (in_array($effect, $effenberger)) {
					$docs[chr(0) . $effect]['desc'] .= '<p class="notebox">' . gettext('<strong>Note:</strong> Although this plugin supports <em>Effenberger effects</em>, due to licensing considerations no <em>Effenberger effects</em> scripts are included. See <a href="http://www.netzgesta.de/cvi/">CVI Astonishing Image Effects</a> by Christian Effenberger to select and download effects.') . '</p>';
				}
			}
		}
		if (count($list) == 0) {
			return array(gettext('No effects') => array('key' => 'image_effect_none', 'type' => OPTION_TYPE_CUSTOM,
							'desc' => ''));
		}
		foreach (array('image_std_images', 'image_custom_images', 'image_std_image_thumbs', 'image_std_album_thumbs', 'image_custom_image_thumbs', 'image_custom_album_thumbs') as $option) {
			$effect = getOption($option);
			if ($effect && $effect != '!' && !array_key_exists($effect, $list)) {
				$error[$effect] = '<p class="errorbox">' . sprintf(gettext('<strong>Error:</strong> <em>%s</em> effect no longer exists.'), $effect) . '</p>';
			}
		}
		$std = array(gettext('Images (standard)') => array('key' => 'image_std_images', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 0,
						'selections' => $list, 'null_selection' => gettext('none'),
						'desc' => gettext('Apply <em>effect</em> to images shown via the <code>printDefaultSizedImage()</code> function.')),
				gettext('Images (custom)') => array('key' => 'image_custom_images', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 0,
						'selections' => $list, 'null_selection' => gettext('none'),
						'desc' => gettext('Apply <em>effect</em> to images shown via the custom image functions.')),
				gettext('Image thumbnails (standard)') => array('key' => 'image_std_image_thumbs', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 0,
						'selections' => $list, 'null_selection' => gettext('none'),
						'desc' => gettext('Apply <em>effect</em> to images shown via the <code>printImageThumb()</code> function.')),
				gettext('Album thumbnails (standard)') => array('key' => 'image_std_album_thumbs', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 0,
						'selections' => $list, 'null_selection' => gettext('none'),
						'desc' => gettext('Apply <em>effect</em> to images shown via the <code>printAlbumThumbImage()</code> function.')),
				gettext('Image thumbnails (custom)') => array('key' => 'image_custom_image_thumbs', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 0,
						'selections' => $list, 'null_selection' => gettext('none'),
						'desc' => gettext('Apply <em>effect</em> to images shown via the custom image functions when <em>thumbstandin</em> is set.')),
				gettext('Album thumbnails  (custom)') => array('key' => 'image_custom_album_thumbs', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 0,
						'selections' => $list, 'null_selection' => gettext('none'),
						'desc' => gettext('Apply <em>effect</em> to images shown via the <code>printCustomAlbumThumbImage()</code> function.')),
				gettext('Random pool') => array('key' => 'image_effects_random', 'type' => OPTION_TYPE_CHECKBOX_ULLIST,
						'order' => 1,
						'checkboxes' => $rand,
						'desc' => gettext('Pool of effects for the <em>random</em> effect selection.')),
				8 => array('type' => OPTION_TYPE_NOTE,
						'order' => 8.9,
						'desc' => '<hr />'),
				chr(0) => array('key' => 'image_effects_docs', 'type' => OPTION_TYPE_CUSTOM,
						'order' => 9,
						'desc' => '<em>' . gettext('Acknowledgments') . '</em>')
		);

		ksort($docs);
		return array_merge($std, $docs);
	}

	/**
	 * handles any custom options
	 *
	 * @param string $option
	 * @param mixed $currentValue
	 */
	function handleOption($option, $currentValue) {
		switch ($option) {
			case 'image_effect_none':
				gettext('No image effects found.');
				break;
			case 'image_effects_docs':
				echo '<em>' . gettext('Effect') . '</em>';
				break;
			default:
				echo str_replace('image_effect_', '', $option);
				break;
		}
	}

	static function effectsJS() {
		global $_image_effects_random;
		$effectlist = array_keys(getPluginFiles('*.txt', 'image_effects'));
		$random = getSerializedArray(getOption('image_effects_random'));
		shuffle($random);
		$common = array();

		do {
			$_image_effects_random = array_shift($random);
			$effectdata = image_effects::getEffect($_image_effects_random);
			$invalid_effect = $effectdata && array_key_exists('error', $effectdata);
		} while ($_image_effects_random && $invalid_effect);

		$selected_effects = array_unique(array(getOption('image_std_images'), getOption('image_custom_images'),
				getOption('image_std_album_thumbs'), getOption('image_std_image_thumbs'),
				getOption('image_custom_album_thumbs'), $_image_effects_random));
		if (false !== $key = array_search('!', $selected_effects)) {
			unset($selected_effects[$key]);
		}
		if (count($selected_effects) > 0) {
			foreach ($selected_effects as $effect) {
				$effectdata = image_effects::getEffect($effect);
				if (array_key_exists('head', $effectdata)) {
					$common_data = trim($effectdata['head']);
					while ($common_data) {
						$i = strcspn($common_data, '= >');
						if ($i === false) {
							$common_data = '';
						} else {
							$tag = '</' . trim(substr($common_data, 1, $i)) . '>';
							$k = strpos($common_data, '>');
							$j = strpos($common_data, $tag, $k + 1);
							if ($j === false) {
								$j = strpos($common_data, '/>');
								if ($j === false) {
									$common_data = '';
								} else {
									$j = $j + 2;
								}
							} else {
								$j = $j + strlen($tag);
							}
							if ($j === false) {
								$common_data = '';
							} else {
								$common_element = substr($common_data, 0, $j);
								$common_data = trim(substr($common_data, strlen($common_element)));
								$common_element = trim($common_element);
								if (!in_array($common_element, $common)) {
									echo $common_element . "\n";
									$common[] = $common_element;
								}
							}
						}
					}
				}
			}
		}
	}

	private static function getEffect($effect) {
		global $image_effects;
		$effectdata = array();
		$file = getPlugin('image_effects/' . internalToFilesystem($effect) . '.txt');
		if (file_exists($file)) {
			$text = file_get_contents($file);
			foreach (array('head', 'class', 'extra', 'validate', 'common', 'source') as $tag) {
				$i = strpos($text, '<' . $tag . '>');
				if ($i !== false) {
					$j = strpos($text, '</' . $tag . '>');
					if ($j !== false) {
						$effectdata[$tag] = str_replace('%CORE_FOLDER%', CORE_FOLDER, str_replace('%SERVERPATH%', SERVERPATH, str_replace('%USER_PLUGIN_FOLDER%', USER_PLUGIN_FOLDER, str_replace('%PLUGIN_FOLDER%', PLUGIN_FOLDER, str_replace('%WEBPATH%', WEBPATH, substr($text, $s = $i + strlen($tag) + 2, $j - $s))))));
					}
				}
			}
			if (empty($effectdata)) {
				return array('error' => sprintf(gettext('<strong>Warning:</strong> <em>%1$s</em> invalid effect definition file'), $effect));
			}
			if (array_key_exists('validate', $effectdata)) {
				$effectdata['error'] = array();
				foreach (explode(';', $effectdata['validate']) as $file) {
					$file = trim($file);
					if ($file && !file_exists($file)) {
						$effectdata['error'][] = basename($file);
					}
				}
				if (count($effectdata['error']) > 0) {
					$effectdata['error'] = sprintf(ngettext('<strong>Warning:</strong> <em>%1$s</em> missing effect component: %2$s', '<strong>Warning:</strong><em>%1$s</em> missing effect components: %2$s', count($effectdata['error'])), $effect, implode(', ', $effectdata['error']));
				} else {
					unset($effectdata['error']);
				}
				unset($effectdata['validate']);
			}
			$image_effects[$effect] = $effectdata;
			return $effectdata;
		} else {
			return array('error' => sprintf(gettext('<strong>Warning:</strong> <em>%1$s</em> missing effect definition file'), $effect));
		}
	}

	private static function insertClass($html, $effect) {
		global $_image_effects_random;
		if ($effect == '!') {
			$effect = $_image_effects_random;
		}
		$effectData = image_effects::getEffect($effect);
		if (array_key_exists('error', $effectData)) {
			$html .= '<span class="errorbox">' . $effectData['error'] . "</span>\n";
		} else {
			if (array_key_exists('class', $effectData)) {
				$i = strpos($html, '<img');
				if ($i !== false) {
					$i = strpos($html, 'class=', $i);
					if ($i === false) {
						$i = strpos($html, '/>');
						$html = substr($html, 0, $i) . 'class="' . $effectData['class'] . '" ' . substr($html, $i);
					} else {
						$quote = substr($html, $i + 6, 1);
						$i = strpos($html, $quote, $i + 7);
						$html = substr($html, 0, $i) . ' ' . $effectData['class'] . substr($html, $i);
					}
				}
			}
			if (array_key_exists('extra', $effectData)) {
				$i = strpos($html, '/>');
				$html = substr($html, 0, $i) . ' ' . $effectData['extra'] . ' ' . substr($html, $i);
			}
		}
		return $html;
	}

	static function std_images($html) {
		if ($effect = getOption('image_std_images')) {
			$html = image_effects::insertClass($html, $effect);
		}
		return $html;
	}

	static function custom_images($html, $thumbstandin) {
		if ($thumbstandin) {
			if ($effect = getOption('image_custom_image_thumbs')) {
				$html = image_effects::insertClass($html, $effect);
			}
		} else {
			if ($effect = getOption('image_custom_images')) {
				$html = image_effects::insertClass($html, $effect);
			}
		}
		return $html;
	}

	static function std_album_thumbs($html) {
		if ($effect = getOption('image_std_album_thumbs')) {
			$html = image_effects::insertClass($html, $effect);
		}
		return $html;
	}

	static function std_image_thumbs($html) {
		if ($effect = getOption('image_std_image_thumbs')) {
			$html = image_effects::insertClass($html, $effect);
		}
		return $html;
	}

	static function custom_album_thumbs($html) {
		if ($effect = getOption('image_custom_album_thumbs')) {
			$html = image_effects::insertClass($html, $effect);
		}
		return $html;
	}

}

?>