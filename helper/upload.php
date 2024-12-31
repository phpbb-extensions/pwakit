<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024 phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\pwakit\helper;

use phpbb\exception\runtime_exception;
use phpbb\files\filespec_storage;
use phpbb\files\upload as files_upload;
use phpbb\storage\storage;

class upload
{
	/** @var files_upload */
	protected files_upload $files_upload;

	/** @var storage */
	protected storage $storage;

	/** @var filespec_storage */
	protected filespec_storage $file;

	/**
	 * Constructor
	 *
	 * @param files_upload $files_upload Files upload object
	 * @param storage $storage Storage object
	 */
	public function __construct(files_upload $files_upload, storage $storage)
	{
		$this->files_upload = $files_upload;
		$this->storage = $storage;
	}

	/**
	 * Set the file spec storage
	 *
	 * @param filespec_storage $file
	 * @return void
	 */
	public function set_file(filespec_storage $file): void
	{
		$this->file = $file;
	}

	/**
	 * Handle upload
	 *
	 * @throws	runtime_exception
	 * @return	string	Filename
	 */
	public function upload(): string
	{
		// Set file restrictions
		$this->files_upload->reset_vars();
		$this->files_upload->set_allowed_extensions(['png']);

		// Upload file
		$this->set_file($this->files_upload->handle_upload('files.types.form_storage', 'pwa_upload'));
		$this->file->clean_filename('real');

		// Move file to proper location
		if (!$this->file->move_file($this->storage, true))
		{
			$this->file->set_error('FILE_MOVE_UNSUCCESSFUL');
		}

		if (count($this->file->error))
		{
			throw new runtime_exception($this->file->error[0]);
		}

		return $this->file->get('realname');
	}

	/**
	 * Remove file from the filesystem
	 *
	 * @return void
	 */
	public function remove(): void
	{
		$this->file->remove($this->storage);
	}
}
