# Tujummal Hussain: portfolio

![Checks](https://github.com/tajamulh193/tajamulhussain/actions/workflows/ci.yml/badge.svg)

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
| `tools/check_links.py` | Checks every link and asset on the site |

All sample projects use fictional businesses and are not client work.

## How the samples are checked

The **Checks** badge above comes from GitHub Actions (`.github/workflows/ci.yml`). On every push it:

1. runs the Node.js API tests,
2. runs the Python tests and builds the sample report,
3. checks every PHP file in the WordPress theme,
4. checks every link, image, script and stylesheet on the site.

A green badge means all of that passed on a clean machine.

## Publish with GitHub Pages

This repository is `tajamulh193/tajamulhussain`, so GitHub Pages publishes it as a project site at `https://tajamulh193.github.io/tajamulhussain/`.

1. Upload everything from the portfolio zip into the repository, including the hidden `.github` folder and the `.nojekyll` file. This `README.md` replaces the short one that is already there.
2. Open **Settings, Pages**. Under **Build and deployment** choose **Deploy from a branch**, branch **main**, folder **/ (root)**, and save.
3. After about a minute the site is live at the address above.
4. Open the **Actions** tab and confirm the Checks run is green.

Every link on the site is relative, so it works under `/tajamulhussain/` without changes.

The Node.js API and the live WordPress theme need a server, which GitHub Pages does not provide. Their entries show the code, tests and a static preview. To add a live link for the API, deploy `projects/project-tracker-api` to a Node host such as Render and link it from the portfolio.
