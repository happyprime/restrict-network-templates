<?php
/**
 * Plugin Name:  Restrict Network Templates
 * Description:  Restrict the use of templates and network- prefixed parts to the main site.
 * Version:      0.0.1
 * Plugin URI:   https://github.com/happyprime/restrict-network-templates/
 * Author:       Happy Prime
 * Author URI:   https://happyprime.co
 * Text Domain:  restrict-network-templates
 * Requires PHP: 7.4
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @package restrict-network-templates
 */

namespace RestrictNetworkTemplates;

add_filter( 'default_template_types', '__return_empty_array' );
add_filter( 'rest_post_dispatch', __NAMESPACE__ . '\filter_wp_template_rest_response', 10, 3 );
add_filter( 'get_block_templates', __NAMESPACE__ . '\filter_block_template_parts', 10, 3 );
add_filter( 'rest_request_before_callbacks', __NAMESPACE__ . '\rest_pre_check', 10, 3 );

/**
 * Filter REST requests for templates to only those the user is able to edit.
 *
 * @param \WP_REST_Respones $response The prepared REST response.
 * @param \WP_REST_Server   $server   The REST server.
 * @param \WP_REST_Request  $request  The REST request.
 * @return \WP_REST_Response The modified REST response.
 */
function filter_wp_template_rest_response( $response, $server, $request ) {
	if ( is_main_site() ) {
		return $response;
	}

	if ( '/wp/v2/templates' === $request->get_route() && 'edit' === $request->get_param( 'context' ) ) {
		$response->data = [];
	}

	return $response;
}

/**
 * Filter block template parts on sub-sites so that network-level template
 * parts are not offered to the user.
 *
 * @param \WP_Block_Template[] $query_result Array of found block templates.
 * @param array                $query {
 *     Optional. Arguments to retrieve templates.
 *
 *     @type array  $slug__in List of slugs to include.
 *     @type int    $wp_id Post ID of customized template.
 * }
 * @param string               $template_type wp_template or wp_template_part.
 * @return \WP_Block_Template[] Modified array of found block templates.
 */
function filter_block_template_parts( $query_result, $query, $template_type ) {
	if ( is_main_site() || 'wp_template_part' !== $template_type ) {
		return $query_result;
	}

	$filtered = [];

	foreach ( $query_result as $template ) {
		if ( 'network-' === substr( $template->slug, 0, 8 ) ) {
			continue;
		}
		$filtered[] = $template;
	}

	return $filtered;
}

/**
 * Prevent a user from saving templates in the site editor.
 *
 * @param mixed            $response Result to send to the client. This is a pre-check, so we expect null.
 * @param array            $handler  Route handler used for the request.
 * @param \WP_REST_Request $request  Request used to generate the response.
 */
function rest_pre_check( $response, $handler, $request ) {
	if ( is_main_site() ) {
		return $response;
	}

	if ( 'GET' === $request->get_method() ) {
		return $response;
	}

	if ( ! is_array( $handler['callback'] ) || ! $handler['callback'][0] instanceof \WP_REST_Templates_Controller ) {
		return $response;
	}

	$route = $request->get_route();

	if ( ! str_starts_with( $route, '/wp/v2/templates' ) ) {
		return $response;
	}

	return new \WP_Error(
		'rest_cannot_manage_templates',
		__( 'Sorry, templates must be managed on the main site.' ),
		array(
			'status' => rest_authorization_required_code(),
		)
	);
}
