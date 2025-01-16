<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024 phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\pwakit;

use phpbb\extension\base;
use phpbb\filesystem\exception\filesystem_exception;

/**
 * Progressive Web App Extension base
 */
class ext extends base
{
	public const PWA_ICON_DIR = 'images/site_icons';
	public const PHPBB_MIN_VERSION = '4.0.0-dev';

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
		if ($old_state !== false)
		{
			return parent::enable_step($old_state);
		}

		$filesystem = $this->container->get('filesystem');
		$root_path = $this->container->getParameter('core.root_path');
		$icon_path = $root_path . self::PWA_ICON_DIR;

		try
		{
			if (!$filesystem->exists($icon_path))
			{
				$filesystem->mkdir($icon_path, 0755);
				$filesystem->touch($icon_path . '/index.htm');
			}
		}
		catch (filesystem_exception $e)
		{
			$log  = $this->container->get('log');
			$user = $this->container->get('user');
			$log->add(
				'critical',
				$user->data['user_id'],
				$user->ip,
				'LOG_PWA_DIR_FAIL',
				false,
				[$e->get_filename(), $e->getMessage()]
			);
		}

		return 'create-icon-dir';
	}

	/**
	 * Enable version check
	 *
	 * @param int|string $version The version to check
	 * @return bool
	 */
	private function version_check(int|string $version): bool
	{
		return phpbb_version_compare($version, self::PHPBB_MIN_VERSION, '>=');
	}
}
