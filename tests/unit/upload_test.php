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

use PHPUnit\Framework\MockObject\MockObject;
use phpbb\exception\runtime_exception;
use phpbb\files\filespec_storage;
use phpbb\files\upload as files_upload;
use phpbb\pwakit\helper\upload;
use phpbb\storage\storage;
use phpbb_test_case;

class upload_test extends phpbb_test_case
{
	protected MockObject|\phpbb\files\upload $files_upload;
	protected MockObject|filespec_storage $file;
	protected storage|MockObject $storage;

	protected static function setup_extensions(): array
	{
		return ['phpbb/pwakit'];
	}

	protected function setUp(): void
	{
		parent::setUp();

		$this->files_upload = $this->getMockBuilder(files_upload::class)
			->disableOriginalConstructor()
			->getMock();
		$this->storage = $this->getMockBuilder(storage::class)
			->disableOriginalConstructor()
			->getMock();
	}

	public function get_upload(): upload
	{
		return new upload(
			$this->files_upload,
			$this->storage
		);
	}

	public function upload_data(): array
	{
		return [
			[false],
			[true],
		];
	}

	/**
	 * @dataProvider upload_data
	 */
	public function test_upload($file_move_success)
	{
		$upload = $this->get_upload();

		$this->files_upload->expects(self::once())
			->method('reset_vars');

		$this->files_upload->expects(self::once())
			->method('set_allowed_extensions')
			->with(['png']);

		$file = $this->getMockBuilder(filespec_storage::class)
			->disableOriginalConstructor()
			->getMock();

		if (!$file_move_success)
		{
			$file->error[] = 'FILE_MOVE_UNSUCCESSFUL';
		}

		$this->files_upload->expects(self::once())
			->method('handle_upload')
			->with('files.types.form_storage', 'pwa_upload')
			->willReturn($file);

		$file->expects(self::once())
			->method('clean_filename')
			->with('real');

		$file->expects(self::once())
			->method('move_file')
			->with($this->storage)
			->willReturn($file_move_success);

		if (!$file_move_success)
		{
			$file->expects(self::once())
				->method('set_error')
				->with('FILE_MOVE_UNSUCCESSFUL');

			$this->expectException(runtime_exception::class);
			$this->expectExceptionMessage('FILE_MOVE_UNSUCCESSFUL');

			$upload->upload();
		}
		else
		{
			$file->expects(self::once())
				->method('get')
				->with('realname')
				->willReturn('abcdef.jpg');

			$result = $upload->upload();

			self::assertEquals('abcdef.jpg', $result);
		}
	}

	public function test_remove()
	{
		$upload = $this->get_upload();

		$file = $this->getMockBuilder(filespec_storage::class)
			->disableOriginalConstructor()
			->getMock();

		$upload->set_file($file);

		$file->expects(self::once())
			->method('remove')
			->with($this->storage);

		$upload->remove();
	}
}
