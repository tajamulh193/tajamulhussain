# Northfield: sample WordPress theme

A custom WordPress theme for a consulting or advisory firm. Built from scratch (no page builder, no starter theme) as a work sample.
**Northfield Advisory is a fictional company and all demo content is sample text.**

## What it includes

- Customizable homepage: hero heading, text, button and link are set in **Appearance > Customize > Homepage**.
- **Case Studies** custom post type with a **Sector** taxonomy, an archive with sector filters and a single template.
- Contact page template with a secure form: nonce check, honeypot field, server-side validation, `wp_mail()` delivery, redirect with a status message.
- Primary and footer menus, a footer widget area, custom logo support, featured images, translation-ready strings.
- Accessible markup: skip link, visible keyboard focus, labelled navigation, mobile menu with `aria-expanded`, reduced-motion support.
- All output escaped with `esc_html()`, `esc_url()` and `esc_attr()`; all input sanitized.

## How it was tested

- Every PHP file passes `php -l`.
- Installed on a real WordPress 7.1.4 site (PHP 8.3), activated, filled with the demo content and every template requested: home, page, contact, blog index, case study archive, sector archive, single case study, 404.
- Contact form tested for four cases: valid message sent, invalid input rejected, bad security token rejected, bot (honeypot) ignored.
- No PHP warnings from the theme in the WordPress debug log.

## Install

1. Zip the `northfield` folder (or use `northfield.zip`).
2. In WordPress: **Appearance > Themes > Add New > Upload Theme**, then activate.
3. Optional demo content: with WP-CLI installed, run `bash demo-content/seed.sh` from your WordPress folder.
4. Set a recipient for the contact form in **Customize > Homepage**. It falls back to the site admin email.

## Folder map

| Path | Purpose |
| --- | --- |
| `northfield/functions.php` | Theme setup, assets, post type, customizer, shortcode, contact handler |
| `northfield/front-page.php` | Homepage |
| `northfield/page-contact.php` | Contact template |
| `northfield/archive-case_study.php`, `single-case_study.php` | Case studies |
| `northfield/template-parts/card.php` | Card used in all grids |
| `demo-content/seed.sh` | WP-CLI script that creates the sample content |
| `preview/` | Static pages exported from the running theme, for viewing without WordPress |
