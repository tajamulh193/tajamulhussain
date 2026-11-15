=== Slotwise Appointments ===
Contributors: tujummalhussain
Tags: booking, appointments, calendar, clinic, salon
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Online appointment booking for clinics, salons and consultants.

== Description ==
* Services with their own duration and price label.
* Opening days and hours, start-time interval, buffer between appointments and booking window.
* Live availability: the form only offers times that are free for the chosen service.
* Double-booking protection: the slot is checked again on the server just before saving.
* Security: REST nonce, honeypot field, rate limiting per visitor, all input sanitised and output escaped.
* Email confirmation to the client and a notice to the business.
* One-click "Confirm" for pending bookings in the admin list.

== Installation ==
1. Upload the slotwise folder to /wp-content/plugins/ and activate it.
2. Go to Appointments > Settings and set your hours.
3. Add services under Appointments > Services.
4. Put [slotwise_booking] on any page.

== Changelog ==
= 1.0.0 =
* First release.
