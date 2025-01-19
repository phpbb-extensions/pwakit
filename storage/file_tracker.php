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

class file_tracker extends \phpbb\storage\file_tracker
{
	public const STORAGE_NAME = 'phpbb_pwakit';

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
}
