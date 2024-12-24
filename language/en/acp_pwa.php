<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

/**
 * DO NOT CHANGE
 */

use mattf\pwakit\ext;

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
	'ACP_PWA_KIT_SETTINGS_EXPLAIN'	=> 'Here you can manage the members of your Web Application Manifest. You can also preview the touch icons found in <samp>' . ext::PWA_ICON_DIR . '</samp>.',
	'ACP_PWA_KIT_SITE_NAME_EXPLAIN'	=> 'Used to specify the full name of your web application. This can be configured in General -> Board Settings.',
	'ACP_PWA_KIT_SHORT_NAME_EXPLAIN'=> 'Used to specify a short name for your web application, which may be used when the full name is too long for the available space. This can be configured in General -> Board Settings.',
	'ACP_PWA_KIT_PRESENTATION'		=> 'Presentation',
	'ACP_PWA_THEME_COLOR'			=> 'Theme colour (optional)',
	'ACP_PWA_THEME_COLOR_EXPLAIN'	=> 'Used to specify the default colour for your web application’s user interface. This colour may be applied to various browser UI elements, such as the toolbar, address bar, and status bar.',
	'ACP_PWA_BG_COLOR'				=> 'Background colour (optional)',
	'ACP_PWA_BG_COLOR_EXPLAIN'		=> 'Used to specify an initial background colour for your web application. This colour appears in the application window before your application’s stylesheets have loaded.',
	'ACP_PWA_KIT_ICONS'				=> 'Web application icons',
	'ACP_PWA_KIT_ICONS_EXPLAIN'		=> 'PNG image files that represent your web application. Multiple sizes are preferred for compatibility with various devices. Upload images to <samp>' . ext::PWA_ICON_DIR . '</samp>.',
	'ACP_PWA_INVALID_COLOR'			=> 'The color code “<samp>%s</samp>” is not a valid hex code.',
]);
