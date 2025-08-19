<?php
/**
 * Plugin Name: WP Migration
 * Plugin URI: https://es.wordpress.org/plugins/all-in-one-wp-migration/
 * Description: All-in-One WP Migration and Backup plugin modified by Esparta Digital for agency clients
 * Author: Esparta digital
 * Author URI: https://espartadigital.com/
 * Version: 8.0.5
 * Text Domain: all-in-one-wp-migration
 * Domain Path: /languages
 * Network: True
 *
 * Copyright (C) 2014-2018 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */


if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}

// Check SSL Mode
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && ( $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) ) {
	$_SERVER['HTTPS'] = 'on';
}

// Plugin Basename
define( 'AI1WM_PLUGIN_BASENAME', basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ) );

// Plugin Path
define( 'AI1WM_PATH', dirname( __FILE__ ) );

// Plugin Url
define( 'AI1WM_URL', plugins_url( '', AI1WM_PLUGIN_BASENAME ) );

// Plugin Storage Url
define( 'AI1WM_STORAGE_URL', plugins_url( 'storage', AI1WM_PLUGIN_BASENAME ) );

// Plugin Backups Url
define( 'AI1WM_BACKUPS_URL', content_url( 'ai1wm-backups', AI1WM_PLUGIN_BASENAME ) );

// Themes Absolute Path
define( 'AI1WM_THEMES_PATH', get_theme_root() );

// Include constants
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'constants.php';

// Include deprecated
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'deprecated.php';

// Include functions
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'functions.php';

// Include exceptions
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'exceptions.php';

// Include loader
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'loader.php';

// =========================================================================
// = All app initialization is done in Ai1wm_Main_Controller __constructor =
// =========================================================================
$main_controller = new Ai1wm_Main_Controller();

// EVITAR ACTUALIZACIONES AUTOMATICAS
add_filter( 'auto_update_plugin', function( $update, $item ) {
    if ( isset( $item->plugin ) && $item->plugin === plugin_basename(__FILE__) ) {
        return false;
    }
    return $update;
}, 10, 2 );

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), function( $actions ) {
    unset( $actions['enable-auto-update'] );
    unset( $actions['disable-auto-update'] );
    return $actions;
});

add_filter( 'plugin_auto_update_setting_html', function( $html, $plugin_file ) {
    if ( $plugin_file === plugin_basename(__FILE__) ) {
        return '';
    }
    return $html;
}, 10, 2 );

// BLOQUEO DE ACTUALIZACIONES BASE DE WORDPRESS.ORG
add_filter( 'site_transient_update_plugins', function( $transient ) {
    $plugin_file = plugin_basename(__FILE__);

    if ( isset( $transient->response[$plugin_file] ) ) {
        $pkg = $transient->response[$plugin_file];
        if ( !isset($pkg->package) || strpos($pkg->package, 'downloads.wordpress.org') !== false ) {
            unset( $transient->response[$plugin_file] );
        }
    }

    return $transient;
});

// SISTEMA DE ACTUALIZACIONES
add_filter( 'pre_set_site_transient_update_plugins', function( $transient ) {
    $plugin_file = plugin_basename(__FILE__);
    $remote = wp_remote_get( 'https://cdn.vantag.es/wp-migration.json' );

    if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) ) {
        return $transient;
    }

    $response = json_decode( wp_remote_retrieve_body( $remote ) );

    if ( isset($response->version) && version_compare( '8.0.0', $response->version, '<' ) ) {
        $transient->response[$plugin_file] = (object) [
            'slug'        => dirname($plugin_file),
            'plugin'      => $plugin_file,
            'new_version' => $response->version,
            'url'         => $response->details_url,
            'package'     => $response->download_url,
        ];
    }

    return $transient;
});
