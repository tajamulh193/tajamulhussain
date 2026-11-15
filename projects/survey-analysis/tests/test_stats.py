"""Checks stats.py and analysis.py against reference values from SciPy 1.x and statsmodels.

The reference numbers were produced once with SciPy/statsmodels and are pasted
here, so the tests themselves need only the Python standard library.
Run from the project folder:  python -m unittest -v
"""
import csv
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
import analysis as A  # noqa: E402
import stats as st  # noqa: E402

ROWS, LOG = A.load_and_clean(A.HERE / "survey.csv")
RES = A.analyse(ROWS)


class Distributions(unittest.TestCase):
    def test_t_two_sided_p(self):  # scipy: 2 * t.sf(2.1, 7)
        self.assertAlmostEqual(st.t_sf2(2.1, 7), 0.07387119621292265, places=12)

    def test_f_upper_tail_p(self):  # scipy: f.sf(3.3, 2, 50)
        self.assertAlmostEqual(st.f_sf(3.3, 2, 50), 0.04506499515321374, places=12)

    def test_t_critical_value(self):  # scipy: t.ppf(.975, 20)
        self.assertAlmostEqual(st.t_ppf975(20), 2.085963447265864, places=9)


class Tests(unittest.TestCase):
    def test_welch_matches_scipy(self):
        t = RES["t_hyb_off"]
        self.assertAlmostEqual(t["t"], 7.135219516131504, places=10)
        self.assertAlmostEqual(t["df"], 326.8127539176392, places=8)
        self.assertAlmostEqual(t["p"] / 6.269659166427931e-12, 1, places=8)

    def test_anova_matches_scipy(self):
        a = RES["anova_mode"]
        self.assertAlmostEqual(a["F"], 27.254521716806913, places=10)
        self.assertAlmostEqual(a["p"] / 7.565854544079872e-12, 1, places=8)

    def test_pearson_matches_scipy(self):
        c = RES["corr"][("wellbeing", "workload")]
        self.assertAlmostEqual(c["r"], -0.37858077187417916, places=12)
        self.assertAlmostEqual(c["p"] / 1.0811509867857212e-15, 1, places=8)

    def test_alpha_known_example(self):
        # three items, perfectly parallel apart from a constant: alpha = 1
        self.assertAlmostEqual(st.cronbach_alpha([[1, 2, 3, 4], [2, 3, 4, 5], [1, 2, 3, 4]]), 1.0)
        self.assertAlmostEqual(RES["alpha"], 0.8154364044301599, places=12)


class Regression(unittest.TestCase):
    REF_B = [3.16722954, 0.33194255, -0.31239352, 0.00519692, 0.01286193, 0.36622383, 0.06053298]
    REF_SE = [0.2402745, 0.03506959, 0.03720629, 0.00492561, 0.00460002, 0.05753475, 0.06808513]

    def test_coefficients_and_se_match_statsmodels(self):
        for c, b, se in zip(RES["ols"]["coefs"], self.REF_B, self.REF_SE):
            self.assertAlmostEqual(c["b"], b, places=7)
            self.assertAlmostEqual(c["se"], se, places=7)

    def test_fit_statistics_match_statsmodels(self):
        o = RES["ols"]
        self.assertAlmostEqual(o["r2"], 0.397857801470477, places=12)
        self.assertAlmostEqual(o["adj_r2"], 0.3890674044116519, places=12)
        self.assertAlmostEqual(o["F"], 45.26050402592976, places=9)
        self.assertAlmostEqual(o["coefs"][1]["lo"], 0.2630044, places=6)
        self.assertAlmostEqual(o["coefs"][1]["hi"], 0.40088069, places=6)

    def test_collinear_predictors_are_rejected(self):
        with self.assertRaises(ValueError):
            st.ols([1, 2, 3, 4], [[1, 2], [2, 4], [3, 6], [4, 8]], ["a", "b"])


class Cleaning(unittest.TestCase):
    def test_bad_rows_are_logged_and_excluded(self):
        self.assertEqual(len(ROWS), 418)
        joined = " ".join(LOG)
        self.assertIn("R018: excluded", joined)
        self.assertIn("R089: excluded, answer outside the 1 to 5 scale in wb3", joined)
        self.assertIn("R204: work mode 'hybrid ' standardised to 'Hybrid'", joined)

    def test_report_escapes_text_and_builds(self):
        with tempfile.TemporaryDirectory() as d:
            src = Path(d) / "s.csv"
            with (A.HERE / "survey.csv").open() as fh:
                rows = list(csv.DictReader(fh))
            rows[0]["work_mode"] = "<script>"
            with src.open("w", newline="") as fh:
                w = csv.DictWriter(fh, fieldnames=list(rows[0]))
                w.writeheader()
                w.writerows(rows)
            out = Path(d) / "r.html"
            self.assertEqual(A.main(["x", str(src), str(out)]), 0)
            html = out.read_text()
            self.assertNotIn("<script>", html)
            self.assertIn("&lt;Script&gt;", html)


if __name__ == "__main__":
    unittest.main()
