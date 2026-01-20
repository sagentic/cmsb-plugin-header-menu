<?php
/**
 * Header Menu Admin - Admin UI
 */

/**
 * Generate plugin navigation bar
 */
function headerMenuAdmin_getPluginNav(string $currentPage): string
{
    $pages = [
        'dashboard' => ['label' => t('Dashboard'), 'action' => 'headerMenuAdmin_dashboard'],
        'add'       => ['label' => t('Add New Link'), 'action' => 'headerMenuAdmin_editRule'],
        'help'      => ['label' => t('Help'), 'action' => 'headerMenuAdmin_help'],
    ];

    $html = '<nav aria-label="' . t('Header Menu Admin navigation') . '"><div class="btn-group" role="group" style="margin-bottom:20px">';
    foreach ($pages as $key => $page) {
        $isActive = ($key === $currentPage);
        $btnClass = $isActive ? 'btn btn-primary' : 'btn btn-default';
        $ariaCurrent = $isActive ? ' aria-current="page"' : '';
        $html .= '<a href="?_pluginAction=' . $page['action'] . '" class="' . $btnClass . '"' . $ariaCurrent . '>' . $page['label'] . '</a>';
    }
    $html .= '</div></nav>';

    return $html;
}

/**
 * Dashboard - List Rules
 */
function headerMenuAdmin_dashboard(): void
{
    $settings = headerMenuAdmin_loadSettings();
    $rules = $settings['rules'] ?? [];

    $adminUI = [];
    $adminUI['PAGE_TITLE'] = [
        t("Plugins") => '?menu=admin&action=plugins',
        t("Header Menu Admin"),
    ];

    $content = '';
    $content .= headerMenuAdmin_getPluginNav('dashboard');

    $content .= '<div class="separator"><div>' . t('Header Links') . '</div></div>';
    
    if (empty($rules)) {
        $content .= '<div class="alert alert-info">' . t('No links defined yet. Click "Add New Link" to get started.') . '</div>';
    } else {
        $content .= '<div class="table-responsive">';
        $content .= '<table class="table table-striped table-hover">';
        $content .= '<thead><tr>';
        $content .= '<th>' . t('Status') . '</th>';
        $content .= '<th>' . t('Type') . '</th>';
        $content .= '<th>' . t('Description') . '</th>';
        $content .= '<th class="text-center">' . t('Actions') . '</th>';
        $content .= '</tr></thead>';
        $content .= '<tbody>';

        foreach ($rules as $index => $rule) {
            $isActive = !empty($rule['enabled']);
            $status = $isActive
                ? '<span class="label label-success">' . t('Active') . '</span>' 
                : '<span class="label label-default">' . t('Inactive') . '</span>';
                
            $typeLabel = ucfirst($rule['type']);
            
            $desc = '';
            if ($rule['type'] === 'remove') {
                $method = $rule['match_method'] ?? 'contains';
                $field = $rule['match_field'] ?? 'link';
                $pattern = htmlencode($rule['match_pattern'] ?? '');
                $desc = t("Remove item where <strong>$field</strong> $method <code>$pattern</code>");
            } else {
                $name = htmlencode($rule['add_menuName'] ?? '(no name)');
                $link = htmlencode($rule['add_link'] ?? '');
                $desc = t("Add link <strong>$name</strong> points to <code>$link</code>");
            }

            $editLink = '?_pluginAction=headerMenuAdmin_editRule&index=' . $index;
            $deleteLink = '?_pluginAction=headerMenuAdmin_deleteRule&index=' . $index . '&_csrf=' . ($_SESSION['_csrf']??'');

            $content .= '<tr>';
            $content .= '<td>' . $status . '</td>';
            $content .= '<td>' . $typeLabel . '</td>';
            $content .= '<td>' . $desc . '</td>';
            $content .= '<td class="text-center">';

            // Edit Button
            $content .= '<a href="' . $editLink . '" class="btn btn-default btn-sm">' . t('Edit') . '</a> ';

            // Delete Button
            $content .= '<a href="' . $deleteLink . '" class="btn btn-danger btn-sm" onclick="return confirm(\'' . t('Are you sure you want to delete this link?') . '\')">' . t('Delete') . '</a>';
            
            $content .= '</td>';
            $content .= '</tr>';
        }

        $content .= '</tbody></table></div>';
    }

    $adminUI['CONTENT'] = $content;
    adminUI($adminUI);
}

/**
 * Edit/Add Rule
 */
function headerMenuAdmin_editRule(): void
{
    $settings = headerMenuAdmin_loadSettings();
    $rules = $settings['rules'] ?? [];
    
    $index = isset($_REQUEST['index']) ? (int)$_REQUEST['index'] : null;
    $isEdit = ($index !== null && isset($rules[$index]));
    
    $rule = $isEdit ? $rules[$index] : [
        'type' => 'remove',
        'enabled' => 1,
        'match_field' => 'link',
        'match_method' => 'contains',
        'match_pattern' => '',
        'add_menuName' => '',
        'add_link' => '',
        'add_visibility' => 'showAlways',
        'add_target' => '_self',
        'add_position' => 'end'
    ];

    // Handle Save
    if (($_REQUEST['save'] ?? '')) {
        security_dieOnInvalidCsrfToken();

        // Define allowed values for validation
        $allowedTypes = ['add', 'remove'];
        $allowedMatchFields = ['link', 'menuName'];
        $allowedMatchMethods = ['contains', 'exact', 'regex'];
        $allowedVisibility = ['showAlways', 'requireLogin', 'requireAdmin'];
        $allowedTargets = ['_self', '_blank'];
        $allowedPositions = ['start', 'end'];

        // Validate and sanitize inputs
        $type = in_array($_POST['type'] ?? '', $allowedTypes) ? $_POST['type'] : 'add';
        $matchField = in_array($_POST['match_field'] ?? '', $allowedMatchFields) ? $_POST['match_field'] : 'link';
        $matchMethod = in_array($_POST['match_method'] ?? '', $allowedMatchMethods) ? $_POST['match_method'] : 'contains';
        $visibility = in_array($_POST['add_visibility'] ?? '', $allowedVisibility) ? $_POST['add_visibility'] : 'showAlways';
        $target = in_array($_POST['add_target'] ?? '', $allowedTargets) ? $_POST['add_target'] : '_self';
        $position = in_array($_POST['add_position'] ?? '', $allowedPositions) ? $_POST['add_position'] : 'end';

        // Required field validation for 'add' type
        $errors = [];
        if ($type === 'add') {
            if (trim($_POST['add_menuName'] ?? '') === '') {
                $errors[] = t('Menu Label is required when adding a link.');
            }
            if (trim($_POST['add_link'] ?? '') === '') {
                $errors[] = t('Link URL is required when adding a link.');
            }
        } elseif ($type === 'remove') {
            if (trim($_POST['match_pattern'] ?? '') === '') {
                $errors[] = t('Pattern/Text is required when removing a link.');
            }
        }

        // Validate regex pattern if method is regex
        if ($type === 'remove' && $matchMethod === 'regex') {
            $pattern = trim($_POST['match_pattern'] ?? '');
            if ($pattern !== '' && @preg_match($pattern, '') === false) {
                $errors[] = t('Invalid regular expression pattern.');
            }
        }

        if (!empty($errors)) {
            // Store errors for display
            $GLOBALS['headerMenuAdmin_errors'] = $errors;
        } else {
            $newRule = [
                'type' => $type,
                'enabled' => isset($_POST['enabled']) ? 1 : 0,
                // Remove options
                'match_field' => $matchField,
                'match_method' => $matchMethod,
                'match_pattern' => trim($_POST['match_pattern'] ?? ''),
                // Add options
                'add_menuName' => trim($_POST['add_menuName'] ?? ''),
                'add_link' => trim($_POST['add_link'] ?? ''),
                'add_visibility' => $visibility,
                'add_target' => $target,
                'add_position' => $position,
            ];

            if ($isEdit) {
                $rules[$index] = $newRule;
            } else {
                $rules[] = $newRule;
            }

            $settings['rules'] = $rules;
            headerMenuAdmin_saveSettings($settings);

            notice($isEdit ? t('Link updated successfully.') : t('Link added successfully.'));
            redirectBrowserToURL('?_pluginAction=headerMenuAdmin_dashboard');
            exit;
        }

        // Repopulate rule with POST data if validation failed
        $rule = [
            'type' => $type,
            'enabled' => isset($_POST['enabled']) ? 1 : 0,
            'match_field' => $matchField,
            'match_method' => $matchMethod,
            'match_pattern' => trim($_POST['match_pattern'] ?? ''),
            'add_menuName' => trim($_POST['add_menuName'] ?? ''),
            'add_link' => trim($_POST['add_link'] ?? ''),
            'add_visibility' => $visibility,
            'add_target' => $target,
            'add_position' => $position,
        ];
    }

    $adminUI = [];
    $adminUI['PAGE_TITLE'] = [
        t("Plugins") => '?menu=admin&action=plugins',
        t("Header Menu Admin") => '?_pluginAction=headerMenuAdmin_dashboard',
        $isEdit ? t("Edit Link") : t("Add New Link"),
    ];

    $adminUI['FORM'] = ['name' => 'ruleForm', 'autocomplete' => 'off'];
    $adminUI['HIDDEN_FIELDS'] = [
        ['name' => '_pluginAction', 'value' => 'headerMenuAdmin_editRule'],
        ['name' => 'save', 'value' => '1'],
    ];
    if ($isEdit) {
        $adminUI['HIDDEN_FIELDS'][] = ['name' => 'index', 'value' => $index];
    }
    
    $adminUI['BUTTONS'] = [
        ['name' => '_action=save', 'label' => t('Save Link')],
    ];

    $content = '';
    $content .= headerMenuAdmin_getPluginNav($isEdit ? 'dashboard' : 'add');

    // Cancel link
    $content .= '<p style="margin-bottom:15px"><a href="?_pluginAction=headerMenuAdmin_dashboard" class="btn btn-default"><i class="fa-duotone fa-solid fa-arrow-left" aria-hidden="true"></i> ' . t('Back to Dashboard') . '</a></p>';

    // Display validation errors
    if (!empty($GLOBALS['headerMenuAdmin_errors'])) {
        $content .= '<div class="alert alert-danger" role="alert">';
        $content .= '<strong>' . t('Please fix the following errors:') . '</strong>';
        $content .= '<ul style="margin-bottom:0">';
        foreach ($GLOBALS['headerMenuAdmin_errors'] as $error) {
            $content .= '<li>' . htmlencode($error) . '</li>';
        }
        $content .= '</ul></div>';
    }

    $content .= '<div class="separator"><div>' . t('Link Configuration') . '</div></div>';
    
    $content .= '<div class="form-horizontal">';
    
    // Enabled
    $content .= '<div class="form-group">';
    $content .= '<div class="col-sm-offset-2 col-sm-10"><div class="checkbox"><label for="field_enabled">';
    $checked = $rule['enabled'] ? ' checked' : '';
    $content .= '<input type="checkbox" name="enabled" id="field_enabled" value="1"' . $checked . '> ' . t('Link is active');
    $content .= '</label></div></div></div>';

    // Type
    $content .= '<div class="form-group">';
    $content .= '<label for="field_type" class="col-sm-2 control-label">' . t('Action Type') . '</label>';
    $content .= '<div class="col-sm-10">';
    $selRemove = ($rule['type'] === 'remove') ? ' selected' : '';
    $selAdd = ($rule['type'] === 'add') ? ' selected' : '';
    $content .= '<select name="type" id="field_type" class="form-control" onchange="toggleFields(this.value)">';
    $content .= '<option value="add"' . $selAdd . '>' . t('Add New Link') . '</option>';
    $content .= '<option value="remove"' . $selRemove . '>' . t('Remove Existing Link') . '</option>';
    $content .= '</select>';
    $content .= '</div></div>';
    
    // SECTION: REMOVE OPTIONS
    $removeDisplay = ($rule['type'] === 'remove') ? 'block' : 'none';
    $content .= '<div id="removeFields" style="display:' . $removeDisplay . '">';
    
    $content .= '<div class="form-group">';
    $content .= '<label for="field_match_field" class="col-sm-2 control-label">' . t('Match Field') . '</label>';
    $content .= '<div class="col-sm-10">';
    $selLink = ($rule['match_field'] === 'link') ? ' selected' : '';
    $selName = ($rule['match_field'] === 'menuName') ? ' selected' : '';
    $content .= '<select name="match_field" id="field_match_field" class="form-control">';
    $content .= '<option value="link"' . $selLink . '>' . t('Link URL (href)') . '</option>';
    $content .= '<option value="menuName"' . $selName . '>' . t('Menu Label (Text)') . '</option>';
    $content .= '</select>';
    $content .= '<p class="help-block">' . t('Choose whether to match against the link URL or the visible menu text.') . '</p>';
    $content .= '</div></div>';

    $content .= '<div class="form-group">';
    $content .= '<label for="field_match_method" class="col-sm-2 control-label">' . t('Match Method') . '</label>';
    $content .= '<div class="col-sm-10">';
    $selContains = ($rule['match_method'] === 'contains') ? ' selected' : '';
    $selExact = ($rule['match_method'] === 'exact') ? ' selected' : '';
    $selRegex = ($rule['match_method'] === 'regex') ? ' selected' : '';
    $content .= '<select name="match_method" id="field_match_method" class="form-control">';
    $content .= '<option value="contains"' . $selContains . '>' . t('Contains') . '</option>';
    $content .= '<option value="exact"' . $selExact . '>' . t('Exact Match') . '</option>';
    $content .= '<option value="regex"' . $selRegex . '>' . t('Regular Expression') . '</option>';
    $content .= '</select>';
    $content .= '</div></div>';

    $content .= '<div class="form-group">';
    $content .= '<label for="field_match_pattern" class="col-sm-2 control-label">' . t('Pattern/Text') . ' <span class="text-danger">*</span></label>';
    $content .= '<div class="col-sm-10">';
    $content .= '<input type="text" name="match_pattern" id="field_match_pattern" class="form-control" value="' . htmlencode($rule['match_pattern']) . '" aria-describedby="match_pattern_help">';
    $content .= '<p class="help-block" id="match_pattern_help">' . t('Text to search for. For regex, include delimiters, e.g. <code>/menu=license/</code>') . '</p>';
    $content .= '</div></div>';

    $content .= '</div>'; // end removeFields
    
    // SECTION: ADD OPTIONS
    $addDisplay = ($rule['type'] === 'add') ? 'block' : 'none';
    $content .= '<div id="addFields" style="display:' . $addDisplay . '">';
    
    $content .= '<div class="form-group">';
    $content .= '<label for="field_add_menuName" class="col-sm-2 control-label">' . t('Menu Label') . ' <span class="text-danger">*</span></label>';
    $content .= '<div class="col-sm-10">';
    $content .= '<input type="text" name="add_menuName" id="field_add_menuName" class="form-control" value="' . htmlencode($rule['add_menuName']) . '" aria-describedby="add_menuName_help">';
    $content .= '<p class="help-block" id="add_menuName_help">' . t('The text that will appear in the menu (e.g., "My Dashboard").') . '</p>';
    $content .= '</div></div>';

    $content .= '<div class="form-group">';
    $content .= '<label for="field_add_link" class="col-sm-2 control-label">' . t('Link URL') . ' <span class="text-danger">*</span></label>';
    $content .= '<div class="col-sm-10">';
    $content .= '<input type="text" name="add_link" id="field_add_link" class="form-control" value="' . htmlencode($rule['add_link']) . '" aria-describedby="add_link_help">';
    $content .= '<p class="help-block" id="add_link_help">' . t('URL to link to. Use relative URLs like <code>?menu=home</code> or full URLs like <code>https://example.com</code>.') . '</p>';
    $content .= '</div></div>';

    $content .= '<div class="form-group">';
    $content .= '<label for="field_add_visibility" class="col-sm-2 control-label">' . t('Visibility') . '</label>';
    $content .= '<div class="col-sm-10">';
    $visOpts = ['showAlways' => t('Always Show'), 'requireLogin' => t('Require Login'), 'requireAdmin' => t('Require Admin')];
    $content .= '<select name="add_visibility" id="field_add_visibility" class="form-control">';
    foreach ($visOpts as $val => $label) {
        $sel = ($rule['add_visibility'] === $val) ? ' selected' : '';
        $content .= '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
    }
    $content .= '</select>';
    $content .= '<p class="help-block">' . t('Control who can see this link.') . '</p>';
    $content .= '</div></div>';

    $content .= '<div class="form-group">';
    $content .= '<label for="field_add_target" class="col-sm-2 control-label">' . t('Target Window') . '</label>';
    $content .= '<div class="col-sm-10">';
    $targOpts = ['_self' => t('Same Window'), '_blank' => t('New Window')];
    $content .= '<select name="add_target" id="field_add_target" class="form-control">';
    foreach ($targOpts as $val => $label) {
        $sel = ($rule['add_target'] === $val) ? ' selected' : '';
        $content .= '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
    }
    $content .= '</select>';
    $content .= '</div></div>';

    $content .= '<div class="form-group">';
    $content .= '<label for="field_add_position" class="col-sm-2 control-label">' . t('Position') . '</label>';
    $content .= '<div class="col-sm-10">';
    $posOpts = ['end' => t('End of List'), 'start' => t('Start of List')];
    $content .= '<select name="add_position" id="field_add_position" class="form-control">';
    foreach ($posOpts as $val => $label) {
        $sel = ($rule['add_position'] === $val) ? ' selected' : '';
        $content .= '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
    }
    $content .= '</select>';
    $content .= '<p class="help-block">' . t('Where to place this link in the menu.') . '</p>';
    $content .= '</div></div>';
    
    $content .= '</div>'; // end addFields
    
    $content .= '</div>'; // end form-horizontal
    
    $content .= '<script>
    function toggleFields(val) {
        if (val === "remove") {
            document.getElementById("removeFields").style.display = "block";
            document.getElementById("addFields").style.display = "none";
        } else {
            document.getElementById("removeFields").style.display = "none";
            document.getElementById("addFields").style.display = "block";
        }
    }
    </script>';

    $adminUI['CONTENT'] = $content;
    adminUI($adminUI);
}

/**
 * Delete Rule
 */
function headerMenuAdmin_deleteRule(): void
{
    security_dieOnInvalidCsrfToken();

    $index = isset($_REQUEST['index']) ? (int)$_REQUEST['index'] : null;
    $settings = headerMenuAdmin_loadSettings();
    $rules = $settings['rules'] ?? [];

    if ($index !== null && isset($rules[$index])) {
        unset($rules[$index]);
        $settings['rules'] = array_values($rules); // re-index
        headerMenuAdmin_saveSettings($settings);
        notice(t('Link deleted successfully.'));
    }

    redirectBrowserToURL('?_pluginAction=headerMenuAdmin_dashboard');
    exit;
}

/**
 * Help Page
 */
function headerMenuAdmin_help(): void
{
    $adminUI = [];
    $adminUI['PAGE_TITLE'] = [
        t("Plugins") => '?menu=admin&action=plugins',
        t("Header Menu Admin") => '?_pluginAction=headerMenuAdmin_dashboard',
        t("Help"),
    ];

    $content = '';
    $content .= headerMenuAdmin_getPluginNav('help');

    $content .= '<div class="separator"><div>' . t('Overview') . '</div></div>';
    $content .= '<p>' . t('Header Menu Admin allows you to customize the admin header menu (My Account, Logoff, Help, License, etc.) without writing any code.') . '</p>';
    $content .= '<p>' . t('You can add custom links, remove existing links, and control visibility based on user roles.') . '</p>';

    $content .= '<div class="separator" style="margin-top:20px"><div>' . t('Features') . '</div></div>';
    $content .= '<ul>';
    $content .= '<li><strong>' . t('Add Custom Links') . ':</strong> ' . t('Add new menu items with custom URLs and labels.') . '</li>';
    $content .= '<li><strong>' . t('Remove Existing Links') . ':</strong> ' . t('Hide standard links like "License", "Help", or "Logoff" by matching their URL or text.') . '</li>';
    $content .= '<li><strong>' . t('Flexible Matching') . ':</strong> ' . t('Match links by URL or menu text using contains, exact match, or regular expressions.') . '</li>';
    $content .= '<li><strong>' . t('Visibility Control') . ':</strong> ' . t('Show links to all users, logged-in users only, or admins only.') . '</li>';
    $content .= '<li><strong>' . t('Target Window') . ':</strong> ' . t('Open links in the same window or a new tab.') . '</li>';
    $content .= '<li><strong>' . t('Position Control') . ':</strong> ' . t('Add links at the start or end of the menu.') . '</li>';
    $content .= '<li><strong>' . t('Enable/Disable Toggle') . ':</strong> ' . t('Quickly enable or disable individual rules.') . '</li>';
    $content .= '</ul>';

    $content .= '<div class="separator" style="margin-top:20px"><div>' . t('Adding Links') . '</div></div>';
    $content .= '<p>' . t('When adding a link, you specify:') . '</p>';
    $content .= '<table class="table table-bordered table-condensed">';
    $content .= '<tr><th style="width:150px">' . t('Setting') . '</th><th>' . t('Description') . '</th></tr>';
    $content .= '<tr><td>' . t('Menu Label') . '</td><td>' . t('The text displayed in the menu') . '</td></tr>';
    $content .= '<tr><td>' . t('Link URL') . '</td><td>' . t('The destination URL (relative or absolute). Examples:') . '<br><code>?menu=home</code><br><code>?_pluginAction=MyPlugin\\myAction</code><br><code>https://example.com</code></td></tr>';
    $content .= '<tr><td>' . t('Visibility') . '</td><td>' . t('Who can see the link (Everyone, Logged In, Admins)') . '</td></tr>';
    $content .= '<tr><td>' . t('Target Window') . '</td><td>' . t('Same window or new tab') . '</td></tr>';
    $content .= '<tr><td>' . t('Position') . '</td><td>' . t('Start or end of the menu') . '</td></tr>';
    $content .= '</table>';

    $content .= '<div class="separator" style="margin-top:20px"><div>' . t('Removing Links') . '</div></div>';
    $content .= '<p>' . t('When removing a link, you specify what to match:') . '</p>';
    $content .= '<table class="table table-bordered table-condensed">';
    $content .= '<tr><th style="width:150px">' . t('Setting') . '</th><th>' . t('Description') . '</th></tr>';
    $content .= '<tr><td>' . t('Match Field') . '</td><td>' . t('Match against the link URL or the visible menu text') . '</td></tr>';
    $content .= '<tr><td>' . t('Match Method') . '</td><td>' . t('How to match: Contains, Exact Match, or Regular Expression') . '</td></tr>';
    $content .= '<tr><td>' . t('Pattern') . '</td><td>' . t('The text or pattern to match') . '</td></tr>';
    $content .= '</table>';

    $content .= '<div class="separator" style="margin-top:20px"><div>' . t('Matching Methods') . '</div></div>';
    $content .= '<dl class="dl-horizontal">';
    $content .= '<dt>' . t('Contains') . '</dt><dd>' . t('Simple substring match (case-insensitive). If the pattern appears anywhere in the field, it matches.') . '<br><em>' . t('Example:') . '</em> <code>license</code> ' . t('matches') . ' <code>?menu=license</code></dd>';
    $content .= '<dt>' . t('Exact Match') . '</dt><dd>' . t('The field must match the pattern exactly (case-sensitive).') . '<br><em>' . t('Example:') . '</em> <code>Logoff</code> ' . t('matches only') . ' <code>Logoff</code>, ' . t('not') . ' <code>logoff</code></dd>';
    $content .= '<dt>' . t('Regular Expression') . '</dt><dd>' . t('Advanced matching using PHP PCRE syntax. Include delimiters.') . '<br><em>' . t('Examples:') . '</em><br><code>/menu=license/</code> - ' . t('matches URLs containing "menu=license"') . '<br><code>/^Logoff$/i</code> - ' . t('matches "Logoff" exactly (case-insensitive)') . '<br><code>/^(Help|License)$/</code> - ' . t('matches "Help" or "License"') . '</dd>';
    $content .= '</dl>';

    $content .= '<div class="separator" style="margin-top:20px"><div>' . t('Common Patterns') . '</div></div>';
    $content .= '<p>' . t('Here are some common patterns for removing standard menu items:') . '</p>';
    $content .= '<table class="table table-bordered table-condensed">';
    $content .= '<tr><th>' . t('To Remove') . '</th><th>' . t('Match Field') . '</th><th>' . t('Pattern') . '</th></tr>';
    $content .= '<tr><td>' . t('License link') . '</td><td>' . t('Link URL') . '</td><td><code>menu=license</code></td></tr>';
    $content .= '<tr><td>' . t('Help link') . '</td><td>' . t('Link URL') . '</td><td><code>menu=help</code></td></tr>';
    $content .= '<tr><td>' . t('Logoff link') . '</td><td>' . t('Menu Label') . '</td><td><code>Logoff</code></td></tr>';
    $content .= '</table>';

    $content .= '<div class="separator" style="margin-top:20px"><div>' . t('Troubleshooting') . '</div></div>';
    $content .= '<dl class="dl-horizontal">';
    $content .= '<dt>' . t('Links not changing') . '</dt><dd>' . t('Ensure the rule is enabled (toggle is on). Check that your pattern matches correctly. Clear your browser cache.') . '</dd>';
    $content .= '<dt>' . t('Regex not matching') . '</dt><dd>' . t('Include delimiters in your pattern (e.g., <code>/pattern/</code>). Use <code>i</code> flag for case-insensitive matching.') . '</dd>';
    $content .= '<dt>' . t('Settings not saving') . '</dt><dd>' . t('Check file permissions on the plugin directory. Ensure PHP can write to the plugin folder.') . '</dd>';
    $content .= '</dl>';

    $content .= '<div class="separator" style="margin-top:20px"><div>' . t('Support') . '</div></div>';
    $content .= '<p>' . t('For CMS Builder support, visit the') . ' <a href="https://www.interactivetools.com/forum/" target="_blank">' . t('Interactive Tools Forum') . '</a>.</p>';
    $content .= '<p>' . t('Plugin Version:') . ' ' . ($GLOBALS['HEADERMENUADMIN_VERSION'] ?? '1.00') . '</p>';

    $adminUI['CONTENT'] = $content;
    adminUI($adminUI);
}
