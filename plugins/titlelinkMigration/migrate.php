<?php

/*
 * This plugin is a migration tool append the <em>mod_rewrite_suffix</em> to
 * CMS titlelilnks
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/utf8mb4Migration
 *
 * @Copyright 2018 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 */

// force UTF-8 Ø

define('OFFSET_PATH', 3);
require_once(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME']))) . "/zp-core/admin-globals.php");

admin_securityChecks(ADMIN_RIGHTS, $return = currentRelativeURL());

XSRFdefender('titlelinkMigration');

migrateTitleLinks('', RW_SUFFIX);

header('Location: ' . getAdminLink('admin.php') . '?action=external&msg=' . gettext('titlelink migration completed.'));
exit();
