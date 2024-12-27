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
class view_test extends \phpbb_functional_test_case
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

		$crawler = self::request('GET', 'adm/index.php?i=acp_extensions&mode=main&sid=' . $this->sid);

		$this->assertStringContainsString('Progressive Web App Kit', $crawler->filter('.ext_enabled')->eq(0)->text());
		$this->assertContainsLang('EXTENSION_DISABLE', $crawler->filter('.ext_enabled')->eq(0)->text());
	}

	public function test_acp_module()
	{
		$this->login();
		$this->admin_login();

		$this->add_lang_ext('phpbb/pwakit', 'info_acp_pwa');

		$crawler = self::request('GET', 'adm/index.php?i=-phpbb-pwakit-acp-pwa_acp_module&mode=settings&sid=' . $this->sid);
		$this->assertContainsLang('ACP_PWA_KIT_SETTINGS', $crawler->filter('div.main > h1')->text());

		$form_data = [
			'pwa_bg_color'		=> '#000000',
			'pwa_theme_color'	=> '#ffffff',
		];
		$form = $crawler->selectButton('submit')->form($form_data);
		$crawler = self::submit($form);
		$this->assertStringContainsString($this->lang('CONFIG_UPDATED'), $crawler->filter('.successbox')->text());

		$crawler = self::request('GET', 'adm/index.php?i=-phpbb-pwakit-acp-pwa_acp_module&mode=settings&sid=' . $this->sid);

		foreach ($form_data as $config_name => $config_value)
		{
			$this->assertEquals($config_value, $crawler->filter('input[name="' . $config_name . '"]')->attr('value'));
		}
	}
}
