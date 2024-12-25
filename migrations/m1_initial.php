<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\pwakit\migrations;

use phpbb\db\migration\migration;

class m1_initial extends migration
{
	public static function depends_on(): array
	{
		return ['\phpbb\db\migration\data\v400\dev'];
	}

	public function effectively_installed(): int
	{
		$sql = 'SELECT module_id
			FROM ' . $this->table_prefix . "modules
			WHERE module_class = 'acp'
				AND module_langname = 'ACP_PWA_KIT_TITLE'";
		$result = $this->db->sql_query($sql);
		$module_id = (int) $this->db->sql_fetchfield('module_id');
		$this->db->sql_freeresult($result);

		return $module_id;
	}

	public function update_data(): array
	{
		return [
			['config.add', ['pwa_bg_color', '']],
			['config.add', ['pwa_theme_color', '']],
			['module.add', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_PWA_KIT_TITLE'
			]],
			['module.add', [
				'acp',
				'ACP_PWA_KIT_TITLE',
				[
					'module_basename'	=> '\phpbb\pwakit\acp\pwa_acp_module',
					'modes'				=> ['settings'],
				],
			]],
		];
	}
}
