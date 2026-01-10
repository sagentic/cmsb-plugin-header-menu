# Quick Start Guide - Header Menu Admin

> **Note:** This plugin only works with CMS Builder, available for download at https://www.interactivetools.com/download/

## 1. Install the Plugin

Copy the `headerMenuAdmin` folder to your CMS Builder plugins directory:

```
/path/to/cmsb/plugins/headerMenuAdmin/
```

## 2. Enable the Menu Entry

Copy the schema file to enable the sidebar menu:

```bash
cp headermenuadmin_menu.schema.php /path/to/cmsb/data/schema/headermenuadmin.schema.php
```

## 3. Access the Plugin

1. Log into CMS Builder admin
2. Find **Header Menu Admin** in the sidebar
3. Click to open the dashboard

## 4. Add Your First Link

1. Click **Add New Link**
2. Enter a Menu Label (e.g., "My Reports")
3. Enter a Link URL (e.g., `?menu=reports` or `https://example.com`)
4. Choose visibility settings
5. Click **Save Link**

## 5. Remove an Existing Link

1. Click **Add New Link**
2. Change Action Type to **Remove Existing Link**
3. Choose Match Field (URL or Text)
4. Choose Match Method (Contains, Exact, or Regex)
5. Enter the pattern to match
6. Click **Save Link**

## Common Patterns

| To Remove | Pattern |
|-----------|---------|
| License link | `menu=license` |
| Help link | `menu=help` |
| Logoff link | `menu=logoff` |

## Need Help?

- See the **Help** tab in the plugin for detailed instructions
- Read the full [README.md](README.md) for advanced usage
- Visit the [Interactive Tools Forum](https://www.interactivetools.com/forum/) for support
