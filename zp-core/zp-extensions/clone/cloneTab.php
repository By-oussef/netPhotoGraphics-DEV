<?php
/**
 * Clone tab
 *
 *
 * @package admin/clone
 */
if (!defined('OFFSET_PATH')) {
	define('OFFSET_PATH', 4);
}
require_once(dirname(dirname(dirname(__FILE__))) . '/admin-globals.php');

admin_securityChecks(ADMIN_RIGHTS, currentRelativeURL());

printAdminHeader('admin');
scriptLoader(CORE_SERVERPATH . 'js/sprintf.js');
?>
<script type="text/javascript">
	function reloadCloneTab() {
		this.document.location.href = '<?php echo getAdminLink(PLUGIN_FOLDER . '/clone/cloneTab.php'); ?>?tab=clone';
	}

</script>
</head>
<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<?php
			printSetupWarning();
			npgFilters::apply('admin_note', 'clone', '');
			?>

			<h1><?php echo gettext('Site clones'); ?></h1>
			<div id="container">
				<div class="tabbox">
					<?php
					$clones = npgClone::clones(false);
					$invalid = false;
					foreach ($clones as $clone => $data) {
						$version = '';
						$v = explode('-', NETPHOTOGRAPHICS_VERSION . '-');
						$myVersion = $v[0];
						if ($data['valid']) {
							$title = gettext('Visit the site.');
							$strike = '';
							if (file_exists($clone . '/' . DATA_FOLDER . '/' . CONFIGFILE)) {
								require ($clone . '/' . DATA_FOLDER . '/' . CONFIGFILE);
								$saveDB = $_DB_details;
								db_close();
								//	Setup for the MyBB database
								$config = array('mysql_host' => $conf['mysql_host'], 'mysql_database' => $conf['mysql_database'], 'mysql_prefix' => $conf['mysql_prefix'], 'mysql_user' => $conf['mysql_user'], 'mysql_pass' => $conf['mysql_pass']);
								if ($_DB_connection = db_connect($config, false)) {
									$sql = 'SELECT * FROM `' . $config['mysql_prefix'] . 'options` WHERE `name`="netphotographics_install"';
									if ($result = query_single_row($sql, FALSE)) {
										$signature = @unserialize($result['value']);
										if ($signature['NETPHOTOGRAPHICS'] != $myVersion) {
											$version = ' (' . sprintf(gettext('Last setup run version: %s'), $signature['NETPHOTOGRAPHICS']) . ')';
										}
									}
								}
								db_close();
								$_DB_connection = db_connect($saveDB);
							}
						} else { // no longer a clone of this installation
							$strike = ' style="text-decoration: line-through;"';
							$title = gettext('No longer a clone of this installation.');
							$invalid = true;
						}
						?>
						<p<?php echo $strike; ?>>
							<a href="<?php echo $data['url'] . CORE_FOLDER . '/admin.php'; ?>" target="_blank" title="<?php echo $title; ?>"><?php echo $clone; ?></a><?php echo $version; ?>
						</p>
						<?php
					}
					if ($invalid) {
						?>
						<p>
							<span class="buttons"><a href="<?php echo getAdminLink(PLUGIN_FOLDER . '/clone/clone.php'); ?>?tab=clone&purge&XSRFToken=<?php echo getXSRFToken('clone'); ?>">
									<?php echo CROSS_MARK_RED; ?>
									<?php echo gettext("Remove invalid clones."); ?>
								</a>
						</p>
						<br class="clearall">
						<?php
					}
					?>
					<br />
					<h2><?php echo gettext('Create a new install with symbolic links to the current installation scripts.'); ?></h2>
					<?php
					if (isset($success)) {
						if ($success) {
							?>
							<div class="notebox">
								<?php
								echo implode("\n", $msg) . "\n";
								?>
							</div>
							<?php
						} else {
							?>
							<div class="errorbox">
								<?php
								echo implode("\n", $msg) . "\n";
								?>
							</div>
							<?php
						}
					} else {
						?>
						<p class="warningbox">
							<?php echo gettext('<strong>Note:</strong> Existing Site scripts will be removed from the target if they exist.') ?>
						</p>

						<br />
						<?php
						$current = $folderlist = array();
						if (isset($_POST['path'])) {
							$path = sanitize($_POST['path']);
						} else {
							if (WEBPATH) {
								$path = str_replace(WEBPATH, '/', SERVERPATH);
								$current = array(trim(dirname(SERVERPATH), '/') . '/');
							} else {
								$path = SERVERPATH . '/';
							}
						}

						$downtitle = '.../' . basename($path);
						$uppath = str_replace('\\', '/', dirname($path));

						$up = explode('/', $uppath);
						$uptitle = array_pop($up);
						if (!empty($up)) {
							$uptitle = array_pop($up) . '/' . $uptitle;
						}
						if (!empty($up)) {
							$uptitle = '.../' . $uptitle;
						}

						if (substr($uppath, -1) != '/') {
							$uppath .= '/';
						}
						$npg_folders = array(ALBUMFOLDER, CACHEFOLDER, STATIC_CACHE_FOLDER, USER_PLUGIN_FOLDER, THEMEFOLDER, UPLOAD_FOLDER, CORE_FOLDER, DATA_FOLDER);

						if (($dir = opendir($path)) !== false) {
							while (($file = readdir($dir)) !== false) {
								if ($file{0} != '.' && $file{0} != '$') {
									if ((is_dir($path . $file))) {
										if (!in_array($file, $npg_folders)) { // no clones "here" or in "hidden" files
											$folderlist[$file] = $path . $file . '/';
										}
									}
								}
							}
							closedir($dir);
						}

						if (WEBPATH) {
							$urlpath = str_replace(WEBPATH, '/', FULLWEBPATH);
						} else {
							$urlpath = FULLWEBPATH;
						}
						$path = str_replace(WEBPATH, '/', SERVERPATH);
						?>
						<script type="text/javascript">
							// <!-- <![CDATA[
							var prime = '<?php echo SERVERPATH; ?>/';
							function buttonAction(data) {
								$('#newDir').val(data);
								$('#changeDir').submit();
							}
							function folderChange() {
								$('#downbutton').attr('title', '<?php echo $downtitle; ?>/' + $('#cloneFolder').val().replace(/\/$/, '').replace(/.*\//, ''));
								$('#cloneButton').attr('title', sprintf('Clone installation to %s', $('#downbutton').attr('title')));
								$('#clonePath').val($('#cloneFolder').val());
								if (prime == $('#clonePath').val()) {
									$('#cloneButton').prop('disabled', true);
								} else {
									$('#cloneButton').prop('disabled', false);
								}
								newinstall = $('#clonePath').val().replace('<?php echo $path; ?>', '');
								$('#cloneWebPath').val('<?php echo $urlpath; ?>' + newinstall);
							}
							window.addEventListener('load', folderChange, false);
							// ]]> -->
						</script>
						<form name="changeDir" id="changeDir" action="<?php echo getAdminLink(PLUGIN_FOLDER . '/clone/cloneTab.php'); ?>?tab=clone" method="post">
							<input type="hidden" name="path" id="newDir" value = "" />
							<?php
							if (empty($folderlist)) {
								echo gettext('No subfolders in: ') . ' ';
							} else {
								echo gettext('Select the destination folder:') . ' ';
							}
							echo $path;
							if (!empty($folderlist)) {
								?>
								<select id="cloneFolder" name="cloneFolder" onchange="folderChange();">
									<?php generateListFromArray($current, $folderlist, false, true); ?>
								</select>
								<?php
							}
							?>
							<span class="icons">
								<a id="upbutton" href="javascript:buttonAction('<?php echo $uppath; ?>');" title="<?php echo $uptitle; ?>">
									<?php echo ARROW_UP_GREEN; ?>
								</a>
							</span>
							<span class="icons"<?php
							if (empty($folderlist)) {
															echo

								' style="display:none;"';
							}
							?>>
								<a id="downbutton" href="javascript:buttonAction($('#cloneFolder').val());" title="">
									<?php echo ARROW_DOWN_GREEN; ?>
								</a>
							</span>
						</form>
						<br class="clearall">
						<form name="clone" action="<?php echo getAdminLink(PLUGIN_FOLDER . '/clone/clone.php'); ?>">
							<input type="hidden" name="tab" value="clone" />
							<?php XSRFToken('clone'); ?>
							<input type="hidden" name="clone" value="true" />
							<input type="hidden" name="clonePath" id="clonePath" value="" />
							<?php echo gettext('Verify WEB link to this install:'); ?><br />
							<input type="text" name="cloneWebPath" id="cloneWebPath" value="" size="100">
							<?php XSRFToken('clone'); ?>
							<br />
							<br />
							<div class="buttons pad_button" id="cloneButton">
								<button id="cloneButton" class="tooltip" type="submit" title=""<?php
								if (empty($folderlist)) {
																	echo ' disabled="disabled"';
								}
								?> >
									<img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/folder.png" alt="" /> <?php echo gettext("Clone installation"); ?>
								</button>
							</div>
							<br class="clearall">
						</form>
						<?php
					}
					?>

				</div>
			</div>
		</div><!-- content -->
		<?php printAdminFooter(); ?>
	</div><!-- main -->
</body>
</html>
