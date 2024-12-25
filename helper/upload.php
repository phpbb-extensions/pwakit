<?php
/**
 *
 * Advertisement management. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017 phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\pwakit\helper;

use phpbb\exception\runtime_exception;
use phpbb\files\filespec;
use phpbb\files\upload as files_upload;
use phpbb\filesystem\filesystem_interface;
use phpbb\pwakit\ext;

class upload
{
	/** @var files_upload */
	protected files_upload $files_upload;

	/** @var filesystem_interface */
	protected filesystem_interface $filesystem;

	/** @var filespec */
	protected filespec $file;

	/**
	 * Constructor
	 *
	 * @param files_upload 			$files_upload	Files upload object
	 * @param filesystem_interface	$filesystem		Filesystem object
	 */
	public function __construct(files_upload $files_upload, filesystem_interface $filesystem)
	{
		$this->files_upload = $files_upload;
		$this->filesystem = $filesystem;
	}

	public function set_file($file): void
	{
		$this->file = $file;
	}

	/**
	 * Handle banner upload
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
		$this->set_file($this->files_upload->handle_upload('files.types.form', 'pwa_upload'));
		$this->file->clean_filename('real');

		// Move file to proper location
		if (!$this->file->move_file(ext::PWA_ICON_DIR))
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
	 */
	public function remove(): void
	{
		$this->file->remove();
	}
}
