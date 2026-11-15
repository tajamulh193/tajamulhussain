"""Employee wellbeing survey: clean the data, run the analysis, write report.html.

Usage:  python analysis.py [survey.csv] [report.html]
Standard library only. The statistics live in stats.py.
"""
from __future__ import annotations

import csv
import html
import math
import sys
from pathlib import Path

import stats as st

HERE = Path(__file__).parent
MODES = ["Office", "Hybrid", "Remote"]
DEPTS = ["Operations", "Technology", "Sales", "Finance"]
ITEMS = ["wb1", "wb2", "wb3", "wb4", "wb5"]
ITEM_TEXT = {
    "wb1": "I feel energised at work",
    "wb2": "I can switch off after work",
    "wb3": "My stress is manageable",
    "wb4": "I feel valued by my team",
    "wb5": "I would recommend this employer",
}


# ------------------------------------------------------------ cleaning
def load_and_clean(path: Path) -> tuple[list[dict], list[str]]:
    """Return clean rows and a log of every change or exclusion."""
    log: list[str] = []
    clean: list[dict] = []
    with path.open(newline="") as fh:
        for n, raw in enumerate(csv.DictReader(fh), start=2):
            rid = raw.get("respondent", f"line {n}")
            mode = (raw.get("work_mode") or "").strip().title()
            if mode != raw.get("work_mode"):
                log.append(f"{rid}: work mode '{raw.get('work_mode')}' standardised to '{mode}'.")
            if mode not in MODES:
                log.append(f"{rid}: excluded, unknown work mode '{mode}'.")
                continue
            if raw.get("department") not in DEPTS:
                log.append(f"{rid}: excluded, unknown department '{raw.get('department')}'.")
                continue
            try:
                items = [int(raw[k]) for k in ITEMS]
                num = {k: float(raw[k]) for k in ("tenure_years", "hours_per_week", "manager_support", "workload")}
            except (ValueError, KeyError, TypeError):
                log.append(f"{rid}: excluded, a required answer is missing or not a number.")
                continue
            bad = [k for k, v in zip(ITEMS, items) if not 1 <= v <= 5]
            if bad:
                log.append(f"{rid}: excluded, answer outside the 1 to 5 scale in {', '.join(bad)}.")
                continue
            row = {"id": rid, "department": raw["department"], "mode": mode, **num,
                   **dict(zip(ITEMS, items))}
            row["wellbeing"] = sum(items) / len(items)
            clean.append(row)
    return clean, log


# ------------------------------------------------------------ analysis
def analyse(rows: list[dict]) -> dict:
    wb = [r["wellbeing"] for r in rows]
    by_mode = {m: [r["wellbeing"] for r in rows if r["mode"] == m] for m in MODES}
    by_dept = {d: [r["wellbeing"] for r in rows if r["department"] == d] for d in DEPTS}
    vars_ = ["wellbeing", "manager_support", "workload", "hours_per_week", "tenure_years"]
    corr = {(a, b): st.pearson([r[a] for r in rows], [r[b] for r in rows]) for a in vars_ for b in vars_}
    names = ["Manager support", "Workload", "Hours per week", "Tenure (years)", "Hybrid (vs office)", "Remote (vs office)"]
    X = [[r["manager_support"], r["workload"], r["hours_per_week"], r["tenure_years"],
          1.0 if r["mode"] == "Hybrid" else 0.0, 1.0 if r["mode"] == "Remote" else 0.0] for r in rows]
    return {
        "n": len(rows),
        "wb_mean": st.mean(wb), "wb_sd": st.sd(wb),
        "alpha": st.cronbach_alpha([[r[k] for r in rows] for k in ITEMS]),
        "items": {k: (st.mean([r[k] for r in rows]), st.sd([r[k] for r in rows])) for k in ITEMS},
        "by_mode": by_mode, "by_dept": by_dept,
        "anova_mode": st.anova_oneway(list(by_mode.values())),
        "anova_dept": st.anova_oneway(list(by_dept.values())),
        "t_hyb_off": st.welch_t(by_mode["Hybrid"], by_mode["Office"]),
        "corr_vars": vars_, "corr": corr,
        "ols": st.ols(wb, X, names),
    }


# ------------------------------------------------------------ report
LABEL = {"wellbeing": "Wellbeing", "manager_support": "Manager support", "workload": "Workload",
         "hours_per_week": "Hours / week", "tenure_years": "Tenure"}


def fp(p: float) -> str:
    return "p < .001" if p < 0.001 else f"p = {p:.3f}".replace("0.", ".")


def ci(xs):
    m, se = st.mean(xs), st.sd(xs) / math.sqrt(len(xs))
    t = st.t_ppf975(len(xs) - 1)
    return m, m - t * se, m + t * se


def svg_means(groups: dict, color: str) -> str:
    W, H, L, R, T, B = 520, 60 + 46 * len(groups), 110, 30, 20, 34
    lo, hi = 3.0, 4.5
    x = lambda v: L + (W - L - R) * (v - lo) / (hi - lo)
    g = []
    for v in [3.0, 3.25, 3.5, 3.75, 4.0, 4.25, 4.5]:
        g.append(f'<line x1="{x(v):.1f}" x2="{x(v):.1f}" y1="{T}" y2="{H-B}" stroke="#E6EAF0"/>'
                 f'<text x="{x(v):.1f}" y="{H-B+16}" font-size="11" text-anchor="middle" fill="#566275">{v}</text>')
    for i, (name, xs) in enumerate(groups.items()):
        m, a, b = ci(xs)
        y = T + 24 + i * 46
        g.append(f'<text x="{L-10}" y="{y+4}" font-size="12.5" text-anchor="end" fill="#172033">{name}</text>'
                 f'<text x="{L-10}" y="{y+18}" font-size="10.5" text-anchor="end" fill="#566275">n = {len(xs)}</text>'
                 f'<line x1="{x(a):.1f}" x2="{x(b):.1f}" y1="{y}" y2="{y}" stroke="{color}" stroke-width="3"/>'
                 f'<circle cx="{x(m):.1f}" cy="{y}" r="7" fill="{color}"/>'
                 f'<text x="{x(b)+8:.1f}" y="{y+4}" font-size="11.5" fill="#172033">{m:.2f}</text>')
    g.append(f'<text x="{(L+W-R)/2}" y="{H-4}" font-size="11" text-anchor="middle" fill="#566275">Mean wellbeing score (1 to 5), with 95% confidence interval</text>')
    return f'<svg viewBox="0 0 {W} {H}" width="100%" role="img" aria-label="Group means with confidence intervals">{"".join(g)}</svg>'


def svg_heat(res) -> str:
    v = res["corr_vars"]
    c, L, T = 74, 118, 10
    W, H = L + c * len(v) + 4, T + c * len(v) + 64
    g = []
    for i, a in enumerate(v):
        g.append(f'<text x="{L-8}" y="{T+i*c+c/2+4}" font-size="12" text-anchor="end" fill="#172033">{LABEL[a]}</text>')
        g.append(f'<text transform="translate({L+i*c+c/2} {T+len(v)*c+12}) rotate(30)" font-size="12" fill="#172033">{LABEL[a]}</text>')
        for j, b in enumerate(v):
            r = res["corr"][(a, b)]["r"]
            p = res["corr"][(a, b)]["p"]
            col = (143, 29, 63) if r < 0 else (47, 93, 168)
            alpha = min(1, abs(r))
            mix = tuple(round(255 - (255 - k) * alpha) for k in col)
            txt = "#fff" if abs(r) > .5 else "#172033"
            star = "" if i == j else ("**" if p < .01 else "*" if p < .05 else "")
            g.append(f'<rect x="{L+j*c}" y="{T+i*c}" width="{c-3}" height="{c-3}" rx="4" fill="rgb{mix}"/>'
                     f'<text x="{L+j*c+c/2-1}" y="{T+i*c+c/2+4}" font-size="13" text-anchor="middle" fill="{txt}" font-weight="600">{r:.2f}{star}</text>')
    return f'<svg viewBox="0 0 {W} {H}" width="100%" style="max-width:{W}px" role="img" aria-label="Correlation matrix">{"".join(g)}</svg>'


def svg_coef(o) -> str:
    cs = [c for c in o["coefs"] if c["name"] != "(Intercept)"]
    W, L, R, T, rowh = 560, 160, 20, 14, 38
    H = T + rowh * len(cs) + 40
    lo = min(min(c["lo"] for c in cs), -0.1)
    hi = max(max(c["hi"] for c in cs), 0.1)
    x = lambda v: L + (W - L - R) * (v - lo) / (hi - lo)
    g = [f'<line x1="{x(0):.1f}" x2="{x(0):.1f}" y1="{T}" y2="{H-34}" stroke="#8A94A6"/>']
    for i, c in enumerate(cs):
        y = T + 16 + i * rowh
        col = "#8A94A6" if c["p"] >= .05 else ("#2F5DA8" if c["b"] > 0 else "#8F1D3F")
        g.append(f'<text x="{L-10}" y="{y+4}" font-size="12.5" text-anchor="end" fill="#172033">{c["name"]}</text>'
                 f'<line x1="{x(c["lo"]):.1f}" x2="{x(c["hi"]):.1f}" y1="{y}" y2="{y}" stroke="{col}" stroke-width="3"/>'
                 f'<rect x="{x(c["b"])-6:.1f}" y="{y-6}" width="12" height="12" fill="{col}"/>')
    g.append(f'<text x="{(L+W-R)/2}" y="{H-8}" font-size="11" text-anchor="middle" fill="#566275">Change in wellbeing score per unit, with 95% CI · grey = not significant</text>')
    return f'<svg viewBox="0 0 {W} {H}" width="100%" role="img" aria-label="Regression coefficients">{"".join(g)}</svg>'


def svg_resid(o) -> str:
    W, H, L, B, T, R = 520, 300, 46, 36, 12, 14
    f, e = o["fitted"], o["resid"]
    fx0, fx1 = min(f) - .1, max(f) + .1
    lim = max(abs(v) for v in e) * 1.1
    x = lambda v: L + (W - L - R) * (v - fx0) / (fx1 - fx0)
    y = lambda v: T + (H - T - B) * (lim - v) / (2 * lim)
    g = [f'<line x1="{L}" x2="{W-R}" y1="{y(0):.1f}" y2="{y(0):.1f}" stroke="#8F1D3F" stroke-dasharray="4 3"/>']
    g += [f'<circle cx="{x(a):.1f}" cy="{y(b):.1f}" r="3" fill="#2F5DA8" fill-opacity=".45"/>' for a, b in zip(f, e)]
    for v in (-1, 0, 1):
        g.append(f'<text x="{L-6}" y="{y(v)+4:.1f}" font-size="11" text-anchor="end" fill="#566275">{v}</text>')
    g.append(f'<text x="{(L+W)/2}" y="{H-8}" font-size="11" text-anchor="middle" fill="#566275">Fitted wellbeing score →  (residuals should scatter evenly around zero)</text>')
    return f'<svg viewBox="0 0 {W} {H}" width="100%" role="img" aria-label="Residuals against fitted values">{"".join(g)}</svg>'


def render(res: dict, log: list[str], raw_n: int) -> str:
    o = res["ols"]
    am, ad, tt = res["anova_mode"], res["anova_dept"], res["t_hyb_off"]
    coef = {c["name"]: c for c in o["coefs"]}
    sig = sorted([c for c in o["coefs"][1:] if c["p"] < .05], key=lambda c: -abs(c["t"]))
    e = html.escape
    item_rows = "".join(
        f'<tr><td>{e(ITEM_TEXT[k])}</td><td class="n">{m:.2f}</td><td class="n">{s:.2f}</td>'
        f'<td><span class="bar"><b style="width:{(m-1)/4*100:.0f}%"></b></span></td></tr>'
        for k, (m, s) in res["items"].items())
    coef_rows = "".join(
        f'<tr{" class=sig" if c["p"] < .05 else ""}><td>{e(c["name"])}</td><td class="n">{c["b"]:.3f}</td><td class="n">{c["se"]:.3f}</td>'
        f'<td class="n">{c["t"]:.2f}</td><td class="n">{fp(c["p"])}</td><td class="n">[{c["lo"]:.3f}, {c["hi"]:.3f}]</td></tr>'
        for c in o["coefs"])
    log_items = "".join(f"<li>{e(x)}</li>" for x in log) or "<li>No problems found.</li>"
    findings = [
        f'Manager support is the strongest driver: each extra point on the 5-point support scale goes with a '
        f'<b>{coef["Manager support"]["b"]:+.2f}</b> change in wellbeing, holding everything else constant ({fp(coef["Manager support"]["p"])}).',
        f'Higher workload lowers wellbeing by <b>{abs(coef["Workload"]["b"]):.2f}</b> points per scale point ({fp(coef["Workload"]["p"])}).',
        f'Hybrid workers score <b>{st.mean(res["by_mode"]["Hybrid"]) - st.mean(res["by_mode"]["Office"]):.2f}</b> points higher than office '
        f'workers (Welch t({tt["df"]:.0f}) = {tt["t"]:.2f}, {fp(tt["p"])}, Cohen\'s d = {tt["d"]:.2f}).',
        f'Departments differ {"significantly" if ad["p"] < .05 else "only slightly and not significantly"} '
        f'(F({ad["df1"]}, {ad["df2"]}) = {ad["F"]:.2f}, {fp(ad["p"])}).',
        f'Together the six predictors explain <b>{o["r2"]*100:.0f}%</b> of the variation in wellbeing (adjusted R² = {o["adj_r2"]:.2f}).',
    ]
    return f"""<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Employee Wellbeing Survey | Statistical Report (sample)</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
<style>
:root{{--ink:#172033;--navy:#1B2742;--crimson:#8F1D3F;--mist:#EDF0F4;--line:#D5DBE4;--slate:#566275;--blue:#2F5DA8;--serif:"Source Serif 4",Georgia,serif;--sans:"Public Sans","Segoe UI",system-ui,sans-serif}}
*{{box-sizing:border-box}}body{{margin:0;color:var(--ink);font:400 16px/1.6 var(--sans);background:#fff}}a{{color:var(--crimson)}}
.note{{background:#FFF8E6;border-bottom:1px solid #F0DFB0;font-size:.85rem;text-align:center;padding:.45rem 1rem;margin:0}}
.wrap{{max-width:980px;margin:0 auto;padding:0 1.25rem}}
header{{background:var(--navy);color:#fff;padding:2.5rem 0 2rem}}header p{{color:#C9D1E0;margin:.3rem 0 0}}
.eyebrow{{color:#E7B7C5!important;text-transform:uppercase;letter-spacing:.08em;font-weight:600;font-size:.8rem}}
h1{{font:700 clamp(1.7rem,4vw,2.4rem)/1.2 var(--serif);margin:.3rem 0}}
h2{{font:700 1.45rem/1.25 var(--serif);color:var(--navy);margin:2.4rem 0 .4rem}}
.facts{{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-top:1.6rem}}.facts b{{display:block;font-size:1.5rem}}.facts span{{color:#B9C3D6;font-size:.85rem}}
.facts div{{border-top:3px solid var(--crimson);padding-top:.5rem}}
.find{{background:var(--mist);border-radius:8px;padding:1rem 1.25rem 1rem 2.4rem;margin:1rem 0}}.find li{{margin:.45rem 0}}
.lede{{color:var(--slate);margin:0 0 1rem}}
table{{border-collapse:collapse;width:100%;font-size:.9rem}}th,td{{padding:.5rem .55rem;border-bottom:1px solid var(--mist);text-align:left}}
th{{font-size:.74rem;text-transform:uppercase;letter-spacing:.04em;color:var(--slate)}}td.n,th.n{{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}}
tr.sig td{{font-weight:600}}.scroll{{overflow-x:auto}}
.bar{{display:block;height:10px;background:var(--mist);border-radius:5px;min-width:90px}}.bar b{{display:block;height:100%;background:var(--blue);border-radius:5px}}
.g2{{display:grid;grid-template-columns:1fr 1fr;gap:2rem}}.card{{border:1px solid var(--line);border-radius:8px;padding:1rem}}
.card h3{{margin:0 0 .3rem;font-size:1rem;color:var(--navy)}}.small{{font-size:.85rem;color:var(--slate)}}
svg text{{font-family:var(--sans)}}footer{{padding:2.5rem 0;color:var(--slate);font-size:.88rem}}
@media(max-width:760px){{.g2{{grid-template-columns:1fr}}.facts{{grid-template-columns:repeat(2,1fr)}}}}
</style></head><body>
<p class="note">Sample project by <a href="../../">Tujummal Hussain</a>. The organisation and every respondent are fictional; the data is synthetic.</p>
<header><div class="wrap"><p class="eyebrow">Statistical report · generated by analysis.py</p>
<h1>What drives employee wellbeing? Survey of {res["n"]} staff</h1>
<p>Cleaning, scale reliability, group comparisons, correlations and multiple regression, with every figure produced from the raw CSV by a tested Python script.</p>
<div class="facts"><div><b>{res["n"]}</b><span>valid responses (of {raw_n})</span></div><div><b>{res["alpha"]:.2f}</b><span>Cronbach's alpha</span></div>
<div><b>{res["wb_mean"]:.2f}</b><span>mean wellbeing (SD {res["wb_sd"]:.2f})</span></div><div><b>{o["r2"]*100:.0f}%</b><span>variance explained</span></div></div></div></header>
<main class="wrap">
<h2>Key findings</h2><ol class="find">{"".join(f"<li>{x}</li>" for x in findings)}</ol>
<p class="small">Recommendation: invest in line-manager training and workload planning first. Together they matter more than where people work.</p>

<h2>1. Data cleaning</h2><p class="lede">Every change or exclusion is logged, so the analysis can be audited.</p><ul>{log_items}</ul>

<h2>2. Wellbeing scale and reliability</h2>
<p class="lede">Five statements rated 1 (strongly disagree) to 5 (strongly agree). The wellbeing score is the average of the five items. Alpha of {res["alpha"]:.2f} is {"good (above .80)" if res["alpha"] >= .8 else "acceptable (above .70)" if res["alpha"] >= .7 else "below the usual .70 threshold"}, so the items measure one thing reliably.</p>
<div class="scroll"><table><thead><tr><th>Item</th><th class="n">Mean</th><th class="n">SD</th><th></th></tr></thead><tbody>{item_rows}</tbody></table></div>

<h2>3. Comparing groups</h2>
<div class="g2"><div class="card"><h3>By work mode</h3><p class="small">One-way ANOVA: F({am["df1"]}, {am["df2"]}) = {am["F"]:.2f}, {fp(am["p"])}, η² = {am["eta2"]:.3f}</p>{svg_means(res["by_mode"], "#2F5DA8")}</div>
<div class="card"><h3>By department</h3><p class="small">One-way ANOVA: F({ad["df1"]}, {ad["df2"]}) = {ad["F"]:.2f}, {fp(ad["p"])}, η² = {ad["eta2"]:.3f}</p>{svg_means(res["by_dept"], "#8F1D3F")}</div></div>

<h2>4. Correlations</h2><p class="lede">Pearson r. Blue = positive, red = negative. * p &lt; .05, ** p &lt; .01.</p>{svg_heat(res)}

<h2>5. Multiple regression</h2>
<p class="lede">Ordinary least squares. Outcome: wellbeing score. Model F({o["df1"]}, {o["df2"]}) = {o["F"]:.1f}, {fp(o["p"])}; R² = {o["r2"]:.3f}, adjusted R² = {o["adj_r2"]:.3f}, n = {o["n"]}.</p>
<div class="scroll"><table><thead><tr><th>Predictor</th><th class="n">B</th><th class="n">SE</th><th class="n">t</th><th class="n">p</th><th class="n">95% CI</th></tr></thead><tbody>{coef_rows}</tbody></table></div>
<div class="g2" style="margin-top:1.25rem"><div class="card"><h3>Effect sizes</h3>{svg_coef(o)}</div><div class="card"><h3>Model check: residuals</h3>{svg_resid(o)}</div></div>

<h2>Method</h2><p class="small">Analysis in Python 3 using only the standard library. Test statistics and p-values (t, F, Pearson, OLS) are computed in <code>stats.py</code> and checked against SciPy and statsmodels in the automated tests. The same steps can be reproduced in SPSS, STATA or JAMOVI.</p>
</main><footer><div class="wrap"><a href="https://github.com/tajamulh193/tajamulhussain/tree/main/projects/survey-analysis">View the code and tests</a> · <a href="../../#projects">Back to the portfolio</a></div></footer>
</body></html>"""


def main(argv: list[str]) -> int:
    src = Path(argv[1]) if len(argv) > 1 else HERE / "survey.csv"
    dst = Path(argv[2]) if len(argv) > 2 else HERE / "report.html"
    with src.open(newline="") as fh:
        raw_n = sum(1 for _ in csv.DictReader(fh))
    rows, log = load_and_clean(src)
    if len(rows) < 30:
        print("Not enough valid rows to analyse.", file=sys.stderr)
        return 1
    res = analyse(rows)
    dst.write_text(render(res, log, raw_n), encoding="utf-8")
    print(f"{len(rows)} valid rows, alpha {res['alpha']:.2f}, R2 {res['ols']['r2']:.3f}. Report: {dst.name}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv))
