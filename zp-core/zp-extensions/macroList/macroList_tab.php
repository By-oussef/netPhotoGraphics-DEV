<?php
/**
 * This is the "files" upload tab
 *
 * @package plugins/macroList
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/admin-globals.php');
admin_securityChecks(ADMIN_RIGHTS, $return = currentRelativeURL());

printAdminHeader('development', gettext('macros'));

echo "\n</head>";
?>

<body>

	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<div id="container">
				<?php
				npgFilters::apply('admin_note', 'development', '');
				?>
				<h1>
					<?php
					echo gettext('Defined Macros');
					?>
				</h1>			<div class="tabbox">
					<?php
					$macros = getMacros();
					ksort($macros);
					if (empty($macros)) {
						echo gettext('No macros have been defined.');
					} else {
						?>
						<div>
							<p><?php echo gettext('These Content macros can be used to insert items as described into <em>descriptions</em>, <em>zenpage content</em>, and <em>zenpage extra content</em>.</p> <p>Replace any parameters (<em>%d</em>) with the appropriate value.'); ?></p>
							<p><?php echo gettext('Parameter types:'); ?></p>
							<ol class="ulclean">
								<li><?php echo gettext('<em><strong>string</strong></em> may be enclosed in quotation marks when the macro is invoked. The quotes are stripped before the macro is processed.'); ?></li>
								<li><?php echo gettext('<em><strong>int</strong></em> a number'); ?></li>
								<li><?php echo gettext('<em><strong>bool</strong></em> <code>true</code> or <code>false</code>'); ?></li>
								<li><?php echo gettext('<em><strong>array</strong></em> an assignment list e.g. <code>u=w</code> <code>x=y</code>....'); ?></li>
							</ol>
							<p><?php echo gettext('Parameters within braces are optional.'); ?></p>
						</div>
						<?php
						foreach ($macros as $macro => $detail) {
							macroList_show($macro, $detail);
						}
					}
					?>
				</div>
			</div>
		</div>
		<?php printAdminFooter(); ?>
	</div>
</body>
</html>
