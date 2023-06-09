# Restrict Network Templates

Restrict the management of templates to a network's main site.

## Description

This plugin should be network activated on a multisite network. When activated:

* The list of default template types is filtered to return an empty list.
* The `/wp/v2/templates` endpoint in WordPress returns an empty list.
* The `WP_REST_Templates_Controller` permissions check is overridden to prevent the update of templates outside of the main site.

This plugin works in tandem with [Network Template Parts](https://github.com/happyprime/network-template-parts) to provide a framework for a shared look and feel of websites on a multisite network.

Activating this plugin **will** impact the usefulness of the full site editor in WordPress and will require thinking about the site in parts rather than full templates.
