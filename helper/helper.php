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

use FastImageSize\FastImageSize;
use phpbb\exception\runtime_exception;
use phpbb\extension\manager as ext_manager;
use phpbb\pwakit\storage\storage;
use phpbb\storage\exception\storage_exception;
use phpbb\storage\helper as storage_helper;

class helper
{
	/** @var ext_manager */
	protected ext_manager $extension_manager;

	/** @var FastImageSize */
	protected FastImageSize $imagesize;

	/** @var storage */
	protected storage $storage;

	/** @var storage_helper */
	protected storage_helper $storage_helper;

	/** @var string */
	protected string $root_path;

	/**
	 * Constructor
	 *
	 * @param ext_manager $extension_manager
	 * @param FastImageSize $imagesize
	 * @param storage $storage
	 * @param storage_helper $storage_helper
	 * @param string $root_path
	 */
	public function __construct(ext_manager $extension_manager, FastImageSize $imagesize, storage $storage, storage_helper $storage_helper, string $root_path)
	{
		$this->extension_manager = $extension_manager;
		$this->imagesize = $imagesize;
		$this->storage = $storage;
		$this->storage_helper = $storage_helper;
		$this->root_path = $root_path;
	}

	/**
	 * Get the storage path for the current storage definition
	 *
	 * @return string
	 */
	public function get_storage_path(): string
	{
		return $this->storage_helper->get_current_definition($this->storage->get_name(), 'path');
	}

	/**
	 * Get an array of icons
	 *
	 * @param string $use_path Optional path to use for icons, for example ./
	 * @return array Array of icons
	 */
	public function get_icons(string $use_path = ''): array
	{
		$images = $this->get_stored_images();
		return $this->prepare_icons($images, $use_path);
	}

	/**
	 * Resync icons by ensuring all uploaded icons are tracked in the storage table
	 *
	 * @return void
	 */
	public function resync_icons(): void
	{
		$path = $this->get_storage_path() . '/';

		// Get both arrays at once and pre-process paths
		$untracked_files = array_map(static function($file) use ($path) {
			return str_replace($path, '', $file);
		}, $this->get_images());

		$tracked_files = array_map(static function($file) use ($path) {
			return str_replace($path, '', $file);
		}, $this->get_stored_images());

		// Process tracking changes
		$files_to_track = array_diff($untracked_files, $tracked_files);
		$files_to_untrack = array_diff($tracked_files, $untracked_files);

		// Batch process tracking operations
		$this->storage->track_files($files_to_track);

		foreach ($files_to_untrack as $file)
		{
			$this->storage->untrack_file($file);
		}
	}

	/**
	 * Delete icon from storage and remove it from the storage table
	 *
	 * @param string $path
	 * @throws runtime_exception
	 * @return string
	 */
	public function delete_icon(string $path): string
	{
		if (empty($path))
		{
			throw new runtime_exception('ACP_PWA_IMG_DELETE_PATH_ERR');
		}

		// Remove any directory traversal attempts
		$storage_path = $this->get_storage_path() . '/';
		$pos = strpos($path, $storage_path);
		if ($pos !== false)
		{
			$path = substr($path, $pos + strlen($storage_path));
		}
		else
		{
			$path = basename($path);
		}

		// Check for valid filename characters
		if (!preg_match('#^[a-zA-Z0-9_\-./]+$#', $path))
		{
			throw new runtime_exception('ACP_PWA_IMG_DELETE_NAME_ERR');
		}

		try
		{
			$this->storage->delete($path);
		}
		catch (storage_exception $e)
		{
			throw new runtime_exception($e->getMessage());
		}

		return $path;
	}

	/**
	 * Get an array of all image paths from the storage table
	 *
	 * @return array Array of found image paths
	 */
	protected function get_stored_images(): array
	{
		$path = $this->get_storage_path();
		$images = $this->storage->get_tracked_files();

		$result = [];
		foreach ($images as $image)
		{
			if (stripos(strrev($image), 'gnp.') === 0)
			{
				$result[] = $path . '/' . $image;
			}
		}

		return $result;
	}

	/**
	 * Get an array of all image paths from our site icons folder
	 *
	 * @return array Array of found image paths
	 */
	protected function get_images(): array
	{
		static $images = null;

		if ($images === null)
		{
			$finder = $this->extension_manager->get_finder();
			$images = $finder
				->set_extensions([])
				->suffix('.png')
				->core_path($this->get_storage_path() . '/')
				->find();
		}

		return array_keys($images);
	}

	/**
	 * Prepare icons array
	 *
	 * @param array $images Array of found image paths
	 * @param string $use_path Optional path to use for icons, for example ./
	 * @return array Array of icons
	 */
	private function prepare_icons(array $images, string $use_path): array
	{
		// Use array_reduce instead of foreach for better performance
		return array_reduce($images, function ($carry, $image) use ($use_path)
		{
			$image_info = $this->imagesize->getImageSize($this->root_path . $image);

			if ($image_info === false)
			{
				return $carry;
			}

			$carry[] = [
				'src' => $use_path ? $use_path . $image : $image,
				'sizes' => $image_info['width'] . 'x' . $image_info['height'],
				'type' => 'image/png'
			];

			return $carry;
		}, []);
	}
}
