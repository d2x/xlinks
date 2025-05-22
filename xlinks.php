<?php
/**
 * Plugin Name: xlinks
 * Description: A plugin to insert deep links into WordPress content with customizable settings.
 * Version: 1.1.3
 * Author: d2x
 * Author URI: https://github.com/d2x/xlinks
 */

// Add menu and submenus
function xlinks_admin_menu() {
    add_menu_page('xlinks', 'xlinks', 'manage_options', 'xlinks', 'xlinks_deep_links_page', 'dashicons-admin-links');
    add_submenu_page('xlinks', 'Deep Links', 'Deep Links', 'manage_options', 'xlinks', 'xlinks_deep_links_page');
    add_submenu_page('xlinks', 'Settings', 'Settings', 'manage_options', 'xlinks-settings', 'xlinks_settings_page');
}
add_action('admin_menu', 'xlinks_admin_menu');

// Settings page
function xlinks_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = get_option('xlinks_settings', array());

    if (isset($_POST['xlinks_settings_save'])) {
        check_admin_referer('xlinks_settings_save');
        $settings['enabled_elements'] = isset($_POST['enabled_elements']) ? array_map('sanitize_text_field', $_POST['enabled_elements']) : array();
        $settings['enabled_content_types'] = isset($_POST['enabled_content_types']) ? array_map('sanitize_text_field', $_POST['enabled_content_types']) : array();
        $settings['excluded_selectors'] = isset($_POST['excluded_selectors']) ? array_map('trim', explode("\n", sanitize_textarea_field($_POST['excluded_selectors']))) : array();
        $settings['excluded_pages'] = isset($_POST['excluded_pages']) ? array_map('intval', $_POST['excluded_pages']) : array();
        update_option('xlinks_settings', $settings);
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $html_tags = array('p', 'div', 'span', 'li', 'td', 'th', 'article', 'section', 'aside', 'header', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6');
    $post_types = get_post_types(array('public' => true), 'objects');
    $pages = get_pages();
    ?>
    <div class="wrap">
        <h1>xlinks Settings</h1>
        <p><a href="#elements">Elements</a> | <a href="#content-types">Content Types</a> | <a href="#exclusions">Exclusions</a></p>
        <form method="post">
            <?php wp_nonce_field('xlinks_settings_save'); ?>
            <h2 id="elements">Enabled HTML Elements</h2>
            <?php
            $enabled_elements = $settings['enabled_elements'] ?? array();
            foreach ($html_tags as $tag) {
                $checked = in_array($tag, $enabled_elements) ? 'checked' : '';
                echo "<label><input type='checkbox' name='enabled_elements[]' value='$tag' $checked> $tag</label><br>";
            }
            ?>
            <h2 id="content-types">Enabled Content Types</h2>
            <?php
            $enabled_content_types = $settings['enabled_content_types'] ?? array();
            foreach ($post_types as $post_type) {
                $checked = in_array($post_type->name, $enabled_content_types) ? 'checked' : '';
                echo "<label><input type='checkbox' name='enabled_content_types[]' value='{$post_type->name}' $checked> {$post_type->label}</label><br>";
            }
            ?>
            <h2 id="exclusions">Exclusions</h2>
            <p>CSS Selectors (.class / #id, one per line):</p>
            <textarea name="excluded_selectors" rows="5" cols="48"><?php echo esc_textarea(implode("\n", $settings['excluded_selectors'] ?? array())); ?></textarea>
            <div style="display: flex; align-items: center;">
                <div>
                    <p>Available Pages</p>
                    <select id="available-pages" multiple size="10" style="width: 200px;">
                        <?php
                        $all_pages = get_pages();
                        $excluded_pages = $settings['excluded_pages'] ?? array();
                        foreach ($all_pages as $page) {
                            if (!in_array($page->ID, $excluded_pages)) {
                                echo "<option value='{$page->ID}'>{$page->post_title}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <div style="margin: 0 10px;">
                        <button type="button" id="add-to-excluded" class="button">→</button><br><br>
                        <button type="button" id="remove-from-excluded" class="button">←</button>
                    </div>
                </div>
                <div>
                    <p>Excluded Pages</p>
                    <select id="excluded-pages" name="excluded_pages[]" multiple size="10" style="width: 200px;">
                        <?php
                        foreach ($all_pages as $page) {
                            if (in_array($page->ID, $excluded_pages)) {
                                echo "<option value='{$page->ID}'>{$page->post_title}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <p><input type="submit" name="xlinks_settings_save" value="Save Settings" class="button-primary"></p>
        </form>
        <script>
            jQuery(document).ready(function($) {
                // Function to sort a select box alphabetically by option text
                function sortSelectBox(selectId) {
                    $(selectId + ' option').sort(function(a, b) {
                        return $(a).text().localeCompare($(b).text());
                    }).appendTo(selectId);
                }
            
                // Sort both select boxes when the page loads
                sortSelectBox('#available-pages');
                sortSelectBox('#excluded-pages');
            
                // Move selected pages from "Available" to "Excluded" and sort "Excluded Pages"
                $('#add-to-excluded').click(function() {
                    $('#available-pages option:selected').each(function() {
                        $(this).appendTo('#excluded-pages');
                    });
                    sortSelectBox('#excluded-pages');
                });
            
                // Move selected pages from "Excluded" back to "Available" and sort "Available Pages"
                $('#remove-from-excluded').click(function() {
                    $('#excluded-pages option:selected').each(function() {
                        $(this).appendTo('#available-pages');
                    });
                    sortSelectBox('#available-pages');
                });
            
                // Ensure all "Excluded Pages" are submitted with the form
                $('form').submit(function() {
                    $('#excluded-pages option').prop('selected', true);
                });
            });
        </script>
    </div>
    <?php
}

// Deep links page
function xlinks_deep_links_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = get_option('xlinks_settings', array());
    $enabled_content_types = $settings['enabled_content_types'] ?? array('page', 'post');

    if (isset($_POST['xlinks_save'])) {
        check_admin_referer('xlinks_save');
        $deep_links = array();
        if (isset($_POST['deep_link'])) {
            foreach ($_POST['deep_link'] as $index => $data) {
                $link_text = sanitize_text_field($data['link_text']);
                $destination_type = sanitize_text_field($data['destination_type']);
                $destination_id = intval($data['destination_id']);
                $enable_page = isset($data['enable_page']) ? 1 : 0;
                $enable_post = isset($data['enable_post']) ? 1 : 0;
                if (!empty($link_text) && !empty($destination_type) && $destination_id > 0) {
                    $deep_links[] = array(
                        'link_text' => $link_text,
                        'destination_type' => $destination_type,
                        'destination_id' => $destination_id,
                        'enable_page' => $enable_page,
                        'enable_post' => $enable_post,
                    );
                }
            }
        }
        update_option('xlinks_deep_links', $deep_links);
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $deep_links = get_option('xlinks_deep_links', array());
    $post_types = get_post_types(array('public' => true), 'objects');
    $filtered_post_types = array_filter($post_types, function($post_type) use ($enabled_content_types) {
        return in_array($post_type->name, $enabled_content_types);
    });
    ?>
    <div class="wrap">
        <h1>Deep Links</h1>
        <form method="post">
            <?php wp_nonce_field('xlinks_save'); ?>
            <table id="deep-links-table" class="widefat">
                <thead>
                    <tr>
                        <th>Link Text</th>
                        <th>Destination Type</th>
                        <th>Destination</th>
                        <th>Rewrite Pages</th>
                        <th>Rewrite Posts</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deep_links as $index => $deep_link) { ?>
                        <tr>
                            <td><input type="text" name="deep_link[<?php echo $index; ?>][link_text]" value="<?php echo esc_attr($deep_link['link_text']); ?>" class="regular-text"></td>
                            <td>
                                <select name="deep_link[<?php echo $index; ?>][destination_type]" class="destination-type">
                                    <?php
                                    $selected_type = $deep_link['destination_type'];
                                    $options = array();

                                    // Add disabled selected type (if applicable)
                                    if (!in_array($selected_type, $enabled_content_types)) {
                                        $post_type_object = get_post_type_object($selected_type);
                                        if ($post_type_object) {
                                            $options[] = array(
                                                'value' => $selected_type,
                                                'label' => $post_type_object->label . ' (disabled)',
                                                'selected' => true,
                                                'disabled' => true,
                                            );
                                        }
                                    }

                                    // Add enabled content types
                                    foreach ($filtered_post_types as $post_type) {
                                        $options[] = array(
                                            'value' => $post_type->name,
                                            'label' => $post_type->label,
                                            'selected' => ($selected_type === $post_type->name),
                                            'disabled' => false,
                                        );
                                    }

                                    // Sort options by label
                                    usort($options, function($a, $b) {
                                        return strnatcasecmp($a['label'], $b['label']);
                                    });

                                    // Output sorted options
                                    foreach ($options as $option) {
                                        echo '<option value="' . esc_attr($option['value']) . '" ' . ($option['selected'] ? 'selected' : '') . '>' . esc_html($option['label']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                            <td>
                                <select name="deep_link[<?php echo $index; ?>][destination_id]" class="destination-select" data-selected="<?php echo $deep_link['destination_id']; ?>"></select>
                            </td>
                            <td><input type="checkbox" name="deep_link[<?php echo $index; ?>][enable_page]" <?php checked($deep_link['enable_page'], 1); ?>></td>
                            <td><input type="checkbox" name="deep_link[<?php echo $index; ?>][enable_post]" <?php checked($deep_link['enable_post'], 1); ?>></td>
                            <td><button type="button" class="button remove-row">Remove</button></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <p><button type="button" id="add-row" class="button">Add Row</button></p>
            <p><input type="submit" name="xlinks_save" value="Save Changes" class="button-primary"></p>
        </form>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('#add-row').click(function() {
            var index = $('#deep-links-table tbody tr').length;
            var row = '<tr>' +
                '<td><input type="text" name="deep_link[' + index + '][link_text]" class="regular-text"></td>' +
                '<td><select name="deep_link[' + index + '][destination_type]" class="destination-type">' +
                '<?php foreach ($filtered_post_types as $post_type) { echo '<option value="' . $post_type->name . '">' . $post_type->label . '</option>'; } ?>' +
                '</select></td>' +
                '<td><select name="deep_link[' + index + '][destination_id]" class="destination-select"></select></td>' +
                '<td><input type="checkbox" name="deep_link[' + index + '][enable_page]"></td>' +
                '<td><input type="checkbox" name="deep_link[' + index + '][enable_post]"></td>' +
                '<td><button type="button" class="button remove-row">Remove</button></td>' +
                '</tr>';
            $('#deep-links-table tbody').append(row);
            $('#deep-links-table tbody tr:last .destination-type').trigger('change');
        });

        $(document).on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
        });

        $(document).on('change', '.destination-type', function() {
            var select = $(this).closest('tr').find('.destination-select');
            var post_type = $(this).val();
            select.empty();
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'xlinks_get_posts',
                    post_type: post_type
                },
                success: function(data) {
                    select.html(data);
                    var selected = select.data('selected');
                    if (selected) {
                        select.val(selected);
                    }
                }
            });
        });

        $('.destination-type').each(function() {
            $(this).trigger('change');
        });
    });
    </script>
    <?php
}

// AJAX handler for destination dropdown
function xlinks_get_posts() {
    $post_type = sanitize_text_field($_GET['post_type']);
    $posts = get_posts(array(
        'post_type' => $post_type,
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'publish',
    ));
    $tree = array();
    foreach ($posts as $post) {
        $parent_id = $post->post_parent ? $post->post_parent : 0;
        $tree[$parent_id][] = $post;
    }
    echo generate_options($tree);
    wp_die();
}

function generate_options($tree, $parent = 0, $depth = 0) {
    $options = '';
    if (isset($tree[$parent])) {
        foreach ($tree[$parent] as $post) {
            $prefix = str_repeat(' - ', $depth);
            $options .= '<option value="' . $post->ID . '">' . $prefix . esc_html($post->post_title) . '</option>';
            $options .= generate_options($tree, $post->ID, $depth + 1);
        }
    }
    return $options;
}
add_action('wp_ajax_xlinks_get_posts', 'xlinks_get_posts');

// Content filter
function xlinks_filter_content($content) {
    global $post;

    if (!is_singular()) {
        return $content;
    }
    $post_type = get_post_type($post);
    if ($post_type != 'page' && $post_type != 'post') {
        return $content;
    }

    $settings = get_option('xlinks_settings', array());
    $excluded_pages = $settings['excluded_pages'] ?? array();
    if (in_array($post->ID, $excluded_pages)) {
        return $content;
    }

    $deep_links = get_option('xlinks_deep_links', array());
    $enabled_content_types = $settings['enabled_content_types'] ?? array('page', 'post');
    $enabled_deep_links = array();
    foreach ($deep_links as $deep_link) {
        if (in_array($deep_link['destination_type'], $enabled_content_types) &&
            $deep_link['destination_id'] != $post->ID &&
            (($post_type == 'page' && $deep_link['enable_page']) || ($post_type == 'post' && $deep_link['enable_post']))) {
            $enabled_deep_links[] = $deep_link;
        }
    }
    if (empty($enabled_deep_links)) {
        return $content;
    }

    $link_counts = array();
    foreach ($enabled_deep_links as $deep_link) {
        $destination_id = $deep_link['destination_id'];
        $link_counts[$destination_id] = 0;
    }

    $dom = new DOMDocument();
    $original_content = $content;
    $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
    $load_success = @$dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    if (!$load_success) {
        error_log('xlinks: Failed to load HTML content in DOMDocument');
        return $original_content;
    }

    $xpath = new DOMXPath($dom);
    $enabled_elements = $settings['enabled_elements'] ?? array('p', 'div', 'li');
    $excluded_selectors = $settings['excluded_selectors'] ?? array();
    $exclusion_conditions = array();
    foreach ($excluded_selectors as $selector) {
        if (strpos($selector, '.') === 0) {
            $class = substr($selector, 1);
            $exclusion_conditions[] = "not(ancestor-or-self::*[contains(concat(' ', @class, ' '), ' {$class} ')])";
        } elseif (strpos($selector, '#') === 0) {
            $id = substr($selector, 1);
            $exclusion_conditions[] = "not(ancestor-or-self::*[@id='{$id}'])";
        }
    }
    $base_conditions = "not(ancestor::a) and not(ancestor::h1) and not(ancestor::h2) and not(ancestor::h3) and not(ancestor::h4) and not(ancestor::h5) and not(ancestor::h6) and not(ancestor::blockquote) and not(ancestor::img) and not(ancestor::canvas)";
    if (!empty($exclusion_conditions)) {
        $base_conditions .= ' and ' . implode(' and ', $exclusion_conditions);
    }
    $elements_query = implode(' | ', array_map(function($tag) use ($base_conditions) {
        return "//{$tag}//text()[{$base_conditions}]";
    }, $enabled_elements));
    $text_nodes = $xpath->query($elements_query);

    foreach ($text_nodes as $text_node) {
        $text = $text_node->nodeValue;
        if (trim($text) === '') {
            continue;
        }
        $new_text = $text;
        foreach ($enabled_deep_links as $deep_link) {
            if ($link_counts[$deep_link['destination_id']] >= 2) {
                continue;
            }
            $link_text = preg_quote($deep_link['link_text'], '/');
            $pattern = '/\b' . $link_text . '\b/i';
            $callback = function($matches) use ($deep_link, &$link_counts) {
                if ($link_counts[$deep_link['destination_id']] < 2) {
                    $link_counts[$deep_link['destination_id']]++;
                    $url = esc_url(get_permalink($deep_link['destination_id']));
                    $title = esc_attr(get_the_title($deep_link['destination_id']));
                    return '<a href="' . $url . '" title="' . $title . '" style="display:inline">' . $matches[0] . '</a>';
                }
                return $matches[0];
            };
            $new_text = preg_replace_callback($pattern, $callback, $new_text, -1, $count);
            if ($count > 0) {
                break;
            }
        }
        if ($new_text !== $text) {
            try {
                $fragment = $dom->createDocumentFragment();
                $fragment->appendXML($new_text);
                $text_node->parentNode->replaceChild($fragment, $text_node);
            } catch (Exception $e) {
                error_log('xlinks: Error replacing text node: ' . $e->getMessage());
                return $original_content;
            }
        }
    }

    $content = '';
    foreach ($dom->childNodes as $node) {
        $content .= $dom->saveHTML($node);
    }
    $content = preg_replace('/<\?xml[^>]+>\n?/', '', $content);
    if (empty($content) || strlen($content) < 10) {
        error_log('xlinks: Processed content is empty or too short');
        return $original_content;
    }
    return $content;
}
add_filter('the_content', 'xlinks_filter_content', 20);

// Automatic Update Functionality
function xlinks_check_for_updates($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    // Define plugin data
    $plugin_slug = 'xlinks';
    $repo_owner = 'd2x';
    $repo_name = 'xlinks';
    $plugin_file = plugin_basename(__FILE__); // e.g., xlinks/xlinks.php
    $current_version = '1.1.3';

    // Fetch the latest release information from GitHub
    $response = wp_remote_get(
        "https://api.github.com/repos/{$repo_owner}/{$repo_name}/releases/latest",
        array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                // Optional: Add 'Authorization' => 'token YOUR_PERSONAL_ACCESS_TOKEN' for private repos
            ),
        )
    );

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return $transient;
    }

    $release = json_decode(wp_remote_retrieve_body($response), true);
    $latest_version = ltrim($release['tag_name'], 'v'); // e.g., "1.1.2" (strip 'v' if present)
    $zip_url = $release['zipball_url'];
    $release_notes = $release['body'];

    // Compare versions
    if (version_compare($latest_version, $current_version, '>')) {
        $transient->response[$plugin_file] = (object) array(
            'slug' => $plugin_slug,
            'new_version' => $latest_version,
            'url' => "https://github.com/{$repo_owner}/{$repo_name}",
            'package' => $zip_url,
            'tested' => get_bloginfo('version'), // Optional: WordPress version tested up to
            'requires' => '5.0', // Optional: Minimum WordPress version
            'requires_php' => '7.0', // Optional: Minimum PHP version
        );
    }

    return $transient;
}
add_filter('site_transient_update_plugins', 'xlinks_check_for_updates');

// Provide plugin information for the "View Details" link
function xlinks_plugin_information($false, $action, $args) {
    if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== 'xlinks') {
        return $false;
    }

    $repo_owner = 'd2x';
    $repo_name = 'xlinks';

    // Fetch release information
    $response = wp_remote_get(
        "https://api.github.com/repos/{$repo_owner}/{$repo_name}/releases/latest",
        array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
        )
    );

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return $false;
    }

    $release = json_decode(wp_remote_retrieve_body($response), true);
    $latest_version = ltrim($release['tag_name'], 'v');
    $release_notes = $release['body'];

    // Plugin information
    return (object) array(
        'name' => 'xlinks',
        'slug' => 'xlinks',
        'version' => $latest_version,
        'author' => 'd2x',
        'requires' => '5.0',
        'tested' => get_bloginfo('version'),
        'requires_php' => '7.0',
        'download_link' => $release['zipball_url'],
        'last_updated' => $release['published_at'],
        'sections' => array(
            'description' => 'A plugin to insert deep links into WordPress content with customizable settings.',
            'changelog' => nl2br($release_notes),
        ),
        'banners' => array(), // Optional: Add banner images if desired
    );
}
add_filter('plugins_api', 'xlinks_plugin_information', 20, 3);