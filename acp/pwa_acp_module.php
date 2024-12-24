<?php
/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace mattf\pwakit\acp;

use Exception;
use mattf\pwakit\helper\helper;
use phpbb\config\config;
use phpbb\language\language;
use phpbb\request\request;
use phpbb\template\template;

class pwa_acp_module
{
	/** @var string $page_title */
	public string $page_title;

	/** @var string $tpl_name */
	public string $tpl_name;

	/** @var string $u_action */
	public string $u_action;

	/** @var config $config */
	protected config $config;

	/** @var helper $helper */
	protected helper $helper;

	/** @var language $language */
	protected language $language;

	/** @var request $request */
	protected request $request;

	/** @var template $template */
	protected template $template;

	/** @var string $phpbb_root_path */
	protected string $phpbb_root_path;

	/** @var array $errors */
	protected array $errors = [];

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

		$this->config = $phpbb_container->get('config');
		$this->helper = $phpbb_container->get('mattf.pwakit.helper');
		$this->language = $phpbb_container->get('language');
		$this->request = $phpbb_container->get('request');
		$this->template = $phpbb_container->get('template');
		$this->phpbb_root_path = $phpbb_container->getParameter('core.root_path');

		$form_key = 'acp_pwakit';
		add_form_key($form_key);

		if ($mode === 'settings')
		{
			$this->language->add_lang('acp/board');
			$this->language->add_lang('acp_pwa', 'mattf/pwakit');

			$this->tpl_name = 'acp_pwakit';
			$this->page_title = 'ACP_PWA_KIT_SETTINGS';

			if ($this->request->is_set_post('submit'))
			{
				if (!check_form_key($form_key))
				{
					trigger_error($this->language->lang('FORM_INVALID'), E_USER_WARNING);
				}

				$this->save_settings();
			}

			$this->display_settings();
		}
	}

	public function display_settings(): void
	{
		$this->template->assign_vars([
			'SITE_NAME'			=> $this->config['sitename'],
			'SITE_NAME_SHORT'	=> $this->config['sitename_short'],
			'PWA_BG_COLOR'		=> $this->config['pwa_bg_color'],
			'PWA_THEME_COLOR'	=> $this->config['pwa_theme_color'],
			'PWA_KIT_ICONS'		=> $this->helper->get_icons($this->phpbb_root_path),
			'U_ACTION'			=> $this->u_action,
		]);
	}

	public function save_settings()
	{
	}

	/**
	 * Display any errors
	 *
	 * @return bool
	 */
	public function display_errors(): bool
	{
		$has_errors = (bool) count($this->errors);

		$this->template->assign_vars([
			'S_ERROR'	=> $has_errors,
			'ERROR_MSG'	=> $has_errors ? implode('<br>', $this->errors) : '',
		]);

		return $has_errors;
	}
}
