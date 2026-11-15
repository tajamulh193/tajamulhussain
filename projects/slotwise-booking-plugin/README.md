# Slotwise Appointments: WordPress booking plugin (sample)

Online appointment booking for clinics, salons and consultants.

**[Open the interactive demo](https://tajamulh193.github.io/tajamulhussain/projects/slotwise-booking-plugin/demo/)** · **[Download slotwise.zip](slotwise.zip)**

## Features

- Services with their own length and price, managed as a custom post type.
- Opening days and hours, start-time interval, buffer between appointments and a booking window, on a settings page.
- `[slotwise_booking]` shortcode: the form asks the REST API (`/wp-json/slotwise/v1/slots`) for free times and only offers those.
- Double-booking protection: the server checks the slot again, inside a short lock, just before saving.
- Security: REST nonce, honeypot field, rate limit per visitor, sanitised input and escaped output.
- Email confirmation to the client and a notice to the business; one-click **Confirm** in the admin list.
- Clean uninstall that removes its options and posts.

## Tests

```bash
php tests/test-availability.php   # 11 tests, runs without WordPress
```

The tests cover slot generation, overlaps, buffers, closed days, past and malformed dates, and the booking window.
Every PHP file also passes `php -l`. The demo page runs the same slot logic in the browser so it works on GitHub Pages.
The plugin has not yet been installed on a live WordPress site.
