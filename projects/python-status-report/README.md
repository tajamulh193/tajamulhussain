# Status report generator

A Python command-line tool that turns a project task list (CSV) into a one-page HTML status report with a Red/Amber/Green rating, progress charts, and lists of overdue, blocked and upcoming work. Built as a work sample. Standard library only, so there is nothing to install.

**See the result:** open `sample-report.html` (made from `tasks.csv`, a fictional project).

## Use it

```bash
python statusreport.py tasks.csv -o report.html --title "Website relaunch"
python -m unittest discover        # 11 tests
```

CSV columns: `id, task, owner, status, due`. Status is one of `Not started`, `In progress`, `Blocked`, `Done`. Due is `YYYY-MM-DD`. Use `--today 2026-11-09` to produce a report for a specific date.

## How the rating works

- **Red:** 20% or more of tasks are overdue, or three or more are blocked.
- **Amber:** anything is overdue or blocked.
- **Green:** nothing is overdue or blocked.

## Design notes

- Bad input gives a clear message with the row number (for example `Row 7: status 'Finished' is not one of ...`) and a non-zero exit code, instead of a stack trace.
- All text from the CSV is HTML-escaped before it is written into the report.
- Charts are inline SVG, so the report is a single file that can be emailed or printed.
