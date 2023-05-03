<?php
/**
 * Plugin Name:  Restrict Network Templates
 * Description:  Restrict the use of templates to the main site.
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
add_filter( 'rest_request_before_callbacks', __NAMESPACE__ . '\rest_pre_check', 10, 3 );

/**
 * Filter REST requests for templates to include results only on the main site.
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
