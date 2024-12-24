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
use mattf\pwakit\helper\upload;
use phpbb\config\config;
use phpbb\exception\runtime_exception;
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

	/** @var upload */
	protected mixed $uploader;

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
		$this->uploader = $phpbb_container->get('mattf.pwakit.upload');
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
			$this->language->add_lang('posting'); // Used by banner_upload() file errors

			$this->tpl_name = 'acp_pwakit';
			$this->page_title = 'ACP_PWA_KIT_SETTINGS';

			$submit = $this->request->is_set_post('submit');
			$upload = $this->request->is_set_post('upload');

			if ($submit || $upload)
			{
				if (!check_form_key($form_key))
				{
					trigger_error($this->language->lang('FORM_INVALID'), E_USER_WARNING);
				}

				if ($upload)
				{
					$this->upload();
				}
				else
				{
					$this->save_settings();
				}
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

		$this->display_errors();
	}

	public function save_settings(): void
	{
		$config_array = [
			'pwa_bg_color'		=> $this->request->variable('pwa_bg_color', ''),
			'pwa_theme_color'	=> $this->request->variable('pwa_theme_color', ''),
		];

		foreach ($config_array as $config_value)
		{
			$this->validate_hex_color($config_value);
		}

		if ($this->display_errors())
		{
			return;
		}

		foreach ($config_array as $config_name => $config_value)
		{
			$this->config->set($config_name, $config_value);
		}

		trigger_error($this->language->lang('CONFIG_UPDATED') . adm_back_link($this->u_action), E_USER_NOTICE);
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

	/**
	 * Validate HTML color hex codes
	 *
	 * @param string $code
	 * @return void
	 */
	protected function validate_hex_color(string $code): void
	{
		$test = false;

		if (!empty($code))
		{
			$test = (bool) preg_match('/^#([0-9A-F]{3}){1,2}$/i', trim($code));
		}

		if ($test === false)
		{
			$this->errors[] = $this->language->lang('ACP_PWA_INVALID_COLOR', $code);
		}
	}

	/**
	 * Upload image and return updated ad code or <img> of new banner when using ajax.
	 */
	public function upload(): void
	{
		try
		{
			$this->uploader->upload();
		}
		catch (runtime_exception $e)
		{
			$this->uploader->remove();

			$this->errors[] = $this->language->lang($e->getMessage());
		}

		if ($this->display_errors())
		{
			return;
		}

		$this->helper->reset_icons($this->phpbb_root_path);

		trigger_error($this->language->lang('CONFIG_UPDATED') . adm_back_link($this->u_action), E_USER_NOTICE);
	}
}
