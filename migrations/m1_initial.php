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

class m1_initial extends migration
{
	public static function depends_on(): array
	{
		return ['\phpbb\db\migration\data\v400\dev'];
	}

	public function effectively_installed(): bool
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'styles', 'pwa_bg_color');
	}

	public function update_schema(): array
	{
		return [
			'add_columns'	=> [
				$this->table_prefix . 'styles'	=> [
					'pwa_bg_color'		=> ['VCHAR:8', ''],
					'pwa_theme_color'	=> ['VCHAR:8', ''],
				],
			],
		];
	}

	public function revert_schema(): array
	{
		return [
			'drop_columns'	=> [
				$this->table_prefix . 'styles'	=> [
					'pwa_bg_color',
					'pwa_theme_color',
				],
			],
		];
	}
}
