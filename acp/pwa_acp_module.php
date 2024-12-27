<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024 phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\pwakit\acp;

use Exception;
use phpbb\pwakit\controller\admin_controller;

class pwa_acp_module
{
	/** @var string $page_title */
	public string $page_title;

	/** @var string $tpl_name */
	public string $tpl_name;

	/** @var string $u_action */
	public string $u_action;

	/**
	 * Main ACP module
	 *
	 * @param $id
	 * @param string $mode
	 * @throws Exception
	 */
	public function main($id, string $mode): void
	{
		global $phpbb_container;

		/** @var admin_controller $admin_controller */
		$admin_controller = $phpbb_container->get('phpbb.pwakit.admin.controller');

		// Make the $u_action url available in the admin controller
		$admin_controller->set_page_url($this->u_action);

		$this->tpl_name = 'acp_pwakit';
		$this->page_title = 'ACP_PWA_KIT_SETTINGS';

		$admin_controller->main($mode);
	}
}
