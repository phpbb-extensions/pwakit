<?php

/**
 *
 * Progressive Web App Kit. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024 phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\pwakit\controller;

use phpbb\cache\driver\driver_interface as cache_driver;
use phpbb\config\config;
use phpbb\db\driver\driver_interface as db_driver;
use phpbb\exception\runtime_exception;
use phpbb\language\language;
use phpbb\pwakit\helper\helper;
use phpbb\pwakit\helper\upload;
use phpbb\request\request;
use phpbb\template\template;

class admin_controller
{
	protected const FORM_KEY = 'acp_pwakit';

	/** @var string $id */
	protected string $id;

	/** @var string $u_action */
	protected string $u_action;

	/** @var cache_driver $cache */
	protected cache_driver $cache;

	/** @var config $config */
	protected config $config;

	/** @var db_driver $db */
	protected db_driver $db;

	/** @var helper $helper */
	protected helper $helper;

	/** @var language $language */
	protected language $language;

	/** @var request $request */
	protected request $request;

	/** @var template $template */
	protected template $template;

	/** @var upload */
	protected upload $upload;

	/** @var string $phpbb_root_path */
	protected string $phpbb_root_path;

	/** @var array $errors */
	protected array $errors = [];

	/** @var string $phpbb_admin_path */
	protected string $phpbb_admin_path;

	/** @var string $php_ext */
	protected string $php_ext;

	/**
	 * Constructor
	 *
	 * @param cache_driver $cache
	 * @param config $config
	 * @param db_driver $db
	 * @param language $language
	 * @param request $request
	 * @param template $template
	 * @param helper $helper
	 * @param upload $upload
	 * @param string $phpbb_root_path
	 * @param string $relative_admin_path
	 * @param string $php_ext
	 */
	public function __construct(cache_driver $cache, config $config, db_driver $db, language $language, request $request,
		template $template, helper $helper, upload $upload, string $phpbb_root_path, string $relative_admin_path, string $php_ext)
	{
		$this->cache = $cache;
		$this->config = $config;
		$this->db = $db;
		$this->helper = $helper;
		$this->upload = $upload;
		$this->language = $language;
		$this->request = $request;
		$this->template = $template;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpbb_admin_path = $phpbb_root_path . $relative_admin_path;
		$this->php_ext = $php_ext;

		$this->language->add_lang('acp/board');
		$this->language->add_lang('acp_pwa', 'phpbb/pwakit');
		$this->language->add_lang('posting'); // Used by upload() file errors
	}

	/**
	 * Main ACP module
	 *
	 * @param string $id
	 * @param string $mode
	 * @param string $u_action
	 * @return void
	 */
	public function main(string $id, string $mode, string $u_action): void
	{
		if ($mode !== 'settings')
		{
			return;
		}

		$this->id = $id;
		$this->u_action = $u_action;

		add_form_key(self::FORM_KEY);

		$action = $this->get_action();

		if ($action)
		{
			$this->execute_action($action);
		}

		$this->display_settings();
	}

	/**
	 * Get the action from the request. We need to check is_set_post() for all actions
	 *
	 * @return string|null
	 */
	protected function get_action(): string|null
	{
		$actions = ['submit', 'resync', 'upload', 'delete'];
		foreach ($actions as $action)
		{
			if ($this->request->is_set_post($action))
			{
				return $action;
			}
		}
		return null;
	}

	/**
	 * Execute the action
	 *
	 * @param string $action
	 * @return void
	 */
	protected function execute_action(string $action): void
	{
		// Actions that require form key validation (not using confirm_box())
		$form_key_actions = ['submit', 'resync', 'upload'];

		// Check form key validation
		if (in_array($action, $form_key_actions, true) && !check_form_key(self::FORM_KEY))
		{
			$this->error('FORM_INVALID');
		}

		// Using match expression (PHP 8.0+)
		match($action) {
			'submit' => $this->save_settings(),
			'resync' => $this->helper->resync_icons(),
			'upload' => $this->upload(),
			'delete' => $this->delete(),
		};
	}

	/**
	 * Display settings
	 *
	 * @return void
	 */
	protected function display_settings(): void
	{
		$this->template->assign_vars([
			'SITE_NAME'			=> $this->config->offsetGet('sitename'),
			'SITE_NAME_SHORT'	=> $this->config->offsetGet('sitename_short') ?: $this->trim_name($this->config->offsetGet('sitename'), 0, 12),
			'PWA_IMAGES_DIR'	=> $this->helper->get_storage_path(),
			'PWA_KIT_ICONS'		=> $this->helper->get_icons($this->phpbb_root_path),
			'STYLES'			=> $this->get_styles(),
			'U_BOARD_SETTINGS'	=> append_sid("{$this->phpbb_admin_path}index.$this->php_ext", 'i=acp_board&amp;mode=settings'),
			'U_STORAGE_SETTINGS'=> append_sid("{$this->phpbb_admin_path}index.$this->php_ext", 'i=acp_storage&amp;mode=settings'),
			'U_ACTION'			=> $this->u_action,
		]);

		$this->display_errors();
	}

	/**
	 * Save settings
	 *
	 * @return void
	 */
	protected function save_settings(): void
	{
		$styles = $this->get_styles();
		$updates = [];

		foreach ($styles as $row)
		{
			$style_id			= $row['style_id'];
			$pwa_bg_color		= $this->request->variable('pwa_bg_color_' . $style_id, '');
			$pwa_theme_color	= $this->request->variable('pwa_theme_color_' . $style_id, '');

			$updates[$style_id] = [
				'pwa_bg_color'		=> $this->validate_hex_color($pwa_bg_color) ? $pwa_bg_color : $row['pwa_bg_color'],
				'pwa_theme_color'	=> $this->validate_hex_color($pwa_theme_color) ? $pwa_theme_color : $row['pwa_theme_color'],
			];
		}

		$this->set_styles($updates);

		if ($this->has_errors())
		{
			return;
		}

		$this->success('CONFIG_UPDATED');
	}

	/**
	 * Are there any errors?
	 *
	 * @return bool
	 */
	protected function has_errors(): bool
	{
		return (bool) count($this->errors);
	}

	/**
	 * Display any errors
	 *
	 * @return void
	 */
	protected function display_errors(): void
	{
		$has_errors = $this->has_errors();

		$this->template->assign_vars([
			'S_ERROR'	=> $has_errors,
			'ERROR_MSG'	=> $has_errors ? implode('<br>', $this->errors) : '',
		]);
	}

	/**
	 * Validate HTML color hex codes
	 *
	 * @param string $code
	 * @return bool
	 */
	protected function validate_hex_color(string $code): bool
	{
		$code = trim($code);

		if ($code === '')
		{
			return true;
		}

		$test = (bool) preg_match('/^#([0-9A-F]{3}){1,2}$/i', $code);

		if ($test === false)
		{
			$this->errors[] = $this->language->lang('ACP_PWA_INVALID_COLOR', $code);
		}

		return $test;
	}

	/**
	 * Upload image
	 *
	 * @return void
	 */
	protected function upload(): void
	{
		try
		{
			$this->upload->upload();
		}
		catch (runtime_exception $e)
		{
			$this->upload->remove();

			$this->errors[] = $this->language->lang($e->getMessage());
		}

		if ($this->has_errors())
		{
			return;
		}

		$this->success('ACP_PWA_IMG_UPLOAD_SUCCESS');
	}

	/**
	 * Delete image
	 *
	 * @return void
	 */
	protected function delete(): void
	{
		$path = $this->request->variable('delete', '');

		if (confirm_box(true))
		{
			try
			{
				$result = $this->helper->delete_icon($path);
				$this->success($this->language->lang('ACP_PWA_IMG_DELETED', $result));
			}
			catch (runtime_exception $e)
			{
				$this->error($this->language->lang('ACP_PWA_IMG_DELETE_ERROR', $this->language->lang($e->getMessage())));
			}
		}
		else
		{
			confirm_box(false, 'ACP_PWA_IMG_DELETE', build_hidden_fields(array(
				'i'			=> $this->id,
				'mode'		=> 'settings',
				'delete'	=> $path,
				'action'	=> $this->u_action,
			)));
		}
	}

	/**
	 * Trim name, accounting for multibyte and emoji chars
	 *
	 * @param string $string
	 * @param int $start
	 * @param int $length
	 * @return string
	 */
	protected function trim_name(string $string, int $start, int $length): string
	{
		// Check if string contains any HTML entities
		if (str_contains($string, '&') && preg_match('/&[#a-zA-Z0-9]+;/', $string))
		{
			$decoded = html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			$trimmed = utf8_substr($decoded, $start, $length);
			return htmlspecialchars($trimmed, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}

		// If no HTML entities, just trim the string directly
		return utf8_substr($string, $start, $length);
	}

	/**
	 * Trigger success message
	 *
	 * @param string $msg Message lang key
	 * @return void
	 */
	protected function success(string $msg): void
	{
		trigger_error($this->language->lang($msg) . adm_back_link($this->u_action));
	}

	/**
	 * Trigger error message
	 *
	 * @param string $msg Message lang key
	 * @return void
	 */
	protected function error(string $msg): void
	{
		trigger_error($this->language->lang($msg) . adm_back_link($this->u_action), E_USER_WARNING);
	}

	/**
	 * Get style data from the styles table
	 *
	 * @return array Style data
	 */
	protected function get_styles(): array
	{
		$sql = 'SELECT style_id, style_name, pwa_bg_color, pwa_theme_color
			FROM ' . STYLES_TABLE . '
			WHERE style_active = 1
			ORDER BY style_name';
		$result = $this->db->sql_query($sql, 3600);

		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Set style data in the styles table
	 *
	 * @param array $rows Array of style table data to update; style_id is key
	 * @return void
	 */
	protected function set_styles(array $rows): void
	{
		if (!empty($rows))
		{
			$this->db->sql_transaction('begin');

			foreach ($rows as $style_id => $row)
			{
				$sql = 'UPDATE ' . STYLES_TABLE . '
					SET ' . $this->db->sql_build_array('UPDATE', $row) . '
					WHERE style_id = ' . (int) $style_id;
				$this->db->sql_query($sql);
			}

			$this->db->sql_transaction('commit');

			$this->cache->destroy('sql', STYLES_TABLE);
		}
	}
}
