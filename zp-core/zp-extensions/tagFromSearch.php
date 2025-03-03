<?php
/**
 * This plugin provides a means to tag objects found by a search. In addition, it
 * (optionally) forces searches by visitors and users without <i>Tags</i> rights to be limited to the
 * tags field.
 *
 * Thus you can apply a unique tag to results of a search so that they are "related" to
 * each other. If you have selected the optional <i>Tags only</i> search <i>normal</i> viewers are limited
 * to searching for defined tags.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/tagFromSearch
 * @pluginCategory media
 *
 * @Copyright 2015 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 */
$plugin_is_filter = 9 | FEATURE_PLUGIN;
$plugin_description = gettext('Facilitates assigning unique tags to related objects.');

$option_interface = 'tagFromSearch';

class tagFromSearch {

	function getOptionsSupported() {

		$options = array(gettext('Tags only searches') => array('key' => 'tagFromSearch_tagOnly', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Restrict viewer searches to the <code>tags</code> field unless they have <em>Tags rights</em>.'))
		);
		return $options;
	}

	static function toolbox() {
		global $_current_search;
		if (npg_loggedin(TAGS_RIGHTS)) {
			?>
			<li>
				<a href="<?php echo getAdminLink(PLUGIN_FOLDER . '/tagFromSearch/tag.php') . '?' . substr($_current_search->getSearchParams(), 1); ?>" title="<?php echo gettext('Tag items found by the search'); ?>" ><?php echo gettext('Tag items'); ?></a>
			</li>
			<?php
		}
	}

	static function head() {
		if (!npg_loggedin(TAGS_RIGHTS)) {
			if (getOption('tagFromSearch_tagOnly')) {
							setOption('search_fields', 'tags', false);
			}
		}
	}

}

npgFilters::register('feature_plugin_load', 'tagFromSearch::head');
npgFilters::register('admin_toolbox_search', 'tagFromSearch::toolbox');
?>