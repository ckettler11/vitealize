<?php

add_action('after_setup_theme', 'vitealize_setup');
function vitealize_setup()
{
  load_theme_textdomain('vitealize', get_template_directory() . '/languages');
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('responsive-embeds');
  add_theme_support('automatic-feed-links');
  add_theme_support('html5', array('search-form', 'navigation-widgets'));
  add_theme_support('woocommerce');

  if (!isset($content_width)) {
    $content_width = 1920;
  }
  register_nav_menus(array('main-menu' => esc_html__('Main Menu', 'vitealize')));
}
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style('vitealize-style', get_stylesheet_uri());
  wp_enqueue_script('jquery');
  if (!IS_LOCAL) {
    $manifest = (array) json_decode(file_get_contents(THEME_DIR . '/build/.vite/manifest.json'));
    $fileinfo = $manifest['theme/index.ts'];
    wp_register_script('vitealize-js', get_stylesheet_directory_uri() . '/build/' . $fileinfo->file, [], null, ['in_footer' => true]);
    wp_enqueue_script('vitealize-js');
    wp_scripts()->add_data('vitealize-js', 'type', 'module');
    if ($fileinfo->css) {
      foreach ($fileinfo->css as $path) {
        wp_enqueue_style($path, get_stylesheet_directory_uri() . '/build/' . $path, [], null);
      }
    }
  }
});

// vite dev must be running 
if (IS_LOCAL) {
  add_action('wp_footer', function () { ?>
    <script type="module" src="http://localhost:5173/@vite/client"></script>
    <script type="module" src="http://localhost:5173/theme/index.ts"></script>
  <?php
  });
}


add_filter('document_title_separator', 'vitealize_document_title_separator');
function vitealize_document_title_separator($sep)
{
  $sep = esc_html('|');
  return $sep;
}
add_filter('the_title', 'vitealize_title');
function vitealize_title($title)
{
  if ($title == '') {
    return esc_html('...');
  } else {
    return wp_kses_post($title);
  }
}
function vitealize_schema_type()
{
  $schema = 'https://schema.org/';
  if (is_single()) {
    $type = "Article";
  } elseif (is_author()) {
    $type = 'ProfilePage';
  } elseif (is_search()) {
    $type = 'SearchResultsPage';
  } else {
    $type = 'WebPage';
  }
  echo 'itemscope itemtype="' . esc_url($schema) . esc_attr($type) . '"';
}
add_filter('nav_menu_link_attributes', 'vitealize_schema_url', 10);
function vitealize_schema_url($atts)
{
  $atts['itemprop'] = 'url';
  return $atts;
}
if (!function_exists('vitealize_wp_body_open')) {
  function vitealize_wp_body_open()
  {
    do_action('wp_body_open');
  }
}
add_action('wp_body_open', 'vitealize_skip_link', 5);
function vitealize_skip_link()
{
  echo '<a href="#content" class="skip-link screen-reader-text">' . esc_html__('Skip to the content', 'vitealize') . '</a>';
}
add_filter('the_content_more_link', 'vitealize_read_more_link');
function vitealize_read_more_link()
{
  if (!is_admin()) {
    return ' <a href="' . esc_url(get_permalink()) . '" class="more-link">' . sprintf(__('...%s', 'vitealize'), '<span class="screen-reader-text">  ' . esc_html(get_the_title()) . '</span>') . '</a>';
  }
}
add_filter('excerpt_more', 'vitealize_excerpt_read_more_link');
function vitealize_excerpt_read_more_link($more)
{
  if (!is_admin()) {
    global $post;
    return ' <a href="' . esc_url(get_permalink($post->ID)) . '" class="more-link">' . sprintf(__('...%s', 'vitealize'), '<span class="screen-reader-text">  ' . esc_html(get_the_title()) . '</span>') . '</a>';
  }
}
add_filter('big_image_size_threshold', '__return_false');
add_filter('intermediate_image_sizes_advanced', 'vitealize_image_insert_override');
function vitealize_image_insert_override($sizes)
{
  unset($sizes['medium_large']);
  unset($sizes['1536x1536']);
  unset($sizes['2048x2048']);
  return $sizes;
}
add_action('widgets_init', 'vitealize_widgets_init');
function vitealize_widgets_init()
{
  register_sidebar(array(
    'name' => esc_html__('Sidebar Widget Area', 'vitealize'),
    'id' => 'primary-widget-area',
    'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
    'after_widget' => '</li>',
    'before_title' => '<h3 class="widget-title">',
    'after_title' => '</h3>',
  ));
}
add_action('wp_head', 'vitealize_pingback_header');
function vitealize_pingback_header()
{
  if (is_singular() && pings_open()) {
    printf('<link rel="pingback" href="%s">' . "\n", esc_url(get_bloginfo('pingback_url')));
  }
}
add_action('comment_form_before', 'vitealize_enqueue_comment_reply_script');
function vitealize_enqueue_comment_reply_script()
{
  if (get_option('thread_comments')) {
    wp_enqueue_script('comment-reply');
  }
}
function vitealize_custom_pings($comment)
{
  ?>
  <li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>"><?php echo esc_url(comment_author_link()); ?></li>
<?php
}
add_filter('get_comments_number', 'vitealize_comment_count', 0);
function vitealize_comment_count($count)
{
  if (!is_admin()) {
    global $id;
    $get_comments = get_comments('status=approve&post_id=' . $id);
    $comments_by_type = separate_comments($get_comments);
    return count($comments_by_type['comment']);
  } else {
    return $count;
  }
}


add_action('post_updated', 'content_dump');
function content_dump()
{
  ob_start();
  $q = new WP_Query([
    'post_type' => ['page', 'post'],
    'posts_per_page' => -1
  ]);

  if ($q->have_posts()) {
    while ($q->have_posts()) {
      $q->the_post();
      the_content();
    }
  }
  $html = ob_get_clean();
  ob_start();
  wp_reset_postdata();
  file_put_contents(THEME_DIR . "/inc/content.php", $html, FILE_USE_INCLUDE_PATH);
  ob_end_clean();
}
