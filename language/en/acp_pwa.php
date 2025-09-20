<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024 phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

/**
 * DO NOT CHANGE
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, [
	'ACP_PWA_KIT_EXPLAIN'			=> 'Here you can manage the appearance and branding of your Web Application as it will appear on mobile devices with custom app icons and UI colors.',
	'ACP_PWA_KIT_SITE_NAME_EXPLAIN'	=> 'Used to specify the full name of your web application. This can be configured in <a href="%s">General » Board settings</a>.',
	'ACP_PWA_KIT_SHORT_NAME_EXPLAIN'=> 'Used to specify a short name for your web application, which may be used when the full name is too long for the available space. This can be configured in  <a href="%s">General » Board settings</a>.',
	'ACP_PWA_KIT_LEGEND_IDENTITY'	=> 'Identity',
	'ACP_PWA_KIT_LEGEND_PRESENTS'	=> 'Presentation',
	'ACP_PWA_KIT_LEGEND_ICONS'		=> 'Icons',
	'ACP_PWA_COLORS'				=> 'Theme &amp; background colours (optional)',
	'ACP_PWA_COLORS_EXPLAIN'		=> 'Used to specify the default colour for your web application’s user interface. Theme colours may be applied to various browser UI elements, such as the toolbar, address bar, and status bar. Background colours appears in the application window before your application’s stylesheets have loaded.',
	'ACP_PWA_THEME_COLOR'			=> 'Theme colour',
	'ACP_PWA_BG_COLOR'				=> 'Background colour',
	'ACP_PWA_INVALID_COLOR'			=> 'The colour code “<samp>%s</samp>” is not a valid hex code.',
	'ACP_PWA_KIT_APP_ICONS'			=> 'Web app icons (optional)',
	'ACP_PWA_KIT_APP_ICONS_EXPLAIN'	=> 'Used to specify the touch-icon for your web application. This icon will be used when your web application is added to the home screen. Multiple sizes are preferred for compatibility with various devices. Note that when adding or removing icons, you may need to clear your web app/browser cache to see them update.',
	'ACP_PWA_KIT_ICONS'				=> 'Web application icons',
	'ACP_PWA_KIT_ICONS_EXPLAIN'		=> 'PNG image files that represent your web application. Multiple sizes are preferred for compatibility with various devices.',
	'ACP_PWA_STORAGE_INCOMPATIBLE'	=> 'To manage icons you must set <strong>Web app icons storage</strong> to <strong>Local</strong> in <a href="%s">General » Storage settings</a>.',
	'ACP_PWA_KIT_NO_ICONS'			=> 'No icons are available. Click <strong>Upload</strong> to add new icons or click <strong>Resync</strong> to find existing icons that were previously uploaded. Recommended PNG sizes include 180x180, 192x192 and 512x512.',
	'ACP_PWA_IMG_UPLOAD'			=> 'Upload web application icons',
	'ACP_PWA_IMG_UPLOAD_EXPLAIN'	=> 'Upload PNG images. Images are currently being stored in <samp>%1$s</samp>. This can be changed at any time to another location in <a href="%2$s">General » Storage settings</a>.',
	'ACP_PWA_IMG_UPLOAD_SUCCESS'	=> 'Image uploaded successfully.',
	'ACP_PWA_IMG_DELETE'			=> 'Delete image',
	'ACP_PWA_IMG_DELETE_CONFIRM'	=> 'Are you sure you want to delete this image?',
	'ACP_PWA_IMG_DELETE_ERROR'		=> 'An error occurred while trying to remove the image. %s',
	'ACP_PWA_IMG_DELETED'			=> '“%s” has been removed.',
	'ACP_PWA_IMG_DELETE_PATH_ERR'	=> 'Invalid file path provided.',
	'ACP_PWA_IMG_DELETE_NAME_ERR'	=> 'Invalid characters in filename.',
	'ACP_PWA_IMG_RESYNC_BTN'		=> 'Resync',
	'ACP_PWA_IMG_UPLOAD_BTN'		=> 'Upload',
]);
