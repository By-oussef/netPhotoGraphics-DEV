<?php

/**
 * This plugin will intercept the load process and force references to the index page to
 * the the single album of the installation
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/singleAlbum
 * @pluginCategory example
 */
$plugin_is_filter = 5 | FEATURE_PLUGIN;
$plugin_description = gettext('Forces a defined album as the index page.');

npgFilters::register('load_request', 'forceAlbum');

function forceAlbum($success) {
	// we presume that the site only serves the one album.
	$gallery = new Gallery();
	$albums = $gallery->getAlbums();
	$_GET['album'] = array_shift($albums);
	return $success;
}

?>