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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbb\event\data;
use phpbb\pwakit\event\main_listener;
use phpbb\pwakit\helper\helper;
use phpbb\template\template;
use phpbb\user;
use phpbb_test_case;

class event_listener_test extends phpbb_test_case
{
	/** @var user|MockObject */
	protected user|MockObject $user;

	/** @var template|MockObject  */
	protected template|MockObject $template;

	/** @var helper */
	protected helper $helper;

	/**
	 * Setup test environment
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->user = $this->createMock(user::class);
		$this->user->optionset('user_id', 2);
		$this->user->data['user_id'] = 2;
		$this->user->style['pwa_bg_color'] = '';
		$this->user->style['pwa_theme_color'] = '';

		$this->template = $this->getMockBuilder(template::class)
			->getMock();

		$this->helper = $this->getMockBuilder(helper::class)
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * Get the event listener
	 *
	 * @return main_listener
	 */
	protected function get_listener(): main_listener
	{
		return new main_listener(
			$this->helper,
			$this->template,
			$this->user
		);
	}

	/**
	 * Test the event listener is constructed correctly
	 */
	public function test_construct()
	{
		static::assertInstanceOf(EventSubscriberInterface::class, $this->get_listener());
	}

	/**
	 * Test the event listener is subscribing events
	 */
	public function test_getSubscribedEvents()
	{
		static::assertEquals([
			'core.page_header',
			'core.modify_manifest',
		], array_keys(\phpbb\pwakit\event\main_listener::getSubscribedEvents()));
	}

	public function header_updates_test_data(): array
	{
		return [
			'header with data' => [
				[
					'pwa_theme_color' => '#foobar',
					'pwa_bg_color' => '#barfoo',
				],
				[
					[
						'src' => 'images/site_icons/touch-icon-192.png',
						'sizes' => '192x192',
						'type' => 'image/png'
					],
					[
						'src' => 'images/site_icons/touch-icon-512.png',
						'sizes' => '512x512',
						'type' => 'image/png'
					],
				],
				[
					'pwa_theme_color' => '#foobar',
					'pwa_bg_color' => '#barfoo',
					'icons' => [
						'images/site_icons/touch-icon-192.png',
						'images/site_icons/touch-icon-512.png',
					]
				],
			],
			'header without data' => [
				[
					'pwa_theme_color' => '',
					'pwa_bg_color' => '',
				],
				[],
				[
					'pwa_theme_color' => '',
					'pwa_bg_color' => '',
					'icons' => [],
				],
			],
		];
	}

	/**
	 * @param $configs
	 * @param $icons
	 * @param $expected
	 * @return void
	 * @dataProvider header_updates_test_data
	 */
	public function test_header_updates($configs, $icons, $expected)
	{
		foreach ($configs as $key => $value)
		{
			$this->user->style[$key] = $value;
		}

		$this->helper->expects(static::once())
			->method('get_icons')
			->willReturn($icons);

		$this->template->expects(static::once())
			->method('assign_vars')
			->with([
				'PWA_THEME_COLOR'	=> $expected['pwa_theme_color'],
				'PWA_BG_COLOR'		=> $expected['pwa_bg_color'],
				'U_TOUCH_ICONS' 	=> $expected['icons'],
			]);

		$this->get_listener()->header_updates();
	}

	public function manifest_updates_test_data(): array
	{
		return [
			'root path, no color options' => [
				'./',
				[],
				[
					'icons' => [
						[
							'src' => './images/site_icons/touch-icon-192.png',
							'sizes' => '192x192',
							'type' => 'image/png'
						],
						[
							'src' => './images/site_icons/touch-icon-512.png',
							'sizes' => '512x512',
							'type' => 'image/png'
						]
					]
				],
			],
			'nested path, with color options' => [
				'./../',
				[
					'pwa_theme_color' => '#ffffff',
					'pwa_bg_color' => '#000000',
				],
				[
					'theme_color' => '#ffffff',
					'background_color' => '#000000',
					'icons' => [
						[
							'src' => './../images/site_icons/touch-icon-192.png',
							'sizes' => '192x192',
							'type' => 'image/png'
						],
						[
							'src' => './../images/site_icons/touch-icon-512.png',
							'sizes' => '512x512',
							'type' => 'image/png'
						]
					],
				],
			],
			'empty' => [
				'',
				[],
				[],
			],
		];
	}

	/**
	 * @param $board_path
	 * @param $configs
	 * @param $expected
	 * @return void
	 * @dataProvider manifest_updates_test_data
	 */
	public function test_manifest_updates($board_path, $configs, $expected)
	{
		$event = new data([
			'manifest' => [
				'name'			=> 'Test Site',
				'short_name'	=> 'TestSite',
				'display'		=> 'standalone',
				'orientation'	=> 'portrait',
				'start_url'		=> './',
				'scope'			=> './',
			],
			'board_path' => $board_path,
		]);

		foreach ($configs as $key => $value)
		{
			$this->user->style[$key] = $value;
		}

		$expected = array_merge($event['manifest'], $expected);

		$this->helper->expects(static::once())
			->method('get_icons')
			->with($board_path)
			->willReturn($expected['icons'] ?? []);

		$this->get_listener()->manifest_updates($event);

		$this->assertSame($expected, $event['manifest']);
	}
}
