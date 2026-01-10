# Header Menu Admin Plugin for CMS Builder

> **Note:** This plugin only works with CMS Builder, available for download at https://www.interactivetools.com/download/

Customize the CMS Builder admin header menu (My Account, Logoff, Help, License, etc.) without writing any code. Add custom links, remove existing links, and control visibility based on user roles.

## Features

- **Admin Interface** - Configure everything through the CMS admin, no code editing required
- **Add Custom Links** - Add new menu items with custom URLs and labels
- **Remove Existing Links** - Hide standard menu items like License, Help, or Logoff
- **Flexible Matching** - Match links by URL or menu text using contains, exact match, or regex
- **Visibility Control** - Show links to all users, logged-in users only, or admins only
- **Target Window** - Open links in the same window or a new tab
- **Position Control** - Add links at the start or end of the menu
- **Enable/Disable Toggle** - Quickly enable or disable individual rules
- **Help Documentation** - Built-in help page with usage instructions

## Requirements

- CMS Builder v3.65 or higher
- PHP 8.0 or higher

## Installation

1. Download and extract the plugin to your CMS Builder plugins directory:
   ```
   /path/to/cmsb/plugins/headerMenuAdmin/
   ```

2. Copy the menu schema file to enable the admin menu entry:
   ```bash
   cp headermenuadmin_menu.schema.php /path/to/cmsb/data/schema/headermenuadmin.schema.php
   ```

3. Log into CMS Builder admin and navigate to **Header Menu Admin** in the sidebar

4. Start adding or removing menu links

## How It Works

This plugin uses CMS Builder's `menulinks_myAccount` filter hook to modify the header menu array before it's rendered. Rules are processed in order, and each rule can either:

- **Add** a new link to the menu
- **Remove** an existing link based on pattern matching

### Adding Links

When adding a link, you specify:

| Setting | Description |
|---------|-------------|
| Menu Label | The text displayed in the menu |
| Link URL | The destination URL (relative or absolute) |
| Visibility | Who can see the link (Everyone, Logged In, Admins) |
| Target Window | Same window or new tab |
| Position | Start or end of the menu |

### Removing Links

When removing a link, you specify:

| Setting | Description |
|---------|-------------|
| Match Field | Match against the URL or menu text |
| Match Method | Contains, Exact Match, or Regular Expression |
| Pattern | The text or pattern to match |

## Use Cases

### Add a Link to Your Dashboard

1. Click "Add New Link"
2. Set Menu Label to "My Dashboard"
3. Set Link URL to `?menu=home`
4. Set Visibility to "Always Show"
5. Save

### Add a Link to a Plugin Action

1. Click "Add New Link"
2. Set Menu Label to "MySQL Console"
3. Set Link URL to `?_pluginAction=DeveloperConsole\mysqlConsole`
4. Set Target to "New Window"
5. Save

### Hide the License Link

1. Click "Add New Link"
2. Change Action Type to "Remove Existing Link"
3. Set Match Field to "Link URL (href)"
4. Set Match Method to "Contains"
5. Set Pattern to `menu=license`
6. Save

### Hide the Help Link for Non-Admins

This requires creating the link with admin-only visibility:

1. First, remove the existing Help link (as above, match `menu=help`)
2. Then add it back with "Require Admin" visibility

## File Structure

```
headerMenuAdmin/
├── headerMenuAdmin.php           # Main plugin file
├── headerMenuAdmin_admin.php     # Admin interface pages
├── headerMenuAdmin_functions.php # Helper functions
├── headerMenuAdmin_settings.json # Settings (auto-created)
├── headermenuadmin_menu.schema.php # Menu entry schema
├── reset_installation.php        # Reset tool
├── LICENSE                       # MIT License
├── CHANGELOG.md                  # Version history
├── README.md                     # This file
└── QUICK_START.md                # Quick installation guide
```

## Troubleshooting

### Links not appearing/disappearing

1. Ensure the rule is enabled (toggle is on)
2. Check that your pattern matches correctly
3. For regex, ensure proper delimiter format (e.g., `/pattern/`)
4. Clear your browser cache

### Plugin not appearing in admin menu

1. Ensure the menu schema file was copied to `/data/schema/headermenuadmin.schema.php`
2. Clear CMS Builder's cache
3. Log out and log back in

### Settings not saving

1. Check file permissions on the plugin directory
2. Ensure PHP can write to the plugin folder
3. Try running the reset tool: `php reset_installation.php`

### Regex not matching

1. Include delimiters in your pattern (e.g., `/menu=license/`)
2. Use `i` flag for case-insensitive matching (e.g., `/logoff/i`)
3. Test your pattern at [regex101.com](https://regex101.com) with PHP/PCRE flavor

## Reset Installation

If you need to reset the plugin to default settings:

```bash
cd /path/to/cmsb/plugins/headerMenuAdmin/
php reset_installation.php
```

This will backup your current settings and create a fresh settings file.

## Support

For CMS Builder support, visit the [Interactive Tools Forum](https://www.interactivetools.com/forum/).

## License

This plugin is released under the MIT License. See the [LICENSE](LICENSE) file for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and changes.

## Credits

- **Author:** Sagentic Web Design
- **Website:** [https://www.sagentic.com](https://www.sagentic.com)
- **CMS Builder:** [Interactive Tools](https://www.interactivetools.com)
