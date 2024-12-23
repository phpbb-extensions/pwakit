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

use mattf\pwakit\helper\helper;
use phpbb\event\data;
use phpbb\template\template;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class main_listener implements EventSubscriberInterface
{
	/** @var helper $pwa_helper */
	protected helper $pwa_helper;

	/** @var template $template */
	protected template $template;

	/**
	 * Constructor
	 *
	 * @param helper $helper
	 * @param template $template
	 */
	public function __construct(helper $helper, template $template)
	{
		$this->pwa_helper = $helper;
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
		$this->template->assign_var(
			'U_TOUCH_ICONS',
			array_column($this->pwa_helper->get_icons(), 'src')
		);
	}

	/**
	 * Add icons to the manifest
	 *
	 * @param data $event
	 * @return void
	 */
	public function manifest_icons(data $event): void
	{
		$icons = $this->pwa_helper->get_icons($event['board_path']);

		if (empty($icons))
		{
			return;
		}

		$event->update_subarray('manifest', 'icons', $icons);
	}
}
