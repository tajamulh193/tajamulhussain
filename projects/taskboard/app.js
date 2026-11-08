(function () {
  "use strict";

  var KEY = "sample-taskboard-v1";
  var COLS = [
    { id: "todo", name: "To do" },
    { id: "doing", name: "In progress" },
    { id: "review", name: "In review" },
    { id: "done", name: "Done" }
  ];
  var PRIO = { high: "High", med: "Medium", low: "Low" };

  function addDays(n) {
    var d = new Date();
    d.setDate(d.getDate() + n);
    return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
  }
  function seed() {
    return [
      { id: 1, title: "Audit current site content", owner: "Amna", due: addDays(-9), prio: "med", col: "done" },
      { id: 2, title: "Agree sitemap and page templates", owner: "Bilal", due: addDays(-4), prio: "high", col: "done" },
      { id: 3, title: "Design homepage and service pages", owner: "Sara", due: addDays(2), prio: "high", col: "review" },
      { id: 4, title: "Build responsive templates", owner: "Hamza", due: addDays(6), prio: "high", col: "doing" },
      { id: 5, title: "Migrate blog posts", owner: "Amna", due: addDays(8), prio: "med", col: "doing" },
      { id: 6, title: "Set up redirects for old URLs", owner: "Hamza", due: addDays(-1), prio: "high", col: "todo" },
      { id: 7, title: "Write launch announcement", owner: "Bilal", due: addDays(12), prio: "low", col: "todo" },
      { id: 8, title: "Accessibility and speed checks", owner: "Sara", due: addDays(10), prio: "med", col: "todo" },
      { id: 9, title: "Client sign-off meeting", owner: "Bilal", due: addDays(14), prio: "med", col: "todo" }
    ];
  }

  var tasks = load();
  var nextId = tasks.reduce(function (m, t) { return Math.max(m, t.id); }, 0) + 1;
  var editingId = null;
  var dragId = null;

  var $ = function (id) { return document.getElementById(id); };
  var board = $("board"), q = $("q"), fOwner = $("fOwner"), fPrio = $("fPrio");
  var dlg = $("dlg"), form = $("taskForm");

  function load() {
    try {
      var raw = localStorage.getItem(KEY);
      if (raw) {
        var parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) return parsed;
      }
    } catch (e) { /* storage unavailable: fall back to sample data */ }
    return seed();
  }
  function save() {
    try { localStorage.setItem(KEY, JSON.stringify(tasks)); } catch (e) { /* ignore */ }
  }
  function today() { return addDays(0); }
  function announce(msg) { $("live").textContent = msg; }

  function visible(t) {
    var term = q.value.trim().toLowerCase();
    if (term && (t.title + " " + t.owner).toLowerCase().indexOf(term) === -1) return false;
    if (fOwner.value && t.owner !== fOwner.value) return false;
    if (fPrio.value && t.prio !== fPrio.value) return false;
    return true;
  }
  function fmt(d) {
    if (!d) return "";
    var p = d.split("-");
    return new Date(+p[0], +p[1] - 1, +p[2]).toLocaleDateString(undefined, { day: "numeric", month: "short" });
  }

  function refreshOwners() {
    var cur = fOwner.value;
    var names = {};
    tasks.forEach(function (t) { if (t.owner) names[t.owner] = true; });
    fOwner.innerHTML = '<option value="">All owners</option>';
    Object.keys(names).sort().forEach(function (n) {
      var o = document.createElement("option");
      o.value = n; o.textContent = n;
      fOwner.appendChild(o);
    });
    fOwner.value = names[cur] ? cur : "";
  }

  function progress() {
    var done = tasks.filter(function (t) { return t.col === "done"; }).length;
    var pct = tasks.length ? Math.round(done / tasks.length * 100) : 0;
    $("pctText").textContent = pct + "% done";
    $("countText").textContent = done + " of " + tasks.length + " tasks";
    $("pctBar").style.width = pct + "%";
    document.querySelector(".progress-track").setAttribute("aria-valuenow", pct);
  }

  function cardEl(t, idx) {
    var li = document.createElement("li");
    li.className = "card";
    li.draggable = true;
    li.dataset.id = t.id;
    li.dataset.prio = t.prio;
    var h = document.createElement("h3");
    h.textContent = t.title;
    var row = document.createElement("div");
    row.className = "row";
    var prio = document.createElement("span");
    prio.className = "prio";
    prio.textContent = PRIO[t.prio] + " priority";
    row.appendChild(prio);
    if (t.owner) { var o = document.createElement("span"); o.textContent = t.owner; row.appendChild(o); }
    if (t.due) {
      var d = document.createElement("span");
      var overdue = t.col !== "done" && t.due < today();
      d.textContent = (overdue ? "Overdue, " : "Due ") + fmt(t.due);
      if (overdue) d.className = "od";
      row.appendChild(d);
    }
    var act = document.createElement("div");
    act.className = "card-actions";
    var ci = COLS.map(function (c) { return c.id; }).indexOf(t.col);
    var left = btn("Move left", "←", ci === 0, function () { move(t.id, COLS[ci - 1].id); });
    var right = btn("Move right", "→", ci === COLS.length - 1, function () { move(t.id, COLS[ci + 1].id); });
    var edit = btn("Edit task", "Edit", false, function () { openDialog(t); });
    edit.classList.add("sp");
    var del = btn("Delete task", "Delete", false, function () {
      tasks = tasks.filter(function (x) { return x.id !== t.id; });
      save(); render(); announce("Task deleted.");
    });
    [left, right, edit, del].forEach(function (b) { act.appendChild(b); });
    li.appendChild(h); li.appendChild(row); li.appendChild(act);

    li.addEventListener("dragstart", function (e) {
      dragId = t.id;
      li.classList.add("dragging");
      e.dataTransfer.effectAllowed = "move";
      e.dataTransfer.setData("text/plain", String(t.id));
    });
    li.addEventListener("dragend", function () {
      dragId = null;
      li.classList.remove("dragging");
      document.querySelectorAll(".col.over").forEach(function (c) { c.classList.remove("over"); });
    });
    return li;
  }
  function btn(label, text, disabled, fn) {
    var b = document.createElement("button");
    b.type = "button";
    b.setAttribute("aria-label", label);
    b.textContent = text;
    b.disabled = disabled;
    b.addEventListener("click", fn);
    return b;
  }

  function move(id, col) {
    var t = tasks.filter(function (x) { return x.id === id; })[0];
    if (!t || t.col === col) return;
    t.col = col;
    save(); render();
    var name = COLS.filter(function (c) { return c.id === col; })[0].name;
    announce('Moved "' + t.title + '" to ' + name + ".");
  }

  function render() {
    refreshOwners();
    board.innerHTML = "";
    COLS.forEach(function (c) {
      var section = document.createElement("section");
      section.className = "col";
      section.dataset.col = c.id;
      var inCol = tasks.filter(function (t) { return t.col === c.id; });
      var shown = inCol.filter(visible);
      var h = document.createElement("h2");
      var name = document.createElement("span");
      name.style.background = "none";
      name.style.padding = "0";
      name.style.color = "inherit";
      name.style.fontSize = "inherit";
      name.textContent = c.name;
      var count = document.createElement("span");
      count.textContent = shown.length;
      h.appendChild(name); h.appendChild(count);
      var ul = document.createElement("ul");
      ul.className = "cards";
      shown.forEach(function (t, i) { ul.appendChild(cardEl(t, i)); });
      section.appendChild(h);
      section.appendChild(ul);
      if (!shown.length) {
        var p = document.createElement("p");
        p.className = "nothing";
        p.textContent = inCol.length ? "No tasks match the filters." : "Nothing here yet. Drop a card in this column.";
        section.appendChild(p);
      }
      section.addEventListener("dragover", function (e) {
        if (dragId === null) return;
        e.preventDefault();
        section.classList.add("over");
      });
      section.addEventListener("dragleave", function (e) {
        if (!section.contains(e.relatedTarget)) section.classList.remove("over");
      });
      section.addEventListener("drop", function (e) {
        e.preventDefault();
        section.classList.remove("over");
        if (dragId !== null) move(dragId, c.id);
      });
      board.appendChild(section);
    });
    progress();
  }

  // Dialog
  var colSel = $("tCol");
  COLS.forEach(function (c) {
    var o = document.createElement("option");
    o.value = c.id; o.textContent = c.name;
    colSel.appendChild(o);
  });
  function openDialog(t) {
    editingId = t ? t.id : null;
    $("dlgTitle").textContent = t ? "Edit task" : "Add task";
    $("saveBtn").textContent = t ? "Save changes" : "Add task";
    $("tTitle").value = t ? t.title : "";
    $("tOwner").value = t ? t.owner : "";
    $("tDue").value = t ? t.due : "";
    $("tPrio").value = t ? t.prio : "med";
    $("tCol").value = t ? t.col : "todo";
    $("tTitle-err").textContent = "";
    $("tTitle").removeAttribute("aria-invalid");
    dlg.showModal();
    $("tTitle").focus();
  }
  $("addBtn").addEventListener("click", function () { openDialog(null); });
  $("cancelBtn").addEventListener("click", function () { dlg.close(); });
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var title = $("tTitle").value.trim();
    if (!title) {
      $("tTitle-err").textContent = "Describe the task in a few words.";
      $("tTitle").setAttribute("aria-invalid", "true");
      $("tTitle").focus();
      return;
    }
    var data = { title: title, owner: $("tOwner").value.trim(), due: $("tDue").value, prio: $("tPrio").value, col: $("tCol").value };
    if (editingId === null) {
      data.id = nextId++;
      tasks.push(data);
      announce("Task added.");
    } else {
      tasks = tasks.map(function (t) { return t.id === editingId ? Object.assign({}, t, data) : t; });
      announce("Task updated.");
    }
    save(); dlg.close(); render();
  });

  [q, fOwner, fPrio].forEach(function (el) { el.addEventListener("input", render); });

  $("exportBtn").addEventListener("click", function () {
    var blob = new Blob([JSON.stringify(tasks, null, 2)], { type: "application/json" });
    var a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "taskboard.json";
    a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
  });
  $("resetBtn").addEventListener("click", function () {
    tasks = seed();
    nextId = tasks.length + 1;
    save(); render();
    announce("Sample data restored.");
  });

  render();
})();
