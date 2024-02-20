<?php
define('IS_LOCAL', str_contains($_SERVER['SERVER_NAME'], ".local"));
define('THEME_DIR', get_stylesheet_directory());
define('THEME_DIR_URI', get_stylesheet_directory_uri());
define('THEME_ASSET_PREFIX', IS_LOCAL ? 'http://localhost:5173' : THEME_DIR_URI . '/build');

include_once(THEME_DIR . '/inc/theme.php');
