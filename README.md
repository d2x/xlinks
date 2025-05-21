# xlinks WordPress Plugin

**xlinks** is a WordPress plugin that enables automatic insertion of deep links into your website's content. It provides a user-friendly interface to manage deep links and customize where and how they appear, enhancing internal linking and improving user navigation.

- **Author**: d2x
- **License**: MIT License

## Features

- **Automatic Deep Linking**: Automatically inserts links for specified keywords or phrases within your content.
- **Customizable Settings**:
    - Choose which HTML elements (e.g., `p`, `div`, `li`) to apply links to.
    - Select content types (e.g., posts, pages) where links should appear.
    - Exclude specific pages or CSS selectors (e.g., `.class`, `#id`) from link insertion.
- **Deep Link Management**:
    - Add, edit, or remove deep links via an intuitive admin interface.
    - Specify link text, destination (post/page), and whether links apply to posts, pages, or both.
- **Performance Optimized**: Limits link insertions to avoid over-linking (max 2 links per destination per page).
- **AJAX-Powered Interface**: Dynamically loads destination options for deep links based on selected content types.
- **Safe and Secure**: Includes nonce verification and sanitization for all inputs.

## Installation

1. **Download the Plugin**:
    - Download the `xlinks` plugin as a `.zip` file from the [GitHub repository](https://github.com/d2x/xlinks).

2. **Install the Plugin**:
    - In your WordPress admin dashboard, navigate to **Plugins > Add New**.
    - Click **Upload Plugin** and select the downloaded `.zip` file.
    - Click **Install Now** and then **Activate** the plugin.

3. **Alternative Manual Installation**:
    - Unzip the plugin and upload the `xlinks` folder to the `/wp-content/plugins/` directory.
    - Activate the plugin through the **Plugins** menu in WordPress.

## Usage

Once activated, the plugin adds a new menu item, **xlinks**, to the WordPress admin dashboard with two submenus: **Deep Links** and **Settings**.

### 1. Deep Links
- Navigate to **xlinks > Deep Links** to manage your deep links.
- **Add a Deep Link**:
    - Click **Add Row** to create a new deep link.
    - Enter the **Link Text** (the keyword/phrase to link).
    - Select the **Destination Type** (e.g., Post, Page).
    - Choose the **Destination** (specific post/page) from the dropdown.
    - Check **Enable on Pages** and/or **Enable on Posts** to control where the link appears.
- **Remove a Deep Link**: Click the **Remove** button next to the row.
- **Save Changes**: Click **Save Changes** to apply your deep links.

### 2. Settings
- Navigate to **xlinks > Settings** to configure how links are applied.
- **Enabled HTML Elements**: Check the HTML tags (e.g., `p`, `div`, `span`) where links can be inserted.
- **Enabled Content Types**: Select the content types (e.g., Posts, Pages) where links should appear.
- **Exclusions**:
    - **CSS Selectors**: Enter CSS selectors (e.g., `.no-links`, `#sidebar`) to exclude from link insertion, one per line.
    - **Excluded Pages**: Move pages between **Available Pages** and **Excluded Pages** using the arrow buttons to prevent linking on specific pages.
- **Save Settings**: Click **Save Settings** to apply your configurations.

### How It Works
- The plugin scans content on singular pages (posts or pages) and inserts links for matching keywords/phrases based on your deep link settings.
- Links are only added to enabled HTML elements and content types, respecting exclusions.
- Each destination is linked a maximum of 2 times per page to prevent over-linking.
- The plugin uses the WordPress `the_content` filter to process content safely and efficiently.

## Requirements

- **WordPress**: Version 5.0 or higher
- **PHP**: Version 7.4 or higher
- **Permissions**: User must have `manage_options` capability to access the plugin's settings.

## Development

The plugin is open-source and welcomes contributions. To contribute:

1. Fork the repository on [GitHub](https://github.com/d2x/xlinks).
2. Create a new branch for your feature or bug fix.
3. Submit a pull request with a clear description of your changes.

### Code Overview
- **Main File**: `xlinks.php`
- **Key Functions**:
    - `xlinks_admin_menu()`: Adds the admin menu and submenus.
    - `xlinks_settings_page()`: Renders the settings page for configuring link insertion.
    - `xlinks_deep_links_page()`: Manages the deep links interface.
    - `xlinks_filter_content()`: Filters content to insert deep links.
    - `xlinks_get_posts()`: AJAX handler for populating destination dropdowns.
- **Technologies**:
    - PHP for server-side logic.
    - jQuery for dynamic admin interface (e.g., adding/removing rows, AJAX requests).
    - DOMDocument and DOMXPath for parsing and modifying content.

## Support

For issues, feature requests, or questions, please:
- Open an issue on the [GitHub repository](https://github.com/d2x/xlinks).