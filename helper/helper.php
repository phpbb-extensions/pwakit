<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace mattf\pwakit\helper;

use FastImageSize\FastImageSize;
use mattf\pwakit\ext;
use phpbb\extension\manager as ext_manager;

class helper
{
	protected ext_manager $extension_manager;
	protected FastImageSize $imagesize;
	protected string $root_path;

	/**
	 * Constructor
	 *
	 * @param ext_manager $extension_manager
	 * @param FastImageSize $imagesize
	 * @param string $root_path
	 */
	public function __construct(ext_manager $extension_manager, FastImageSize $imagesize, string $root_path)
	{
		$this->extension_manager = $extension_manager;
		$this->imagesize = $imagesize;
		$this->root_path = $root_path;
	}

	/**
	 * Get an array of icons
	 *
	 * @param string $use_path Optional path to use for icons, for example ./
	 * @return array Array of icons
	 */
	public function get_icons(string $use_path = ''): array
	{
		static $cached_icons = [];

		// Use the path as cache key to store different versions
		$cache_key = md5($use_path);

		if (isset($cached_icons[$cache_key]))
		{
			return $cached_icons[$cache_key];
		}

		$images = $this->get_images();
		$icons = $this->prepare_icons($images, $use_path);

		// Cache the result
		$cached_icons[$cache_key] = $icons;

		return $icons;
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
				->core_path(ext::PWA_ICON_DIR . '/')
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
