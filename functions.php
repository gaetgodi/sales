<?php
function divi_sales_child_enqueue_styles() {
    wp_enqueue_style( "divi-parent-style", get_template_directory_uri() . "/style.css" );
    wp_enqueue_style( "godindev-fonts",
        get_stylesheet_directory_uri() . "/00-fonts.css",
        array( "divi-parent-style" ),
        wp_get_theme()->get("Version")
    );
    wp_enqueue_style( "godindev-tokens",
        get_stylesheet_directory_uri() . "/00-tokens.css",
        array( "godindev-fonts" ),
        wp_get_theme()->get("Version")
    );
    wp_enqueue_style( "godindev-components",
        get_stylesheet_directory_uri() . "/01-components.css",
        array( "godindev-tokens" ),
        wp_get_theme()->get("Version")
    );
    wp_enqueue_style( "divi-sales-child-style",
        get_stylesheet_directory_uri() . "/style.css",
        array( "divi-parent-style", "godindev-components" ),
        wp_get_theme()->get("Version")
    );
}
add_action( "wp_enqueue_scripts", "divi_sales_child_enqueue_styles" );
