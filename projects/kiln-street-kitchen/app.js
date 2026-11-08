(function () {
  "use strict";

  var MENU = [
    { n: "Oven-warm focaccia", d: "Rosemary, sea salt, cultured butter.", p: 6, c: ["small", "veg"] },
    { n: "Charred leeks", d: "Hazelnut romesco, pickled shallot.", p: 11, c: ["small", "veg"] },
    { n: "Whipped feta", d: "Honey, chilli oil, flatbread.", p: 12, c: ["small", "veg"] },
    { n: "Smoked mackerel pate", d: "Cucumber, dill, rye crisps.", p: 13, c: ["small"] },
    { n: "Fire-roasted carrots", d: "Tahini yoghurt, dukkah, mint.", p: 12, c: ["fire", "veg"] },
    { n: "Half chicken", d: "Brined, wood-roasted, salsa verde.", p: 26, c: ["fire"] },
    { n: "Lamb shoulder", d: "Slow-cooked, pomegranate, white beans.", p: 29, c: ["fire"] },
    { n: "Whole sea bream", d: "Lemon, capers, grilled greens.", p: 31, c: ["fire"] },
    { n: "Burnt honey tart", d: "Salted caramel, creme fraiche.", p: 9, c: ["sweet", "veg"] },
    { n: "Baked plums", d: "Almond cream, toasted oats.", p: 9, c: ["sweet", "veg"] }
  ];
  var LABELS = { small: "Small plate", fire: "From the fire", sweet: "Sweet" };

  var HOURS = [
    ["Sunday", "12:00 to 21:00"],
    ["Monday", "Closed"],
    ["Tuesday", "17:00 to 22:00"],
    ["Wednesday", "17:00 to 22:00"],
    ["Thursday", "17:00 to 22:00"],
    ["Friday", "12:00 to 23:00"],
    ["Saturday", "12:00 to 23:00"]
  ];

  // Menu rendering and filtering
  var list = document.getElementById("menuList");
  var empty = document.getElementById("menuEmpty");
  function renderMenu(filter) {
    list.innerHTML = "";
    var shown = MENU.filter(function (m) { return filter === "all" || m.c.indexOf(filter) !== -1; });
    shown.forEach(function (m) {
      var li = document.createElement("li");
      var top = document.createElement("div");
      top.className = "top";
      var h = document.createElement("h3");
      h.textContent = m.n;
      if (m.c.indexOf("veg") !== -1) {
        var t = document.createElement("span");
        t.className = "tag";
        t.textContent = "V";
        t.title = "Vegetarian";
        h.appendChild(t);
      }
      var price = document.createElement("span");
      price.className = "price";
      price.textContent = "$" + m.p;
      top.appendChild(h);
      top.appendChild(price);
      var p = document.createElement("p");
      p.textContent = m.d;
      li.appendChild(top);
      li.appendChild(p);
      list.appendChild(li);
    });
    empty.hidden = shown.length > 0;
  }
  var chips = document.querySelectorAll(".chip");
  chips.forEach(function (chip) {
    chip.addEventListener("click", function () {
      chips.forEach(function (c) { c.classList.remove("on"); c.setAttribute("aria-pressed", "false"); });
      chip.classList.add("on");
      chip.setAttribute("aria-pressed", "true");
      renderMenu(chip.getAttribute("data-filter"));
    });
  });
  renderMenu("all");

  // Opening hours with today highlighted
  var hoursBody = document.getElementById("hours");
  var today = new Date().getDay();
  HOURS.forEach(function (h, i) {
    var tr = document.createElement("tr");
    if (i === today) tr.className = "today";
    var th = document.createElement("th");
    th.scope = "row";
    th.textContent = h[0];
    var td = document.createElement("td");
    td.textContent = h[1];
    tr.appendChild(th);
    tr.appendChild(td);
    hoursBody.appendChild(tr);
  });

  // Mobile navigation
  var toggle = document.querySelector(".nav-toggle");
  var nav = document.getElementById("nav");
  toggle.addEventListener("click", function () {
    var open = nav.classList.toggle("open");
    toggle.setAttribute("aria-expanded", String(open));
  });
  nav.addEventListener("click", function (e) {
    if (e.target.tagName === "A") {
      nav.classList.remove("open");
      toggle.setAttribute("aria-expanded", "false");
    }
  });

  // Reservation form
  var form = document.getElementById("bookForm");
  var dateEl = document.getElementById("date");
  var timeEl = document.getElementById("time");
  var done = document.getElementById("done");

  var iso = function (d) {
    return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
  };
  dateEl.min = iso(new Date());

  function slotsFor(dateStr) {
    var d = new Date(dateStr + "T12:00:00");
    var day = d.getDay();
    if (day === 1) return [];
    var from = day === 0 ? 12 : (day === 5 || day === 6 ? 12 : 17);
    var to = day === 0 ? 20 : (day === 5 || day === 6 ? 22 : 21);
    var out = [];
    for (var h = from; h <= to; h++) {
      out.push(String(h).padStart(2, "0") + ":00");
      if (h < to) out.push(String(h).padStart(2, "0") + ":30");
    }
    return out;
  }
  function fillTimes() {
    timeEl.innerHTML = "";
    var first = document.createElement("option");
    first.value = "";
    first.textContent = dateEl.value ? "Choose a time" : "Pick a date first";
    timeEl.appendChild(first);
    if (!dateEl.value) return;
    var slots = slotsFor(dateEl.value);
    if (!slots.length) {
      first.textContent = "Closed on Mondays";
      return;
    }
    slots.forEach(function (s) {
      var o = document.createElement("option");
      o.value = s;
      o.textContent = s;
      timeEl.appendChild(o);
    });
  }
  dateEl.addEventListener("change", fillTimes);
  fillTimes();

  function setErr(id, msg) {
    var field = document.getElementById(id);
    var err = document.getElementById(id + "-err");
    err.textContent = msg || "";
    if (msg) field.setAttribute("aria-invalid", "true");
    else field.removeAttribute("aria-invalid");
    return !msg;
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    done.hidden = true;
    var name = form.name.value.trim();
    var email = form.email.value.trim();
    var ok = true;
    ok = setErr("name", name ? "" : "Enter your name so we can find your table.") && ok;
    ok = setErr("email", /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ? "" : "Enter an email address like name@example.com.") && ok;
    var dateMsg = "";
    if (!dateEl.value) dateMsg = "Choose a date.";
    else if (dateEl.value < dateEl.min) dateMsg = "Choose today or a later date.";
    else if (!slotsFor(dateEl.value).length) dateMsg = "We are closed on Mondays. Choose another day.";
    ok = setErr("date", dateMsg) && ok;
    ok = setErr("time", timeEl.value ? "" : "Choose a time.") && ok;
    if (!ok) {
      var firstBad = form.querySelector('[aria-invalid="true"]');
      if (firstBad) firstBad.focus();
      return;
    }
    done.textContent = "Thanks, " + name.split(" ")[0] + ". This is a sample form, so no booking was sent. On a live site this request would go to the restaurant by email.";
    done.hidden = false;
    form.reset();
    dateEl.min = iso(new Date());
    fillTimes();
  });
})();
