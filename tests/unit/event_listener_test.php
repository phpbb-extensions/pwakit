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
	protected user|MockObject $user;
	protected template|MockObject $template;
	protected helper $pwa_helper;

	/**
	 * Setup test environment
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->user = $this->getMockBuilder(user::class)
			->disableOriginalConstructor()
			->getMock();
		$this->user->method('optionset')
			->with('user_id', 2)
			->willReturn(null);
		$this->user->data['user_id'] = 2;
		$this->user->style['pwa_bg_color'] = '';
		$this->user->style['pwa_theme_color'] = '';

		$this->template = $this->getMockBuilder(template::class)
			->getMock();

		$this->pwa_helper = $this->getMockBuilder(helper::class)
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
			$this->pwa_helper,
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
		$events = main_listener::getSubscribedEvents();
		static::assertEquals([
			'core.page_header' => 'header_updates',
			'core.modify_manifest' => 'manifest_updates',
		], $events);
	}

	public static function header_updates_test_data(): array
	{
		return [
			'valid hex colors' => [
				[
					'pwa_theme_color' => '#ffffff',
					'pwa_bg_color' => '#000000',
				],
				self::getValidIcons(),
				[
					'pwa_theme_color' => '#ffffff',
					'pwa_bg_color' => '#000000',
					'icons' => [
						'images/site_icons/touch-icon-192.png',
						'images/site_icons/touch-icon-512.png',
					]
				],
			],
			'invalid hex colors' => [
				[
					'pwa_theme_color' => '#gggggg',
					'pwa_bg_color' => 'invalid',
				],
				[],
				[
					'pwa_theme_color' => '#gggggg',
					'pwa_bg_color' => 'invalid',
					'icons' => [],
				],
			],
			'empty values' => [
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

	private static function getValidIcons(): array
	{
		return [
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
		// Setup expectations
		$this->pwa_helper->expects(static::once())
			->method('get_icons')
			->willReturn($icons);

		$templateVars = [
			'PWA_THEME_COLOR' => $expected['pwa_theme_color'],
			'PWA_BG_COLOR' => $expected['pwa_bg_color'],
			'U_TOUCH_ICONS' => $expected['icons'],
		];

		$this->template->expects(static::once())
			->method('assign_vars')
			->with(static::identicalTo($templateVars));

		// Apply configurations
		foreach ($configs as $key => $value) {
			$this->user->style[$key] = $value;
		}

		$listener = $this->get_listener();
		$listener->header_updates();

		// Verify the final state
		$this->assertEquals($configs['pwa_theme_color'], $this->user->style['pwa_theme_color']);
		$this->assertEquals($configs['pwa_bg_color'], $this->user->style['pwa_bg_color']);
	}

	public static function manifest_updates_test_data(): array
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
		$initialManifest = [
			'name' => 'Test Site',
			'short_name' => 'TestSite',
			'display' => 'standalone',
			'orientation' => 'portrait',
			'start_url' => './',
			'scope' => './',
		];

		$event = new data([
			'manifest' => $initialManifest,
			'board_path' => $board_path,
		]);

		// Set up and verify the initial state
		$this->assertSame($initialManifest, $event['manifest']);

		foreach ($configs as $key => $value) {
			$this->user->style[$key] = $value;
		}

		$expected = array_merge($initialManifest, $expected);

		// Verify helper method call
		$this->pwa_helper->expects(static::once())
			->method('get_icons')
			->with($board_path)
			->willReturn($expected['icons'] ?? []);

		// Execute test
		$listener = $this->get_listener();
		$listener->manifest_updates($event);

		// Verify the final state
		$this->assertSame($expected, $event['manifest']);

		// Verify manifest structure
		if (!empty($event['manifest'])) {
			$this->assertArrayHasKey('name', $event['manifest']);
			$this->assertArrayHasKey('short_name', $event['manifest']);
			$this->assertArrayHasKey('display', $event['manifest']);
			$this->assertArrayHasKey('orientation', $event['manifest']);
		}

		// Verify color format if present
		if (isset($event['manifest']['theme_color'])) {
			$this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $event['manifest']['theme_color']);
		}
		if (isset($event['manifest']['background_color'])) {
			$this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $event['manifest']['background_color']);
		}
	}
}
