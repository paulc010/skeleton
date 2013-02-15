<?php
/*
* 2007-2012 eCartService.net
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.md
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
*
*  @author eCartService.net <pcampbell@ecartservice.net>
*  @copyright  2009-2013 eCartService.net
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*/

if (!defined('_PS_VERSION_'))
	exit;
	
class Skeleton extends Module
{
	/* Maintain compatibility */
	private $compat;
	
	/* Internal properites */
	private $mod_warnings = array();
	private $mod_errors = array();
	private $html;
	private $configuration_vars = array();
	private $deprecated_configuration_vars = array();

	/*
	 * Module Initialisation
	 *
	 */
	
	public function __construct()
	{
		// Manage compatibility
		$version_mask = explode('.', _PS_VERSION_, 3);
		$this->compat = (int)($version_mask[0] * 10) + $version_mask[1];
		
		// Module properties
		$this->name = 'skeleton';
		$this->tab = $this->compat > 13 ? 'others' : 'Custom';
		$this->version = '1.5';
		
		// Version-specific module properties
		if ($this->compat > 13) 
			$this->author = 'eCartService.net';
		if ($this->compat > 14)
			$this->need_instance = 0;

		parent::__construct();
		
		$this->displayName = $this->l('Skeleton Module');
		$this->description = $this->l('Starter module development framework. Modify and enjoy!');
		
		// Set default config values if they don't already exist (here for compatibility in case the user doesn't uninstall/install at upgrade)
		// Also set internal data and check for store configuration changes that may impact (e.g. default language or currency changes)
		if ($this->isInstalled($this->name))
			$this->configureSettings();
	}

	public function install()
	{
		$this->setConfigurationVars();
		return ($this->setHooks() && parent::install());
	}
	
	private function setHooks()
	{
			return true;
	}
	
	private function configureSettings()
	{
		// Module configuration. Example usage:
		//$this->addConfigValue('PAUL_TEST_CONFIG_VAR', 'Test default value', 'My setting');
		// Note that the "key" will always be forced UPPERCASE
		// You can access this property using its key in LOWERCASE e.g.  $this->paul_test_config_var within the class
		//
		// If you later remove a setting you can use the following to remove it from the database
		//$this->deprecateConfigValue('PAUL_TEST_CONFIG_VAR');

		$this->upgradeConfigurationVars();
		$this->setConfigurationVars();
	}
	
	private function upgradeConfigurationVars()
	{
		// Make any changes required to configuration values (add/modify/delete) as appropriate.
		foreach ($this->deprecated_configuration_vars as $config_var)
			if (Configuration::get($this->name.'_'.$config_var))
				Configuration::deleteByName($this->name.'_'.$config_var);
		
		// You can add custom code for more specific manipulation of values.
		return true;
	}
	
	private function setConfigurationVars()
	{
		// Ensure that all config variables are initialised with at least the default value and save them as class properties
		foreach ($this->configuration_vars as $config)
		{
			if (!$config_value = Configuration::get($this->name.'_'.$config['name']))
			{
				Configuration::updateValue($this->name.'_'.$config['name'], $config['default']);
				$config_value = $config['default'];
			}
			$this->{strtolower($config['name'])} = $config_value;
		}
	}
	
	private function updateConfigurationVars()
	{
		// Save to database
		foreach ($this->configuration_vars as $config)
			Configuration::updateValue($this->name.'_'.$config['name'], Tools::getValue('input_'.$config['name'], Configuration::get($this->name.'_'.$config['name'])));
		
		// Update class properties
		$this->setConfigurationVars();
	}
	
	private function addConfigValue($name, $default_value, $label = '', $type = 'text', $help = '')
	{
		$config_entry = array('name' => strtoupper($name), 'default' => $default_value, 'type' => $type, 'label' => !empty($label) ? $label : $name, 'help' => $help);
		$this->configuration_vars[] = $config_entry;
	}
	
	private function deprecateConfigValue($name)
	{
		$this->deprecated_configuration_vars[] = strtoupper($name);
	}
	
	private function renameConfigValue($name)
	{
		
	}
	
	/*
	 * Module Administration
	 *
	 * You can delete this section if your module has no configurable options
	 */
	public function getContent()
	{
		$this->html = '<h2>'.$this->displayName.'</h2>';
		if (!count($this->configuration_vars))
			$this->addWarning('This module has nothing to configure!');

		$nb_warnings = $this->displayWarnings();
		$nb_errors = $this->displayErrors();
  
		if (Tools::getValue('btnUpdate'))
		{
			if ($this->postValidation() && !$nb_errors)
				$this->updateConfigurationVars();
		}
  
		if (count($this->configuration_vars))
			$this->displayConfigurationForm();
  
		return $this->html;
	}
	
	protected function displayConfigurationForm()
	{
		$this->html .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
		$this->html .= '<fieldset>';
		$this->html .= '<legend><img src="../img/admin/cog.gif" alt="" class="middle" />'.$this->l('Settings').'</legend>';
		
		foreach ($this->configuration_vars as $config_var)
		{
			$this->html .= '<label>'.$config_var['label'].'</label>';
			$this->html .= '<input name="input_'.$config_var['name'].'" type="'.$config_var['type'].'" value="'.(Tools::getValue('input_'.$config_var['name']) ? Tools::getValue('input_'.$config_var['name']) : Configuration::get($this->name.'_'.$config_var['name'])).'"/>';
			$this->html .= !empty($config_var['help']) ? '<p class="clear">'.$config_var['help'].'</p>' : '<br /><br />';
		}
		
		$this->html .= '</fieldset>';
		$this->html .= '<br/><input name="btnUpdate" id="btnUpdate" class="button" value="'.$this->l('Update Settings').'" type="submit" />';
		$this->html .= '</form>';
	}
	
	protected function postValidation()
	{
		// Add any form validation rules. For security you should AT LEAST apply type rules to the $_POST values....
		// DON'T rely on Prestashop cleaning these for you...
		return true;	
	}
	
	private function addError($error_message)
	{
		$this->mod_errors[] = $error_message;
	}
	
	private function displayErrors()
	{
		$nb_errors = count($this->mod_errors);
		if ($nb_errors)
		{
			$this->html .= '<script type="text/javascript">
				$(document).ready(function() {
					$(\'#hideError\').unbind(\'click\').click(function(){
						$(\'.error\').hide(\'slow\', function (){
							$(\'.error\').remove();
						});
						return false;
					});
				});
			  </script>
			<div class="error">
				<span style="float:right">
					<a id="hideError" href="#">
						<img alt="X" src="../img/admin/close.png" />
					</a>
				</span>';
			$this->html .= ($nb_errors > 1 ? $this->l('There are') : $this->l('There is')).' '.$nb_errors.' '.($nb_errors > 1 ? $this->l('errors') : $this->l('error')).':'
			.'<ol>';
			foreach ($this->mod_errors as $error)
				$this->html .= '<li>'.$error.'</li>';
			$this->html .= '</ol>';

			$this->html .= '</div>';
		}
		return $nb_errors;
	}
	
	private function addWarning($warning_message)
	{
		$this->mod_warnings[] = $warning_message;
	}
	
	private function	displayWarnings()
	{
		$nb_warnings = count($this->mod_warnings);
		if ($nb_warnings)
		{
			$this->html .= '<script type="text/javascript">
					$(document).ready(function() {
						$(\'#hideWarn\').unbind(\'click\').click(function(){
							$(\'.warn\').hide(\'slow\', function (){
								$(\'.warn\').remove();
							});
							return false;
						});
					});
				  </script>
			<div class="warn">
				<span style="float:right">
					<a id="hideWarn" href="#">
						<img alt="X" src="../img/admin/close.png" />
					</a>
				</span>';
			
			$this->html .= ($nb_warnings > 1 ? $this->l('There are') : $this->l('There is')).' '.$nb_warnings.' '.($nb_warnings > 1 ? $this->l('warnings') : $this->l('warning')).':'
			.'<ol>';
			foreach ($this->mod_warnings as $warning)
				$this->html .= '<li>'.$warning.'</li>';
			$this->html .= '</ol>';
			
			$this->html .= '</div>';
		}
		return $nb_warnings;
	}
}