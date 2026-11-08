import tempfile
import unittest
from datetime import date
from pathlib import Path

import statusreport as sr

TODAY = date(2026, 11, 9)


def write_csv(text: str) -> Path:
    f = tempfile.NamedTemporaryFile("w", suffix=".csv", delete=False, encoding="utf-8")
    f.write(text)
    f.close()
    return Path(f.name)


HEADER = "id,task,owner,status,due\n"


class LoadTests(unittest.TestCase):
    def test_loads_valid_rows(self):
        tasks = sr.load_tasks(write_csv(HEADER + "1,Plan,Amna,Done,2026-11-01\n2,Build,,In progress,2026-11-20\n"))
        self.assertEqual(len(tasks), 2)
        self.assertEqual(tasks[1].owner, "Unassigned")

    def test_bad_status_names_the_row(self):
        with self.assertRaisesRegex(ValueError, "Row 3: status 'Finished'"):
            sr.load_tasks(write_csv(HEADER + "1,A,x,Done,2026-11-01\n2,B,x,Finished,2026-11-01\n"))

    def test_bad_date(self):
        with self.assertRaisesRegex(ValueError, "Row 2: due date"):
            sr.load_tasks(write_csv(HEADER + "1,A,x,Done,31/12/2026\n"))

    def test_missing_column(self):
        with self.assertRaisesRegex(ValueError, "missing these columns: due"):
            sr.load_tasks(write_csv("id,task,owner,status\n1,A,x,Done\n"))

    def test_empty_file(self):
        with self.assertRaisesRegex(ValueError, "no tasks"):
            sr.load_tasks(write_csv(HEADER))


class MetricTests(unittest.TestCase):
    def setUp(self):
        self.tasks = sr.load_tasks(Path(__file__).resolve().parent.parent / "tasks.csv")

    def test_counts(self):
        m = sr.compute_metrics(self.tasks, TODAY)
        self.assertEqual(m["total"], 12)
        self.assertEqual(m["by_status"], {"Not started": 4, "In progress": 3, "Blocked": 2, "Done": 3})
        self.assertEqual(m["percent_done"], 25)

    def test_overdue_excludes_done_and_is_sorted(self):
        m = sr.compute_metrics(self.tasks, TODAY)
        self.assertEqual([t.id for t in m["overdue"]], ["11", "7"])

    def test_due_soon_window(self):
        m = sr.compute_metrics(self.tasks, TODAY)
        self.assertEqual([t.id for t in m["due_soon"]], ["4", "5", "6"])

    def test_rag_rules(self):
        self.assertEqual(sr.rag_rating(10, 0, 0), "Green")
        self.assertEqual(sr.rag_rating(10, 1, 0), "Amber")
        self.assertEqual(sr.rag_rating(10, 2, 0), "Red")   # 20% overdue
        self.assertEqual(sr.rag_rating(30, 0, 3), "Red")   # three blocked
        self.assertEqual(sr.rag_rating(10, 0, 1), "Amber")


class RenderTests(unittest.TestCase):
    def test_html_escapes_task_text(self):
        t = [sr.Task("1", "<script>alert(1)</script>", "A&B", "Blocked", date(2026, 11, 1))]
        out = sr.render_html(sr.compute_metrics(t, TODAY), "Title <b>", TODAY)
        self.assertNotIn("<script>alert(1)</script>", out)
        self.assertIn("&lt;script&gt;", out)
        self.assertIn("A&amp;B", out)

    def test_cli_writes_file_and_reports_errors(self):
        out = Path(tempfile.mkdtemp()) / "r.html"
        code = sr.main([str(Path(__file__).resolve().parent.parent / "tasks.csv"), "-o", str(out), "--today", "2026-11-09"])
        self.assertEqual(code, 0)
        self.assertIn("Status report for 09 November 2026", out.read_text(encoding="utf-8"))
        self.assertEqual(sr.main([str(out.parent / "missing.csv")]), 1)
        self.assertEqual(sr.main(["x.csv", "--today", "nope"]), 2)


if __name__ == "__main__":
    unittest.main()
