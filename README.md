# Restrict Network Templates

Restrict the use of templates and `network-` prefixed template parts to the main site on a network.

This plugin should be network activated on a multisite network. When activated:

* The list of default template types is filtered to return an empty list.
* The `/wp/v2/templates` endpoint in WordPress returns an empty list.
* Any template part with a filename starting with `network-` is removed from lists of template parts.
* The `WP_REST_Templates_Controller` permissions check is overridden to prevent the update of templates and `network-` prefixed template parts.

This plugin works in tandem with [Network Template Parts](https://github.com/happyprime/network-template-parts) to provide a framework for a shared look and feel of websites on a multisite network.

Activating this plugin **will** impact the usefulness of the full site editor in WordPress and will require thinking about the site in parts rather than full templates. It assumes the main template files in your theme will be built using the blocks provided in [Network Template Parts](https://github.com/happyprime/network-template-parts).
