<?php
/**
 * @package plugins/menu_manager
 */
define('OFFSET_PATH', 4);
require_once(dirname(dirname(dirname(__FILE__))) . '/admin-globals.php');
if (extensionEnabled('zenpage')) {
	require_once(dirname(dirname(dirname(__FILE__))) . '/' . PLUGIN_FOLDER . '/zenpage/admin-functions.php');
}
require_once(dirname(__FILE__) . '/menu_manager-admin-functions.php');

admin_securityChecks(ADMIN_RIGHTS, currentRelativeURL());

$page = 'edit';

$result = NULL;
$reports = array();
if (isset($_GET['id'])) {
	$result = getItem(sanitize($_GET['id']));
}
if (isset($_GET['save'])) {
	XSRFdefender('update_menu');
	if ($_POST['update']) {
		$result = updateMenuItem($reports);
	} else {
		$result = addItem($reports);
	}
}
if (isset($_GET['del'])) {
	XSRFdefender('delete_menu');
	deleteItem($reports);
}

printAdminHeader('menu', (is_array($result) && $result['id']) ? gettext('edit') : gettext('add'));
scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/zenpage/zenpage.css');
$menuset = checkChosenMenuset();
?>
</head>
<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php
		printTabs();
		?>
		<div id="content">
			<script type="text/javascript">
				// <!-- <![CDATA[
				function handleSelectorChange(type) {
					$('#add,#titlelabel,#link_row,#link,#link_label,#visible_row,#show_visible,#span_row').show();
					$('#include_li_label').hide();
					$('#type').val(type);
					$('#link_label').html('<?php echo js_encode(gettext('URL')); ?>');
					$('#titlelabel').html('<?php echo js_encode(gettext('Title')); ?>');
					$('#XSRFToken').val('<?php echo getXSRFToken('update_menu'); ?>');
					switch (type) {
						case 'all_items':
							$('#albumselector,#pageselector,#categoryselector,#custompageselector,#titleinput,#titlelabel,#link_row,#visible_row,#span_row').hide();
							$('#selector').html('<?php echo js_encode(gettext("All menu items")); ?>');
							$('#description').html('<?php echo js_encode(gettext('This adds menu items for all objects. (It creates a "default" menuset.)')); ?>');
							break;
						case 'siteindex':
							$('#albumselector,#pageselector,#categoryselector,#custompageselector,#link_row').hide();
							$('#selector').html('<?php echo js_encode(gettext("Site index")); ?>');
							$('#description').html('<?php echo js_encode(gettext("This is the site Index page.")); ?>');
							$('#link').attr('disabled', true);
							$('#titleinput').show();
							$('#link').val('<?php echo WEBPATH; ?>/');
							break;
						case 'all_albums':
							$('#albumselector,#pageselector,#categoryselector,#titleinput,#titlelabel,#link_row,#visible_row,#span_row').hide();
							$('#selector').html('<?php echo js_encode(gettext("All Albums")); ?>');
							$('#description').html('<?php echo js_encode(gettext("This adds menu items for all albums.")); ?>');
							break;
						case 'album':
							$('#pageselector,#categoryselector,#custompageselector,#titleinput,#link_row').hide();
							$('#selector').html('<?php echo js_encode(gettext("Album")); ?>');
							$('#description').html('<?php echo js_encode(gettext("Creates a link to an Album.")); ?>');
							$('#link').attr('disabled', true);
							$('#albumselector').show();
							$('#titlelabel').html('<?php echo js_encode(gettext('Album')); ?>');
							$('#albumselector').change(function () {
								$('#link').val($(this).val());
							});
							break;
<?php
if (extensionEnabled('zenpage')) {
	?>
							case 'all_pages':
								$('#albumselector,#pageselector,#categoryselector,#custompageselector,#titleinput,#titlelabel,#link_row,#visible_row,#span_row').hide();
								$('#selector').html('<?php echo js_encode(gettext("All Zenpage pages")); ?>');
								$('#description').html('<?php echo js_encode(gettext("This adds menu items for all Zenpage pages.")); ?>');
								break;
							case 'page':
								$('#albumselector,#categoryselector,#custompageselector,#link_row,#titleinput').hide();
								$('#selector').html('<?php echo js_encode(gettext("Zenpage page")); ?>');
								$('#description').html('<?php echo js_encode(gettext("Creates a link to a Zenpage Page.")); ?>');
								$('#link').attr('disabled', true);
								$('#pageselector').show();
								$('#titlelabel').html('<?php echo js_encode(gettext('Page')); ?>');
								$('#pageselector').change(function () {
									$('#link').val($(this).val());
								});
								break;
							case 'newsindex':
								$('#albumselector,#pageselector,#categoryselector,#custompageselector,#link_row').hide();
								$('#selector').html('<?php echo js_encode(gettext("Zenpage news index")); ?>');
								$('#description').html('<?php echo js_encode(gettext("Creates a link to the Zenpage News Index.")); ?>');
								$('#link').attr('disabled', true);
								$('#titleinput').show();
								$('#link').val('<?php echo getNewsIndexURL(); ?>');
								break;
							case 'all_categories':
								$('#albumselector,#pageselector,#categoryselector,#custompageselector,#titleinput,#titlelabel,#link_row,#visible_row,#span_row').hide();
								$('#selector').html('<?php echo js_encode(gettext("All Zenpage categories")); ?>');
								$('#description').html('<?php echo js_encode(gettext("This adds menu items for all Zenpage categories.")); ?>');
								break;
							case 'category':
								$('#albumselector,#pageselector,#custompageselector,#custompageselector,#titleinput,#link_row').hide();
								$('#selector').html('<?php echo js_encode(gettext("Zenpage news category")); ?>');
								$('#description').html('<?php echo js_encode(gettext("Creates a link to a Zenpage News article category.")); ?>');
								$("#link").attr('disabled', true);
								$('#categoryselector').show();
								$('#titlelabel').html('<?php echo js_encode(gettext('Category')); ?>');
								$('#categoryselector').change(function () {
									$('#link').val($(this).val());
								});
								break;
	<?php
}
?>
						case "albumindex":
							$('#albumselector,#pageselector,#categoryselector,#custompageselector,#link_row').hide();
							$('#selector').html('<?php echo js_encode(gettext("Album index")); ?>');
							$('#description').html('<?php echo js_encode(gettext("Creates a link to Album index page for themes which do not show the albums on the Gallery index.")); ?>');
							$('#link').attr('disabled', true);
							$('#titleinput').show();
							$('#link').val('<?php echo WEBPATH; ?>/');
							break;
						case 'custompage':
							$('#albumselector,#pageselector,#categoryselector,#link').hide();
							$('#custompageselector').show();
							$('#selector').html('<?php echo js_encode(gettext("Custom page")); ?>');
							$('#description').html('<?php echo js_encode(gettext('Creates a link to a custom theme script page.')); ?>');
							$('#link_label').html('<?php echo js_encode(gettext('Script page')); ?>');
							$('#titleinput').show();
							break;
						case "dynamiclink":
							$('#albumselector,#pageselector,#categoryselector,#custompageselector').hide();
							$('#selector').html('<?php echo js_encode(gettext("Dynamic link")); ?>');
							$('#description').html('<?php echo js_encode(gettext("Creates a dynamic link. The string will be evaluated by PHP to create the link.")); ?>');
							$('#link').prop('disabled', false);
							$('#link_label').html('<?php echo js_encode(gettext('URL')); ?>');
							$('#titleinput').show();
							break;
						case "customlink":
							$('#albumselector,#pageselector,#categoryselector,#custompageselector').hide();
							$('#selector').html('<?php echo js_encode(gettext("Custom link")); ?>');
							$('#description').html('<?php echo js_encode(gettext("Creates a link outside the standard structure. Use of a full URL is recommended (e.g. http://www.domain.com).")); ?>');
							$('#link').prop('disabled', false);
							$('#link_label').html('<?php echo js_encode(gettext('URL')); ?>');
							$('#titleinput').show();
							break;
						case 'menulabel':
							$('#albumselector,#pageselector,#categoryselector,#custompageselector,#link_row').hide();
							$('#selector').html('<?php echo js_encode(gettext("Label")); ?>');
							$('#description').html('<?php echo js_encode(gettext("Creates a <em>label</em> to use in menu structures).")); ?>');
							$('#titleinput').show();
							break;
						case 'menufunction':
							$('#albumselector,#pageselector,#categoryselector,#custompageselector').hide();
							$('#selector').html('<?php echo js_encode(gettext("Function")); ?>');
							$('#description').html('<?php echo js_encode(gettext('Executes the PHP function provided.')); ?>');
							$('#link_label').html('<?php echo js_encode(gettext('Function')); ?>');
							$('#link').prop('disabled', false);
							$('#titleinput').show();
							$('#include_li_label').show();
							break;
						case 'html':
							$('#albumselector,#pageselector,#categoryselector,#custompageselector,#span_row').hide();
							$('#selector').html('<?php echo js_encode(gettext("HTML")); ?>');
							$('#description').html('<?php echo js_encode(gettext('Inserts custom HTML.')); ?>');
							$('#link_label').html('<?php echo js_encode(gettext('HTML')); ?>');
							$('#link').prop('disabled', false);
							$('#titleinput').show();
							$('#include_li_label').show();
							break;
						case "":
							$("#selector").html("");
							$("#add").hide();
							break;
					}
				}
				//]]> -->
			</script>
			<script type="text/javascript">
				//<!-- <![CDATA[
				window.addEventListener('load', function () {
<?php
if (is_array($result)) {
	?>
						handleSelectorChange('<?php echo $result['type']; ?>');
	<?php
} else {
	?>
						$('#albumselector,#pageselector,#categoryselector,#titleinput').hide();
	<?php
}
?>
					$('#typeselector').change(function () {
						$('input').val(''); // reset all input values so we do not carry them over from one type to another
						$('#link').val('');
						handleSelectorChange($(this).val());
					});
				}, false);
				//]]> -->
			</script>
			<?php
			npgFilters::apply('admin_note', 'menu', 'edit');
			?>
			<h1>
				<?php
				if (is_array($result) && $result['id']) {
					echo gettext("Menu Manager: Edit Menu Item");
				} else {
					echo gettext("Menu Manager: Add Menu Item");
				}
				?>
			</h1>
			<div class="tabbox">
				<?php
				foreach ($reports as $report) {
					echo $report;
				}
				if (isset($_GET['save']) && !isset($_GET['add'])) {
					?>
					<div class="messagebox fade-message">
						<h2>
							<?php echo gettext("Changes applied") ?>
						</h2>
					</div>
					<?php
				}
				?>
				<p class="buttons">
					<a href="<?php echo getAdminLink(PLUGIN_FOLDER . '/menu_manager/menu_tab.php'); ?>?menuset=<?php echo $menuset; ?>">
						<?php echo BACK_ARROW_BLUE; ?>
						<strong><?php echo gettext("Back"); ?></strong>
					</a>
					<span class="floatright">
						<a href="<?php echo getAdminLink(PLUGIN_FOLDER . '/menu_manager/menu_tab_edit.php'); ?>?add&amp;menuset=<?php echo urlencode($menuset); ?>">
							<?php echo PLUS_ICON; ?>
							<strong>
								<?php echo gettext("Add Menu Items"); ?>
							</strong>
						</a>
					</span>
				</p>
				<br class="clearall"><br />
				<div style="padding:15px; margin-top: 10px">
					<?php
					$action = $type = $id = $link = '';
					if (is_array($result)) {
						$type = $result['type'];
						$id = $result['id'];
						if (array_key_exists('link', $result)) {
							$link = $result['link'];
						}
						$action = !empty($id);
					}
					if (isset($_GET['add']) && !isset($_GET['save'])) {
						$add = '&amp;add';
						?>
						<select id="typeselector" name="typeselector" >
							<option value=""><?php echo gettext("*Select the type of the menus item you wish to add*"); ?></option>
							<option value="all_items"><?php echo gettext("All menu items"); ?></option>
							<option value="siteindex"><?php echo gettext("Site index"); ?></option>
							<option value="albumindex"><?php echo gettext("Album index"); ?></option>
							<option value="all_albums"><?php echo gettext("All Albums"); ?></option>
							<option value="album"><?php echo gettext("Album"); ?></option>
							<?php
							if (extensionEnabled('zenpage')) {
								?>
								<option value="all_pages"><?php echo gettext("All pages"); ?></option>
								<option value="page"><?php echo gettext("Page"); ?></option>
								<option value="newsindex"><?php echo gettext("News index"); ?></option>
								<option value="all_categories"><?php echo gettext("All news categories"); ?></option>
								<option value="category"><?php echo gettext("News category"); ?></option>
								<?php
							}
							?>
							<option value="custompage"><?php echo gettext("Custom theme page"); ?></option>
							<option value="customlink"><?php echo gettext("Custom link"); ?></option>
							<option value="dynamiclink"><?php echo gettext("Dynamic link"); ?></option>
							<option value="menulabel"><?php echo gettext("Label"); ?></option>
							<option value="menufunction"><?php echo gettext("Function"); ?></option>
							<option value="html"><?php echo gettext("HTML"); ?></option>
						</select>
						<?php
					} else {
						$add = '&amp;update';
					}
					?>
					<form class="dirtylistening" onReset="setClean('add');" autocomplete="off"  method="post" id="add" name="add" action="<?php echo getAdminLink(PLUGIN_FOLDER . '/menu_manager/menu_tab_edit.php'); ?>?save<?php
					echo $add;
					if ($menuset) {
											echo '&amp;menuset=' . $menuset;
					}
					?>" style="display: none">
								<?php XSRFToken('update_menu'); ?>
						<input type="hidden" name="update" id="update" value="<?php echo html_encode($action); ?>" />
						<input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
						<input type="hidden" name="link-old" id="link-old" value="<?php echo html_encode($link); ?>" />
						<input type="hidden" name="type" id="type" value="<?php echo $type; ?>" />
						<table style="width: 80%">
							<?php
							if (is_array($result)) {
								$selector = html_encode($menuset);
							} else {
								$result = array('id' => NULL, 'title' => '', 'link' => '', 'show' => 1, 'type' => NULL, 'include_li' => 1, 'span_id' => '', 'span_class' => '');
								$selector = getMenuSetSelector(false);
							}
							?>
							<tr>
								<td colspan="100%"><?php printf(gettext("Menu <em>%s</em>"), $selector); ?></td>
							</tr>
							<tr style="vertical-align: top">
								<td style="width: 13%"><?php echo gettext("Type"); ?></td>
								<td id="selector"></td>
							</tr>
							<tr style="vertical-align: top">
								<td><?php echo gettext("Description"); ?></td>
								<td id="description"></td>
							</tr>
							<tr>
								<td><span id="titlelabel"><?php echo gettext("Title"); ?></span></td>
								<td>
									<span id="titleinput"><?php print_language_string_list($result['title'], "title", false, NULL, '', 100); ?></span>
									<?php
									printAlbumsSelector($result['link']);
									if (class_exists('CMS')) {
										printPagesSelector($result['link']);
										printNewsCategorySelector($result['link']);
									}
									?>
								</td>
							</tr>
							<tr id="link_row">
								<td><span id="link_label"></span></td>
								<td>
									<?php printCustomPageSelector($result['link']); ?>
									<input name="link" type="text" size="100" id="link" value="<?php echo html_encode($result['link']); ?>" />
								</td>
							</tr>
							<tr id="visible_row">
								<td>
									<label id="show_visible" for="show" style="display: inline">
										<input name="show" type="checkbox" id="show" value="1" <?php
										if ($result['show'] == 1) {
											echo "checked='checked'";
										}
										?> style="display: inline" />
													 <?php echo gettext("published"); ?>
									</label>
								</td>
								<td>
									<label id="include_li_label" style="display: inline">
										<input name="include_li" type="checkbox" id="include_li" value="1" <?php
										if ($result['include_li'] == 1) {
											echo "checked='checked'";
										}
										?> style="display: inline" />
													 <?php echo gettext("Include <em>&lt;LI&gt;</em> element"); ?>
									</label>
								</td>
							</tr>
							<tr id="span_row">
								<td>
									<label>
										<span class="nowrap">
											<input name="span" type="checkbox" id="span" value="1" <?php
											if ($result['span_id'] || $result['span_class']) {
												echo "checked='checked'";
											}
											?> style="display: inline" />
														 <?php echo gettext("Add <em>span</em> tags"); ?>
										</span>
									</label>
								</td>
								<td>
									<?php echo gettext('ID'); ?>
									<input name="span_id" type="text" size="20" id="span_id" value="<?php echo html_encode($result['span_id']); ?>" />
									<?php echo gettext('Class'); ?>
									<input name="span_class" type="text" size="20" id="span_class" value="<?php echo html_encode($result['span_class']); ?>" />
								</td>
							</tr>
							<?php
							if (is_array($result) && !empty($result['type'])) {
								$array = getItemTitleAndURL($result);
								if ($array['invalid']) {
									?>
									<tr>
										<td colspan="100%">
											<span class="notebox"><?php
												switch ($array['invalid']) {
													case 1:
														printf(gettext('Target does not exist in <em>%1$s</em> theme'), $array['theme']);
														break;
													case 2:
														echo gettext('Target does not exist');
														break;
													case 3:
														echo gettext('Zenpage plugin not enabled');
														break;
												}
												if (array_key_exists('theme', $array)) {
													printf(gettext('Target does not exist in <em>%1$s</em> theme'), $array['theme']);
												} else {
													echo gettext('Target does not exist.');
												}
												?></span>
										</td>
									</tr>
									<?php
								}
							}
							?>
						</table>
						<p class="buttons">
							<button type="submit"><?php echo CHECKMARK_GREEN; ?> <?php echo gettext("Apply"); ?></strong></button>
							<button type="reset">
								<?php echo CROSS_MARK_RED; ?>
								<strong><?php echo gettext("Reset"); ?></strong>
							</button>
						</p>
						<br class="clearall"><br />
					</form>
				</div>
			</div>
			<?php printAdminFooter(); ?>
		</div>
	</div>
</body>
</html>
