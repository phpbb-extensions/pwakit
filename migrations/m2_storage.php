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
use phpbb\pwakit\ext;
use phpbb\storage\provider\local;

class m2_storage extends migration
{
	public static function depends_on(): array
	{
		return ['\phpbb\pwakit\migrations\m1_initial'];
	}

	public function effectively_installed(): int
	{
		return $this->config->offsetExists('storage\\phpbb_pwakit\\config\\path');
	}

	public function update_data(): array
	{
		return [
			['config.add', ['storage\\phpbb_pwakit\\provider', local::class]],
			['config.add', ['storage\\phpbb_pwakit\\config\\path', ext::PWA_ICON_DIR]],
		];
	}

	public function revert_data(): array
	{
		return [
			['config.remove', ['storage\\phpbb_pwakit\\provider']],
			['config.remove', ['storage\\phpbb_pwakit\\config\\path']],
			['custom', [[$this, 'delete_storage']]],
		];
	}

	public function delete_storage(): void
	{
		$sql = 'DELETE FROM ' . $this->tables['storage'] . "
			WHERE storage = 'phpbb_pwakit'";
		$this->db->sql_query($sql);
	}
}
