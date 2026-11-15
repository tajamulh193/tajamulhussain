"""Small statistics library written with the Python standard library only.

Every function here is checked against SciPy and statsmodels in tests/,
so the report can run anywhere Python 3.9+ is installed, with no extra packages.
"""
from __future__ import annotations

import math
from typing import Sequence


# ---------------------------------------------------------------- basics
def mean(xs: Sequence[float]) -> float:
    if not xs:
        raise ValueError("mean of empty data")
    return sum(xs) / len(xs)


def var(xs: Sequence[float], ddof: int = 1) -> float:
    n = len(xs)
    if n - ddof <= 0:
        raise ValueError("not enough data for variance")
    m = mean(xs)
    return sum((x - m) ** 2 for x in xs) / (n - ddof)


def sd(xs: Sequence[float]) -> float:
    return math.sqrt(var(xs))


# ------------------------------------------------- special functions
def _betacf(a: float, b: float, x: float) -> float:
    """Continued fraction for the incomplete beta function (Lentz's method)."""
    tiny, eps = 1e-300, 3e-16
    qab, qap, qam = a + b, a + 1.0, a - 1.0
    c, d = 1.0, 1.0 - qab * x / qap
    d = 1.0 / (d if abs(d) > tiny else tiny)
    h = d
    for m in range(1, 400):
        m2 = 2 * m
        aa = m * (b - m) * x / ((qam + m2) * (a + m2))
        d = 1.0 + aa * d
        d = 1.0 / (d if abs(d) > tiny else tiny)
        c = 1.0 + aa / c
        c = c if abs(c) > tiny else tiny
        h *= d * c
        aa = -(a + m) * (qab + m) * x / ((a + m2) * (qap + m2))
        d = 1.0 + aa * d
        d = 1.0 / (d if abs(d) > tiny else tiny)
        c = 1.0 + aa / c
        c = c if abs(c) > tiny else tiny
        delta = d * c
        h *= delta
        if abs(delta - 1.0) < eps:
            break
    return h


def betainc(a: float, b: float, x: float) -> float:
    """Regularised incomplete beta I_x(a, b)."""
    if x <= 0:
        return 0.0
    if x >= 1:
        return 1.0
    lbeta = math.lgamma(a + b) - math.lgamma(a) - math.lgamma(b)
    front = math.exp(lbeta + a * math.log(x) + b * math.log1p(-x))
    if x < (a + 1) / (a + b + 2):
        return front * _betacf(a, b, x) / a
    return 1.0 - front * _betacf(b, a, 1.0 - x) / b


def t_sf2(t: float, df: float) -> float:
    """Two-sided p-value for a t statistic."""
    return betainc(df / 2.0, 0.5, df / (df + t * t))


def f_sf(f: float, d1: float, d2: float) -> float:
    """Upper-tail p-value for an F statistic."""
    if f <= 0:
        return 1.0
    return betainc(d2 / 2.0, d1 / 2.0, d2 / (d2 + d1 * f))


def t_ppf975(df: float) -> float:
    """Critical t value for a 95% two-sided interval (bisection)."""
    lo, hi = 0.0, 50.0
    for _ in range(100):
        mid = (lo + hi) / 2
        if t_sf2(mid, df) > 0.05:
            lo = mid
        else:
            hi = mid
    return (lo + hi) / 2


# ------------------------------------------------------- tests
def cronbach_alpha(items: Sequence[Sequence[float]]) -> float:
    """items: list of columns (one list per scale item)."""
    k = len(items)
    if k < 2:
        raise ValueError("alpha needs at least two items")
    n = len(items[0])
    if any(len(col) != n for col in items):
        raise ValueError("all items need the same number of answers")
    totals = [sum(col[i] for col in items) for i in range(n)]
    return k / (k - 1) * (1 - sum(var(c) for c in items) / var(totals))


def welch_t(a: Sequence[float], b: Sequence[float]) -> dict:
    va, vb, na, nb = var(a), var(b), len(a), len(b)
    se2 = va / na + vb / nb
    t = (mean(a) - mean(b)) / math.sqrt(se2)
    df = se2 ** 2 / ((va / na) ** 2 / (na - 1) + (vb / nb) ** 2 / (nb - 1))
    pooled = math.sqrt(((na - 1) * va + (nb - 1) * vb) / (na + nb - 2))
    return {"t": t, "df": df, "p": t_sf2(t, df), "d": (mean(a) - mean(b)) / pooled}


def anova_oneway(groups: Sequence[Sequence[float]]) -> dict:
    allx = [x for g in groups for x in g]
    gm, k, n = mean(allx), len(groups), len(allx)
    ssb = sum(len(g) * (mean(g) - gm) ** 2 for g in groups)
    ssw = sum(sum((x - mean(g)) ** 2 for x in g) for g in groups)
    d1, d2 = k - 1, n - k
    f = (ssb / d1) / (ssw / d2)
    return {"F": f, "df1": d1, "df2": d2, "p": f_sf(f, d1, d2), "eta2": ssb / (ssb + ssw)}


def pearson(x: Sequence[float], y: Sequence[float]) -> dict:
    if len(x) != len(y):
        raise ValueError("x and y must be the same length")
    mx, my = mean(x), mean(y)
    sxy = sum((a - mx) * (b - my) for a, b in zip(x, y))
    sxx = sum((a - mx) ** 2 for a in x)
    syy = sum((b - my) ** 2 for b in y)
    r = sxy / math.sqrt(sxx * syy)
    n = len(x)
    if abs(r) >= 1:
        return {"r": r, "p": 0.0, "n": n}
    t = r * math.sqrt((n - 2) / (1 - r * r))
    return {"r": r, "p": t_sf2(t, n - 2), "n": n}


# -------------------------------------------------- regression
def _inverse(m: list[list[float]]) -> list[list[float]]:
    n = len(m)
    a = [row[:] + [1.0 if i == j else 0.0 for j in range(n)] for i, row in enumerate(m)]
    for col in range(n):
        piv = max(range(col, n), key=lambda r: abs(a[r][col]))
        if abs(a[piv][col]) < 1e-12:
            raise ValueError("predictors are perfectly collinear")
        a[col], a[piv] = a[piv], a[col]
        p = a[col][col]
        a[col] = [v / p for v in a[col]]
        for r in range(n):
            if r != col and a[r][col] != 0:
                f = a[r][col]
                a[r] = [v - f * w for v, w in zip(a[r], a[col])]
    return [row[n:] for row in a]


def ols(y: Sequence[float], X: Sequence[Sequence[float]], names: Sequence[str]) -> dict:
    """Ordinary least squares with an intercept. X is a list of rows."""
    rows = [[1.0, *r] for r in X]
    n, p = len(rows), len(rows[0])
    if n <= p:
        raise ValueError("need more rows than predictors")
    xtx = [[sum(r[i] * r[j] for r in rows) for j in range(p)] for i in range(p)]
    xty = [sum(r[i] * yy for r, yy in zip(rows, y)) for i in range(p)]
    inv = _inverse(xtx)
    beta = [sum(inv[i][j] * xty[j] for j in range(p)) for i in range(p)]
    fitted = [sum(b * v for b, v in zip(beta, r)) for r in rows]
    resid = [yy - f for yy, f in zip(y, fitted)]
    sse = sum(e * e for e in resid)
    my = mean(y)
    sst = sum((yy - my) ** 2 for yy in y)
    df = n - p
    s2 = sse / df
    tcrit = t_ppf975(df)
    coefs = []
    for i, name in enumerate(["(Intercept)", *names]):
        se = math.sqrt(s2 * inv[i][i])
        t = beta[i] / se
        coefs.append({"name": name, "b": beta[i], "se": se, "t": t, "p": t_sf2(t, df),
                      "lo": beta[i] - tcrit * se, "hi": beta[i] + tcrit * se})
    r2 = 1 - sse / sst
    f = ((sst - sse) / (p - 1)) / s2
    return {"coefs": coefs, "r2": r2, "adj_r2": 1 - (1 - r2) * (n - 1) / df, "n": n,
            "F": f, "df1": p - 1, "df2": df, "p": f_sf(f, p - 1, df),
            "fitted": fitted, "resid": resid}
