<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024 phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\pwakit\migrations;

use phpbb\db\migration\migration;

class m2_data extends migration
{
	public static function depends_on(): array
	{
		return ['\phpbb\pwakit\migrations\m1_initial'];
	}

	public function effectively_installed(): bool
	{
		$sql = 'SELECT module_id
			FROM ' . $this->table_prefix . "modules
			WHERE module_class = 'acp'
				AND module_langname = 'ACP_PWA_KIT_TITLE'";
		$result = $this->db->sql_query($sql);
		$module_id = (int) $this->db->sql_fetchfield('module_id');
		$this->db->sql_freeresult($result);

		return (bool) $module_id;
	}

	public function update_data(): array
	{
		return [
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
