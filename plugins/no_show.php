<?php

/* Totally hides unpublished images from not signed in viewers.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/no_show
 * @pluginCategory example
 */
$plugin_is_filter = 5 | THEME_PLUGIN;
$plugin_description = gettext('Prevents guest viewers from viewing unpublished images albums.');

if (!OFFSET_PATH) {
	npgFilters::register('album_instantiate', 'no_show_hideAlbum');
	npgFilters::register('image_instantiate', 'no_show_hideImage');
}

function no_show_hideImage($imageObj) {
	$album = $imageObj->getAlbum();
	$check = checkAlbumPassword($album);
	if ($check == 'public_access') {
		$imageObj->exists = $imageObj->getShow();
	}
	return $imageObj;
}

function no_show_hideAlbum($albumObj) {
	$check = checkAlbumPassword($albumObj);
	if ($check == 'public_access') {
		$albumObj->exists = $albumObj->getShow();
	}
	return $albumObj;
}

?>