<?php
/**
 * Compatibility stub for sites upgrading from version 1.4.5 or earlier.
 *
 * WordPress stores the active plugin path in wp_options (active_plugins).
 * The main file was renamed from lcg_adl_main.php to logo-showcase-ultimate.php
 * in v1.5.0. Without this stub, WordPress cannot find the original path and
 * silently deactivates the plugin during the update — before any plugin code
 * can run, making a programmatic fix impossible.
 *
 * This file has no plugin header intentionally so WordPress does not treat it
 * as a second plugin entry point.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access is not allowed' );
}

require_once plugin_dir_path( __FILE__ ) . 'logo-showcase-ultimate.php';
