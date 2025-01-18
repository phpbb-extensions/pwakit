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

use Symfony\Component\DependencyInjection\ContainerInterface;
use p_master;
use phpbb\cache\driver\dummy;
use phpbb\db\driver\driver_interface;
use phpbb\module\module_manager;
use phpbb\pwakit\acp\pwa_acp_module;
use phpbb\pwakit\controller\admin_controller;
use phpbb\request\request;
use phpbb\template\template;
use phpbb_mock_event_dispatcher;
use phpbb_mock_extension_manager;
use phpbb_test_case;

require_once __DIR__ . '/../../../../../includes/functions_module.php';

class acp_module_test extends phpbb_test_case
{
	/** @var phpbb_mock_extension_manager */
	protected phpbb_mock_extension_manager $extension_manager;

	/** @var module_manager */
	protected module_manager $module_manager;

	protected function setUp(): void
	{
		global $phpbb_dispatcher, $phpbb_extension_manager, $phpbb_root_path, $phpEx;

		$this->extension_manager = new phpbb_mock_extension_manager(
			$phpbb_root_path,
			[
				'phpbb/pwakit' => [
					'ext_name' => 'phpbb/pwakit',
					'ext_active' => '1',
					'ext_path' => 'ext/phpbb/pwakit/',
				],
			]);
		$phpbb_extension_manager = $this->extension_manager;

		$this->module_manager = new module_manager(
			new dummy(),
			$this->getMockBuilder(driver_interface::class)->getMock(),
			$this->extension_manager,
			MODULES_TABLE,
			$phpbb_root_path,
			$phpEx
		);

		$phpbb_dispatcher = new phpbb_mock_event_dispatcher();
	}

	public function test_module_info()
	{
		self::assertEquals([
			'\\phpbb\\pwakit\\acp\\pwa_acp_module' => [
				'filename'	=> '\\phpbb\\pwakit\\acp\\pwa_acp_module',
				'title'		=> 'ACP_PWA_KIT_TITLE',
				'modes'		=> [
					'settings'	=> [
						'title'	=> 'ACP_PWA_KIT_SETTINGS',
						'auth'	=> 'ext_phpbb/pwakit && acl_a_board',
						'cat'	=> ['ACP_PWA_KIT_TITLE']
					],
				],
			],
		], $this->module_manager->get_module_infos('acp', 'pwa_acp_module'));
	}

	public function module_auth_test_data(): array
	{
		return [
			// module_auth, expected result
			['ext_foo/bar', false],
			['ext_phpbb/pwakit', true],
		];
	}

	/**
	 * @dataProvider module_auth_test_data
	 */
	public function test_module_auth($module_auth, $expected)
	{
		self::assertEquals($expected, p_master::module_auth($module_auth, 0));
	}

	public function main_module_test_data(): array
	{
		return [
			['settings'],
		];
	}

	/**
	 * @dataProvider main_module_test_data
	 */
	public function test_main_module($mode)
	{
		global $phpbb_container, $request, $template;

		if (!defined('IN_ADMIN'))
		{
			define('IN_ADMIN', true);
		}

		$request = $this->getMockBuilder(request::class)
			->disableOriginalConstructor()
			->getMock();
		$template = $this->getMockBuilder(template::class)
			->disableOriginalConstructor()
			->getMock();
		$phpbb_container = $this->getMockBuilder(ContainerInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$admin_controller = $this->getMockBuilder(admin_controller::class)
			->disableOriginalConstructor()
			->getMock();

		$phpbb_container
			->expects(self::once())
			->method('get')
			->with('phpbb.pwakit.admin.controller')
			->willReturn($admin_controller);

		$admin_controller
			->expects(self::once())
			->method('main');

		$p_master = new p_master();
		$p_master->module_ary[0]['is_duplicate'] = 0;
		$p_master->module_ary[0]['url_extra'] = '';
		$p_master->load('acp', pwa_acp_module::class, $mode);
	}
}
