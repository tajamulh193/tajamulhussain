# Tujummal Hussain: portfolio

Portfolio of Tujummal Hussain, project manager and web developer (PhD, Finance and Economics) in Islamabad, Pakistan.

- Portfolio: https://tajamulh193.github.io/tajamulhussain/
- WhatsApp: +92 320 5660010
- Business site: https://www.writesbytj.com

## What is in this repository

| Folder | What it is |
| --- | --- |
| `index.html` | The portfolio page (plain HTML, CSS and a little JavaScript) |
| `assets/` | Portrait and project preview images |
| `projects/kiln-street-kitchen` | Sample restaurant website |
| `projects/taskboard` | Sample Kanban web app |
| `projects/northfield-wp-theme` | Sample custom WordPress theme, with a static preview and an installable `.zip` |
| `projects/project-tracker-api` | Sample Node.js REST API with 13 tests |
| `projects/python-status-report` | Sample Python tool with 11 tests |
| `projects/tally-app-design` | Sample mobile app UI design |
| `projects/programme-dashboard` | Sample programme dashboard: earned value, Gantt, RAID log, RACI |
| `projects/business-plan-model` | Sample business plan with a live 36-month financial model |
| `projects/survey-analysis` | Sample Python statistical analysis with 12 tests |
| `projects/beam-analysis` | Sample engineering beam analysis tool |
| `projects/saffron-woo-theme` | Sample WooCommerce block theme, with an interactive preview and an installable `.zip` |
| `projects/slotwise-booking-plugin` | Sample WordPress appointment booking plugin, with a demo, 11 tests and an installable `.zip` |
| `projects/tidewell-saas` | Sample SaaS product website with a live dashboard demo and interactive pricing |
| `projects/halden-moss-architects` | Sample architecture studio website with a sun-path study and floor plan |
| `projects/northbound-travel` | Sample travel website with a map-based trip planner |

All sample projects use fictional businesses and are not client work.

## How the samples are checked

Projects with tests can be checked on your own machine:

- `projects/project-tracker-api`: `npm test` (13 tests)
- `projects/python-status-report`: `python -m unittest` (11 tests)
- `projects/survey-analysis`: `python -m unittest` (12 tests)
- `projects/slotwise-booking-plugin`: `php tests/test-availability.php` (11 tests)

## Publish with GitHub Pages

This repository is `tajamulh193/tajamulhussain`, so GitHub Pages publishes it as a project site at `https://tajamulh193.github.io/tajamulhussain/`.

1. Upload the files into the repository.
2. Open **Settings, Pages**. Under **Build and deployment** choose **Deploy from a branch**, branch **main**, folder **/ (root)**, and save.
3. After about a minute the site is live at the address above.

Every link on the site is relative, so it works under `/tajamulhussain/` without changes.

The Node.js API and the WordPress theme and plugin need a server, which GitHub Pages does not provide. Their entries show the code, tests and a static preview. To add a live link for the API, deploy `projects/project-tracker-api` to a Node host such as Render and link it from the portfolio.
