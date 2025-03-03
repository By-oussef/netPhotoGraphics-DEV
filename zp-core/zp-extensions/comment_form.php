<?php
/**
 * This is the unified comment handling facility
 *
 * Place a call on the function <var>printCommentForm()</var> in your script where you
 * wish the comment items to appear.
 *
 * The plugin uses <var>%CORE_FOLDER%/%PLUGIN_FOLDER%/comment_form/comment_form.php</var>.
 * However, you may override this form by placing a script of the same name in a similar folder in your theme.
 * This will allow you to customize the appearance of the comments on your site.
 *
 * There are several options to tune what the plugin will do.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/comment_form
 * @pluginCategory theme
 */
$plugin_is_filter = defaultExtension(5 | FEATURE_PLUGIN);
$plugin_description = gettext("Provides a unified comment handling facility.");

$option_interface = 'comment_form';

npgFilters::register('admin_toolbox_global', 'comment_form::toolbox');

require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/comment_form/class-comment.php');
require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/comment_form/functions.php');

if (OFFSET_PATH) {
	npgFilters::register('admin_overview', 'comment_form_print10Most');
	npgFilters::register('admin_tabs', 'comment_form::admin_tabs');
} else {
	npgFilters::register('handle_comment', 'comment_form_postcomment');
	npgFilters::register('object_addComment', 'comment_form_addComment');
	if (getOption('comment_form_pagination')) {
		npgFilters::register('theme_body_close', 'comment_form_PaginationJS');
	}

	if (getOption('tinymce_comments')) {
		require_once(getPlugin('tinymce.php'));
		npgFilters::register('theme_body_close', 'comment_form_visualEditor');
	}
}

class comment_form {

	/**
	 * class instantiation function
	 *
	 */
	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('email_new_comments', 1);
			setOptionDefault('comment_name_required', 'required');
			setOptionDefault('comment_email_required', 'required');
			setOptionDefault('comment_web_required', 1);
			setOptionDefault('Use_Captcha', false);
			setOptionDefault('comment_form_addresses', 0);
			setOptionDefault('comment_form_require_addresses', 0);
			setOptionDefault('comment_form_members_only', 0);
			setOptionDefault('comment_form_albums', 1);
			setOptionDefault('comment_form_images', 1);
			setOptionDefault('comment_form_articles', 1);
			setOptionDefault('comment_form_pages', 1);
			setOptionDefault('comment_form_rss', 1);
			setOptionDefault('comment_form_private', 1);
			setOptionDefault('comment_form_anon', 1);
			setOptionDefault('comment_form_showURL', 1);
			setOptionDefault('comment_form_comments_per_page', 10);
			setOptionDefault('comment_form_pagination', true);
			setOptionDefault('comment_form_toggle', 1);
			setOptionDefault('tinymce_comments', 'comment-ribbon.php');
			setOptionDefault('tinymce_admin_comments', 'comment-ribbon.php');
		}
		if (getOption('tinymce_comments')) {
			require_once(getPlugin('tinymce.php'));
			npgFilters::register('theme_body_close', 'comment_form_visualEditor');
		}
	}

	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		global $_captcha;
		$checkboxes = array(gettext('Albums') => 'comment_form_albums', gettext('Images') => 'comment_form_images');
		if (extensionEnabled('zenpage')) {
			$checkboxes = array_merge($checkboxes, array(gettext('Pages') => 'comment_form_pages', gettext('News') => 'comment_form_articles'));
		}

		$options = array(
				gettext('Enable comment notification') => array('key' => 'email_new_comments', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 0,
						'desc' => gettext('Email the Admin when new comments are posted')),
				gettext('Name field') => array('key' => 'comment_name_required', 'type' => OPTION_TYPE_RADIO,
						'order' => 0.1,
						'buttons' => array(gettext('Omit') => 0, gettext('Show') => 1, gettext('Require') => 'required'),
						'desc' => gettext('If the <em>Name</em> field is required, the poster must provide a name.')),
				gettext('Email field') => array('key' => 'comment_email_required', 'type' => OPTION_TYPE_RADIO,
						'order' => 0.2,
						'buttons' => array(gettext('Omit') => 0, gettext('Show') => 1, gettext('Require') => 'required'),
						'desc' => gettext('If the <em>Email</em> field is required, the poster must provide an email address.')),
				gettext('Website field') => array('key' => 'comment_web_required', 'type' => OPTION_TYPE_RADIO,
						'order' => 0.3,
						'buttons' => array(gettext('Omit') => 0, gettext('Show') => 1, gettext('Require') => 'required'),
						'desc' => gettext('If the <em>Website</em> field is required, the poster must provide a website.')),
				gettext('Captcha field') => array('key' => 'Use_Captcha', 'type' => OPTION_TYPE_RADIO,
						'order' => 0.4,
						'buttons' => array(gettext('Omit') => 0, gettext('For guests') => 2, gettext('Require') => 1),
						'desc' => ($_captcha->name) ? gettext('If <em>Captcha</em> is required, the form will include a Captcha verification.') : '<span class="notebox">' . gettext('No captcha handler is enabled.') . '</span>'),
				gettext('Address fields') => array('key' => 'comment_form_addresses', 'type' => OPTION_TYPE_RADIO,
						'order' => 7,
						'buttons' => array(gettext('Omit') => 0, gettext('Show') => 1, gettext('Require') => 'required'),
						'desc' => gettext('If <em>Address fields</em> are shown or required, the form will include positions for address information. If required, the poster must supply data in each address field.')),
				gettext('Allow comments on') => array('key' => 'comment_form_allowed', 'type' => OPTION_TYPE_CHECKBOX_ARRAY,
						'order' => 0.9,
						'checkboxes' => $checkboxes,
						'desc' => gettext('Comment forms will be presented on the checked pages.')),
				gettext('Toggled comment block') => array('key' => 'comment_form_toggle', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 2,
						'desc' => gettext('If checked, existing comments will be initially hidden. Clicking on the provided button will show them.')),
				gettext('Show author URL') => array('key' => 'comment_form_showURL', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 7,
						'desc' => gettext('To discourage SPAM, uncheck this box and the author URL will not be revealed.')),
				gettext('Only members can comment') => array('key' => 'comment_form_members_only', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 4,
						'desc' => gettext('If checked, only logged in users will be allowed to post comments.')),
				gettext('Allow private postings') => array('key' => 'comment_form_private', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 6,
						'desc' => gettext('If checked, posters may mark their comments as private (not for publishing).')),
				gettext('Allow anonymous posting') => array('key' => 'comment_form_anon', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 5,
						'desc' => gettext('If checked, posters may exclude their personal information from the published post.')),
				gettext('Include RSS link') => array('key' => 'comment_form_rss', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 8,
						'desc' => gettext('If checked, an RSS link will be included at the bottom of the comment section.')),
				gettext('Comments per page') => array('key' => 'comment_form_comments_per_page', 'type' => OPTION_TYPE_NUMBER,
						'order' => 9,
						'desc' => gettext('The comments that should show per page on the admin tab and when using the jQuery pagination'))
		);
		if (defined('TINYMCE')) {
			$editorConfig = stripsuffix(basename(TINYMCE)) . '::getConfigFiles';
			$configarray = $editorConfig('comment');
			$commentEditor = array(
					gettext('Comment editor configuration') => array('key' => 'tinymce_comments', 'type' => OPTION_TYPE_SELECTOR,
							'order' => 1,
							'selections' => $configarray,
							'null_selection' => gettext('Disabled'),
							'desc' => gettext('Configuration file for TinyMCE when used for comments. Set to <code>Disabled</code> to disable visual editing.')),
					gettext('Admin comment editor configuration') => array('key' => 'tinymce_admin_comments', 'type' => OPTION_TYPE_SELECTOR,
							'order' => 1.1,
							'selections' => $configarray,
							'null_selection' => gettext('Disabled'),
							'desc' => gettext('Configuration file for TinyMCE when used for the <em>edit comments</em> tab.')), gettext('Pagination') => array('key' => 'comment_form_pagination', 'type' => OPTION_TYPE_CHECKBOX,
							'order' => 3,
							'desc' => gettext('Uncheck to disable the jQuery pagination of comments. Enabled by default.')),
			);
			$options = array_merge($options, $commentEditor);
		}

		return $options;
	}

	function handleOption($option, $currentValue) {

	}

	static function admin_tabs($tabs) {
		if (npg_loggedin(COMMENT_RIGHTS)) {
			$tabs['comments'] = array('text' => gettext("comments"),
					'link' => getAdminLink(PLUGIN_FOLDER . '/comment_form/admin-comments.php') . '?page=comments&tab=' . gettext('comments'),
					'subtabs' => NULL);
		}
		return $tabs;
	}

	static function toolbox() {
		if (npg_loggedin(COMMENT_RIGHTS)) {
			?>
			<li>
				<?php printLinkHTML(getAdminLink(PLUGIN_FOLDER . '/comment_form/admin-comments.php') . '?page=comments&amp;tab=' . gettext('comments'), gettext("Comments"), NULL, NULL, NULL); ?>
			</li>
			<?php
		}
	}

}

/**
 * Prints a form for posting comments
 *
 * @param bool $showcomments defaults to true for showing list of comments
 * @param string $addcommenttext alternate text for "Add a comment:"
 * @param bool $addheader set true to display comment count header
 * @param string $comment_commententry_mod use to add styles, classes to the comment form div
 * @param bool $desc_order default false, set to true to change the comment order to descending ( = newest to oldest)
 */
function printCommentForm($showcomments = true, $addcommenttext = NULL, $addheader = true, $comment_commententry_mod = '', $desc_order = false) {
	global $_gallery_page, $_current_admin_obj, $_current_comment, $_captcha, $_authority, $_HTML_cache, $_current_image, $_current_album, $_CMS_current_page, $_CMS_current_article;

	if (getOption('email_new_comments')) {
		$email_list = $_authority->getAdminEmail();
		if (empty($email_list)) {
			setOption('email_new_comments', 0);
		}
	}
	if (is_null($addcommenttext)) {
		$addcommenttext = '<h3>' . gettext('Add a comment:') . '</h3>';
	}
	switch ($_gallery_page) {
		case 'album.php':
			if (!getOption('comment_form_albums')) {
							return;
			}
			$obj = $_current_album;
			break;
		case 'image.php':
			if (!getOption('comment_form_images')) {
							return;
			}
			$obj = $_current_image;
			break;
		case 'pages.php':
			if (!getOption('comment_form_pages')) {
							return;
			}
			$obj = $_CMS_current_page;
			break;
		case 'news.php':
			if (!getOption('comment_form_articles') || !is_NewsArticle()) {
							return;
			}
			$obj = $_CMS_current_article;
			break;
		default:
			return;
			break;
	}
	$comments_open = $obj->getCommentsAllowed();
	$num = getCommentCount();
	if ($comments_open || $num) {
		?>
		<!-- printCommentForm -->
		<div id="commentcontent">
			<?php
			if ($showcomments) {
				if ($num == 0) {
					if ($addheader) {
											echo '<h3 class="empty">' . gettext('No Comments') . '</h3>';
					}
					$display = '';
				} else {
					if ($addheader) {
											echo '<h3>' . sprintf(ngettext('%u Comment', '%u Comments', $num), $num) . '</h3>';
					}
					if (getOption('comment_form_toggle')) {
						?>
						<div id="comment_toggle"><!-- place holder for toggle button --></div>
						<script type="text/javascript">
							// <!-- <![CDATA[
							function toggleComments(hide) {
								if (hide) {
									$('div.comment').hide();
									$('.Pagination').hide();
									$('#comment_toggle').html('<button class="button buttons" onclick="toggleComments(false);"><?php echo gettext('show comments'); ?></button>');
								} else {
									$('div.comment').show();
									$('.Pagination').show();
									$('#comment_toggle').html('<button class="button buttons" onclick="toggleComments(true);"><?php echo gettext('hide comments'); ?></button>');
								}
							}
							window.addEventListener('load', function () {
								toggleComments(window.location.hash.search(/#_comment_id_/));
							}, false);
							// ]]> -->
						</script>
						<?php
						$display = ' style="display:none"';
					} else {
						$display = '';
					}
				}
				$hideoriginalcomments = '';
				if (getOption('comment_form_pagination') && COMMENTS_PER_PAGE < $num) {
					$hideoriginalcomments = ' style="display:none"'; // hide original comment display to be replaced by jQuery pagination
				}
				if (getOption('comment_form_pagination') && COMMENTS_PER_PAGE < $num) {
					?>
					<div class="Pagination"></div><!-- this is the jquery pagination nav placeholder -->
					<div id="Commentresult"></div>
					<?php
				}
				?>
				<div id="comments"<?php echo $hideoriginalcomments; ?>>
					<?php
					while (next_comment($desc_order)) {
						if (!getOption('comment_form_showURL')) {
							$_current_comment['website'] = '';
						}
						?>
						<div class="comment" <?php echo $display; ?>>
							<div class="commentinfo">
								<h4 id="_comment_id_<?php echo $_current_comment['id']; ?>"><?php printCommentAuthorLink(); ?>: <?php echo gettext('on'); ?> <?php
									echo $_current_comment['date'];
									printEditCommentLink(gettext('Edit'), ', ', '');
									?></h4>
							</div><!-- class "commentinfo" -->
							<div class="commenttext"><?php echo html_encodeTagged(getCommentBody(), false); ?></div><!-- class "commenttext" -->
						</div><!-- class "comment" -->
						<?php
					}
					?>
				</div><!-- id "comments" -->
				<?php
			}
			if (getOption('comment_form_pagination') && COMMENTS_PER_PAGE < $num) {
				?>
				<div class="Pagination"></div><!-- this is the jquery pagination nav placeholder -->
				<?php
			}
			?>
			<!-- Comment Box -->
			<?php
			if ($comments_open) {
				if (MEMBERS_ONLY_COMMENTS && !npg_loggedin(POST_COMMENT_RIGHTS)) {
					echo gettext('Only registered users may post comments.');
				} else {
					$disabled = array('name' => '', 'website' => '', 'anon' => '', 'private' => '', 'comment' => '',
							'street' => '', 'city' => '', 'state' => '', 'country' => '', 'postal' => '');
					$stored = array_merge(array('email' => '', 'custom' => ''), $disabled, getCommentStored());
					$addresses = getSerializedArray(@$stored['addresses']);
					foreach ($addresses as $key => $value) {
						if (!empty($value)) {
													$stored[$key] = $value;
						}
					}

					foreach ($stored as $key => $value) {
						$disabled[$key] = false;
					}

					if (npg_loggedin()) {
						if (extensionEnabled('userAddressFields')) {
							$address = userAddressFields::getCustomDataset($_current_admin_obj);
							foreach ($address as $key => $value) {
								if (!empty($value)) {
									$disabled[$key] = true;
									$stored[$key] = $value;
								}
							}
						}
						$name = $_current_admin_obj->getName();
						if (!empty($name)) {
							$stored['name'] = $name;
							$disabled['name'] = ' disabled="disabled"';
						} else {
							$user = $_current_admin_obj->getUser();
							if (!empty($user)) {
								$stored['name'] = $user;
								$disabled['name'] = ' disabled="disabled"';
							}
						}
						$email = $_current_admin_obj->getEmail();
						if (!empty($email)) {
							$stored['email'] = $email;
							$disabled['email'] = ' disabled="disabled"';
						}
						if (!empty($address['website'])) {
							$stored['website'] = $address['website'];
							$disabled['website'] = ' disabled="disabled"';
						}
					}
					$data = npgFilters::apply('comment_form_data', array('data' => $stored, 'disabled' => $disabled));
					$disabled = $data['disabled'];
					$stored = $data['data'];

					foreach ($data as $check) {
						foreach ($check as $v) {
							if ($v) {
								$_HTML_cache->disable(); //	shouldn't cache partially filled in pages
								break 2;
							}
						}
					}

					if (!empty($addcommenttext)) {
						echo $addcommenttext;
					}
					?>
					<div id="commententry" <?php echo $comment_commententry_mod; ?>>
						<?php
						$theme = getCurrentTheme();
						$form = getPlugin('comment_form/comment_form.php', $theme);
						require($form);
						?>
					</div><!-- id="commententry" -->
					<?php
				}
			} else {
				?>
				<div id="commententry">
					<h3><?php echo gettext('Closed for comments.'); ?></h3>
				</div><!-- id="commententry" -->
				<?php
			}
			?>
		</div><!-- id="commentcontent" -->
		<?php
		if (getOption('comment_form_rss') && getOption('RSS_comments')) {
			?>
			<br clear="all" />
			<?php
			if (class_exists('RSS')) {
				switch ($_gallery_page) {
					case "image.php":
						printRSSLink("Comments-image", "", gettext("Subscribe to comments"), "");
						break;
					case "album.php":
						printRSSLink("Comments-album", "", gettext("Subscribe to comments"), "");
						break;
					case "news.php":
						printRSSLink("Comments-news", "", gettext("Subscribe to comments"), "");
						break;
					case "pages.php":
						printRSSLink("Comments-page", "", gettext("Subscribe to comments"), "");
						break;
				}
			}
		}
		?>
		<!-- end printCommentForm -->
		<?php
	}
}
?>