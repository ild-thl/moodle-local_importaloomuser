<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Link to CSV user upload
 *
 * @package    local
 * @subpackage importaloomuser
 * @copyright   2023 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Ensure the configurations for this site are set
if ( $hassiteconfig ){

	// Create the new settings page
	// - in a local plugin this is not defined as standard, so normal $settings->methods will throw an error as
	// $settings will be NULL
	$settings = new admin_settingpage( 'local_importaloomuser', 'Import aloom user' );

	// Create 
	$ADMIN->add( 'localplugins', $settings );

	// Add a setting field to the settings for this page
	$settings->add( new admin_setting_configtext(
		
		// This is the reference you will use to your configuration
		'local_importaloomuser_event_id',
	
		// This is the friendly title for the config, which will be displayed
		'Event ID',
	
		// This is helper text for this config field
		'Aloom Event ID (in der URL)',
	
		// This is the default value
		'10886',
	
		// This is the type of Parameter this config is
		PARAM_TEXT
	
	) );

    	// Add a setting field to the settings for this page
	$settings->add( new admin_setting_configpasswordunmask(
		
		// This is the reference you will use to your configuration
		'local_importaloomuserdb_token',
	
		// This is the friendly title for the config, which will be displayed
		'X-Auth-Token:',
	
		// This is helper text for this config field
		'X-Auth-Token des Events',
	
		// This is the default value
		'',
	
		// This is the type of Parameter this config is
		PARAM_TEXT
	
	) );
}
