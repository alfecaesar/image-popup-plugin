<?php
/*
Plugin Name: Image Popup Plugin
Description: A WordPress plugin to select or upload images and display them as a popup on selected pages with publish and expiry dates using Fancybox.
Version: 1.4.0
Author: Alfe Caesar Lagas
*/

// Enqueue necessary scripts and styles
function image_popup_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('fancybox', 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js', array('jquery'), '3.5.7', true);
    wp_enqueue_script('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), '11.1.14', true);
    wp_enqueue_style('fancybox-style', 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css', array(), '3.5.7');
    wp_enqueue_style('swiper-style', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.1.14');
    wp_enqueue_style('image-popup-style', plugins_url('/css/image-popup.css', __FILE__), array(), '1.0.11');
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
    add_meta_box('image_popup_multiple_images', 'Multiple Images', 'image_popup_multiple_images_meta_box', 'image_popup', 'normal');
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
    $link_text = get_post_meta($post->ID, '_image_popup_link_text', true);
    $link_target = get_post_meta($post->ID, '_image_popup_link_target', true);
    ?>
    <p>
        <label for="image_popup_link_url">Link URL:</label><br>
        <input type="url" id="image_popup_link_url" name="image_popup_link_url" value="<?php echo esc_url($link_url); ?>">
    </p>
    <p>
        <label for="image_popup_link_text">Button Text:</label><br>
        <input type="text" id="image_popup_link_text" name="image_popup_link_text" value="<?php echo $link_text; ?>">
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

// Callback function for multiple images meta box
function image_popup_multiple_images_meta_box($post) {
    wp_nonce_field(basename(__FILE__), 'image_popup_media_nonce');
    $image_id_2 = get_post_meta($post->ID, '_image_popup_media_id_2', true);
    $image_url_2 = $image_id_2 ? wp_get_attachment_image_src($image_id_2, 'full')[0] : '';
    $image_link_2 = get_post_meta($post->ID, '_image_popup_media_link_2', true);
    $image_text_2 = get_post_meta($post->ID, '_image_popup_media_text_2', true);
    $link_target_2 = get_post_meta($post->ID, '_image_popup_media_target_2', true);

    $image_id_3 = get_post_meta($post->ID, '_image_popup_media_id_3', true);
    $image_url_3 = $image_id_3 ? wp_get_attachment_image_src($image_id_3, 'full')[0] : '';
    $image_link_3 = get_post_meta($post->ID, '_image_popup_media_link_3', true);
    $image_text_3 = get_post_meta($post->ID, '_image_popup_media_text_3', true);
    $link_target_3 = get_post_meta($post->ID, '_image_popup_media_target_3', true);

    $image_id_4 = get_post_meta($post->ID, '_image_popup_media_id_4', true);
    $image_url_4 = $image_id_4 ? wp_get_attachment_image_src($image_id_4, 'full')[0] : '';
    $image_link_4 = get_post_meta($post->ID, '_image_popup_media_link_4', true);
    $image_text_4 = get_post_meta($post->ID, '_image_popup_media_text_4', true);
    $link_target_4 = get_post_meta($post->ID, '_image_popup_media_target_4', true);

    $image_id_5 = get_post_meta($post->ID, '_image_popup_media_id_5', true);
    $image_url_5 = $image_id_5 ? wp_get_attachment_image_src($image_id_5, 'full')[0] : '';
    $image_link_5 = get_post_meta($post->ID, '_image_popup_media_link_5', true);
    $image_text_5 = get_post_meta($post->ID, '_image_popup_media_text_5', true);
    $link_target_5 = get_post_meta($post->ID, '_image_popup_media_target_5', true);
    ?>
    <hr />
    <p>
        <label for="image_popup_media">Select Image 2:</label><br>
        <img id="image_popup_media_preview_2" src="<?php echo esc_url($image_url_2); ?>" style="max-width: 100%; display: <?php echo $image_url_2 ? 'block' : 'none'; ?>" /><br>
        <input type="hidden" id="image_popup_media_id_2" name="image_popup_media_id_2" value="<?php echo esc_attr($image_id_2); ?>" />
        <button type="button" class="button" id="image_popup_media_button_2"><?php _e('Select or Upload Image'); ?></button>
        <button type="button" class="button" id="image_popup_media_remove_button_2" style="display: <?php echo $image_url_2 ? 'inline-block' : 'none'; ?>"><?php _e('Remove Image'); ?></button>
    </p>
    <p>
        <label for="image_popup_media">Link URL:</label><br>
        <input type="text" id="image_popup_media_link_2" name="image_popup_media_link_2" value="<?php echo esc_url($image_link_2); ?>" />
    </p>
    <p>
        <label for="image_popup_media">Button Text:</label><br>
        <input type="text" id="image_popup_media_text_2" name="image_popup_media_text_2" value="<?php echo $image_text_2; ?>" />
    </p>
    <p>
        <label for="image_popup_media_target_2">Target:</label><br>
        <select id="image_popup_media_target_2" name="image_popup_media_target_2">
            <option value="_self">Same Tab</option>
            <option value="_blank">New Tab</option>
        </select>
        <?php if ($link_target_2){ ?>
            <script>
                var targetElement = document.getElementById("image_popup_media_target_2");
                targetElement.value = "<?php echo $link_target_2; ?>"
            </script>
        <?php } ?>
    </p>
    <hr />
    <p>
        <label for="image_popup_media">Select Image 3:</label><br>
        <img id="image_popup_media_preview_3" src="<?php echo esc_url($image_url_3); ?>" style="max-width: 100%; display: <?php echo $image_url_3 ? 'block' : 'none'; ?>" /><br>
        <input type="hidden" id="image_popup_media_id_3" name="image_popup_media_id_3" value="<?php echo esc_attr($image_id_3); ?>" />
        <button type="button" class="button" id="image_popup_media_button_3"><?php _e('Select or Upload Image'); ?></button>
        <button type="button" class="button" id="image_popup_media_remove_button_3" style="display: <?php echo $image_url_3 ? 'inline-block' : 'none'; ?>"><?php _e('Remove Image'); ?></button>
    </p>
    <p>
        <label for="image_popup_media">Link URL:</label><br>
        <input type="text" id="image_popup_media_link_3" name="image_popup_media_link_3" value="<?php echo esc_url($image_link_3); ?>" />
    </p>
    <p>
        <label for="image_popup_media">Button Text:</label><br>
        <input type="text" id="image_popup_media_text_3" name="image_popup_media_text_3" value="<?php echo $image_text_3; ?>" />
    </p>
    <p>
        <label for="image_popup_media_target_3">Target:</label><br>
        <select id="image_popup_media_target_3" name="image_popup_media_target_3">
            <option value="_self">Same Tab</option>
            <option value="_blank">New Tab</option>
        </select>
        <?php if ($link_target_3){ ?>
            <script>
                var targetElement = document.getElementById("image_popup_media_target_3");
                targetElement.value = "<?php echo $link_target_3; ?>"
            </script>
        <?php } ?>
    </p>
    <hr />
    <p>
        <label for="image_popup_media">Select Image 4:</label><br>
        <img id="image_popup_media_preview_4" src="<?php echo esc_url($image_url_4); ?>" style="max-width: 100%; display: <?php echo $image_url_4 ? 'block' : 'none'; ?>" /><br>
        <input type="hidden" id="image_popup_media_id_4" name="image_popup_media_id_4" value="<?php echo esc_attr($image_id_4); ?>" />
        <button type="button" class="button" id="image_popup_media_button_4"><?php _e('Select or Upload Image'); ?></button>
        <button type="button" class="button" id="image_popup_media_remove_button_4" style="display: <?php echo $image_url_4 ? 'inline-block' : 'none'; ?>"><?php _e('Remove Image'); ?></button>
    </p>
    <p>
        <label for="image_popup_media">Link URL:</label><br>
        <input type="text" id="image_popup_media_link_4" name="image_popup_media_link_4" value="<?php echo esc_url($image_link_4); ?>" />
    </p>
    <p>
        <label for="image_popup_media">Button Text:</label><br>
        <input type="text" id="image_popup_media_text_4" name="image_popup_media_text_4" value="<?php echo $image_text_4; ?>" />
    </p>
    <p>
        <label for="image_popup_media_target_4">Target:</label><br>
        <select id="image_popup_media_target_4" name="image_popup_media_target_4">
            <option value="_self">Same Tab</option>
            <option value="_blank">New Tab</option>
        </select>
        <?php if ($link_target_4){ ?>
            <script>
                var targetElement = document.getElementById("image_popup_media_target_4");
                targetElement.value = "<?php echo $link_target_4; ?>"
            </script>
        <?php } ?>
    </p>
    <hr />
    <p>
        <label for="image_popup_media">Select Image 5:</label><br>
        <img id="image_popup_media_preview_5" src="<?php echo esc_url($image_url_5); ?>" style="max-width: 100%; display: <?php echo $image_url_5 ? 'block' : 'none'; ?>" /><br>
        <input type="hidden" id="image_popup_media_id_5" name="image_popup_media_id_5" value="<?php echo esc_attr($image_id_5); ?>" />
        <button type="button" class="button" id="image_popup_media_button_5"><?php _e('Select or Upload Image'); ?></button>
        <button type="button" class="button" id="image_popup_media_remove_button_5" style="display: <?php echo $image_url_5 ? 'inline-block' : 'none'; ?>"><?php _e('Remove Image'); ?></button>
    </p>
    <p>
        <label for="image_popup_media">Link URL:</label><br>
        <input type="text" id="image_popup_media_link_5" name="image_popup_media_link_5" value="<?php echo esc_url($image_link_5); ?>" />
    </p>
    <p>
        <label for="image_popup_media">Button Text:</label><br>
        <input type="text" id="image_popup_media_text_5" name="image_popup_media_text_5" value="<?php echo $image_text_5; ?>" />
    </p>
    <p>
        <label for="image_popup_media_target_5">Target:</label><br>
        <select id="image_popup_media_target_5" name="image_popup_media_target_5">
            <option value="_self">Same Tab</option>
            <option value="_blank">New Tab</option>
        </select>
        <?php if ($link_target_5){ ?>
            <script>
                var targetElement = document.getElementById("image_popup_media_target_5");
                targetElement.value = "<?php echo $link_target_5; ?>"
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
    if (isset($_POST['image_popup_link_text'])) {
        update_post_meta($post_id, '_image_popup_link_text', $_POST['image_popup_link_text']);
    }
    if (isset($_POST['image_popup_link_target'])) {
        update_post_meta($post_id, '_image_popup_link_target', $_POST['image_popup_link_target']);
    }
    if (isset($_POST['image_popup_media_id_2'])) {
        $media_id = intval($_POST['image_popup_media_id_2']);
        update_post_meta($post_id, '_image_popup_media_id_2', $media_id);
    }
    if (isset($_POST['image_popup_media_link_2'])) {
        update_post_meta($post_id, '_image_popup_media_link_2', esc_url_raw($_POST['image_popup_media_link_2']));
    }
    if (isset($_POST['image_popup_media_text_2'])) {
        update_post_meta($post_id, '_image_popup_media_text_2', $_POST['image_popup_media_text_2']);
    }
    if (isset($_POST['image_popup_media_target_2'])) {
        update_post_meta($post_id, '_image_popup_media_target_2', $_POST['image_popup_media_target_2']);
    }
    if (isset($_POST['image_popup_media_id_3'])) {
        $media_id = intval($_POST['image_popup_media_id_3']);
        update_post_meta($post_id, '_image_popup_media_id_3', $media_id);
    }
    if (isset($_POST['image_popup_media_link_3'])) {
        update_post_meta($post_id, '_image_popup_media_link_3', esc_url_raw($_POST['image_popup_media_link_3']));
    }
    if (isset($_POST['image_popup_media_text_3'])) {
        update_post_meta($post_id, '_image_popup_media_text_3', $_POST['image_popup_media_text_3']);
    }
    if (isset($_POST['image_popup_media_target_3'])) {
        update_post_meta($post_id, '_image_popup_media_target_3', $_POST['image_popup_media_target_3']);
    }
    if (isset($_POST['image_popup_media_id_4'])) {
        $media_id = intval($_POST['image_popup_media_id_4']);
        update_post_meta($post_id, '_image_popup_media_id_4', $media_id);
    }
    if (isset($_POST['image_popup_media_link_4'])) {
        update_post_meta($post_id, '_image_popup_media_link_4', esc_url_raw($_POST['image_popup_media_link_4']));
    }
    if (isset($_POST['image_popup_media_text_4'])) {
        update_post_meta($post_id, '_image_popup_media_text_4', $_POST['image_popup_media_text_4']);
    }
    if (isset($_POST['image_popup_media_target_4'])) {
        update_post_meta($post_id, '_image_popup_media_target_4', $_POST['image_popup_media_target_4']);
    }
    if (isset($_POST['image_popup_media_id_5'])) {
        $media_id = intval($_POST['image_popup_media_id_5']);
        update_post_meta($post_id, '_image_popup_media_id_5', $media_id);
    }
    if (isset($_POST['image_popup_media_link_5'])) {
        update_post_meta($post_id, '_image_popup_media_link_5', esc_url_raw($_POST['image_popup_media_link_5']));
    }
    if (isset($_POST['image_popup_media_text_5'])) {
        update_post_meta($post_id, '_image_popup_media_text_5', $_POST['image_popup_media_text_5']);
    }
    if (isset($_POST['image_popup_media_target_5'])) {
        update_post_meta($post_id, '_image_popup_media_target_5', $_POST['image_popup_media_target_5']);
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
                $link_text = get_post_meta($popup_post->ID, '_image_popup_link_text', true);
                $link_target = get_post_meta($popup_post->ID, '_image_popup_link_target', true);

                $image_id_2 = get_post_meta($popup_post->ID, '_image_popup_media_id_2', true);
                $image_url_2 = $image_id_2 ? wp_get_attachment_image_src($image_id_2, 'full')[0] : '';
                $link_url_2 = get_post_meta($popup_post->ID, '_image_popup_media_link_2', true);
                $link_text_2 = get_post_meta($popup_post->ID, '_image_popup_media_text_2', true);
                $link_target_2 = get_post_meta($popup_post->ID, '_image_popup_media_target_2', true);

                $image_id_3 = get_post_meta($popup_post->ID, '_image_popup_media_id_3', true);
                $image_url_3 = $image_id_3 ? wp_get_attachment_image_src($image_id_3, 'full')[0] : '';
                $link_url_3 = get_post_meta($popup_post->ID, '_image_popup_media_link_3', true);
                $link_text_3 = get_post_meta($popup_post->ID, '_image_popup_media_text_3', true);
                $link_target_3 = get_post_meta($popup_post->ID, '_image_popup_media_target_3', true);

                $image_id_4 = get_post_meta($popup_post->ID, '_image_popup_media_id_4', true);
                $image_url_4 = $image_id_4 ? wp_get_attachment_image_src($image_id_4, 'full')[0] : '';
                $link_url_4 = get_post_meta($popup_post->ID, '_image_popup_media_link_4', true);
                $link_text_4 = get_post_meta($popup_post->ID, '_image_popup_media_text_4', true);
                $link_target_4 = get_post_meta($popup_post->ID, '_image_popup_media_target_4', true);

                $image_id_5 = get_post_meta($popup_post->ID, '_image_popup_media_id_5', true);
                $image_url_5 = $image_id_5 ? wp_get_attachment_image_src($image_id_5, 'full')[0] : '';
                $link_url_5 = get_post_meta($popup_post->ID, '_image_popup_media_link_5', true);
                $link_text_5 = get_post_meta($popup_post->ID, '_image_popup_media_text_5', true);
                $link_target_5 = get_post_meta($popup_post->ID, '_image_popup_media_target_5', true);

                $link_class = 'btn-default';
                $link_class_2 = 'btn-default';
                $link_class_3 = 'btn-default';
                $link_class_4 = 'btn-default';
                $link_class_5 = 'btn-default';

                if($image_url){
                    if($link_url){
                        $link_class = 'btn-full';
                    }
                    else{
                        $link_url = '#';
                    }
                    if($link_text){
                        $link_class = 'btn-link';
                    }
                    $link = '<a class="' . $link_class . '" target="' . $link_target . '" href="' . esc_url($link_url) . '">' . $link_text . '</a>';
                    $img_elem_1 = '<div class="swiper-slide">
                                    <img src="' . esc_url($image_url) . '"> 
                                    '. $link . '
                                   </div>';
                }
                else{
                    $img_elem_1 = '';
                }

                if($image_url_2){
                    if($link_url_2){
                        $link_class_2 = 'btn-full';
                    }
                    else{
                        $link_url_2 = '#';
                    }
                    if($link_text_2){
                        $link_class_2 = 'btn-link';
                    }
                    $link_2 = '<a class="' . $link_class_2 . '" target="' . $link_target_2 . '" href="' . esc_url($link_url_2) . '">' . $link_text_2 . '</a>';
                    $img_elem_2 = '<div class="swiper-slide">
                                    <img src="' . esc_url($image_url_2) . '"> 
                                    '. $link_2 . '
                                   </div>';
                }
                else{
                    $img_elem_2 = '';
                }

                if($image_url_3){
                    if($link_url_3){
                        $link_class_3 = 'btn-full';
                    }
                    else{
                        $link_url_3 = '#';
                    }
                    if($link_text_3){
                        $link_class_3 = 'btn-link';
                    }
                    $link_3 = '<a class="' . $link_class_3 . '" target="' . $link_target_3 . '" href="' . esc_url($link_url_3) . '">' . $link_text_3 . '</a>';
                    $img_elem_3 = '<div class="swiper-slide">
                                    <img src="' . esc_url($image_url_3) . '"> 
                                    '. $link_3 . '
                                   </div>';
                }
                else{
                    $img_elem_3 = '';
                }

                if($image_url_4){
                    if($link_url_4){
                        $link_class_4 = 'btn-full';
                    }
                    else{
                        $link_url_4 = '#';
                    }
                    if($link_text_4){
                        $link_class_4 = 'btn-link';
                    }
                    $link_4 = '<a class="' . $link_class_4 . '" target="' . $link_target_4 . '" href="' . esc_url($link_url_4) . '">' . $link_text_4 . '</a>';
                    $img_elem_4 = '<div class="swiper-slide">
                                    <img src="' . esc_url($image_url_4) . '"> 
                                    '. $link_4 . '
                                   </div>';
                }
                else{
                    $img_elem_4 = '';
                }

                if($image_url_5){
                    if($link_url_5){
                        $link_class_5 = 'btn-full';
                    }
                    else{
                        $link_url_5 = '#';
                    }
                    if($link_text_5){
                        $link_class_5 = 'btn-link';
                    }
                    $link_5 = '<a class="' . $link_class_5 . '" target="' . $link_target_5 . '" href="' . esc_url($link_url_5) . '">' . $link_text_5 . '</a>';
                    $img_elem_5 = '<div class="swiper-slide">
                                    <img src="' . esc_url($image_url_5) . '"> 
                                    '. $link_5 . '
                                   </div>';
                }
                else{
                    $img_elem_5 = '';
                }
                

                // Check if image URL exists
                $image_element = '
                            <div id="image-popup-container">
                                <div class="swiper popupSwiper">
                                    <div class="swiper-wrapper">
                                        ' . $img_elem_1 . $img_elem_2 . $img_elem_3 . $img_elem_4 . $img_elem_5 . '
                                    </div>
                                    <div class="swiper-button-next"></div>
                                    <div class="swiper-button-prev"></div>
                                </div>
                                <button data-fancybox-close="" class="fancybox-button fancybox-button--close" title="Close">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M12 10.6L6.6 5.2 5.2 6.6l5.4 5.4-5.4 5.4 1.4 1.4 5.4-5.4 5.4 5.4 1.4-1.4-5.4-5.4 5.4-5.4-1.4-1.4-5.4 5.4z"></path>
                                    </svg>
                                </button>
                            </div>
                        ';
                echo $image_element;
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
                    toolbar: false,
                    buttons: [],
                });
                var swiper = new Swiper(".popupSwiper", {
                    initialSlide: 0,
                    navigation: {
                        nextEl: ".swiper-button-next",
                        prevEl: ".swiper-button-prev",
                    },
                });
                swiper.slideTo(0, false,false);
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
        wp_enqueue_script('custom_post_type_admin_script', plugins_url('/js/image-popup-admin.js', __FILE__), array('jquery'), '1.0.0', true);
    }

    if ($pagenow == 'post.php' || $pagenow == 'edit.php'){
        wp_enqueue_style('image-popup-admin-style', plugins_url('/css/image-popup-admin.css', __FILE__), array(), '1.0');
        wp_enqueue_script('custom_post_type_admin_script', plugins_url('/js/image-popup-media.js', __FILE__), array('jquery'), '1.0.1', true);
    }
   
}
add_action('admin_enqueue_scripts', 'custom_post_type_admin_enqueue_scripts'); 