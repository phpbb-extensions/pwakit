<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024 phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\pwakit\tests\unit;

use phpbb\cache\driver\driver_interface as cache;
use phpbb\db\driver\driver_interface as db;
use phpbb\pwakit\storage\file_tracker;
use phpbb_test_case;

class file_tracker_test extends phpbb_test_case
{
	public function test_track_file_invalidates_tracked_files_query(): void
	{
		$destroyed = [];
		$cache = $this->createMock(cache::class);
		$cache->expects($this->exactly(3))
			->method('destroy')
			->willReturnCallback(static function(string $key, string $table = '') use (&$destroyed): void {
				$destroyed[] = [$key, $table];
			});

		$db = $this->createMock(db::class);
		$db->expects($this->once())
			->method('sql_multi_insert')
			->with('phpbb_storage', [[
				'storage' => file_tracker::STORAGE_NAME,
				'file_path' => 'icon.png',
				'filesize' => 123,
			]]);

		$tracker = new file_tracker($cache, $db, 'phpbb_storage');
		$tracker->track_file(file_tracker::STORAGE_NAME, 'icon.png', 123);

		$this->assertSame([
			['_storage_phpbb_pwakit_totalsize', ''],
			['_storage_phpbb_pwakit_numfiles', ''],
			['sql', 'phpbb_storage'],
		], $destroyed);
	}

	public function test_untrack_file_invalidates_tracked_files_query(): void
	{
		$destroyed = [];
		$cache = $this->createMock(cache::class);
		$cache->expects($this->exactly(3))
			->method('destroy')
			->willReturnCallback(static function(string $key, string $table = '') use (&$destroyed): void {
				$destroyed[] = [$key, $table];
			});

		$db = $this->createMock(db::class);
		$db->expects($this->once())
			->method('sql_build_array')
			->with('DELETE', [
				'file_path' => 'icon.png',
				'storage' => file_tracker::STORAGE_NAME,
			])
			->willReturn("file_path = 'icon.png' AND storage = 'phpbb_pwakit'");
		$db->expects($this->once())
			->method('sql_query')
			->with($this->stringContains("DELETE FROM phpbb_storage"));

		$tracker = new file_tracker($cache, $db, 'phpbb_storage');
		$tracker->untrack_file(file_tracker::STORAGE_NAME, 'icon.png');

		$this->assertSame([
			['_storage_phpbb_pwakit_totalsize', ''],
			['_storage_phpbb_pwakit_numfiles', ''],
			['sql', 'phpbb_storage'],
		], $destroyed);
	}
}
