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

use Exception;
use phpbb\db\driver\driver_interface as dbal;
use phpbb_mock_cache;
use phpbb_mock_event_dispatcher;
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
use phpbb_database_test_case;

class admin_controller_test extends phpbb_database_test_case
{
	public static bool $confirm;
	public static bool $valid_form;

	protected dbal $db;
	protected config $config;
	protected language $language;
	protected request $request;
	protected template|MockObject $template;
	protected helper $helper;
	protected upload $upload;
	protected string $phpbb_root_path;
	protected admin_controller $admin_controller;

	protected static function setup_extensions(): array
	{
		return ['phpbb/pwakit'];
	}

	protected function getDataSet()
	{
		return $this->createXMLDataSet(__DIR__ . '/../fixtures/styles.xml');
	}

	/**
	 * Setup test environment
	 */
	protected function setUp(): void
	{
		parent::setUp();

		global $phpbb_dispatcher, $phpbb_root_path, $phpEx;

		$this->db = $this->new_dbal();

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
		$this->helper->method('is_storage_local')->willReturn(true);
		$this->helper->method('get_storage_path')->willReturn('images/site_icons');
		$this->helper->method('get_icons')->willReturn([]);

		$this->upload = $this->getMockBuilder(upload::class)
			->disableOriginalConstructor()
			->getMock();

		$this->phpbb_root_path = $phpbb_root_path;

		$phpbb_dispatcher = new phpbb_mock_event_dispatcher();

		self::$valid_form = true;
		self::$confirm = true;

		$this->admin_controller = new admin_controller(
			new phpbb_mock_cache(),
			$this->config,
			$this->db,
			$this->language,
			$this->request,
			$this->template,
			$this->helper,
			$this->upload,
			$this->phpbb_root_path,
			'adm/',
			$phpEx
		);
	}

	public static function module_access_test_data(): array
	{
		return [
			'correct mode' => ['settings', true],
			'incorrect mode' => ['foobar', false],
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

	public static function form_checks_data(): array
	{
		return [
			'submit test' => ['submit'],
			'upload test' => ['upload'],
			'resync test' => ['resync'],
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

	public static function display_settings_test_data(): array
	{
		return [
			'site name and short name' => [
				[
					'sitename' => 'phpBB',
					'sitename_short' => 'phpBB',
				],
				[
					'sitename' => 'phpBB',
					'sitename_short' => 'phpBB',
				],
			],
			'site name only' => [
				[
					'sitename' => 'phpBB',
					'sitename_short' => '',
				],
				[
					'sitename' => 'phpBB',
					'sitename_short' => 'phpBB',
				],
			],
			'long site name only' => [
				[
					'sitename' => 'phpBB Long Site Name',
					'sitename_short' => '',
				],
				[
					'sitename' => 'phpBB Long Site Name',
					'sitename_short' => 'phpBB Long S',
				],
			],
			'long mb site name only' => [
				[
					'sitename' => utf8_encode_ucr('phpBB™ 😂😂😂😂😂😂😂😂'),
					'sitename_short' => '',
				],
				[
					'sitename' => utf8_encode_ucr('phpBB™ 😂😂😂😂😂😂😂😂'),
					'sitename_short' => 'phpBB™ 😂😂😂😂😂',
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

		$expected_style_data = [
			0 => [
				'style_id' => 1,
				'style_name' => 'prosilver',
				'pwa_bg_color' => '#fff000',
				'pwa_theme_color' => '#000fff',
			],
			1 => [
				'style_id' => 2,
				'style_name' => 'silverfoo',
				'pwa_bg_color' => '',
				'pwa_theme_color' => '',
			],
		];

		$expectedCalls = [
			[
				'SITE_NAME'			=> $expected['sitename'],
				'SITE_NAME_SHORT'	=> $expected['sitename_short'],
				'PWA_IMAGES_DIR'	=> 'images/site_icons',
				'PWA_KIT_ICONS'		=> [],
				'STYLES'			=> $expected_style_data,
				'S_STORAGE_LOCAL'	=> true,
				'U_BOARD_SETTINGS'	=> "{$this->phpbb_root_path}adm/index.php?i=acp_board&amp;mode=settings",
				'U_STORAGE_SETTINGS'=> "{$this->phpbb_root_path}adm/index.php?i=acp_storage&amp;mode=settings",
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

	public static function submit_test_data(): array
	{
		return [
			'all good inputs' => [
				[
					'pwa_bg_color_1' => '#000000',
					'pwa_theme_color_1' => '#ffffff',
					'pwa_bg_color_2' => '#cccccc',
					'pwa_theme_color_2' => '#dddddd',
				],
				[
					0 => ['pwa_bg_color' => '#000000', 'pwa_theme_color' => '#ffffff'],
					1 => ['pwa_bg_color' => '#cccccc', 'pwa_theme_color' => '#dddddd'],
				],
				'CONFIG_UPDATED',
			],
			'one style with good inputs #1' => [
				[
					'pwa_bg_color_1' => '#000000',
					'pwa_theme_color_1' => '#ffffff',
					'pwa_bg_color_2' => '',
					'pwa_theme_color_2' => '',
				],
				[
					0 => ['pwa_bg_color' => '#000000', 'pwa_theme_color' => '#ffffff'],
					1 => ['pwa_bg_color' => '', 'pwa_theme_color' => ''],
				],
				'CONFIG_UPDATED',
			],
			'one style with good inputs #2' => [
				[
					'pwa_bg_color_1' => '#000000',
					'pwa_theme_color_1' => '',
					'pwa_bg_color_2' => '',
					'pwa_theme_color_2' => '#ffffff',
				],
				[
					0 => ['pwa_bg_color' => '#000000', 'pwa_theme_color' => ''],
					1 => ['pwa_bg_color' => '', 'pwa_theme_color' => '#ffffff'],
				],
				'CONFIG_UPDATED',
			],
			'one bad input' => [
				[
					'pwa_bg_color_1' => '#000000',
					'pwa_theme_color_1' => 'fff',
					'pwa_bg_color_2' => '#ffffff',
					'pwa_theme_color_2' => '',
				],
				[
					0 => ['pwa_bg_color' => '#000000', 'pwa_theme_color' => '#000fff'],
					1 => ['pwa_bg_color' => '#ffffff', 'pwa_theme_color' => ''],
				],
				'ACP_PWA_INVALID_COLOR',
			],
			'all bad inputs' => [
				[
					'pwa_bg_color_1' => 'foo',
					'pwa_theme_color_1' => 'bar',
					'pwa_bg_color_2' => '123456',
					'pwa_theme_color_2' => '######',
				],
				[
					0 => ['pwa_bg_color' => '#fff000', 'pwa_theme_color' => '#000fff'],
					1 => ['pwa_bg_color' => '', 'pwa_theme_color' => ''],
				],
				'ACP_PWA_INVALID_COLOR<br>ACP_PWA_INVALID_COLOR<br>ACP_PWA_INVALID_COLOR<br>ACP_PWA_INVALID_COLOR',
			],
			'all empty inputs' => [
				[
					'pwa_bg_color_1' => '',
					'pwa_theme_color_1' => '',
					'pwa_bg_color_2' => '',
					'pwa_theme_color_2' => '',
				],
				[
					0 => ['pwa_bg_color' => '', 'pwa_theme_color' => ''],
					1 => ['pwa_bg_color' => '', 'pwa_theme_color' => ''],
				],
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
		$is_success = $expected_msg === 'CONFIG_UPDATED';
		
		if ($is_success)
		{
			$this->setExpectedTriggerError(E_USER_NOTICE, 'CONFIG_UPDATED');
		}
		else
		{
			$call_count = 0;
			$this->template->method('assign_vars')
				->willReturnCallback(function ($params) use (&$call_count, $expected_msg) {
					if (++$call_count === 2)
					{
						$this->assertEquals(['S_ERROR' => true, 'ERROR_MSG' => $expected_msg], $params);
					}
				});
		}

		$this->request->expects($this->exactly(4))
			->method('variable')
			->willReturnMap([
				['pwa_bg_color_1', '', false, request_interface::REQUEST, $form_data['pwa_bg_color_1']],
				['pwa_theme_color_1', '', false, request_interface::REQUEST, $form_data['pwa_theme_color_1']],
				['pwa_bg_color_2', '', false, request_interface::REQUEST, $form_data['pwa_bg_color_2']],
				['pwa_theme_color_2', '', false, request_interface::REQUEST, $form_data['pwa_theme_color_2']],
			]);

		$this->request_submit('submit');
		
		if ($is_success)
		{
			$this->call_admin_controller();
		}
		else
		{
			try
			{
				$this->call_admin_controller();
			}
			catch (Exception $e)
			{
				$this->assertEquals($expected_msg, $e->getMessage());
			}
		}

		$result = $this->db->sql_query('SELECT pwa_bg_color, pwa_theme_color FROM ' . STYLES_TABLE . ' WHERE style_active = 1 ORDER BY style_id');
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		$this->assertSame($expected, $rows);
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

	public static function delete_test_data(): array
	{
		return [
			'not confirmed' => [
				'foo.png',
				false,
				false
			],
			'confirmed valid data' => [
				'foo.png',
				true,
				false
			],
			'confirmed invalid data' => [
				'',
				true,
				true
			],
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
		$this->admin_controller->main('\\phpbb\\pwakit\\acp\\pwa_acp_module', $mode, '');
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
