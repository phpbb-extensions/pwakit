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

use phpbb\event\data;
use phpbb\pwakit\helper\helper;
use phpbb\template\template;
use phpbb\user;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class main_listener implements EventSubscriberInterface
{
	/** @var helper $pwa_helper */
	protected helper $pwa_helper;

	/** @var template $template */
	protected template $template;

	/** @var user $user */
	protected user $user;

	/**
	 * Constructor
	 *
	 * @param helper $helper
	 * @param template $template
	 * @param user $user
	 */
	public function __construct(helper $helper, template $template, user $user)
	{
		$this->pwa_helper = $helper;
		$this->template = $template;
		$this->user = $user;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'core.page_header'		=> 'header_updates',
			'core.modify_manifest'	=> 'manifest_updates',
			KernelEvents::RESPONSE	=> 'manifest_response',
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
			'PWA_THEME_COLOR'	=> $this->get_style_color('pwa_theme_color'),
			'U_TOUCH_ICONS'		=> $this->pwa_helper->get_icons(),
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

		// TODO This may need to be removed if manifest goes stateless (no user/session)
		// Add theme and background colors if configured
		$theme_color = $this->get_style_color('pwa_theme_color');
		if ($theme_color !== '')
		{
			$manifest_updates['theme_color'] = $theme_color;
		}

		$background_color = $this->get_style_color('pwa_bg_color');
		if ($background_color !== '')
		{
			$manifest_updates['background_color'] = $background_color;
		}

		// Add icons if available
		if (!empty($icons = $this->pwa_helper->get_icons($event['scope'])))
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

	/**
	 * Use the registered manifest media type and prevent shared caches from
	 * serving one user's style colours to users of another style.
	 *
	 * @param ResponseEvent $event
	 * @return void
	 */
	public function manifest_response(ResponseEvent $event): void
	{
		if ($event->getRequest()->attributes->get('_route') !== 'phpbb_manifest_controller')
		{
			return;
		}

		$response = $event->getResponse();
		$response->headers->set('Content-Type', 'application/manifest+json');
		$response->setPrivate();
		$response->setVary('Cookie', false);
	}

	/**
	 * Return a style colour only when it is safe and valid for HTML and manifest output.
	 *
	 * @param string $key Style data key
	 * @return string
	 */
	private function get_style_color(string $key): string
	{
		$color = $this->user->style[$key] ?? '';

		return is_string($color) && preg_match('/^#(?:[0-9a-f]{3}){1,2}$/i', $color) === 1
			? $color
			: '';
	}
}
