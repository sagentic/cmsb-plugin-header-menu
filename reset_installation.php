#!/usr/bin/env php
<?php
/**
 * Header Menu Admin - Reset Installation Script
 *
 * This script resets the plugin to its default state by:
 * 1. Backing up the current settings file
 * 2. Deleting the settings file
 *
 * Usage: php reset_installation.php
 */

// Ensure running from command line
if (php_sapi_name() !== 'cli') {
	die("This script must be run from the command line.\n");
}

echo "Header Menu Admin - Reset Installation\n";
echo "======================================\n\n";

$settingsFile = __DIR__ . '/headerMenuAdmin_settings.json';

// Check if settings file exists
if (!file_exists($settingsFile)) {
	echo "No settings file found. Plugin is already in default state.\n";
	exit(0);
}

// Create backup
$backupFile = __DIR__ . '/headerMenuAdmin_settings.backup.' . date('Y-m-d_His') . '.json';
if (copy($settingsFile, $backupFile)) {
	echo "Backup created: " . basename($backupFile) . "\n";
} else {
	echo "Warning: Could not create backup file.\n";
}

// Delete settings file
if (unlink($settingsFile)) {
	echo "Settings file deleted successfully.\n";
	echo "\nPlugin has been reset to default state.\n";
	echo "A fresh settings file will be created when you save settings in the admin.\n";
} else {
	echo "Error: Could not delete settings file.\n";
	echo "Please check file permissions and try again.\n";
	exit(1);
}

echo "\nDone!\n";
