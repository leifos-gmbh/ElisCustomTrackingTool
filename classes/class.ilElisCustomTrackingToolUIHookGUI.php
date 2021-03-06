<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");

/**
 * UIHook class for adding the main menu button
 * linking to the synchronization plugin GUI
 *
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id: class.ilSyncSlaveClientObjectsUIHookGUI.php 31357 2011-10-28 11:12:35Z bheyser $
 *
 */
class ilElisCustomTrackingToolUIHookGUI extends ilUIHookPluginGUI
{
	/**
	 * Modify HTML output of GUI elements. Modifications modes are:
	 * - ilUIHookPluginGUI::KEEP (No modification)
	 * - ilUIHookPluginGUI::REPLACE (Replace default HTML with your HTML)
	 * - ilUIHookPluginGUI::APPEND (Append your HTML to the default HTML)
	 * - ilUIHookPluginGUI::PREPEND (Prepend your HTML to the default HTML)
	 *
	 * @param string $a_comp component
	 * @param string $a_part string that identifies the part of the UI that is handled
	 * @param string $a_par array of parameters (depend on $a_comp and $a_part)
	 *
	 * @return array array with entries "mode" => modification mode, "html" => your html
	 */
	function getHTML($a_comp, $a_part, $a_par = array())
	{
		global $rbacsystem, $lng;


		$configfile = 'custom.ini.php';

		$ini = new ilIniFile($configfile);
		$ini->read();

		if( !file_exists($configfile) )
		{
			#throw new Exception('Custom Config Error: Config File does not exist!');
		}

		switch( false )
		{
			case $a_comp == "Services/MainMenu":
			case $a_part == "main_menu_list_entries":
				//case $rbacsystem->checkAccess("visible,read", SYSTEM_FOLDER_ID):
			case $rbacsystem->checkAccess('read', ADMINISTRATION_SERVICES_PLUGINS_REF_ID):
			case CLIENT_ID == $ini->readVariable('soap', 'client') :

				return array(
					"mode" => ilUIHookPluginGUI::KEEP,
					"html" => ""
				);
		}

		#$tpl->addCss($this->getPluginObject()->getDirectory().'/templates/menu_entry.css');

		$menuEntryTpl = $this->getPluginObject()->getTemplate('tpl.menu_entry.html');

		$pluginConfigLinkHREF = $this->getConfigurationScreenLinkTarget();
		$pluginConfigLinkTEXT = ilElisCustomTrackingToolPlugin::_getInstance()->txt('ectt_menu_item');

		$menuEntryTpl->setVariable('PLUGIN_CONFIG_LINK_HREF', $pluginConfigLinkHREF);
		$menuEntryTpl->setVariable('PLUGIN_CONFIG_LINK_TEXT', $pluginConfigLinkTEXT);

		return array(
			"mode" => ilUIHookPluginGUI::APPEND,
			"html" => $menuEntryTpl->get()
		);
	}

	/**
	 * Modify GUI objects, before they generate ouput
	 *
	 * @param string $a_comp component
	 * @param string $a_part string that identifies the part of the UI that is handled
	 * @param string $a_par array of parameters (depend on $a_comp and $a_part)
	 */
	function modifyGUI($a_comp, $a_part, $a_par = array())
	{

	}

	/**
	 *
	 * @global ilCtrl $ilCtrl
	 * @return string $target
	 */
	public function getConfigurationScreenLinkTarget()
	{
		$index = $this->getPluginObject()->getPluginName().'ConfigGuiTarget';

		if( !isset($_SESSION[$index]) )
		{
			$_SESSION[$index] = $this->buildConfigurationScreenLinkTarget();
		}

		return $_SESSION[$index];
	}

	private function buildConfigurationScreenLinkTarget()
	{
		$ctrlPath = array(
			'iladministrationgui',
			'ilobjcomponentsettingsgui',
			'il'.strtolower($this->getPluginObject()->getPluginName()).'configgui'
		);

		global $ilDB;

		$class_IN_ctrlClasses = $ilDB->in('class', $ctrlPath, false, 'text');

		$query = "
			SELECT	class, cid
			
			FROM	ctrl_classfile
			
			WHERE	$class_IN_ctrlClasses
		";

		$resultSet = $ilDB->query($query);

		$ctrlClasses = array_flip($ctrlPath);

		$commandNodeIds = array();

		while( $dataSet = $ilDB->fetchAssoc($resultSet) )
		{
			$commandNodeIds[ $ctrlClasses[ $dataSet['class'] ] ] = $dataSet['cid'];
		}

		ksort($commandNodeIds);

		$query = "
			SELECT		ref_id
			FROM		object_data odat
			INNER JOIN	object_reference oref
			ON			odat.obj_id = oref.obj_id
			WHERE		title = '__ComponentSettings'
			AND			type = 'cmps'
		";

		$resultSet = $ilDB->query($query);

		$componentSettingsRefId = null;

		while( $dataSet = $ilDB->fetchAssoc($resultSet) )
		{
			$componentSettingsRefId = $dataSet['ref_id'];
		}

		$params = array(
			'ref_id' => $componentSettingsRefId,
			'admin_mode' => 'settings',
			'ctype' => 'Services',
			'cname' => 'UIComponent',
			'slot_id' => 'uihk',
			'pname' => $this->getPluginObject()->getPluginName(),
			'cmd' => 'configure',
			'baseClass' => 'ilAdministrationGUI',
			'cmdClass' => 'il'.$this->getPluginObject()->getPluginName().'ConfigGUI',
			'cmdNode' => implode(':', $commandNodeIds),
		);

		$target = 'ilias.php';

		foreach($params as $paramName => $paramValue)
		{
			$target = ilUtil::appendUrlParameterString($target, "$paramName=$paramValue", false);
		}

		return $target;
	}

}

