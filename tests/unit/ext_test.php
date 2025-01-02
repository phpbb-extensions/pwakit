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

use phpbb\config\config;
use phpbb\db\migrator;
use phpbb\filesystem\exception\filesystem_exception;
use phpbb\filesystem\filesystem;
use phpbb\finder\finder;
use phpbb\log\log;
use phpbb\pwakit\ext;
use phpbb_mock_user;
use phpbb_test_case;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ext_test extends phpbb_test_case
{
	protected ContainerInterface|MockObject $container;

	protected finder|MockObject $extension_finder;

	protected migrator|MockObject $migrator;

	protected function setUp(): void
	{
		parent::setUp();

		// Stub the container
		$this->container = $this->createMock(ContainerInterface::class);

		// Stub the ext finder and disable its constructor
		$this->extension_finder = $this->createMock(finder::class);

		// Stub the migrator and disable its constructor
		$this->migrator = $this->createMock(migrator::class);
	}

	/**
	 * Data set for test_ext
	 *
	 * @return array
	 */
	public function ext_test_data(): array
	{
		return [
			[ext::PHPBB_MIN_VERSION, true], // current setting is enable-able
			['4.0.0', true], // future phpbb is enable-able
			['3.3.14', false], // old phpbb is not enable-able
		];
	}

	/**
	 * Test the extension can only be enabled when the minimum
	 * phpBB version requirement is satisfied.
	 *
	 * @param $version
	 * @param $expected
	 *
	 * @dataProvider ext_test_data
	 */
	public function test_ext($version, $expected)
	{
		// Instantiate config object and set config version
		$config = new config([
			'version' => $version,
		]);

		// Mocked container should return the config object
		// when encountering $this->container->get('config')
		$this->container->expects(self::once())
			->method('get')
			->with('config')
			->willReturn($config);

		$ext = new ext($this->container, $this->extension_finder, $this->migrator, 'phpbb/pwakit', '');

		self::assertSame($expected, $ext->is_enableable());
	}

	public function enable_test_data(): array
	{
		return [
			[true, false, 'create-icon-dir'],
			[false, false, 'create-icon-dir'],
		];
	}

	/**
	 * @dataProvider enable_test_data
	 */
	public function test_enable($file_exists, $old_state, $expected)
	{
		$filesystem = $this->getMockBuilder(filesystem::class)
			->disableOriginalConstructor()
			->onlyMethods(['mkdir', 'exists', 'touch'])
			->getMock();

		$filesystem->expects($old_state ? self::never() : self::once())
			->method('exists')
			->willReturn($file_exists);

		$filesystem->expects($file_exists ? self::never() : self::once())
			->method('mkdir');

		$this->container->expects(self::once())
			->method('get')
			->with('filesystem')
			->willReturn($filesystem);

		$ext = new ext($this->container, $this->extension_finder, $this->migrator, 'phpbb/pwakit', '');

		self::assertEquals($expected, $ext->enable_step($old_state));
	}

	public function test_enable_fails()
	{
		$filesystem = $this->getMockBuilder(filesystem::class)
			->disableOriginalConstructor()
			->getMock();

		// Verify the mock is created
		self::assertNotNull($filesystem, 'Filesystem mock should not be null');

		$filesystem->expects(self::once())
			->method('exists')
			->willReturn(false);

		$filesystem->expects(self::once())
			->method('mkdir')
			->willThrowException(new filesystem_exception('Test Error', 'images/site_icons'));

		$user = new phpbb_mock_user();
		$user->data['user_id'] = '2';
		$user->ip = '1.0.0.01';

		$log = $this->getMockBuilder(log::class)
			->disableOriginalConstructor()
			->getMock();

		$log->expects($this->once())
			->method('add')
			->with('critical', '2', '1.0.0.01', 'LOG_PWA_DIR_FAIL', false, ['images/site_icons', 'Test Error']);

		// Make container return filesystem first
		$this->container->expects($this->exactly(3))
			->method('get')
			->willReturnCallback(function ($service) use ($filesystem, $log, $user) {
				return match ($service) {
					'filesystem' => $filesystem,
					'log' => $log,
					'user' => $user,
					default => null,
				};
			});

		$ext = new ext($this->container, $this->extension_finder, $this->migrator, 'phpbb/pwakit', '');

		self::assertEquals('create-icon-dir', $ext->enable_step(false));
	}
}
