<?php
/*
Plugin Name: Header Menu Admin
Description: Advanced management of CMS header links (Logoff, Help, License, etc) with an admin panel.
Version: 1.00
Requires at least: 3.65
Author: Sagentic Web Design
Author URI: https://www.sagentic.com
*/

// Plugin constants
$GLOBALS['HEADERMENUADMIN_VERSION'] = '1.00';

// Load helper functions
require_once __DIR__ . '/headerMenuAdmin_functions.php';

// Register hook to modify links
addFilter('menulinks_myAccount', 'headerMenuAdmin_modifyLinks');

// Load admin UI (only in admin area)
if (defined('IS_CMS_ADMIN')) {
    require_once __DIR__ . '/headerMenuAdmin_admin.php';

    // Register admin menu pages
    // The main entry point is Dashboard
    pluginAction_addHandlerAndLink(t('Header Menu Admin'), 'headerMenuAdmin_dashboard', 'admins');
    
    // Additional actions

    pluginAction_addHandler('headerMenuAdmin_help', 'admins');
    pluginAction_addHandler('headerMenuAdmin_editRule', 'admins'); // Specific edit page
    pluginAction_addHandler('headerMenuAdmin_deleteRule', 'admins'); // Delete action
}
