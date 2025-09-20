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
use PHPUnit\Framework\MockObject\MockObject;
use phpbb\cache\driver\driver_interface as cache;
use phpbb\config\config;
use phpbb\di\service_collection;
use phpbb\exception\runtime_exception;
use phpbb\filesystem\filesystem;
use phpbb\language\language;
use phpbb\language\language_file_loader;
use phpbb\pwakit\helper\helper;
use phpbb\pwakit\storage\file_tracker;
use phpbb\storage\storage;
use phpbb\storage\adapter\local as adapter_local;
use phpbb\storage\adapter_factory;
use phpbb\storage\provider\local as provider_local;
use phpbb\storage\state_helper;
use phpbb\template\template;
use phpbb_database_test_case;
use phpbb_mock_container_builder;
use phpbb_mock_extension_manager;

class helper_test extends phpbb_database_test_case
{
	protected const FIXTURES = __DIR__ . '/../fixtures/';

	protected config $config;
	protected template|MockObject $template;
	protected helper $helper;
	protected storage $storage;
	protected file_tracker $file_tracker;
	protected string $storage_path;
	protected string $phpbb_root_path;

	protected static function setup_extensions(): array
	{
		return ['phpbb/pwakit'];
	}

	protected function getDataSet()
	{
		return $this->createXMLDataSet(self::FIXTURES . 'storage.xml');
	}

	protected function setUp(): void
	{
		parent::setUp();

		global $phpbb_root_path, $phpEx;

		$this->phpbb_root_path = $phpbb_root_path;

		$this->storage_path = 'ext/phpbb/pwakit/tests/fixtures/site_icons';

		$this->config = new config([
			'storage\phpbb_pwakit\provider' => provider_local::class,
			'storage\phpbb_pwakit\config\path' => $this->storage_path
		]);

		$phpbb_extension_manager = new phpbb_mock_extension_manager($phpbb_root_path);

		$cache = $this->createMock(cache::class);

		$db = $this->new_dbal();

		$phpbb_container = new phpbb_mock_container_builder();

		$language = new language(new language_file_loader($phpbb_root_path, $phpEx));

		$storage_provider = new provider_local($language);
		$phpbb_container->set('storage.provider.local', $storage_provider);
		$provider_collection = new service_collection($phpbb_container);
		$provider_collection->add('storage.provider.local');
		$provider_collection->add_service_class('storage.provider.local', provider_local::class);

		$adapter_local = new adapter_local(new filesystem(), $phpbb_root_path);
		$phpbb_container->set('storage.adapter.local', $adapter_local);
		$adapter_collection = new service_collection($phpbb_container);
		$adapter_collection->add('storage.adapter.local');
		$adapter_collection->add_service_class('storage.adapter.local', adapter_local::class);

		$adapter_factory = new adapter_factory(
			$this->config,
			$adapter_collection,
			$provider_collection
		);
		$state_helper = $this->getMockBuilder(state_helper::class)
			->disableOriginalConstructor()
			->getMock();

		$this->template = $this->getMockBuilder(template::class)
			->getMock();

		$this->file_tracker = new file_tracker(
			$cache,
			$db,
			'phpbb_storage'
		);

		$this->storage = new storage(
			$adapter_factory,
			$this->file_tracker,
			'phpbb_pwakit'
		);

		$this->helper = new \phpbb\pwakit\helper\helper(
			$phpbb_extension_manager,
			new FastImageSize(),
			$this->storage,
			$this->file_tracker,
			new \phpbb\storage\helper(
				$this->config,
				$adapter_factory,
				$state_helper,
				$provider_collection,
				$adapter_collection
			),
			$provider_collection,
			$phpbb_root_path
		);

		@copy(self::FIXTURES . 'foo.png', self::FIXTURES . 'site_icons/foo.png');
	}

	protected function tearDown(): void
	{
		foreach (['foo.png', 'bar.png'] as $file)
		{
			$path = self::FIXTURES . 'site_icons/' . $file;
			if (file_exists($path))
			{
				@unlink($path);
			}
		}

		parent::tearDown();
	}

	public function test_get_tracked_files()
	{
		$this->assertEquals(['foo.png'], $this->file_tracker->get_tracked_files());
	}

	public function test_is_storage_local()
	{
		$this->assertTrue($this->helper->is_storage_local());
	}

	public function test_is_storage_not_local()
	{
		$this->config->set('storage\phpbb_pwakit\provider', 'foo');
		$this->assertFalse($this->helper->is_storage_local());
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

	public function test_get_icons_empty()
	{
		// delete physical foo.png file
		@unlink(self::FIXTURES . 'site_icons/foo.png');

		$this->assertCount(0, array_column($this->helper->get_icons(), 'src'));
	}

	public static function delete_icon_test_data(): array
	{
		return [
			'empty icon name' => [
				'',
				'ACP_PWA_IMG_DELETE_PATH_ERR',
				['foo.png']  // nothing gets deleted
			],
			'invalid icon name' => [
				'f$$.png',
				'ACP_PWA_IMG_DELETE_NAME_ERR',
				['foo.png'] // nothing gets deleted
			],
			'no exists icon name' => [
				'bar.png',
				'STORAGE_FILE_NO_EXIST',
				['foo.png'] // nothing gets deleted
			],
			'icon name with full storage path' => [
				'ext/phpbb/pwakit/tests/fixtures/site_icons/foo.png',
				'',
				[] // gets deleted
			],
			'icon name with possible path traversal' => [
				'../foo.png',
				'',
				[] // gets deleted
			],
			'icon name only' => [
				'foo.png',
				'',
				[] //gets deleted
			],
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
			$this->assertEquals(basename($icon), $deleted);
		}
		catch (runtime_exception $e)
		{
			$this->assertEquals($exception, $e->getMessage());
		}

		$this->assertEquals($expected, $this->file_tracker->get_tracked_files());
	}

	public function test_resync_icons()
	{
		// delete physical foo.png file
		@unlink(self::FIXTURES . 'site_icons/foo.png');

		// add new bar.png file
		@copy(self::FIXTURES . 'bar.png', self::FIXTURES . 'site_icons/bar.png');

		// assert our storage tracking is currently still tracking the deleted image only
		$this->assertEquals(['foo.png'], $this->file_tracker->get_tracked_files());

		// resync icons
		$this->helper->resync_icons();

		// assert we're no longer tracking the deleted file, and we are tracking the newly added file
		$this->assertEquals(['bar.png'], $this->file_tracker->get_tracked_files());
	}
}
