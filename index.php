<?php
/*
Plugin Name: Image Popup Plugin
Description: A WordPress plugin to select or upload images and display them as a popup on selected pages with publish and expiry dates using Fancybox.
Version: 1.0.0
Author: Alfe Caesar Lagas
*/

// Enqueue necessary scripts and styles
function image_popup_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('fancybox', 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js', array('jquery'), '3.5.7', true);
    wp_enqueue_style('fancybox-style', 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css', array(), '3.5.7');
    wp_enqueue_style('image-popup-style', plugins_url('/css/image-popup.css', __FILE__), array(), '1.0.0');
}
add_action('wp_enqueue_scripts', 'image_popup_enqueue_scripts');

// Create custom post type for image popup
function image_popup_custom_post_type() {
    $labels = array(
        'name' => 'Image Popups',
        'singular_name' => 'Image Popup',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Image Popup',
        'edit_item' => 'Edit Image Popup',
        'new_item' => 'New Image Popup',
        'view_item' => 'View Image Popup',
        'search_items' => 'Search Image Popups',
        'not_found' => 'No Image Popups found',
        'not_found_in_trash' => 'No Image Popups found in Trash',
        'parent_item_colon' => '',
        'menu_name' => 'Image Popups'
    );
    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-format-image',
        'supports' => array('title', 'thumbnail'),
        'capability_type' => 'post',
        'rewrite' => array('slug' => 'image-popup'),
    );
    register_post_type('image_popup', $args);
}
add_action('init', 'image_popup_custom_post_type');

// Add custom meta boxes for publish date, expiry date, selected pages, and link URL
function image_popup_meta_boxes() {
    add_meta_box('image_popup_dates', 'Publish and Expiry Dates', 'image_popup_dates_callback', 'image_popup', 'side');
    add_meta_box('image_popup_pages', 'Select Pages', 'image_popup_pages_callback', 'image_popup', 'side');
    add_meta_box('image_popup_link', 'Image Link', 'image_popup_link_meta_box', 'image_popup', 'normal');
}
add_action('add_meta_boxes', 'image_popup_meta_boxes');

// Callback function for publish and expiry dates meta box
function image_popup_dates_callback($post) {
    wp_nonce_field(basename(__FILE__), 'image_popup_nonce');
    $publish_date = get_post_meta($post->ID, '_image_popup_publish_date', true);
    $expiry_date = get_post_meta($post->ID, '_image_popup_expiry_date', true);
    ?>
    <p>
        <label for="image_popup_publish_date">Publish Date:</label>
        <input type="datetime-local" id="image_popup_publish_date" name="image_popup_publish_date" value="<?php echo $publish_date ? esc_attr(date('Y-m-d\TH:i', strtotime($publish_date))) : ''; ?>">
    </p>
    <p>
        <label for="image_popup_expiry_date">Expiry Date:</label>
        <input type="datetime-local" id="image_popup_expiry_date" name="image_popup_expiry_date" value="<?php echo $expiry_date ? esc_attr(date('Y-m-d\TH:i', strtotime($expiry_date))) : ''; ?>">
    </p>
    <?php
}

// Callback function for select pages meta box
function image_popup_pages_callback($post) {
    wp_nonce_field(basename(__FILE__), 'image_popup_nonce_pages');
    $selected_pages = get_post_meta($post->ID, '_image_popup_selected_pages', true);
    $pages = get_pages();
    ?>
    <p>
        <label for="image_popup_selected_pages">Select Pages:</label><br>
        <select id="image_popup_selected_pages" name="image_popup_selected_pages[]" multiple>
            <?php foreach ($pages as $page) : ?>
                <option value="<?php echo esc_attr($page->ID); ?>" <?php if (is_array($selected_pages) && in_array($page->ID, $selected_pages)) echo 'selected'; ?>><?php echo esc_html($page->post_title); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}

// Callback function for image link meta box
function image_popup_link_meta_box($post) {
    $link_url = get_post_meta($post->ID, '_image_popup_link_url', true);
    $link_target = get_post_meta($post->ID, '_image_popup_link_target', true);
    ?>
    <p>
        <label for="image_popup_link_url">Link URL:</label><br>
        <input type="url" id="image_popup_link_url" name="image_popup_link_url" value="<?php echo esc_url($link_url); ?>">
    </p>
    <p>
        <label for="image_popup_link_url">Target:</label><br>
        <select id="image_popup_link_target" name="image_popup_link_target">
            <option value="_self">Same Tab</option>
            <option value="_blank">New Tab</option>
        </select>
        <?php if ($link_target){ ?>
            <script>
                var targetElement = document.getElementById("image_popup_link_target");
                targetElement.value = "<?php echo $link_target; ?>"
            </script>
        <?php } ?>
    </p>
    <?php
}

// Save meta box data
function image_popup_save_meta_box_data($post_id) {
    if (!isset($_POST['image_popup_nonce']) || !wp_verify_nonce($_POST['image_popup_nonce'], basename(__FILE__))) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['image_popup_publish_date'])) {
        update_post_meta($post_id, '_image_popup_publish_date', sanitize_text_field($_POST['image_popup_publish_date']));
    }
    if (isset($_POST['image_popup_expiry_date'])) {
        update_post_meta($post_id, '_image_popup_expiry_date', sanitize_text_field($_POST['image_popup_expiry_date']));
    }
    if (isset($_POST['image_popup_selected_pages'])) {
        update_post_meta($post_id, '_image_popup_selected_pages', array_map('intval', $_POST['image_popup_selected_pages']));
    } else {
        update_post_meta($post_id, '_image_popup_selected_pages', array());
    }
    if (isset($_POST['image_popup_link_url'])) {
        update_post_meta($post_id, '_image_popup_link_url', esc_url_raw($_POST['image_popup_link_url']));
    }
    if (isset($_POST['image_popup_link_target'])) {
        update_post_meta($post_id, '_image_popup_link_target', $_POST['image_popup_link_target']);
    }
}
add_action('save_post', 'image_popup_save_meta_box_data');

// Display the popup image with link based on publish date and expiry date
function display_popup_image_with_link() {
    global $post;

    // Query image popup custom post type
    $popup_posts = get_posts(array(
        'post_type' => 'image_popup',
        'posts_per_page' => -1, // Get all posts
    ));

    // Get the ID of the current page
    $current_page_id = $post->ID;

    // Get the current datetime
    $current_datetime = current_time('Y-m-d').'T'.current_time('H:i');

    // Iterate through each image popup post
    foreach ($popup_posts as $popup_post) {
        // Get the selected pages for the current popup post
        $selected_pages = get_post_meta($popup_post->ID, '_image_popup_selected_pages', true);

        // Check if the current page matches any of the selected pages
        if ($selected_pages && in_array($current_page_id, $selected_pages)) {
            // Get the publish datetime and expiry datetime of the popup post
            $publish_datetime = get_post_meta($popup_post->ID, '_image_popup_publish_date', true);
            $expiry_datetime = get_post_meta($popup_post->ID, '_image_popup_expiry_date', true);

            // Check if the current datetime is within the publish and expiry datetime range
            if (($publish_datetime && $current_datetime >= $publish_datetime) && (!$expiry_datetime || $current_datetime < $expiry_datetime)) {
                // Get the image URL and link URL of the popup post
                $image_url = get_the_post_thumbnail_url($popup_post->ID);
                $link_url = get_post_meta($popup_post->ID, '_image_popup_link_url', true);
                $link_target = get_post_meta($popup_post->ID, '_image_popup_link_target', true);

                if(!$link_target){
                    $link_target = '_self';
                }

                // Check if image URL exists
                if( $image_url){
                    if ($link_url) {
                        // Output the popup image with link
                        $image_element = '<div id="image-popup-container"><a target="'. $link_target .'" href="' . esc_url($link_url) . '"><img src="' . esc_url($image_url) . '" alt="' . esc_attr(get_the_title($popup_post->ID)) . '"></a></div>';
                        echo $image_element;
                    }
                    else{
                        // Output the popup image without link
                        $image_element = '<div id="image-popup-container"><img src="' . esc_url($image_url) . '" alt="' . esc_attr(get_the_title($popup_post->ID)) . '"></div>';
                        echo $image_element;
                    }
                }
            }
        }
    }
}

// Hook the function to wp_footer
add_action('wp_footer', 'display_popup_image_with_link');


// Add custom columns to the post list table
function image_popup_custom_columns($columns) {
    // Remove the Date column
    unset($columns['date']);

    // Add new columns for Publish Date and Expiry Date with time
    $columns['popup_publish_datetime'] = 'Publish Date';
    $columns['popup_expiry_datetime'] = 'Expiry Date';
    $columns['popup_status'] = 'Popup Status';

    return $columns;
}
add_filter('manage_image_popup_posts_columns', 'image_popup_custom_columns');

// Display content for custom columns
function image_popup_custom_column_content($column, $post_id) {
    $current_datetime = current_time('Y-m-d').'T'.current_time('H:i');
    switch ($column) {
        case 'popup_publish_datetime':
            $publish_datetime = get_post_meta($post_id, '_image_popup_publish_date', true);
            echo $publish_datetime ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($publish_datetime)) : 'Not Set';
            break;
        case 'popup_expiry_datetime':
            $expiry_datetime = get_post_meta($post_id, '_image_popup_expiry_date', true);
            echo $expiry_datetime ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($expiry_datetime)) : 'Not Set';
            break;
        case 'popup_status':
            $publish_datetime = get_post_meta($post_id, '_image_popup_publish_date', true);
            $expiry_datetime = get_post_meta($post_id, '_image_popup_expiry_date', true);

            if ($publish_datetime && $current_datetime >= $publish_datetime) {
                if (!$expiry_datetime || $current_datetime < $expiry_datetime) {
                    echo '<span style="color:green;">Published</span>';
                } else {
                    echo '<span style="color:red;">Expired</span>';
                }
            } else {
                echo '<span style="color:orange;">Not Published</span>';
            }
            break;
    }
}
add_action('manage_image_popup_posts_custom_column', 'image_popup_custom_column_content', 10, 2);

// Make the custom columns sortable
function image_popup_sortable_columns($columns) {
    $columns['popup_publish_datetime'] = 'popup_publish_datetime';
    $columns['popup_expiry_datetime'] = 'popup_expiry_datetime';
    $columns['popup_status'] = 'popup_status';
    return $columns;
}
add_filter('manage_edit-image_popup_sortable_columns', 'image_popup_sortable_columns');

// Sort posts by popup publish date
function image_popup_sort_by_publish_datetime($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->get('orderby') === 'popup_publish_datetime') {
        $query->set('meta_key', '_image_popup_publish_date');
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'image_popup_sort_by_publish_datetime');

// Sort posts by popup expiry date
function image_popup_sort_by_expiry_datetime($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->get('orderby') === 'popup_expiry_datetime') {
        $query->set('meta_key', '_image_popup_expiry_date');
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'image_popup_sort_by_expiry_datetime');



// Initialize Fancybox on page load
function image_popup_initialize_fancybox() {
    ?>
    <script type="text/javascript">
        window.onload = function() {
            if(document.querySelectorAll('#image-popup-container').length > 0){
                $.fancybox.open({
                    src:  '#image-popup-container',
                    type: 'inline',
                    clickContent: false,
                    toolbar: true,
                    buttons: ['close'],
                });
            }
        };
    </script>
    <?php
}
add_action('wp_footer', 'image_popup_initialize_fancybox');



// Enqueue JavaScript in admin
function custom_post_type_admin_enqueue_scripts() {
    global $pagenow, $typenow;

    if ($pagenow == 'edit.php' && $typenow == 'image_popup') { 
        wp_enqueue_script('custom_post_type_admin_script', plugins_url('/js/image-popup-admin.js', __FILE__), array('jquery'), '', true);
    }

    if ($pagenow == 'post.php' || $pagenow == 'edit.php'){
        wp_enqueue_style('image-popup-admin-style', plugins_url('/css/image-popup-admin.css', __FILE__), array(), '1.0');
    }
   
}
add_action('admin_enqueue_scripts', 'custom_post_type_admin_enqueue_scripts'); 