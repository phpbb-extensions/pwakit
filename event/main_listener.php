<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace mattf\pwakit\event;

use FastImageSize\FastImageSize;
use mattf\pwakit\ext;
use phpbb\event\data;
use phpbb\extension\manager as ext_manager;
use phpbb\template\template;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class main_listener implements EventSubscriberInterface
{
	protected ext_manager $extension_manager;
	protected FastImageSize $imagesize;
	protected template $template;

	/**
	 * Constructor
	 *
	 * @param ext_manager $extension_manager
	 * @param FastImageSize $imagesize
	 * @param template $template
	 */
	public function __construct(ext_manager $extension_manager, FastImageSize $imagesize, template $template)
	{
		$this->extension_manager = $extension_manager;
		$this->imagesize = $imagesize;
		$this->template = $template;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'core.page_header'		=> 'touch_icons',
			'core.modify_manifest'	=> 'manifest_icons',
		];
	}

	/**
	 * Add touch icons to a template var
	 *
	 * @return void
	 */
	public function touch_icons(): void
	{
		$this->template->assign_var('U_TOUCH_ICONS', array_column($this->get_icons(), 'src'));
	}

	/**
	 * Add icons to the manifest
	 *
	 * @param data $event
	 * @return void
	 */
	public function manifest_icons(data $event): void
	{
		$icons = $this->get_icons();
		if (empty($icons))
		{
			return;
		}

		$icons = array_map(static function($icon) use ($event) {
			$icon['src'] = $event['board_path'] . $icon['src'];
			return $icon;
		}, $icons);

		$event->update_subarray('manifest', 'icons', $icons);
	}

	/**
	 * Get an array of icons
	 *
	 * @return array Array of icons
	 */
	protected function get_icons(): array
	{
		static $icons = [];

		if (!empty($icons))
		{
			return $icons;
		}

		$images = $this->get_images();
		$icons = [];

		foreach ($images as $image)
		{
			$image_info = $this->imagesize->getImageSize($image);
			if ($image_info === false)
			{
				continue;
			}

			$icons[] = [
				'src'   => $image,
				'sizes' => $image_info['width'] . 'x' . $image_info['height'],
				'type'  => 'image/png'
			];
		}

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
}
