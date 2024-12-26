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
use phpbb\cache\driver\driver_interface as cache;
use phpbb\extension\manager as ext_manager;
use phpbb\storage\helper as storage_helper;

class helper
{
	/** @var cache */
	protected cache $cache;

	/** @var ext_manager */
	protected ext_manager $extension_manager;

	/** @var FastImageSize */
	protected FastImageSize $imagesize;

	/** @var string */
	protected string $root_path;

	/** @var storage_helper */
	protected storage_helper $storage;

	/**
	 * Constructor
	 *
	 * @param cache $cache
	 * @param ext_manager $extension_manager
	 * @param FastImageSize $imagesize
	 * @param storage_helper $storage
	 * @param string $root_path
	 */
	public function __construct(cache $cache, ext_manager $extension_manager, FastImageSize $imagesize, storage_helper $storage, string $root_path)
	{
		$this->cache = $cache;
		$this->extension_manager = $extension_manager;
		$this->imagesize = $imagesize;
		$this->storage = $storage;
		$this->root_path = $root_path;
	}

	/**
	 * Get an array of icons (icons are cached for an hour))
	 *
	 * @param string $use_path Optional path to use for icons, for example ./
	 * @return array Array of icons
	 */
	public function get_icons(string $use_path = ''): array
	{
		// Use the path as cache key to store different versions
		$cache_key = md5($use_path);

		$icons = $this->cache->get('pwakit_icons_' . $cache_key);

		if ($icons === false)
		{
			$images = $this->get_images();
			$icons = $this->prepare_icons($images, $use_path);

			$this->cache->put('pwakit_icons_' . $cache_key, $icons, 3600);
		}

		return $icons;
	}

	/**
	 * Reset icons by clearing any cache of icons
	 *
	 * @param string $use_path
	 * @return void
	 */
	public function reset_icons(string $use_path = ''): void
	{
		$cache_key = md5($use_path);

		$this->cache->destroy('pwakit_icons_' . $cache_key);
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
				->suffix(".png")
				->core_path($this->storage->get_current_definition('phpbb_pwakit', 'path') . '/')
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
