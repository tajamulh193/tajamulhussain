#!/usr/bin/env bash
# Loads sample content for the Northfield theme using WP-CLI.
# All names, companies and stories below are fictional sample content.
#
# Usage (from your WordPress folder):  bash seed.sh
# Or with a custom command:            WP="php wp-cli.phar --path=/path/to/wordpress" bash seed.sh
set -euo pipefail
WP=${WP:-wp}

$WP option update blogdescription "Practical advisory for owner-led companies"

# Sectors
for s in "Manufacturing" "Retail" "Professional services"; do
  $WP term create sector "$s" >/dev/null 2>&1 || true
done

# Pages
HOME=$($WP post create --post_type=page --post_title="Home" --post_status=publish --porcelain --post_content="")
ABOUT=$($WP post create --post_type=page --post_title="About" --post_status=publish --porcelain --post_content="<!-- wp:paragraph --><p>Northfield Advisory is a fictional consulting firm used to demonstrate a WordPress theme. The text on this page is sample content.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>How we work</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Every engagement starts with a one-page plan: what we will do, who owns each part and how we will know it worked. We report weekly, in plain language, and we write down what we change so your team can keep it running after we leave.</p><!-- /wp:paragraph --><!-- wp:quote --><blockquote class=\"wp-block-quote\"><p>Good advice is advice a busy team can actually follow on Monday morning.</p></blockquote><!-- /wp:quote --><!-- wp:heading --><h2>What you can expect</h2><!-- /wp:heading --><!-- wp:list --><ul><li>A named lead and a fixed weekly check-in.</li><li>A written handover for every piece of work.</li><li>Fees agreed before work begins.</li></ul><!-- /wp:list -->")
CONTACT=$($WP post create --post_type=page --post_title="Contact" --post_name=contact --post_status=publish --porcelain --post_content="<!-- wp:paragraph --><p>Tell us what you need and when. This form is part of the sample theme: it checks a security token, ignores bots and sends the message by email.</p><!-- /wp:paragraph -->")
$WP post meta update "$CONTACT" _wp_page_template page-contact.php
INSIGHTS=$($WP post create --post_type=page --post_title="Insights" --post_status=publish --porcelain --post_content="")

$WP option update show_on_front page
$WP option update page_on_front "$HOME"
$WP option update page_for_posts "$INSIGHTS"
$WP rewrite structure '/%postname%/' --hard >/dev/null 2>&1 || true

# Case studies
mk_case () {
  local title="$1" sector="$2" excerpt="$3" body="$4"
  local id
  id=$($WP post create --post_type=case_study --post_title="$title" --post_excerpt="$excerpt" --post_status=publish --porcelain --post_content="$body")
  $WP post term set "$id" sector "$sector" --by=slug >/dev/null 2>&1 || $WP post term set "$id" sector "$sector" >/dev/null
}
mk_case "Cutting delivery delays at a regional food producer" "Manufacturing" \
  "A sample engagement: mapping the order-to-delivery flow and fixing the three points where orders stalled." \
  "<!-- wp:heading --><h2>The problem</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Orders were reaching customers later than promised, and nobody could say why. Sales, the warehouse and the drivers each kept their own list.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>The work</h2><!-- /wp:heading --><!-- wp:paragraph --><p>We followed fifty orders from confirmation to delivery, found the three points where orders waited, and replaced the three separate lists with one shared board and a daily fifteen-minute review.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>The result</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Late orders became visible the same day, so the team could act on them. The routine was written down and handed to the operations manager.</p><!-- /wp:paragraph -->"
mk_case "A launch plan for a retail website relaunch" "Retail" \
  "A sample engagement: planning content migration, redirects and sign-off for a store moving to a new website." \
  "<!-- wp:heading --><h2>The problem</h2><!-- /wp:heading --><!-- wp:paragraph --><p>A growing retailer needed to move to a new website without losing search traffic or confusing returning customers.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>The work</h2><!-- /wp:heading --><!-- wp:paragraph --><p>We built a launch plan with owners and dates, audited existing pages, mapped every old address to a new one and ran two rounds of review with the shop team before go-live.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>The result</h2><!-- /wp:heading --><!-- wp:paragraph --><p>The site launched on the planned date with a checklist the team now reuses for campaigns.</p><!-- /wp:paragraph -->"
mk_case "Setting up a project office for a small accountancy" "Professional services" \
  "A sample engagement: lightweight project tracking and weekly reporting for a ten-person firm." \
  "<!-- wp:heading --><h2>The problem</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Client work was tracked in personal spreadsheets, so partners could not see which deadlines were at risk.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>The work</h2><!-- /wp:heading --><!-- wp:paragraph --><p>We introduced one shared tracker, a standard status vocabulary and a Monday summary for the partners.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>The result</h2><!-- /wp:heading --><!-- wp:paragraph --><p>At-risk work now shows up at the start of the week instead of the end.</p><!-- /wp:paragraph -->"

# Posts
$WP post create --post_title="Five questions to ask before you hire a consultant" --post_status=publish --post_excerpt="A short checklist for owners deciding whether outside help is worth it." --post_content="<!-- wp:paragraph --><p>Outside help is worth paying for when the problem is clear and your team lacks the time or the experience to fix it. These questions help you find out which case you are in.</p><!-- /wp:paragraph --><!-- wp:list {\"ordered\":true} --><ol><li>What decision will this work help us make?</li><li>Who inside the company owns the result?</li><li>What will be handed over when it ends?</li><li>How will we know it worked?</li><li>What happens if we do nothing?</li></ol><!-- /wp:list -->" >/dev/null
$WP post create --post_title="Why weekly status reports beat monthly ones" --post_status=publish --post_excerpt="Short, regular updates catch problems while they are still cheap to fix." --post_content="<!-- wp:paragraph --><p>A monthly report tells you what went wrong. A weekly one tells you what is about to. Keep it to one page: what is done, what is next and what is blocked.</p><!-- /wp:paragraph -->" >/dev/null
$WP post create --post_title="A simple way to decide what to do first" --post_status=publish --post_excerpt="Rank work by how much it matters and how hard it is, then start in the top-left." --post_content="<!-- wp:paragraph --><p>List the work. Score each item for impact and effort from one to five. Start with high impact and low effort, and be honest about the rest.</p><!-- /wp:paragraph -->" >/dev/null

# Menus
$WP menu create "Primary" >/dev/null 2>&1 || true
$WP menu item add-post Primary "$HOME" --title="Home" >/dev/null
$WP menu item add-post Primary "$ABOUT" >/dev/null
$WP menu item add-custom Primary "Case studies" "$($WP option get home)/case-studies/" >/dev/null
$WP menu item add-post Primary "$INSIGHTS" >/dev/null
$WP menu item add-post Primary "$CONTACT" >/dev/null
$WP menu location assign Primary primary

$WP menu create "Footer" >/dev/null 2>&1 || true
$WP menu item add-post Footer "$ABOUT" >/dev/null
$WP menu item add-post Footer "$INSIGHTS" >/dev/null
$WP menu item add-post Footer "$CONTACT" >/dev/null
$WP menu location assign Footer footer

# Footer widget (replaces the default widgets WordPress puts in the first sidebar)
$WP widget reset footer-about >/dev/null 2>&1 || true
$WP widget add text footer-about 1 --title="Northfield Advisory" --text="Practical advisory for owner-led companies. Northfield is a fictional firm used to demonstrate a WordPress theme." >/dev/null

$WP rewrite flush --hard >/dev/null 2>&1 || true
echo "Sample content loaded."
