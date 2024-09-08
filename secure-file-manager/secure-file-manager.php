<?php
/*
Plugin Name: Secure File Manager
Description: A plugin to manage encrypted files with a login system.
Version: 1.8
Author: Mdevign
*/

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Incluir archivos necesarios
include_once plugin_dir_path(__FILE__) . 'includes/secure-file-manager-settings.php';
include_once plugin_dir_path(__FILE__) . 'includes/secure-file-manager-functions.php';

// Registro del shortcode
add_shortcode('secure_login', 'render_secure_login');

// Registro de la página de ajustes
add_action('admin_menu', 'secure_file_manager_menu');

function secure_file_manager_menu() {
    add_menu_page(
        'Secure File Manager Settings',
        'File Manager',
        'manage_options',
        'secure-file-manager',
        'secure_file_manager_settings_page',
        'dashicons-admin-generic'
    );
}

// Encolar scripts y estilos necesarios
function secure_file_manager_enqueue_scripts() {
    wp_enqueue_script('secure-file-manager-js', plugins_url('/assets/js/secure-file-manager.js', __FILE__), array('jquery'), '1.0', true);
    
    // Localizar la variable ajaxurl
    wp_localize_script('secure-file-manager-js', 'secure_file_manager_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    
    // Encolar el archivo CSS
    wp_enqueue_style('secure-file-manager-css', plugins_url('/assets/css/secure-file-manager.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'secure_file_manager_enqueue_scripts');

// Crear la tabla de usuarios personalizados al activar el plugin
register_activation_hook(__FILE__, 'secure_file_manager_create_db');

function secure_file_manager_create_db() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'secure_file_users';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        username varchar(60) NOT NULL,
        password varchar(255) NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY username (username)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Iniciar sesión personalizada
function secure_file_manager_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'secure_file_manager_start_session');

// Cerrar sesión personalizada
function secure_file_manager_end_session() {
    session_destroy();
}
add_action('wp_logout', 'secure_file_manager_end_session');
add_action('wp_login', 'secure_file_manager_end_session');

?>
