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

class storage extends \phpbb\storage\storage
{
	/**
	 * Gets tracked files in the storage table
	 *
	 * @return array
	 */
	public function get_tracked_files(): array
	{
		$sql = 'SELECT file_path FROM ' . $this->file_tracker->storage_table . "
			WHERE storage = '" . $this->db->sql_escape($this->get_name()) . "'
			ORDER BY file_path";
		$result = $this->db->sql_query($sql);
		$files = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return array_column($files, 'file_path');
	}

	/**
	 * Wrapper for file_tracker::track_file
	 *
	 * @param string $path
	 * @return void
	 */
	public function track_file(string $path): void
	{
		$this->file_tracker->track_file('phpbb_pwakit', $path, filesize($path));
	}

	/**
	 * Wrapper for file_tracker::untrack_file
	 *
	 * @param string $path
	 * @return void
	 */
	public function untrack_file(string $path): void
	{
		$this->file_tracker->untrack_file('phpbb_pwakit', $path);
	}

	/**
	 * Wrapper for file_tracker::track_files
	 *
	 * @param array $paths
	 * @return void
	 */
	public function track_files(array $paths): void
	{
		$files = [];
		foreach ($paths as $path)
		{
			$files[] = [
				'file_path' => $path,
				'filesize'  => filesize($path),
			];
		}

		if (!empty($files))
		{
			$this->file_tracker->track_files('phpbb_pwakit', $files);
		}
	}
}
