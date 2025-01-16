<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024 phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\pwakit\tests\functional;

/**
 * @group functional
 */
class acp_settings_test extends \phpbb_functional_test_case
{
	/**
	 * @inheritdoc
	 */
	protected static function setup_extensions(): array
	{
		return ['phpbb/pwakit'];
	}

	public function test_extension_enabled()
	{
		$this->login();
		$this->admin_login();
		$this->add_lang('acp/extensions');

		$crawler = self::request('GET', 'adm/index.php?i=acp_extensions&mode=main&sid=' . $this->sid);

		$this->assertStringContainsString('Progressive Web App Kit', $crawler->filter('.ext_enabled')->eq(0)->text());
		$this->assertContainsLang('EXTENSION_DISABLE', $crawler->filter('.ext_enabled')->eq(0)->text());
	}

	public function test_basic_form()
	{
		$this->login();
		$this->admin_login();

		$this->add_lang_ext('phpbb/pwakit', 'info_acp_pwa');

		// Check ACP page loads
		$crawler = self::request('GET', 'adm/index.php?i=-phpbb-pwakit-acp-pwa_acp_module&mode=settings&sid=' . $this->sid);
		$this->assertContainsLang('ACP_PWA_KIT_SETTINGS', $crawler->filter('div.main > h1')->text());

		// The _1 means these are for prosilver (style id 1)
		$form_data = [
			'pwa_bg_color_1'	=> '#333333',
			'pwa_theme_color_1'	=> '#666666',
		];

		// Check initial data fields are empty
		foreach ($form_data as $name => $value)
		{
			$this->assertEquals('', $crawler->filter('input[name="' . $name . '"]')->attr('value'));
		}

		// Submit form
		$form = $crawler->selectButton('submit')->form($form_data);
		$crawler = self::submit($form);
		$this->assertStringContainsString($this->lang('CONFIG_UPDATED'), $crawler->filter('.successbox')->text());

		// Check saved data now appears in data fields
		$crawler = self::request('GET', 'adm/index.php?i=-phpbb-pwakit-acp-pwa_acp_module&mode=settings&sid=' . $this->sid);
		foreach ($form_data as $name => $value)
		{
			$this->assertEquals($value, $crawler->filter('input[name="' . $name . '"]')->attr('value'));
		}

		// Check saved data appears in the forum's meta tags as expected
		$crawler = self::request('GET', 'index.php?sid=' . $this->sid);
		$this->assertEquals($form_data['pwa_theme_color_1'], $crawler->filter('meta[name="theme-color"]')->attr('content'));
		$this->assertEquals($form_data['pwa_bg_color_1'], $crawler->filter('meta[name="background-color"]')->attr('content'));
	}
}
