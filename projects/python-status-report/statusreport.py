#!/usr/bin/env python3
"""Turn a project task list (CSV) into a one-page HTML status report.

Usage:
    python statusreport.py tasks.csv -o report.html --title "Website relaunch"

Only the Python standard library is used.
"""
from __future__ import annotations

import argparse
import csv
import html
import sys
from collections import Counter, OrderedDict
from dataclasses import dataclass
from datetime import date, timedelta
from pathlib import Path

STATUSES = ("Not started", "In progress", "Blocked", "Done")
COLUMNS = ("id", "task", "owner", "status", "due")
COLORS = {"Not started": "#9AA6B8", "In progress": "#2F5FD0", "Blocked": "#C0392B", "Done": "#2E7D4F"}


@dataclass(frozen=True)
class Task:
    id: str
    title: str
    owner: str
    status: str
    due: date

    def is_overdue(self, today: date) -> bool:
        return self.status != "Done" and self.due < today


def load_tasks(path: str | Path) -> list[Task]:
    """Read tasks from a CSV file. Raises ValueError naming the row and problem."""
    tasks: list[Task] = []
    with open(path, newline="", encoding="utf-8-sig") as fh:
        reader = csv.DictReader(fh)
        missing = [c for c in COLUMNS if c not in (reader.fieldnames or [])]
        if missing:
            raise ValueError(f"The CSV is missing these columns: {', '.join(missing)}.")
        for line, row in enumerate(reader, start=2):  # line 1 is the header
            status = (row["status"] or "").strip()
            if status not in STATUSES:
                raise ValueError(f"Row {line}: status '{status}' is not one of {', '.join(STATUSES)}.")
            try:
                due = date.fromisoformat((row["due"] or "").strip())
            except ValueError:
                raise ValueError(f"Row {line}: due date '{row['due']}' must look like 2026-12-31.") from None
            title = (row["task"] or "").strip()
            if not title:
                raise ValueError(f"Row {line}: the task name is empty.")
            tasks.append(Task((row["id"] or "").strip(), title, (row["owner"] or "").strip() or "Unassigned", status, due))
    if not tasks:
        raise ValueError("The CSV has no tasks.")
    return tasks


def compute_metrics(tasks: list[Task], today: date) -> dict:
    total = len(tasks)
    by_status = Counter(t.status for t in tasks)
    overdue = sorted((t for t in tasks if t.is_overdue(today)), key=lambda t: t.due)
    soon_end = today + timedelta(days=7)
    due_soon = sorted((t for t in tasks if t.status != "Done" and today <= t.due <= soon_end), key=lambda t: t.due)
    blocked = [t for t in tasks if t.status == "Blocked"]

    owners: "OrderedDict[str, Counter]" = OrderedDict()
    for t in sorted(tasks, key=lambda t: t.owner):
        owners.setdefault(t.owner, Counter())[t.status] += 1

    return {
        "total": total,
        "by_status": {s: by_status.get(s, 0) for s in STATUSES},
        "percent_done": round(by_status.get("Done", 0) / total * 100),
        "overdue": overdue,
        "due_soon": due_soon,
        "blocked": blocked,
        "owners": owners,
        "rag": rag_rating(total, len(overdue), len(blocked)),
    }


def rag_rating(total: int, overdue: int, blocked: int) -> str:
    """Red: 20% or more of tasks overdue, or 3+ blocked. Amber: anything overdue or blocked. Green otherwise."""
    if total and (overdue / total >= 0.2 or blocked >= 3):
        return "Red"
    if overdue or blocked:
        return "Amber"
    return "Green"


def _e(value: object) -> str:
    return html.escape(str(value), quote=True)


def _bar_chart(by_status: dict[str, int]) -> str:
    top = max(by_status.values()) or 1
    rows = []
    for i, status in enumerate(STATUSES):
        n = by_status[status]
        y = i * 34
        w = round(n / top * 300)
        rows.append(
            f'<text x="0" y="{y + 18}" font-size="14" fill="#16202E">{_e(status)}</text>'
            f'<rect x="110" y="{y + 4}" width="{max(w, 2)}" height="20" rx="3" fill="{COLORS[status]}"/>'
            f'<text x="{116 + max(w, 2)}" y="{y + 19}" font-size="14" font-weight="600" fill="#16202E">{n}</text>'
        )
    return f'<svg viewBox="0 0 460 {len(STATUSES) * 34}" role="img" aria-label="Tasks by status">{"".join(rows)}</svg>'


def _owner_chart(owners: dict) -> str:
    rows = []
    for i, (owner, counts) in enumerate(owners.items()):
        total = sum(counts.values())
        y = i * 34
        x = 110.0
        segs = []
        for status in STATUSES:
            n = counts.get(status, 0)
            if not n:
                continue
            w = n / 8 * 300  # fixed scale: 8 tasks fill the chart
            segs.append(f'<rect x="{x:.1f}" y="{y + 4}" width="{w:.1f}" height="20" fill="{COLORS[status]}"><title>{_e(status)}: {n}</title></rect>')
            x += w
        rows.append(f'<text x="0" y="{y + 19}" font-size="14" fill="#16202E">{_e(owner)}</text>{"".join(segs)}<text x="{x + 6:.1f}" y="{y + 19}" font-size="14" font-weight="600" fill="#16202E">{total}</text>')
    return f'<svg viewBox="0 0 460 {max(len(owners), 1) * 34}" role="img" aria-label="Tasks by owner and status">{"".join(rows)}</svg>'


def _table(tasks: list[Task], empty: str, today: date) -> str:
    if not tasks:
        return f'<p class="none">{_e(empty)}</p>'
    body = "".join(
        f"<tr><td>{_e(t.title)}</td><td>{_e(t.owner)}</td><td>{_e(t.status)}</td>"
        f'<td class="num">{_e(t.due.isoformat())}</td><td class="num">{_e(_relative(t.due, today))}</td></tr>'
        for t in tasks
    )
    return f'<div class="scroll"><table><thead><tr><th>Task</th><th>Owner</th><th>Status</th><th>Due</th><th>When</th></tr></thead><tbody>{body}</tbody></table></div>'


def _relative(due: date, today: date) -> str:
    d = (due - today).days
    if d == 0:
        return "today"
    return f"in {d} day{'s' if d != 1 else ''}" if d > 0 else f"{-d} day{'s' if d != -1 else ''} late"


def render_html(metrics: dict, title: str, today: date) -> str:
    rag = metrics["rag"]
    rag_color = {"Green": "#2E7D4F", "Amber": "#B7791F", "Red": "#C0392B"}[rag]
    s = metrics["by_status"]
    summary = (
        f"{metrics['percent_done']}% of {metrics['total']} tasks are done. "
        f"{len(metrics['overdue'])} overdue, {len(metrics['blocked'])} blocked, {len(metrics['due_soon'])} due in the next 7 days."
    )
    return f"""<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<link rel="icon" href="data:,">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{_e(title)} | Status report</title>
<style>
body{{margin:0;background:#F4F6F9;color:#16202E;font:16px/1.5 system-ui,-apple-system,"Segoe UI",Arial,sans-serif}}
.note{{background:#16202E;color:#fff;font-size:.85rem;text-align:center;padding:.4rem 1rem}}
main{{max-width:960px;margin:0 auto;padding:1.5rem 1.25rem 3rem}}
h1{{margin:0;font-size:1.8rem}} .date{{color:#5B6678;margin:.15rem 0 1.25rem}}
.head{{display:flex;gap:1rem;align-items:center;flex-wrap:wrap;background:#fff;border:1px solid #D3D9E2;border-left:8px solid {rag_color};border-radius:10px;padding:1rem 1.25rem}}
.rag{{font-weight:700;color:#fff;background:{rag_color};border-radius:6px;padding:.25rem .8rem}}
.cards{{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.75rem;margin:1rem 0}}
.card{{background:#fff;border:1px solid #D3D9E2;border-radius:10px;padding:.8rem 1rem}}
.card b{{display:block;font-size:1.8rem;line-height:1.1}} .card span{{color:#5B6678;font-size:.9rem}}
section{{background:#fff;border:1px solid #D3D9E2;border-radius:10px;padding:1rem 1.25rem;margin-top:1rem}}
h2{{font-size:1.1rem;margin:0 0 .75rem}}
.charts{{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(300px,100%),1fr));gap:1rem}}
svg{{width:100%;height:auto}}
table{{border-collapse:collapse;width:100%;font-size:.95rem}}
th,td{{text-align:left;padding:.45rem .5rem;border-bottom:1px solid #E1E6EE}} th{{color:#5B6678;font-weight:600}}
td.num{{white-space:nowrap;font-variant-numeric:tabular-nums}}
.none{{color:#5B6678;margin:0}}
.scroll{{overflow-x:auto}}
main{{overflow-wrap:anywhere}}
.legend{{display:flex;gap:1rem;flex-wrap:wrap;font-size:.85rem;color:#5B6678;margin-top:.5rem}}
.legend i{{display:inline-block;width:.8rem;height:.8rem;border-radius:2px;margin-right:.3rem;vertical-align:-1px}}
</style></head><body>
<div class="note">Sample output of a Python tool by Tujummal Hussain. The project and people are fictional.</div>
<main>
<h1>{_e(title)}</h1>
<p class="date">Status report for {_e(today.strftime('%d %B %Y'))}</p>
<div class="head"><span class="rag">{_e(rag)}</span><span>{_e(summary)}</span></div>
<div class="cards">
<div class="card"><b>{metrics['percent_done']}%</b><span>Complete</span></div>
<div class="card"><b>{s['In progress']}</b><span>In progress</span></div>
<div class="card"><b>{len(metrics['blocked'])}</b><span>Blocked</span></div>
<div class="card"><b>{len(metrics['overdue'])}</b><span>Overdue</span></div>
</div>
<section><h2>Progress</h2><div class="charts"><div>{_bar_chart(s)}</div><div>{_owner_chart(metrics['owners'])}
<div class="legend">{''.join(f'<span><i style="background:{COLORS[x]}"></i>{_e(x)}</span>' for x in STATUSES)}</div></div></div></section>
<section><h2>Overdue</h2>{_table(metrics['overdue'], 'Nothing is overdue.', today)}</section>
<section><h2>Blocked</h2>{_table(metrics['blocked'], 'Nothing is blocked.', today)}</section>
<section><h2>Due in the next 7 days</h2>{_table(metrics['due_soon'], 'Nothing is due this week.', today)}</section>
</main></body></html>
"""


def main(argv: list[str] | None = None) -> int:
    p = argparse.ArgumentParser(description="Create an HTML status report from a CSV task list.")
    p.add_argument("csv", help="CSV with columns: id, task, owner, status, due (YYYY-MM-DD)")
    p.add_argument("-o", "--output", default="report.html", help="HTML file to write (default: report.html)")
    p.add_argument("--title", default="Project status", help="Report title")
    p.add_argument("--today", help="Report date as YYYY-MM-DD (default: today)")
    args = p.parse_args(argv)

    try:
        today = date.fromisoformat(args.today) if args.today else date.today()
    except ValueError:
        print("error: --today must look like 2026-12-31", file=sys.stderr)
        return 2
    try:
        tasks = load_tasks(args.csv)
    except (OSError, ValueError) as err:
        print(f"error: {err}", file=sys.stderr)
        return 1

    metrics = compute_metrics(tasks, today)
    Path(args.output).write_text(render_html(metrics, args.title, today), encoding="utf-8")
    print(f"Wrote {args.output}: {metrics['rag']} status, {metrics['percent_done']}% done, {len(metrics['overdue'])} overdue.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
