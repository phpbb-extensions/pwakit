<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024 phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\pwakit\controller;

use PHPUnit\Framework\MockObject\MockObject;
use phpbb\config\config;
use phpbb\exception\runtime_exception;
use phpbb\language\language;
use phpbb\language\language_file_loader;
use phpbb\pwakit\helper\helper;
use phpbb\pwakit\helper\upload;
use phpbb\request\request;
use phpbb\request\request_interface;
use phpbb\template\template;
use phpbb_test_case;

class admin_controller_test extends phpbb_test_case
{
	public static bool $confirm;

	public static bool $valid_form;

	protected config $config;

	protected language $language;

	protected request $request;

	protected template|MockObject $template;

	protected helper $helper;

	protected upload $upload;

	/**
	 * Setup test environment
	 */
	protected function setUp(): void
	{
		parent::setUp();

		global $phpbb_root_path, $phpEx;

		$this->config = new config([
			'sitename' => 'phpBB',
			'sitename_short' => 'phpBB',
			'pwa_bg_color' => '',
			'pwa_theme_color' => '',
		]);

		$this->language = new language(new language_file_loader($phpbb_root_path, $phpEx));

		$this->request = $this->getMockBuilder(request::class)
			->disableOriginalConstructor()
			->getMock();

		$this->template = $this->getMockBuilder(template::class)
			->getMock();

		$this->helper = $this->getMockBuilder(helper::class)
			->disableOriginalConstructor()
			->getMock();
		$this->helper->method('get_storage_path')->willReturn('images/site_icons');
		$this->helper->method('get_icons')->willReturn([]);

		$this->upload = $this->getMockBuilder(upload::class)
			->disableOriginalConstructor()
			->getMock();

		self::$valid_form = true;
		self::$confirm = true;

		$this->admin_controller = new admin_controller(
			$this->config,
			$this->language,
			$this->request,
			$this->template,
			$this->helper,
			$this->upload,
			$phpbb_root_path
		);

		$this->admin_controller->set_page_url('');
	}

	public function module_access_test_data(): array
	{
		return [
			['settings', true],
			['foobar', false],
		];
	}

	/**
	 * @param $mode
	 * @param $expected
	 * @return void
	 * @dataProvider module_access_test_data
	 */
	public function test_module_access($mode, $expected)
	{
		$this->request->expects($expected ? $this->atLeastOnce() : $this->never())
			->method('is_set_post');


		$this->call_admin_controller($mode);
	}

	public function form_checks_data(): array
	{
		return [
			['submit'],
			['upload'],
			['resync'],
		];
	}

	/**
	 * @param $action
	 * @dataProvider form_checks_data
	 */
	public function test_form_checks($action)
	{
		self::$valid_form = false;

		$this->request_submit($action);

		$this->setExpectedTriggerError(E_USER_WARNING, $this->language->lang('FORM_INVALID'));

		$this->call_admin_controller();
	}

	public function display_settings_test_data(): array
	{
		return [
			[
				[
					'sitename' => 'phpBB',
					'sitename_short' => 'phpBB',
					'pwa_bg_color' => '',
					'pwa_theme_color' => '',
				],
				[
					'sitename' => 'phpBB',
					'sitename_short' => 'phpBB',
					'pwa_bg_color' => '',
					'pwa_theme_color' => '',
				],
			],
			[
				[
					'sitename' => 'phpBB',
					'sitename_short' => '',
					'pwa_bg_color' => '#fff000',
					'pwa_theme_color' => '#000fff',
				],
				[
					'sitename' => 'phpBB',
					'sitename_short' => 'phpBB',
					'pwa_bg_color' => '#fff000',
					'pwa_theme_color' => '#000fff',
				],
			],
			[
				[
					'sitename' => 'phpBB Long Site Name',
					'sitename_short' => '',
					'pwa_bg_color' => '',
					'pwa_theme_color' => '',
				],
				[
					'sitename' => 'phpBB Long Site Name',
					'sitename_short' => 'phpBB Long S',
					'pwa_bg_color' => '',
					'pwa_theme_color' => '',
				],
			],
			[
				[
					'sitename' => utf8_encode_ucr('phpBB™ 😂😂😂😂😂😂😂😂'),
					'sitename_short' => '',
					'pwa_bg_color' => '',
					'pwa_theme_color' => '',
				],
				[
					'sitename' => utf8_encode_ucr('phpBB™ 😂😂😂😂😂😂😂😂'),
					'sitename_short' => 'phpBB™ 😂😂😂😂😂',
					'pwa_bg_color' => '',
					'pwa_theme_color' => '',
				],
			],
		];
	}

	/**
	 * @param $configs
	 * @param $expected
	 * @dataProvider display_settings_test_data
	 */
	public function test_display_settings($configs, $expected)
	{
		foreach ($configs as $key => $value)
		{
			$this->config->set($key, $value);
		}

		$expectedCalls = [
			[
				'SITE_NAME'			=> $expected['sitename'],
				'SITE_NAME_SHORT'	=> $expected['sitename_short'],
				'PWA_BG_COLOR'		=> $expected['pwa_bg_color'],
				'PWA_THEME_COLOR'	=> $expected['pwa_theme_color'],
				'PWA_IMAGES_DIR'	=> 'images/site_icons',
				'PWA_KIT_ICONS'		=> [],
				'U_ACTION'			=> '',
			],
			[
				'S_ERROR' => false,
				'ERROR_MSG' => '',
			]
		];

		$callCount = 0;
		$this->template->expects($this->exactly(2))
			->method('assign_vars')
			->willReturnCallback(function($params) use (&$callCount, $expectedCalls) {
				$this->assertEquals($expectedCalls[$callCount], $params);
				$callCount++;
			});

		$this->call_admin_controller();
	}

	public function submit_test_data(): array
	{
		return [
			[	// all good inputs
				[
					'pwa_bg_color' => '#000000',
					'pwa_theme_color' => '#ffffff',
				],
				true,
				'CONFIG_UPDATED',
			],
			[	// one bad input
				[
					'pwa_bg_color' => '#000000',
					'pwa_theme_color' => 'ffffff',
				],
				false,
				'ACP_PWA_INVALID_COLOR',
			],
			[	// all bad inputs
				[
					'pwa_bg_color' => '000000',
					'pwa_theme_color' => 'ffffff',
				],
				false,
				'ACP_PWA_INVALID_COLOR<br>ACP_PWA_INVALID_COLOR',
			],
			[	// all empty inputs
				[
					'pwa_bg_color' => '',
					'pwa_theme_color' => '',
				],
				true,
				'CONFIG_UPDATED'
			],
		];
	}

	/**
	 * Test submit/save settings
	 *
	 * @param $form_data
	 * @param $expected
	 * @param $expected_msg
	 * @dataProvider submit_test_data
	 */
	public function test_submit($form_data, $expected, $expected_msg)
	{
		if ($expected)
		{
			$this->setExpectedTriggerError(E_USER_NOTICE, $expected_msg);
		}
		else
		{
			$firstCallDone = false;
			$this->template->method('assign_vars')
				->willReturnCallback(function ($params) use (&$firstCallDone, $expected_msg) {
					if (!$firstCallDone)
					{
						$firstCallDone = true; //skip first call
					}
					else
					{
						$this->assertEquals([
							'S_ERROR' => true,
							'ERROR_MSG' => $expected_msg,
						], $params);
					}
					return null;
				})
			;
		}

		$this->request_submit('submit');

		$this->request->expects($this->exactly(2))
			->method('variable')
			->willReturnMap([
				['pwa_bg_color', '', false, request_interface::REQUEST, $form_data['pwa_bg_color']],
				['pwa_theme_color', '', false, request_interface::REQUEST, $form_data['pwa_theme_color']]
			]);

		$this->call_admin_controller();

		if ($expected)
		{
			$this->assertEquals($form_data['pwa_bg_color'], $this->config['pwa_bg_color']);
			$this->assertEquals($form_data['pwa_theme_color'], $this->config['pwa_theme_color']);
		}
		else
		{
			$this->assertNotEquals($form_data['pwa_bg_color'], $this->config['pwa_bg_color']);
			$this->assertNotEquals($form_data['pwa_theme_color'], $this->config['pwa_theme_color']);
		}
	}

	public function test_upload()
	{
		$this->setExpectedTriggerError(E_USER_NOTICE, 'ACP_PWA_IMG_UPLOAD_SUCCESS');

		$this->request_submit('upload');

		$this->upload->expects($this->once())
			->method('upload')
			->willReturn('test.png');

		$this->call_admin_controller();
	}

	public function test_upload_error()
	{
		$this->request_submit('upload');

		$this->upload->expects($this->once())
			->method('upload')
			->willThrowException(new runtime_exception());

		$this->upload->expects($this->once())
			->method('remove');

		$this->call_admin_controller();
	}

	public function test_resync()
	{
		$this->request_submit('resync');

		$this->helper->expects($this->once())
			->method('resync_icons');

		$this->call_admin_controller();
	}

	public function delete_test_data(): array
	{
		return [
			['foo.png', false, false], // not confirmed yet
			['foo.png', true, false], // confirmed and valid data, no errors
			['', true, true], // confirmed with invalid data, errors
		];
	}

	/**
	 * @param $image
	 * @param $confirmed
	 * @param $error
	 * @dataProvider delete_test_data
	 */
	public function test_delete($image, $confirmed, $error)
	{
		self::$confirm = $confirmed;

		$this->request_submit('delete');

		$this->request->expects($this->once())
			->method('variable')
			->with('delete')
			->willReturn($image);

		if ($confirmed)
		{
			$helperExpectation = $this->helper->expects($this->once())
				->method('delete_icon')
				->with($image);

			if ($error)
			{
				$this->setExpectedTriggerError(E_USER_WARNING, 'ACP_PWA_IMG_DELETE_ERROR');
				$helperExpectation->willThrowException(new runtime_exception());
			}
			else
			{
				$this->setExpectedTriggerError(E_USER_NOTICE, 'ACP_PWA_IMG_DELETED');
				$helperExpectation->willReturn($image);
			}
		}
		else
		{
			$this->helper->expects($this->never())->method('delete_icon');
		}

		$this->call_admin_controller();
	}

	/**
	 * @param string $mode
	 * @return void
	 */
	private function call_admin_controller(string $mode = 'settings'): void
	{
		$this->admin_controller->main(0, $mode);
	}

	/**
	 * @param $value
	 * @return void
	 */
	private function request_submit($value): void
	{
		$this->request->expects($this->atLeastOnce())
			->method('is_set_post')
			->willReturnCallback(fn($param) => $param === $value);
	}
}

/**
 * Mock check_form_key()
 * Note: use the same namespace as the admin_controller
 *
 * @return bool
 */
function check_form_key(): bool
{
	return \phpbb\pwakit\controller\admin_controller_test::$valid_form;
}

/**
 * Mock add_form_key()
 * Note: use the same namespace as the admin_controller
 */
function add_form_key()
{
}

/**
 * Mock confirm_box()
 * Note: use the same namespace as the admin_controller
 *
 * @return bool
 */
function confirm_box(): bool
{
	return \phpbb\pwakit\controller\admin_controller_test::$confirm;
}

/**
 * Mock adm_back_link()
 * Note: use the same namespace as the admin_controller
 */
function adm_back_link()
{
}
