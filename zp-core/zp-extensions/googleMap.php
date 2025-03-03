<?php
/**
 * This is plugin for display pictures on a Google Map
 * Uses :
 * 		CodeIgniter Google Maps API V3 Class (hacked for zenphoto needs) (https://github.com/BIOSTALL/CodeIgniter-Google-Maps-V3-API-Library)
 * 		markerClustererPlus library 2.0.15
 * 		overlappingMarkerSpiderfier library 0.3 (https://github.com/jawj/OverlappingMarkerSpiderfier)
 *
 * @author Stephen Billard (sbillard) & Vincent Bourganel (vincent3569)
 *
 * @package plugins/googleMap
 * @pluginCategory theme
 *
 */
$plugin_is_filter = 5 | THEME_PLUGIN;
$plugin_description = gettext('Display Google Maps based on <em>latitude</em> and <em>longitude</em> metadata in the images.');
$plugin_notice = sprintf(gettext('<strong>Note</strong>: Google does place limits on the use of its <a href="%s"><em>Maps API</em></a>. Please review these to be sure your site is in compliance.'), 'http://googlegeodevelopers.blogspot.com/2011/10/introduction-of-usage-limits-to-maps.html');

$option_interface = 'GoogleMap';
if (isset($_gallery_page) && $_gallery_page != 'index.php') {
	if (getOption('gmap_sessions')) {
		npg_session_start();
	}
}

/**
 * googleMap
 *
 */
class GoogleMap {

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('gmap_map_roadmap', 1);
			setOptionDefault('gmap_map_hybrid', 1);
			setOptionDefault('gmap_map_satellite', 1);
			setOptionDefault('gmap_map_terrain', 1);
			setOptionDefault('gmap_map_api_key', '');
			setOptionDefault('gmap_starting_map', 'HYBRID');
			setOptionDefault('gmap_control_type', 'HORIZONTAL_BAR');
			setOptionDefault('gmap_zoom_size', 'LARGE');
			setOptionDefault('gmap_cluster_max_zoom', 13);
			setOptionDefault('gmap_sessions', 1);
			setOptionDefault('gmap_display', 'show');

			if (class_exists('cacheManager')) {
				cacheManager::deleteCacheSizes('GoogleMap');
				cacheManager::addCacheSize('GoogleMap', 150, NULL, NULL, NULL, NULL, NULL, NULL, true, NULL, NULL, NULL);
			}
		}
	}

	function getOptionsSupported() {

		$MapTypes = array(); // order matters here because the first allowed map is selected if the 'gmap_starting_map' is not allowed
		if (getOption('gmap_map_hybrid')) {
					$MapTypes[gettext('Hybrid')] = 'HYBRID';
		}
		if (getOption('gmap_map_roadmap')) {
					$MapTypes[gettext('Map')] = 'ROADMAP';
		}
		if (getOption('gmap_map_satellite')) {
					$MapTypes[gettext('Satellite')] = 'SATELLITE';
		}
		if (getOption('gmap_map_terrain')) {
					$MapTypes[gettext('Terrain')] = 'TERRAIN';
		}

		$defaultMap = getOption('gmap_starting_map');
		if (array_search($defaultMap, $MapTypes) === false) { // the starting map is not allowed, pick a new one
			$temp = $MapTypes;
			$defaultMap = array_shift($temp);
			setOption('gmap_starting_map', $defaultMap);
		}

		$options = array(
				gettext('Allowed maps') => array('key' => 'gmap_allowed_maps', 'type' => OPTION_TYPE_CHECKBOX_ARRAY,
						'order' => 1,
						'checkboxes' => array(gettext('Hybrid') => 'gmap_map_hybrid',
								gettext('Map') => 'gmap_map_roadmap',
								gettext('Satellite') => 'gmap_map_satellite',
								gettext('Terrain') => 'gmap_map_terrain'),
						'desc' => gettext('Select the map types that are allowed.')),
				gettext('Initial map display selection') => array('key' => 'gmap_starting_map', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 2,
						'selections' => $MapTypes,
						'desc' => gettext('Select the initial type of map to display.')),
				gettext('Map display') => array('key' => 'gmap_display', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 3,
						'selections' => array(gettext('show') => 'show',
								gettext('hide') => 'hide',
								gettext('colorbox') => 'colorbox'),
						'desc' => gettext('Select <em>hide</em> to initially hide the map. Select <em>colorbox</em> for the map to display in a colorbox. Select <em>show</em> and the map will display when the page loads.')),
				gettext('Map controls') => array('key' => 'gmap_control_type', 'type' => OPTION_TYPE_RADIO,
						'order' => 4,
						'buttons' => array(gettext('None') => 'none',
								gettext('Default') => 'DEFAULT',
								gettext('Dropdown') => 'DROPDOWN_MENU',
								gettext('Horizontal') => 'HORIZONTAL_BAR'),
						'desc' => gettext('Display options for the Map type control.')),
				gettext('Zoom controls') => array('key' => 'gmap_zoom_size', 'type' => OPTION_TYPE_RADIO,
						'order' => 5,
						'buttons' => array(gettext('Small') => 'SMALL',
								gettext('Default') => 'DEFAULT',
								gettext('Large') => 'LARGE'),
						'desc' => gettext('Display options for the Zoom control.')),
				gettext('Max zoom level') => array('key' => 'gmap_cluster_max_zoom', 'type' => OPTION_TYPE_NUMBER,
						'order' => 6,
						'desc' => gettext('The max zoom level for clustering pictures on map.')),
				gettext('Map sessions') => array('key' => 'gmap_sessions', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 9,
						'desc' => gettext('If checked GoogleMaps will use sessions to pass map data for the <em>colorbox</em> display option. We recommend this option be selected. It protects against reference forgery security attacks and mitigates problems with data exceeding the allowed by some browsers.')),
				gettext('API key') => array('key' => 'gmap_map_api_key', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 10,
						'desc' => gettext('As of June 22, 2016 Google Maps no longer support keyless access (any request that doesn\'t include an API key). You may need to obtain an API key from <a href="https://console.developers.google.com/project">Google</a>.'))
		);

		return $options;
	}

	function handleOption($option, $currentValue) {

	}

	static function log_message($class, $msg) {
		// do nothing
	}

	static function get_instance() {
		// standin for CI library
		return new codeIgniter_kludge();
	}

	static function omsAdditions() {
		// maybe we can move some of the zenphoto hacks here.
		return '';
	}

	/**
	 * Add required informations in the header
	 */
	static function js() {
		if (!defined('BASEPATH')) {
					define('BASEPATH', true);
		}
		//	for no access test in googleMap.php
		require_once(dirname(__FILE__) . '/googleMap/CodeIgniter-Google-Maps-V3-API/Googlemaps.php');
		$key = getOption('gmap_map_api_key');
		if (!empty($key)) {
			$key = '&amp;key=' . $key;
		}
		$loc = getOption('locale');
		if (!empty($loc)) {
			$loc = '&amp;language=' . substr($loc, 0, 2);
		}
		?>
		<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3.exp<?php echo $key . $loc; ?>"></script>
		<?php
		scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/googleMap/markerClustererPlus/markerclusterer.js');
		scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/googleMap/overlappingMarkerSpiderfier/oms.min.js');
		scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/googleMap/googleMap.css');
	}

	/**
	 * converts a cordinate in string format to a float
	 * NOTE: this function presumes that there are no thousands separators!!!
	 *
	 * @param string $num
	 * @return float
	 */
	static function inputConvert($num) {
		if (is_string($num)) {
			$d = preg_split('/[,\.]/', $num . '.0');
			$float = abs($d[0]) + $d[1] * pow(10, -strlen($d[1]));
			if (strpos($num, '-') !== FALSE) {
				$float = - $float;
			}
		} else {
			$float = (float) $num;
		}
		return $float;
	}

	/**
	 * $returns coordinate informations for an image
	 * @param $image		image object
	 */
	static function getGeoCoord($image) {
		if (isImageClass($image)) {
			$lat = $image->get('GPSLatitude');
			$long = $image->get('GPSLongitude');
			$thumbimg = $image->getCustomImage(150, NULL, NULL, NULL, NULL, NULL, NULL, true);
			if (!empty($lat) && !empty($long)) {
				$lat_f = self::inputConvert($lat);
				$long_f = self::inputConvert($long);
				$thumb = '<a href="javascript:image(\'' . $image->albumname . '\',\'' . $image->filename . '\');"><img src="' . $thumbimg . '" /></a>';
				return array('lat' => $lat_f, 'long' => $long_f, 'title' => $image->getTitle(), 'desc' => $image->getDesc(), 'thumb' => $thumb);
			}
		}
		return false;
	}

	/**
	 * Add a point to a map object
	 * @param $map			google map object
	 * @param $coord		coordinates array
	 */
	static function addGeoCoord($map, $coord) {
		global $_x, $_y, $_z, $_n;
		if ($coord) {
			$marker = array();

			$title = str_replace('/', '\/', str_replace('"', '\"', str_replace(array("\n", "\r"), "", html_encodeTagged($coord['title']))));
			$desc = str_replace('/', '\/', str_replace('"', '\"', str_replace(array("\n", "\r"), "", html_encodeTagged($coord['desc']))));
			$thumb = str_replace('/', '\/', str_replace('"', '\"', str_replace(array("\n", "\r"), "", html_encodeTagged($coord['thumb']))));
			if ($title <> '') {
				$title = '<h3 class="map_title">' . $title . '</h3>';
			}
			if ($desc <> '') {
				$desc = '<div class="map_desc">' . $desc . '</div>';
			}
			if ($coord['thumb'] <> '') {
				$thumb = '<p class="map_img">' . $coord['thumb'] . '</p>';
			}

			$marker['position'] = number_format($coord['lat'], 12, '.', '') . ", " . number_format($coord['long'], 12, '.', '');
			$marker['title'] = addslashes($coord['title']);
			$marker['infowindow_content'] = $title . $thumb . $desc;
			$map->add_marker($marker);
			$lat_f = $coord['lat'] * M_PI / 180;
			$long_f = $coord['long'] * M_PI / 180;
			$_x = $_x + cos($lat_f) * cos($long_f);
			$_y = $_y + cos($lat_f) * sin($long_f);
			$_z = $_z + sin($lat_f);
			$_n++;
		}
	}

	/**
	 * Gathers the data for an image
	 * @param $image		image object
	 * @param $map			google map object
	 */
	static function getImageGeodata($image, $map) {
		$coord = self::getGeoCoord($image);
		if ($coord) {
			self::addGeoCoord($map, $coord);
		}
		return $coord;
	}

	/**
	 * Gathers the map data for an album
	 * @param $album		album object
	 * @param $map			google map object
	 */
	static function getAlbumGeodata($album, $map) {
		$result = false;
		$images = $album->getImages(0, 0, null, null, false);
		foreach ($images as $an_image) {
			$image = newImage($album, $an_image);
			$coord = self::getGeoCoord($image);
			if ($coord) {
				$result = true; // at least one image has geodata
				self::addGeoCoord($map, $coord);
			}
		}
		return $result;
	}

}

// codeIgniter stuff
require_once(CORE_SERVERPATH . COMMON_FOLDER . '/jsMin/JSMin.php');

class CI_load {

	function library($library) {
		//	better be jsmin, that's all we do
		assert($library == 'jsmin');
	}

}

class CI_jsmin {

	function min($js) {
		return JSMin::minify($js);
	}

}

class codeIgniter_kludge { //	dummy for all the CI stuff in the CodeIngnter-Google_maps script

	var $load;
	var $jsmin;

	function __construct() {
		$this->load = new CI_load();
		$this->jsmin = new CI_jsmin();
	}

}

/**
 * Output the google map
 *
 * @param string $text text for the "toggle" link that shows/hides the map. Set empty to omit (then Map is always displayed)
 * @param string $id used to set the IDs for the toggle href element ($id_toggle) and the map element ($id_data)
 * @param string $hide initial map state: "hide", "show", or "colorbox"
 * @param object $obj optional image/album object. Pass string for generic map and use callback to set points
 * @param function $callback optional callback function to set map options.
 */
function printGoogleMap($text = NULL, $id = NULL, $hide = NULL, $obj = NULL, $callback = NULL) {
	global $_current_album, $_current_image, $_x, $_y, $_z, $_n;

	/* API keys are required post June 2016 */
	if (!getOption('gmap_map_api_key')) {
		?>
		<br clear="all">
		<div class="errorbox">
			<?php echo gettext('No Google Map API key has been set.'); ?>
		</div>
		<?php
		return;
	}

	/* controls of parameters */
	if (is_null($obj)) {
		if (is_null($_current_image)) {
			$obj = $_current_album;
		} else {
			$obj = $_current_image;
		}
	}
	if (is_null($obj)) {
		return false;
	}
	if (is_object($obj)) {
		$type = $obj->table;
		$typeid = $obj->getID();
	} else {
		$type = $obj;
		$typeid = '';
	}
	if (is_null($text)) {
		$text = gettext('Google Map');
	}
	if (empty($text)) {
		$hide = 'show';
	}
	if (is_null($hide)) {
		$hide = getOption('gmap_display');
	}
	if (!is_string($hide)) {
		if ($hide) {
			$hide = 'hide';
		} else {
			$hide = 'show';
		}
	}

	/* map configuration */
	$mapControls = getOption('gmap_control_type');
	if ($mapControls == 'none') {
		$mapTypeControl = false;
	} else {
		$mapTypeControl = true;
		$map_control_type = $mapControls;

		$allowedMapTypes = array();
		if (getOption('gmap_map_hybrid')) {
					$allowedMapTypes[] = 'HYBRID';
		}
		if (getOption('gmap_map_roadmap')) {
					$allowedMapTypes[] = 'ROADMAP';
		}
		if (getOption('gmap_map_satellite')) {
					$allowedMapTypes[] = 'SATELLITE';
		}
		if (getOption('gmap_map_terrain')) {
					$allowedMapTypes[] = 'TERRAIN';
		}
	}

	$config['center'] = '0, 0';
	$config['zoom'] = 'auto';
	$config['cluster'] = true;
	$config['zoomControlStyle'] = getOption('gmap_zoom_size');
	if ($mapTypeControl) {
		$config['map_type'] = getOption('gmap_starting_map');
		$config['map_types_available'] = $allowedMapTypes;
	} else {
		$config['disableMapTypeControl'] = true;
	}
	$config['clusterMaxZoom'] = getOption('gmap_cluster_max_zoom');
	$config['clusterAverageCenter'] = true;
	$config['onclick'] = "iw.close();";
	$config['minifyJS'] = !TEST_RELEASE;
	if (!class_exists('Googlemaps')) {
		GoogleMap::js();
	}

	$map = new Googlemaps($config);

	/* add markers from geocoded pictures */
	switch ($type) {
		case 'images':
			if (GoogleMap::getImageGeodata($obj, $map)) {
				break;
			} else {
				$map = NULL;
				return false;
			}
		case 'albums':
			if (GoogleMap::getAlbumGeodata($obj, $map)) {
				break;
			} else {
				$map = NULL;
				return false;
			}
		default:
			break;
	}

	if ($_n == 1) {
			$map->zoom = 13;
	}

	if ($_n) {
		$_x = $_x / $_n;
		$_y = $_y / $_n;
		$_z = $_z / $_n;
		$lon = number_format(atan2($_y, $_x) * 180 / M_PI, 12, '.', '');
		$hyp = sqrt($_x * $_x + $_y * $_y);
		$lat = number_format(atan2($_z, $hyp) * 180 / M_PI, 12, '.', '');
		$map->center = $lat . ', ' . $lon;
	}

	if (!is_null($callback)) {
		call_user_func($callback, $map);
	}

	/* map display */
	if (is_null($id)) {
		$id = $type . $typeid . '_googlemap';
	}
	$id_toggle = $id . '_toggle';
	$id_data = $id . '_data';

	switch ($hide) {
		case 'show':
			$map->create_map();
			?>
			<script type="text/javascript">
				//<![CDATA[
			<?php
			echo $map->output_js_contents;
			echo GoogleMap::omsAdditions();
			?>

				function image(album, image) {
					window.location = '<?php echo WEBPATH ?>/index.php?album=' + album + '&image=' + image;
				}
				//]]>
			</script>
			<div id="<?php echo $id_data; ?>">
				<?php echo $map->output_html; ?>
			</div>
			<?php
			break;
		case 'hide':
			$map->create_map();
			?>
			<script type="text/javascript">
				//<![CDATA[
			<?php
			echo $map->output_js_contents;
			echo GoogleMap::omsAdditions();
			?>

				function image(album, image) {
					window.location = '<?php echo WEBPATH ?>/index.php?album=' + album + '&image=' + image;
				}

				function toggle_<?php echo $id_data; ?>() {
					if ($('#<?php echo $id_data; ?>').hasClass('hidden_map')) {
						$('#<?php echo $id_data; ?>').removeClass('hidden_map');
						//re-render the map because drawing it when hidden is problematic
						//and drawing it "off screen" presumes the size of the screen[sic]
						var center = map.getCenter();
						google.maps.event.trigger(map, "resize");
						map.setCenter(center);
						fitMapToBounds();
					} else {
						$('#<?php echo $id_data; ?>').addClass('hidden_map');
					}
				}
				//]]>
			</script>
			<span class="map_ref">
				<a id="<?php echo $id_toggle; ?>" href="javascript:toggle_<?php echo $id_data; ?>();" title="<?php echo gettext('Display or hide the Google Map.'); ?>"><?php echo $text; ?></a>
			</span>
			<div id="<?php echo $id_data; ?>" class="hidden_map">
				<?php echo $map->output_html; ?>
			</div>
			<?php
			break;
		case 'colorbox':
			if (npgFilters::has_filter('theme_head', 'colorbox::css')) {
				$map->create_map();
				$map_data["output_js_contents"] = $map->output_js_contents;
				$map_data["output_html"] = $map->output_html;

				if (getOption('gmap_sessions')) {
					$param = '';
					$_SESSION['GoogleMapVars'] = $map_data;
				} else {
					$serializedData = serialize($map_data);
					if (function_exists('bzcompress')) {
						$data = bzcompress($serializedData);
					} else {
						$data = gzcompress($serializedData);
					}
					$param = '?map_data=' . base64_encode($data);
				}
				?>
				<span class="map_ref">
					<a href="<?php echo getAdminLink(PLUGIN_FOLDER . '/googleMap/map.php') . $param ?>" title="<?php echo $text; ?>" class="google_map"><?php echo $text; ?></a>
				</span>
				<script type="text/javascript">
					//<![CDATA[
					window.addEventListener('load', function () {
						$(".google_map").colorbox({
							iframe: true,
							innerWidth: $(window).width() * 0.8,
							innerHeight: $(window).height() * 0.8,
							close: '<?php echo gettext("close"); ?>',
							onComplete: function () {
								parent.resizeColorBoxMap();
								$(window).resize(resizeColorBoxMap);
							}
						});
					}, false);
					//]]>
				</script>
				<?php
			}
			break;
	}
}
?>