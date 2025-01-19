<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024 phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\pwakit\storage;

use phpbb\cache\driver\driver_interface as cache;
use phpbb\config\config;
use phpbb\db\driver\driver_interface as db;

class file_tracker extends \phpbb\storage\file_tracker
{
	public const STORAGE_NAME = 'phpbb_pwakit';

	protected config $config;

	protected string $phpbb_root_path;

	/**
	 * Constructor
	 *
	 * @param cache $cache
	 * @param db $db
	 * @param string $storage_table
	 * @param config $config
	 * @param string $phpbb_root_path
	 */
	public function __construct(cache $cache, db $db, string $storage_table, config $config, string $phpbb_root_path)
	{
		parent::__construct($cache, $db, $storage_table);
		$this->config = $config;
		$this->phpbb_root_path = $phpbb_root_path;
	}

	/**
	 * Gets tracked files in the storage table
	 *
	 * @return array
	 */
	public function get_tracked_files(): array
	{
		$sql = 'SELECT file_path FROM ' . $this->storage_table . "
			WHERE storage = '" . self::STORAGE_NAME . "'
			ORDER BY file_path";
		$result = $this->db->sql_query($sql);
		$files = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return array_column($files, 'file_path');
	}

	public function pwakit_track_files(string $storage, array $files): void
	{
		$files_ary = [];
		foreach ($files as $file)
		{
			$files_ary[] = [
				'file_path'	=> $file,
				'filesize'	=> filesize($this->phpbb_root_path . $this->config['storage\\phpbb_pwakit\\config\\path'] . '/' . $file),
			];
		}

		if (!empty($files_ary))
		{
			parent::track_files($storage, $files_ary);
		}
	}
}
