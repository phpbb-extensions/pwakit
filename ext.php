<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace mattf\pwakit;

use phpbb\extension\base;
use phpbb\filesystem\exception\filesystem_exception;

/**
 * Progressive Web App Extension base
 *
 * It is recommended to remove this file from
 * an extension if it is not going to be used.
 */
class ext extends base
{
	public const NEW_ICON_DIR = 'images/site_icons';
	protected const PHPBB_MIN_VERSION = '4.0.0-dev';

	/**
	 * {@inheritdoc}
	 */
	public function is_enableable(): bool|array
	{
		$config = $this->container->get('config');
		return $this->version_check($config['version']) && $this->version_check(PHPBB_VERSION);
	}

	/**
	 * Create the directory images/site_icons if it does not already exist
	 *
	 * {@inheritdoc}
	 */
	public function enable_step($old_state): bool|string
	{
		if ($old_state === false)
		{
			$filesystem = $this->container->get('filesystem');
			$root_path = $this->container->getParameter('core.root_path');
			$new_icon_path = $root_path . self::NEW_ICON_DIR;

			try
			{
				if (!$filesystem->exists($new_icon_path))
				{
					$filesystem->mkdir($new_icon_path, 0755);
					$filesystem->touch($new_icon_path . '/index.htm');
				}
			}
			catch (filesystem_exception $e)
			{
				$log  = $this->container->get('log');
				$user = $this->container->get('user');
				$log->add('critical', $user->data['user_id'], $user->ip, 'LOG_PWA_DIR_FAIL', false, [$e->get_filename(), $e->getMessage()]);
			}

			return 'create-icon-dir';
		}

		return parent::enable_step($old_state);
	}

	/**
	 * Enable version check
	 *
	 * @param int|string $version The version to check
	 * @return bool
	 */
	protected function version_check(int|string $version): bool
	{
		return phpbb_version_compare($version, self::PHPBB_MIN_VERSION, '>=');
	}
}
