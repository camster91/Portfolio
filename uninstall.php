<?php
/**
 * Uninstall Script for Motomotus Portfolio.
 *
 * Portfolio posts and their metadata are client content, not disposable plugin
 * state. Preserve them when the plugin is removed so a rollback, replacement,
 * or folder consolidation cannot erase production content.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// No destructive cleanup is performed by design.
