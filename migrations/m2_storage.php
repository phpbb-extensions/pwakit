<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024 phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\pwakit\migrations;

use phpbb\cache\driver\driver_interface;
use phpbb\db\migration\container_aware_migration;
use phpbb\extension\manager;
use phpbb\pwakit\ext;
use phpbb\pwakit\storage\storage;
use phpbb\storage\adapter\adapter_interface;
use phpbb\storage\adapter_factory;
use phpbb\storage\exception\storage_exception;
use phpbb\storage\provider\local;

class m2_storage extends container_aware_migration
{
	public static function depends_on(): array
	{
		return ['\phpbb\pwakit\migrations\m1_initial'];
	}

	public function effectively_installed(): int
	{
		return $this->config->offsetExists('storage\\phpbb_pwakit\\provider')
			&& $this->config->offsetExists('storage\\phpbb_pwakit\\config\\path');
	}

	public function update_data(): array
	{
		return [
			['config.add', ['storage\\phpbb_pwakit\\provider', local::class]],
			['config.add', ['storage\\phpbb_pwakit\\config\\path', ext::PWA_ICON_DIR]],
			['custom', [[$this, 'add_tracked_files']]],
		];
	}

	public function revert_data(): array
	{
		return [
			['config.remove', ['storage\\phpbb_pwakit\\provider']],
			['config.remove', ['storage\\phpbb_pwakit\\config\\path']],
			['custom', [[$this, 'remove_tracked_files']]],
		];
	}

	/**
	 * Scan the site_icons folder if exists, and track any PNG files found in the storage db table
	 *
	 * @return void
	 */
	public function add_tracked_files(): void
	{
		/** @var manager $extension_manager */
		$extension_manager = $this->container->get('ext.manager');

		/** @var driver_interface $cache */
		$cache = $this->container->get('cache.driver');

		/** @var adapter_interface|adapter_factory $factory */
		$factory = $this->container->get('storage.adapter.factory');

		$storage = new storage(
			$this->db,
			$cache,
			$factory,
			'phpbb_pwakit',
			$this->tables['storage']
		);

		$storage_path = ext::PWA_ICON_DIR . '/';

		// Get all files at once
		$files = $extension_manager->get_finder()
			->set_extensions([])
			->suffix('.png')
			->core_path($storage_path)
			->get_files();

		// Extract just the file paths relative to the storage dir
		$files = array_map(static function($image) use ($storage_path) {
			$pos = strpos($image, $storage_path);
			return $pos !== false ? substr($image, $pos + strlen($storage_path)) : $image;
		}, $files);

		// Track each file
		foreach ($files as $file)
		{
			try
			{
				$storage->track_file($file);
			}
			catch (storage_exception)
			{
				// If file doesn't exist or other error, continue with next file
				continue;
			}
		}
	}

	/**
	 * Delete all our tracked files from the storage table
	 *
	 * @return void
	 */
	public function remove_tracked_files(): void
	{
		$this->db->sql_query('DELETE FROM ' . $this->tables['storage'] . " WHERE storage = 'phpbb_pwakit'");
	}
}
