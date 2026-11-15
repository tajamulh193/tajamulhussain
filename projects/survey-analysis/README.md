# Employee wellbeing survey: statistical analysis (sample)

A reproducible research analysis written in Python with **no third-party packages**.
It reads a raw survey export, cleans it, and writes a one-page statistical report.

**[View the report](https://tajamulh193.github.io/tajamulhussain/projects/survey-analysis/report.html)**

The organisation and respondents are fictional. `make_data.py` creates the synthetic data with a fixed seed.

## What it does

| Step | Method |
|---|---|
| Cleaning | Standardises labels, excludes missing or out-of-range answers, and logs every change |
| Reliability | Cronbach's alpha for the 5-item wellbeing scale |
| Group comparisons | One-way ANOVA (with eta squared) and Welch's t-test (with Cohen's d) |
| Relationships | Pearson correlation matrix with significance |
| Drivers | Multiple OLS regression with standard errors, p-values and 95% confidence intervals |
| Model check | Residuals plotted against fitted values |

## Run it

```bash
python make_data.py      # optional: rebuild survey.csv
python analysis.py       # writes report.html
python -m unittest -v    # 12 tests
```

## How the numbers are checked

`stats.py` implements the t, F and beta distributions and least-squares regression from scratch.
The tests compare every statistic with reference values from **SciPy** and **statsmodels**,
matching to 7 to 12 decimal places, and confirm that untrusted text in the CSV is escaped in the report.
