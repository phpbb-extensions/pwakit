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

use FastImageSize\FastImageSize;
use phpbb\config\config;
use phpbb\di\service_collection;
use phpbb\exception\runtime_exception;
use phpbb\filesystem\filesystem;
use phpbb\pwakit\helper\helper;
use phpbb\pwakit\storage\storage;
use phpbb\storage\adapter_factory;
use phpbb\template\template;
use PHPUnit\Framework\MockObject\MockObject;

class helper_test extends \phpbb_database_test_case
{
	/** @var config */
	protected config $config;

	/** @var template|MockObject  */
	protected template|MockObject $template;

	/** @var helper */
	protected helper $helper;

	/** @var storage */
	protected storage $storage;

	/** @var string */
	protected string $storage_path;

	protected string $phpbb_root_path;

	protected static function setup_extensions(): array
	{
		return array('phpbb/pwakit');
	}

	protected function getDataSet()
	{
		return $this->createXMLDataSet(__DIR__ . '/fixtures/storage.xml');
	}

	protected function setUp(): void
	{
		parent::setUp();

		global $phpbb_root_path;

		$this->phpbb_root_path = $phpbb_root_path;

		$this->storage_path = 'ext/phpbb/pwakit/tests/site_icons';

		$this->config = new config([
			'storage\phpbb_pwakit\provider' => 'phpbb\\storage\\provider\\local',
			'storage\phpbb_pwakit\config\path' => $this->storage_path
		]);

		$phpbb_extension_manager = new \phpbb_mock_extension_manager($phpbb_root_path);

		$cache = $this->createMock('\phpbb\cache\driver\driver_interface');

		$db = $this->new_dbal();

		$phpbb_container = new \phpbb_mock_container_builder();

		$storage_provider = new \phpbb\storage\provider\local();
		$phpbb_container->set('storage.provider.local', $storage_provider);
		$provider_collection = new service_collection($phpbb_container);
		$provider_collection->add('storage.provider.local');
		$provider_collection->add_service_class('storage.provider.local', 'phpbb\storage\provider\local');

		$adapter_local = new \phpbb\storage\adapter\local(new filesystem(), $phpbb_root_path);
		$phpbb_container->set('storage.adapter.local', $adapter_local);
		$adapter_collection = new service_collection($phpbb_container);
		$adapter_collection->add('storage.adapter.local');
		$adapter_collection->add_service_class('storage.adapter.local', 'phpbb\storage\adapter\local');

		$adapter_factory = new adapter_factory(
			$this->config,
			$adapter_collection,
			$provider_collection
		);
		$state_helper = $this->getMockBuilder('\phpbb\storage\state_helper')
			->disableOriginalConstructor()
			->getMock();

		$this->template = $this->getMockBuilder(template::class)
			->getMock();

		$this->storage = new storage(
			$db,
			$cache,
			$adapter_factory,
			'phpbb_pwakit',
			'phpbb_storage'
		);

		$this->helper = new \phpbb\pwakit\helper\helper(
			$phpbb_extension_manager,
			new FastImageSize(),
			$this->storage,
			new \phpbb\storage\helper(
				$this->config,
				$adapter_factory,
				$state_helper,
				$provider_collection,
				$adapter_collection
			),
			$phpbb_root_path
		);

		@copy(__DIR__ . '/fixtures/foo.png', $this->phpbb_root_path . $this->storage_path . '/foo.png');
	}

	protected function tearDown(): void
	{
		foreach (['foo.png', 'bar.png'] as $file)
		{
			$path = $this->phpbb_root_path . $this->storage_path . '/' . $file;
			if (file_exists($path))
			{
				@unlink($path);
			}
		}

		parent::tearDown();
	}

	public function test_get_tracked_files()
	{
		$this->assertEquals(['foo.png'], $this->storage->get_tracked_files());
	}

	public function test_get_storage_path()
	{
		$this->assertEquals($this->storage_path, $this->helper->get_storage_path());
	}

	public function test_get_icons()
	{
		$expected[] = [
			'src' => $this->storage_path . '/foo.png',
			'sizes' => '1x1',
			'type' => 'image/png'
		];

		$this->assertEquals($expected,  $this->helper->get_icons());
	}

	public function delete_icon_test_data(): array
	{
		return [
			['', 'ACP_PWA_IMG_DELETE_PATH_ERR', ['foo.png']],
			['foo$$.png', 'ACP_PWA_IMG_DELETE_NAME_ERR', ['foo.png']],
			['foo.png', '', []]
		];
	}

	/**
	 * @param $icon
	 * @param $exception
	 * @param $expected
	 * @dataProvider delete_icon_test_data
	 */
	public function test_delete_icon($icon, $exception, $expected)
	{
		try
		{
			$deleted = $this->helper->delete_icon($icon);
			$this->assertEquals($icon, $deleted);
		}
		catch (runtime_exception $e)
		{
			$this->assertEquals($exception, $e->getMessage());
		}

		$this->assertEquals($expected, $this->storage->get_tracked_files());
	}

	public function test_resync_icons()
	{
		// delete physical foo.png file
		@unlink($this->phpbb_root_path . $this->storage_path . '/foo.png');

		// add new bar.png file
		@copy(__DIR__ . '/fixtures/bar.png', $this->phpbb_root_path . $this->storage_path . '/bar.png');

		// assert our storage tracking is currently still tracking the deleted image only
		$this->assertEquals(['foo.png'], $this->storage->get_tracked_files());

		// resync icons
		$this->helper->resync_icons();

		// assert we're no longer tracking the deleted file, and we are tracking the newly added file
		$this->assertEquals(['bar.png'], $this->storage->get_tracked_files());
	}
}
