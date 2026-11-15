"""Create the synthetic survey used by the sample (fixed seed, so it is repeatable).

Fictional organisation, invented respondents. Run: python make_data.py
"""
import csv
import random
from pathlib import Path

rng = random.Random(2026)
DEPTS = ["Operations", "Technology", "Sales", "Finance"]
MODES = ["Office", "Hybrid", "Remote"]


def likert(v: float) -> int:
    return max(1, min(5, round(v)))


rows = []
for i in range(1, 421):
    dept = rng.choices(DEPTS, weights=[34, 26, 24, 16])[0]
    mode = rng.choices(MODES, weights=[38, 42, 20])[0]
    tenure = round(min(25, rng.expovariate(1 / 5.5)), 1)
    hours = round(rng.gauss(44 + (4 if dept == "Sales" else 0) - (2 if mode == "Hybrid" else 0), 5.5))
    support = rng.gauss(3.4 + (0.25 if mode == "Hybrid" else 0) - (0.2 if dept == "Operations" else 0), 0.75)
    workload = rng.gauss(3.2 + 0.06 * (hours - 44), 0.7)
    true_wb = (2.2 + 0.42 * support - 0.33 * workload + {"Office": 0, "Hybrid": 0.32, "Remote": 0.12}[mode]
               + 0.012 * tenure + rng.gauss(0, 0.45))
    items = [likert(true_wb + 1.0 + rng.gauss(0, 0.62)) for _ in range(5)]
    rows.append({
        "respondent": f"R{i:03d}", "department": dept, "work_mode": mode,
        "tenure_years": tenure, "hours_per_week": hours,
        "manager_support": round(min(5, max(1, support)), 2),
        "workload": round(min(5, max(1, workload)), 2),
        **{f"wb{k+1}": v for k, v in enumerate(items)},
    })

# a few realistic data problems for the cleaning step to catch
rows[17]["hours_per_week"] = ""          # missing answer
rows[88]["wb3"] = 9                      # out-of-range Likert
rows[203]["work_mode"] = "hybrid "       # inconsistent label

out = Path(__file__).with_name("survey.csv")
with out.open("w", newline="") as fh:
    w = csv.DictWriter(fh, fieldnames=list(rows[0]))
    w.writeheader()
    w.writerows(rows)
print(f"Wrote {len(rows)} rows to {out.name}")
