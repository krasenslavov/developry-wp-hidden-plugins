<!-- @format -->

# Developry — WP Hidden Plugins

Hide and disable unused WordPress plugins for later use without deleting them.

## Description

Developry WP Hidden Plugins allows you to temporarily hide inactive WordPress plugins by renaming their directories with a dot prefix, making them invisible to WordPress without permanently deleting them. This is useful for:

- Keeping plugins you might need later without cluttering your plugins list
- Testing plugin conflicts by temporarily hiding suspected plugins
- Maintaining a clean plugins list in production while keeping backup plugins available
- Managing large plugin collections more efficiently

## Features

- **Bulk Hide/Show Actions**: Hide or show multiple plugins at once using WordPress bulk actions
- **Hidden Status Filter**: View all hidden plugins in a separate filtered view
- **Security Features**:
  - Capability checks ensure only administrators can hide/show plugins
  - Optional password protection for additional security
  - Prevents hiding of active plugins and itself
- **Error Handling**: Comprehensive error logging and validation
- **Internationalization**: Full i18n support for translations
- **WordPress Standards**: Follows WordPress coding standards and best practices

## Installation

### From GitHub

1. Download the latest release from GitHub
2. Extract the zip file
3. Upload the `developry-wp-hidden-plugins` folder to `/wp-content/plugins/`
4. Activate the plugin through the 'Plugins' menu in WordPress

### Manual Installation

1. Clone this repository:
   ```bash
   git clone https://github.com/krasenslavov/developry-wp-hidden-plugins.git
   ```
2. Navigate to your WordPress plugins directory
3. Copy the plugin folder to `/wp-content/plugins/`
4. Activate the plugin through the 'Plugins' menu in WordPress

## Usage

### Hiding Plugins

1. Go to **Plugins** in your WordPress admin
2. Select the inactive plugins you want to hide
3. Choose **Make Hidden** from the bulk actions dropdown
4. Click **Apply**
5. The selected plugins will be hidden and moved to the "Hidden" status

**Note**: You can only hide inactive plugins. Active plugins and the Hidden Plugins plugin itself cannot be hidden.

### Showing Plugins

1. Go to **Plugins** in your WordPress admin
2. Click on **Hidden** status filter to view hidden plugins
3. Select the plugins you want to make visible again
4. Choose **Make Visible** from the bulk actions dropdown
5. Click **Apply**
6. The plugins will be restored and visible in the main plugins list

### Password Protection (Optional)

For additional security, you can set up password protection:

1. Go to **Settings > Hidden Plugins**
2. Enter a password in the "Admin Password" field
3. Click **Save Settings**

When a password is set, only administrators with the `manage_options` capability can hide or show plugins.

To remove password protection, leave the password field blank and save.

## How It Works

The plugin works by renaming plugin directories:

- **Hiding**: Renames `plugin-name/` to `.plugin-name/` (adds a dot prefix)
- **Showing**: Renames `.plugin-name/` back to `plugin-name/` (removes the dot prefix)

This makes plugins invisible to WordPress without deleting any files. The plugin keeps track of hidden plugins in the WordPress options table.

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- User must have `activate_plugins` capability (Administrator role)

## Limitations

- Cannot hide active plugins
- Cannot hide itself
- Only works with plugins in their own directories (single-file plugins are skipped)
- File system must allow renaming directories

## Development

### Running Tests

This plugin includes PHPUnit tests. To run them:

1. Set up the WordPress test environment:

   ```bash
   bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
   ```

2. Run PHPUnit:
   ```bash
   phpunit
   ```

Or set the `WP_TESTS_DIR` environment variable:

```bash
export WP_TESTS_DIR=/path/to/wordpress-tests-lib
phpunit
```

### Filters

The plugin provides filters for customization:

#### `dwphp_restore_on_deactivation`

Controls whether hidden plugins are restored when the plugin is deactivated.

```php
add_filter( 'dwphp_restore_on_deactivation', '__return_true' );
```

Default: `false`

## Security

- Capability checks ensure only administrators can hide/show plugins
- Active plugins cannot be hidden
- The plugin itself cannot be hidden
- All input is sanitized and validated
- All output is properly escaped
- Error logging for troubleshooting

## Support

For bugs, feature requests, or contributions, please visit:

- **GitHub**: https://github.com/krasenslavov/developry-wp-hidden-plugins
- **Issues**: https://github.com/krasenslavov/developry-wp-hidden-plugins/issues

## Author

**Krasen Slavov**

- Website: https://krasenslavov.com/
- Company: https://developry.com/

## License

This plugin is licensed under the GPL v2 or later.

```
Copyright 2018 - 2025 Developry Ltd.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Changelog

### 1.0.0

- Complete rewrite with security improvements
- Added settings page for password configuration
- Implemented proper error handling and logging
- Added PHPUnit tests
- Added internationalization support
- Added activation/deactivation hooks
- Improved code structure and WordPress standards compliance
- Added comprehensive documentation

### 0.1.0

- Initial release
- Basic hide/show functionality
