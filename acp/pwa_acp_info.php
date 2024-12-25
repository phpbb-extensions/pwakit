<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\pwakit\acp;

class pwa_acp_info
{
	public function module(): array
	{
		return [
			'filename'	=> '\phpbb\pwakit\acp\pwa_acp_module',
			'title'		=> 'ACP_PWA_KIT_TITLE',
			'modes'		=> [
				'settings'	=> [
					'title' => 'ACP_PWA_KIT_SETTINGS',
					'auth' => 'ext_phpbb/pwakit && acl_a_board',
					'cat' => ['ACP_PWA_KIT_TITLE']
				],
			],
		];
	}
}
