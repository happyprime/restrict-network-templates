# Restrict Network Templates

Restrict the management of templates to a network's main site.

## Description

This plugin should be network activated on a multisite network. When activated:

* The list of default template types is filtered to return an empty list on every site.
* On sites other than the main site, the `/wp/v2/templates` endpoint returns an empty list in the `edit` context.
* On sites other than the main site, REST requests to create, update, or delete templates are rejected. Template parts are not affected.

This plugin works in tandem with [Network Template Parts](https://github.com/happyprime/network-template-parts) to provide a framework for a shared look and feel of websites on a multisite network.

Activating this plugin **will** impact the usefulness of the full site editor in WordPress and will require thinking about the site in parts rather than full templates.

## Development

```sh
npm install
composer install
npm run env:start
```

`env:start` starts a WordPress 7.1 multisite network at http://localhost:8930 and runs `.dev/seed.php`, which:

* Network-activates the plugin and Twenty Twenty-Five.
* Creates a second site at http://localhost:8930/second/.
* Creates `siteadmin` / `password`, an administrator of the second site who is not a super admin. `admin` / `password` is the super admin.
* Adds "Default template" and "Custom template" pages to both sites. The second uses the theme's "Page No Title" template.

Open the Site Editor on each site to compare: the second site lists no templates and refuses to save one, while template parts still save.

`npm run env:seed` re-runs the seed. `npm run env:stop` stops the containers and `npm run env:destroy` removes them.

Checks:

```sh
composer phpcs
composer phpstan
npm run lint:package
```
