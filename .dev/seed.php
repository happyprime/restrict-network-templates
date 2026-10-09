<?php
/**
 * Seeds a two-site network for local testing.
 *
 * Run with: npm run env:seed
 *
 * @package restrict-network-templates
 */

if ( ! defined( 'ABSPATH' ) || ! is_multisite() ) {
	WP_CLI::error( 'Run this against the multisite wp-env install.' );
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * Activates Twenty Twenty-Five and pretty permalinks on the current site.
 */
function restrict_network_templates_dev_setup_site(): void {
	switch_theme( 'twentytwentyfive' );

	global $wp_rewrite;
	$wp_rewrite->set_permalink_structure( '/%postname%/' );
	flush_rewrite_rules( false );
}

/**
 * Creates or updates a page by slug and returns its ID.
 *
 * @param string $slug     Page slug.
 * @param string $title    Page title.
 * @param string $content  Page content.
 * @param int    $author   Page author ID.
 * @param string $template Page template slug, or an empty string for the default.
 */
function restrict_network_templates_dev_page( string $slug, string $title, string $content, int $author, string $template = '' ): int {
	$existing = get_page_by_path( $slug );
	$args     = [
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_name'    => $slug,
		'post_title'   => $title,
		'post_content' => $content,
		'post_author'  => $author,
	];

	if ( $existing ) {
		$args['ID'] = $existing->ID;
	}

	$page_id = wp_insert_post( wp_slash( $args ), true );

	if ( is_wp_error( $page_id ) ) {
		WP_CLI::warning( $page_id->get_error_message() );
		return 0;
	}

	if ( '' === $template ) {
		delete_post_meta( $page_id, '_wp_page_template' );
	} else {
		update_post_meta( $page_id, '_wp_page_template', $template );
	}

	return $page_id;
}

/**
 * Creates the demo pages on the current site.
 *
 * @param string $site_label Label used in page content.
 * @param int    $author     Page author ID.
 */
function restrict_network_templates_dev_pages( string $site_label, int $author ): void {
	restrict_network_templates_dev_page(
		'default-template',
		'Default template',
		'<!-- wp:paragraph --><p>A page on the ' . $site_label . ' using the default page template.</p><!-- /wp:paragraph -->',
		$author
	);

	restrict_network_templates_dev_page(
		'custom-template',
		'Custom template',
		'<!-- wp:paragraph --><p>A page on the ' . $site_label . ' assigned the theme\'s "Page No Title" template. Open it in the editor and check the Template panel.</p><!-- /wp:paragraph -->',
		$author,
		'page-no-title'
	);
}

/**
 * Network-activates the plugin, creates the second site and its administrator, and seeds both sites.
 */
function restrict_network_templates_dev_seed(): void {
	WP_Theme::network_enable_theme( 'twentytwentyfive' );

	$plugin = 'restrict-network-templates/plugin.php';

	// wp-env activates the plugin on the main site only; the plugin is meant to run network wide.
	deactivate_plugins( $plugin, true, false );
	$activated = activate_plugin( $plugin, '', true );

	if ( is_wp_error( $activated ) ) {
		WP_CLI::error( $activated->get_error_message() );
	}

	$main_site_id = get_main_site_id();

	switch_to_blog( $main_site_id );
	update_option( 'blogname', 'Main Site' );
	restrict_network_templates_dev_setup_site();
	restrict_network_templates_dev_pages( 'main site', 1 );
	restore_current_blog();

	$network = get_network();
	$domain  = $network ? $network->domain : 'localhost';
	$path    = ( $network ? $network->path : '/' ) . 'second/';
	$sub_id  = get_blog_id_from_url( $domain, $path );

	if ( ! $sub_id ) {
		$sub_id = wpmu_create_blog( $domain, $path, 'Second Site', 1 );

		if ( is_wp_error( $sub_id ) ) {
			WP_CLI::error( $sub_id->get_error_message() );
		}
	}

	$site_admin = get_user_by( 'login', 'siteadmin' );

	if ( $site_admin ) {
		$site_admin_id = $site_admin->ID;
	} else {
		$site_admin_id = wpmu_create_user( 'siteadmin', 'password', 'siteadmin@example.test' );

		if ( ! $site_admin_id ) {
			WP_CLI::error( 'Could not create the siteadmin user.' );
		}
	}

	add_user_to_blog( $sub_id, $site_admin_id, 'administrator' );
	revoke_super_admin( $site_admin_id );

	switch_to_blog( $sub_id );
	restrict_network_templates_dev_setup_site();
	restrict_network_templates_dev_pages( 'second site', $site_admin_id );
	restore_current_blog();

	WP_CLI::success(
		sprintf(
			'Seeded %1$s and %2$s. Log in as admin/password (super admin) or siteadmin/password (second site administrator).',
			get_home_url( $main_site_id ),
			get_home_url( $sub_id )
		)
	);
}

restrict_network_templates_dev_seed();
