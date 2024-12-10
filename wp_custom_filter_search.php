<?php
function custom_search_and_filter_shortcode()
{
    ob_start();

    // Get all categories
    $categories = get_categories(array('hide_empty' => true));

    // Display the search and filter form
?>
    <form id="search-filter-form">
        <input type="text" name="search" placeholder="Search by title" value="">
        <div class="checkbox-filters">
            <?php foreach ($categories as $category) : ?>
                <label>
                    <input type="checkbox" name="categories[]" value="<?php echo esc_attr($category->slug); ?>">
                    <?php echo esc_html($category->name); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <button type="submit">Filter</button>
    </form>
    <div id="search-results"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('search-filter-form').addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent form from reloading the page
                const formData = new FormData(this);
                formData.append('action', 'custom_search_and_filter');

                fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('search-results').innerHTML = data;
                    });
            });

            let debounceTimer;
            document.querySelectorAll('#search-filter-form input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        const formData = new FormData(document.getElementById('search-filter-form'));
                        formData.append('action', 'custom_search_and_filter');

                        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.text())
                            .then(data => {
                                document.getElementById('search-results').innerHTML = data;
                            });
                    }, 300); // Adjust delay as needed
                });
            });

        });
    </script>
<?php

    return ob_get_clean();
}
add_shortcode('custom_search_filter', 'custom_search_and_filter_shortcode');

// AJAX handler for the search and filter
function custom_search_and_filter_ajax_handler()
{
    $search_query = sanitize_text_field($_POST['search']);
    $categories = isset($_POST['categories']) ? array_map('sanitize_text_field', $_POST['categories']) : [];

    $args = [
        'post_type' => 'post',
        's' => $search_query,
        'category_name' => $categories ? implode(',', $categories) : '',
    ];

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            echo '<div>';
            echo '<h3><a href="' . get_permalink() . '">' . get_the_title() . '</a></h3>';
            echo '</div>';
        }
    } else {
        echo '<p>No results found.</p>';
    }

    wp_die();
}
add_action('wp_ajax_custom_search_and_filter', 'custom_search_and_filter_ajax_handler');
add_action('wp_ajax_nopriv_custom_search_and_filter', 'custom_search_and_filter_ajax_handler');
