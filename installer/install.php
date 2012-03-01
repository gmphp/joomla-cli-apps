#!/usr/bin/env php
<?php
/**
 * @package		Joomla.Cli
 *
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// Make sure we're being called from the command line, not a web interface
if (array_key_exists('REQUEST_METHOD', $_SERVER)) die();

/**
 * This is a CRON script which should be called from the command-line, not the
 * web. For example something like:
 * /usr/bin/php /path/to/site/cli/update_cron.php
 */

// Set flag that this is a parent file.
define('_JEXEC', 1);
define('DS', DIRECTORY_SEPARATOR);

error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors', 1);

// Load system defines
if (file_exists(dirname(dirname(__FILE__)) . '/defines.php'))
{
	require_once dirname(dirname(__FILE__)) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(dirname(__FILE__)));
	require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_LIBRARIES . '/import.php';
require_once JPATH_LIBRARIES . '/cms.php';

// Force library to be in JError legacy mode
JError::$legacy = true;

// Load the configuration
require_once JPATH_CONFIGURATION . '/configuration.php';

/**
 * This script will fetch the update information for all extensions and store
 * them in the database, speeding up your administrator.
 *
 * @package  Joomla.CLI
 * @since    2.5
 */
class Install extends JApplicationCli
{
	/**
	 * Entry point for the script
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function execute()
	{
		jimport('joomla.installer.installer');
		jimport('joomla.installer.helper');
		jimport('joomla.application.component.helper');
		
		$config = JFactory::getConfig();
		$config->set('debug', true); // force enable debug
		
		if (count($this->input->args) != 1)
		{
			$this->out("Usage: {$this->input->executable} </path/to/install_folder_or_package>");
			exit(1);
		}
		
		$source = $this->input->args[0];
		
		if (!file_exists($source))
		{
			$this->out("Error: file or directory not found!");
			exit(1);
		}
		
		$cleanupDir = false;		
		if (is_file($source))
		{
			// is a file!
			$this->out("Installing from file: $source");
			
			// need to extract it 
			$package = JInstallerHelper::unpack($source);
			if (!$package)
			{
				$this->out('Error: unable to extract package');
				exit(1);
			}
			$cleanupDir = true;
			$sourceDir = $package['dir'];
		}
		else
		{
			$this->out("Installing from directory: $source");
			$sourceDir = $source;
		}
		
		$installer = JInstaller::getInstance();
		$result = $installer->install($source);
		
		if ($result)
		{
			$this->out("Extension install was successful!");
			
			if (strlen($installer->get('extension_message')))
			{
				$this->out("Extension Message: " . $installer->get('extension_message'));				
			}
			if (strlen($installer->get('redirect_url')))
			{
				$this->out("Redirect URL : " . $installer->get('redirect_url'));
			}
		}
		else
		{
			$this->out("Failed to install extension!");
		}
		
		if (strlen($installer->message))
		{
			$this->out("Installer Message: " . $installer->message);
		}
	
		// clean up after ourselves	
		if ($cleanupDir)
		{
			@rmdir($source);
		}	
	}
}

JApplicationCli::getInstance('Install')->execute();
