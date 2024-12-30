<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024 phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\pwakit\event;

use phpbb\config\config;
use phpbb\event\data;
use phpbb\pwakit\helper\helper;
use phpbb\template\template;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class main_listener implements EventSubscriberInterface
{
	/** @var config $config */
	protected config $config;

	/** @var helper $pwa_helper */
	protected helper $pwa_helper;

	/** @var template $template */
	protected template $template;

	/**
	 * Constructor
	 *
	 * @param config $config
	 * @param helper $helper
	 * @param template $template
	 */
	public function __construct(config $config, helper $helper, template $template)
	{
		$this->config = $config;
		$this->pwa_helper = $helper;
		$this->template = $template;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'core.page_header'		=> 'header_updates',
			'core.modify_manifest'	=> 'manifest_updates',
		];
	}

	/**
	 * Add header variables to the page header
	 *
	 * @return void
	 */
	public function header_updates(): void
	{
		$this->template->assign_vars([
			'PWA_THEME_COLOR'	=> $this->config['pwa_theme_color'],
			'PWA_BG_COLOR'		=> $this->config['pwa_bg_color'],
			'U_TOUCH_ICONS' 	=> array_column($this->pwa_helper->get_icons(), 'src'),
		]);
	}

	/**
	 * Add members to the manifest
	 *
	 * @param data $event
	 * @return void
	 */
	public function manifest_updates(data $event): void
	{
		// Prepare manifest updates array
		$manifest_updates = [];

		// Add theme and background colors if configured
		if (!empty($this->config['pwa_theme_color']))
		{
			$manifest_updates['theme_color'] = $this->config['pwa_theme_color'];
		}
		if (!empty($this->config['pwa_bg_color']))
		{
			$manifest_updates['background_color'] = $this->config['pwa_bg_color'];
		}

		// Add icons if available
		if (!empty($icons = $this->pwa_helper->get_icons($event['board_path'])))
		{
			$manifest_updates['icons'] = $icons;
		}

		// Update manifest only if there are changes
		if (!empty($manifest_updates))
		{
			foreach ($manifest_updates as $key => $value)
			{
				$event->update_subarray('manifest', $key, $value);
			}
		}
	}
}
