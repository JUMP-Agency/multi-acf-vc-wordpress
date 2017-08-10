# Multi-Site ACF Component for Visual Composer

A component for Visual Composer plugin which allows you to pull in ACF fields from another blog within your Multi-Site installation. In it's current form it isn't ready to be used purely via the front-end. The 'master' blog ID has to be hard coded in the plugin code. 


This plugin allows you to create ACF fields on one website within a multi-site installation which can then be pulled by any other sub-site. This essentially allows for one source of truth for data, giving you the ability to update content once and have it display on any site referencing that field.


## Requirements

Currently this plugin does require Advanced Custom Fields Pro for it's ability to create options pages. We needed to designate one options page that had multiple tabs, each tab representing an area of content. Each of those tabs then had fields such as "hero content", "fold content", etc. Then we can reference those fields to populate sub-sites.


## Installing / Getting started

To install, clone this repository into the `plugins` folder in your WordPress installation, or download as a zip and upload manually.

```shell
git clone https://github.com/JUMP-Agency/multi-acf-vc-wordpress
```

Next, create an Advanced Custom Fields options page

```php
if ( function_exists('acf_add_options_page') ) {
    acf_add_options_page(array(
        'page_title' 	=> 'Site Content',
        'menu_title'	=> 'Site Content',
        'menu_slug' 	=> 'site-content',
        'capability'	=> 'edit_posts',
        'redirect'		=> false
    ));
}
```

An beneficial additional step is to limit which blogs can access this new options page, so that way it is only editable by your 'master' designated blog. This step is entirely `optional`.

```php
if ( 1 === get_current_blog_id() ) {
    if ( function_exists('acf_add_options_page') ) {
        ...
    }
}
```

With that in place, go into your 'master' blog and start creating your fields. Ensure you set the fields to display `if options page is equal to Site Content`.

Lastly, edit a page with Visual Composer and select the `Advanced Custom Field` component. Your options groups and fields will be displayed in dropdowns.


## Developing

If you would like to contribute, feel free to create a pull request. 


## Versioning

This project uses [SemVer](http://semver.org/) for versioning.


## Tests

There are currently no tests implemented.


## Style guide

Code style follows the [PHPCS WordPress](https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards) standards.


## Licensing

MIT