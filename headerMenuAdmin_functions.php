<?php
/**
 * Header Menu Admin - Helper Functions
 */

/**
 * Load plugin settings from JSON file
 * 
 * @return array
 */
function headerMenuAdmin_loadSettings(): array {
    $settingsFile = __DIR__ . '/headerMenuAdmin_settings.json';
    $defaults = [
        'rules' => []
    ];

    if (file_exists($settingsFile)) {
        $json = file_get_contents($settingsFile);
        $settings = json_decode($json, true);
        if (is_array($settings)) {
            return array_merge($defaults, $settings);
        }
    }

    return $defaults;
}

/**
 * Save plugin settings to JSON file
 * 
 * @param array $settings
 * @return bool
 */
function headerMenuAdmin_saveSettings(array $settings): bool {
    $settingsFile = __DIR__ . '/headerMenuAdmin_settings.json';
    $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    // Ensure we have permission to write
    if (file_put_contents($settingsFile, $json) !== false) {
        return true;
    }
    
    return false;
}

/**
 * Modify the header links based on settings
 * 
 * @param array $menuArray
 * @return array
 */
function headerMenuAdmin_modifyLinks($menuArray) {
    $settings = headerMenuAdmin_loadSettings();
    $rules = $settings['rules'] ?? [];
    
    if (empty($rules)) {
        return $menuArray;
    }

    foreach ($rules as $rule) {
        if (empty($rule['enabled'])) {
            continue;
        }

        if ($rule['type'] === 'remove') {
            $menuArray = headerMenuAdmin_applyRemoveRule($menuArray, $rule);
        } elseif ($rule['type'] === 'add') {
            $menuArray = headerMenuAdmin_applyAddRule($menuArray, $rule);
        }
    }

    return $menuArray;
}

/**
 * Apply a remove rule
 */
function headerMenuAdmin_applyRemoveRule($menuArray, $rule) {
    $field = $rule['match_field'] ?? 'link'; // link or menuName
    $pattern = $rule['match_pattern'] ?? '';
    $method = $rule['match_method'] ?? 'contains'; // contains, exact, regex

    if (empty($pattern)) {
        return $menuArray;
    }

    foreach ($menuArray as $index => $item) {
        $value = $item[$field] ?? '';
        $matched = false;

        switch ($method) {
            case 'regex':
                // Apply timeout protection for regex to prevent ReDoS
                $oldLimit = ini_get('pcre.backtrack_limit');
                ini_set('pcre.backtrack_limit', 10000); // Limit backtracking
                if (@preg_match($pattern, $value)) {
                    $matched = true;
                }
                ini_set('pcre.backtrack_limit', $oldLimit);
                break;
            case 'exact':
                if ($value === $pattern) {
                    $matched = true;
                }
                break;
            case 'contains':
            default:
                if (stripos($value, $pattern) !== false) {
                    $matched = true;
                }
                break;
        }

        if ($matched) {
            unset($menuArray[$index]);
        }
    }
    
    return array_values($menuArray); // Re-index array
}

/**
 * Apply an add rule
 */
function headerMenuAdmin_applyAddRule($menuArray, $rule) {
    $newMenu = [
        'menuName'   => $rule['add_menuName'] ?? '',
        'menuType'   => 'custom',
        'link'       => $rule['add_link'] ?? '#',
        'visibility' => $rule['add_visibility'] ?? 'showAlways',
        'target'     => $rule['add_target'] ?? '_self',
        'isSelected' => false,
    ];
    
    // Visibility checks logic is handled by CMSB usually, but if we need to set 'visibility' key, it works.
    // 'visibility' options: showAlways, requireLogin, requireAdmin, requireSectionAccess
    
    $position = $rule['add_position'] ?? 'end';
    
    if ($position === 'start') {
        array_unshift($menuArray, $newMenu);
    } else {
        array_push($menuArray, $newMenu);
    }
    
    return $menuArray;
}
