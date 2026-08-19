/* ============================================================
   STACKWAVE - Main JavaScript
   Vertical Sidebar Layout | Bootstrap 5
   ============================================================ */

"use strict";

/* ================================================================
   1. THEME TOGGLE (runs immediately to prevent flash)
   ================================================================ */
(function () {
  const saved = localStorage.getItem("sw-theme") || "light";
  document.documentElement.setAttribute("data-theme", saved);
})();

/* ================================================================
   2. DOM READY
   ================================================================ */
document.addEventListener("DOMContentLoaded", function () {

  /* ---- 2.1 Theme Button ---- */
  const themeBtn  = document.getElementById("themeToggle");
  const themeIcon = document.getElementById("themeIcon");

  function syncThemeIcon(theme) {
    if (!themeIcon) return;
    themeIcon.className = theme === "dark" ? "bi bi-sun" : "bi bi-moon-stars";
  }
  syncThemeIcon(document.documentElement.getAttribute("data-theme"));

  if (themeBtn) {
    themeBtn.addEventListener("click", function () {
      const cur  = document.documentElement.getAttribute("data-theme");
      const next = cur === "dark" ? "light" : "dark";
      document.documentElement.setAttribute("data-theme", next);
      localStorage.setItem("sw-theme", next);
      syncThemeIcon(next);
    });
  }

  /* ---- 2.2 Sidebar Toggle (desktop collapse) ---- */
  // Restore collapse state
  if (localStorage.getItem("sw-sidebar") === "collapsed") {
    document.body.classList.add("sidebar-collapsed");
  }
  function toggleSidebar() {
    document.body.classList.toggle("sidebar-collapsed");
    const collapsed = document.body.classList.contains("sidebar-collapsed");
    localStorage.setItem("sw-sidebar", collapsed ? "collapsed" : "expanded");
    // Flip chevron icon in sidebar logo
    const chevron = document.querySelector(".sidebar-logo-toggle i");
    if (chevron) {
      chevron.className = collapsed ? "bi bi-chevron-right" : "bi bi-chevron-left";
    }
  }
  const sidebarToggleBtn  = document.getElementById("sidebarToggle");
  const sidebarToggleBtn2 = document.getElementById("sidebarToggle2");
  if (sidebarToggleBtn)  sidebarToggleBtn.addEventListener("click", toggleSidebar);
  if (sidebarToggleBtn2) sidebarToggleBtn2.addEventListener("click", function(e) {
    e.preventDefault(); e.stopPropagation(); toggleSidebar();
  });

  /* ---- 2.3 Mobile Sidebar Toggle ---- */
  const mobileSidebarBtn = document.getElementById("mobileSidebarToggle");
  const appSidebar       = document.getElementById("appSidebar");
  const sidebarOverlay   = document.getElementById("sidebarOverlay");

  function openMobileSidebar() {
    if (appSidebar)     appSidebar.classList.add("open");
    if (sidebarOverlay) sidebarOverlay.classList.add("show");
    document.body.style.overflow = "hidden";
  }
  function closeMobileSidebar() {
    if (appSidebar)     appSidebar.classList.remove("open");
    if (sidebarOverlay) sidebarOverlay.classList.remove("show");
    document.body.style.overflow = "";
  }

  if (mobileSidebarBtn) mobileSidebarBtn.addEventListener("click", openMobileSidebar);
  if (sidebarOverlay)   sidebarOverlay.addEventListener("click", closeMobileSidebar);

  /* ---- 2.4 Active Sidebar Link ---- */
  const page = window.location.pathname.split("/").pop() || "index.html";
  document.querySelectorAll(".sidebar-link[href]").forEach(function (link) {
    const href = link.getAttribute("href");
    if (href === page || href === "./" + page) {
      link.classList.add("active");
      // Open parent collapse if inside submenu
      const parentCollapse = link.closest(".collapse");
      if (parentCollapse) {
        parentCollapse.classList.add("show");
        const toggle = document.querySelector('[data-bs-target="#' + parentCollapse.id + '"]');
        if (toggle) toggle.setAttribute("aria-expanded", "true");
      }
      // Mark parent sidebar-item active
      const parentItem = link.closest(".sidebar-item");
      if (parentItem) parentItem.classList.add("active");
    }
  });

  /* ---- 2.5 Page Loader ---- */
  const loader = document.getElementById("pageLoader");
  if (loader) {
    window.addEventListener("load", function () {
      loader.classList.add("hidden");
      setTimeout(() => loader.style.display = "none", 400);
    });
    // Fallback: hide after 2s
    setTimeout(() => {
      if (loader) { loader.classList.add("hidden"); setTimeout(() => loader.style.display = "none", 400); }
    }, 2000);
  }

  /* ---- 2.6 Animated Counters ---- */
  function animateCounters() {
    document.querySelectorAll("[data-count]").forEach(function (el) {
      const target  = parseInt(el.getAttribute("data-count"), 10);
      const prefix  = el.getAttribute("data-prefix") || "";
      const suffix  = el.getAttribute("data-suffix") || "";
      const dur     = 1600;
      const step    = 16;
      const steps   = dur / step;
      const inc     = target / steps;
      let cur       = 0;
      const timer   = setInterval(function () {
        cur += inc;
        if (cur >= target) { cur = target; clearInterval(timer); }
        el.textContent = prefix + Math.floor(cur).toLocaleString() + suffix;
      }, step);
    });
  }
  // Run on intersection
  if ("IntersectionObserver" in window) {
    const io = new IntersectionObserver(function (entries, obs) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { animateCounters(); obs.disconnect(); }
      });
    }, { threshold: 0.2 });
    const firstCounter = document.querySelector("[data-count]");
    if (firstCounter) io.observe(firstCounter);
  } else {
    animateCounters();
  }

  /* ---- 2.7 Progress Bars ---- */
  document.querySelectorAll(".sw-progress-bar[data-width]").forEach(function (bar) {
    setTimeout(() => bar.style.width = bar.getAttribute("data-width"), 300);
  });

  /* ---- 2.8 Chart Period Buttons ---- */
  document.querySelectorAll(".chart-toolbar").forEach(function (tb) {
    tb.querySelectorAll(".chart-period-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        tb.querySelectorAll(".chart-period-btn").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
      });
    });
  });

  /* ---- 2.9 Notification / Badge dismiss ---- */
  document.querySelectorAll(".notif-dismiss").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      btn.closest(".notif-item").remove();
    });
  });

  /* ================================================================
     3. PAGE-SPECIFIC INITIALIZATIONS
     ================================================================ */

  /* --- ApexCharts Helper --- */
  function chartExists(id) {
    return typeof ApexCharts !== "undefined" && document.getElementById(id);
  }

  /* ============================================================
     3.1 MODERN DASHBOARD — index.html
     ============================================================ */
  if (chartExists("revenueAreaChart")) {
    new ApexCharts(document.getElementById("revenueAreaChart"), {
      chart: { type: "area", height: 290, toolbar: { show: false }, sparkline: { enabled: false }, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [
        { name: "Revenue", data: [31000, 40000, 28000, 51000, 42000, 59000, 48000, 70000, 61000, 78000, 68000, 84250] },
        { name: "Expenses", data: [11000, 18000, 12000, 22000, 19000, 28000, 21000, 30000, 27000, 32000, 29000, 35000] }
      ],
      colors: ["#5D87FF", "#13DEB9"],
      fill: { type: ["gradient", "gradient"], gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] } },
      stroke: { curve: "smooth", width: 2.5 },
      xaxis: { categories: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"], labels: { style: { colors: "#7C8FAC", fontSize: "12px" } }, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { formatter: v => "$" + (v / 1000).toFixed(0) + "K", style: { colors: "#7C8FAC", fontSize: "12px" } } },
      grid: { borderColor: "#eaecf4", strokeDashArray: 4 },
      tooltip: { y: { formatter: v => "$" + v.toLocaleString() } },
      legend: { show: false },
      dataLabels: { enabled: false }
    }).render();
  }

  if (chartExists("userGrowthDonut")) {
    new ApexCharts(document.getElementById("userGrowthDonut"), {
      chart: { type: "donut", height: 290, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [38, 26, 18, 10, 8],
      labels: ["Organic", "Direct", "Social", "Email", "Referral"],
      colors: ["#5D87FF", "#13DEB9", "#FFAE1F", "#7460EE", "#FA896B"],
      plotOptions: { pie: { donut: { size: "72%", labels: { show: true, total: { show: true, label: "Total", formatter: () => "48,290", style: { fontSize: "16px", fontWeight: 700, color: "#1e2a3a" } } } } } },
      dataLabels: { enabled: false },
      legend: { position: "bottom", fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: "12px" },
      stroke: { show: false }
    }).render();
  }

  if (chartExists("weeklySalesBar")) {
    new ApexCharts(document.getElementById("weeklySalesBar"), {
      chart: { type: "bar", height: 130, toolbar: { show: false }, sparkline: { enabled: false }, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [{ name: "Sales", data: [3200, 4100, 2800, 5100, 3900, 4700, 3549] }],
      colors: ["#5D87FF"],
      plotOptions: { bar: { borderRadius: 6, columnWidth: "55%" } },
      xaxis: { categories: ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"], labels: { style: { colors: "#7C8FAC", fontSize: "11px" } }, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { show: false },
      grid: { show: false },
      dataLabels: { enabled: false },
      tooltip: { y: { formatter: v => "$" + v.toLocaleString() } }
    }).render();
  }

  if (chartExists("trafficSourcesBar")) {
    new ApexCharts(document.getElementById("trafficSourcesBar"), {
      chart: { type: "bar", height: 310, toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [{ name: "Visitors", data: [4200, 3800, 2900, 1800, 950] }],
      colors: ["#5D87FF"],
      plotOptions: { bar: { borderRadius: 6, horizontal: true, barHeight: "55%",
        distributed: true,
        colors: { ranges: [{ from: 0, to: 99999, color: undefined }] }
      } },
      colors: ["#5D87FF", "#13DEB9", "#FFAE1F", "#7460EE", "#FA896B"],
      xaxis: { categories: ["Organic","Direct","Social Media","Email","Referral"], labels: { style: { colors: "#7C8FAC", fontSize: "12px" } } },
      yaxis: { labels: { style: { colors: "#7C8FAC", fontSize: "12px" } } },
      grid: { borderColor: "#eaecf4", strokeDashArray: 4 },
      dataLabels: { enabled: false },
      legend: { show: false }
    }).render();
  }

  /* ============================================================
     3.2 ECOMMERCE DASHBOARD — dashboard-ecommerce.html
     ============================================================ */
  if (chartExists("ecomRevenueMixed")) {
    new ApexCharts(document.getElementById("ecomRevenueMixed"), {
      chart: { type: "line", height: 330, toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [
        { name: "Revenue", type: "column", data: [18400,22100,19800,24600,21900,28400,26800,31200,29400,34800,32100,38500] },
        { name: "Target",  type: "line",   data: [20000,21000,22000,23000,24000,25000,26000,28000,30000,32000,34000,36000] }
      ],
      colors: ["#5D87FF", "#FFAE1F"],
      plotOptions: { bar: { borderRadius: 6, columnWidth: "55%" } },
      fill: { opacity: [0.85, 1] },
      stroke: { width: [0, 3], curve: "smooth", dashArray: [0, 6] },
      markers: { size: [0, 5], strokeColors: "#ffffff", strokeWidth: 2 },
      xaxis: { categories: ["Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec","Jan"], axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: "#7C8FAC", fontSize: "12px" } } },
      yaxis: { labels: { formatter: v => "$" + (v / 1000).toFixed(0) + "k", style: { colors: "#7C8FAC", fontSize: "12px" } } },
      dataLabels: { enabled: false },
      grid: { borderColor: "#eaecf4", strokeDashArray: 4 },
      legend: { position: "top", horizontalAlign: "right", labels: { colors: "#7C8FAC" } },
      tooltip: { shared: true, intersect: false, y: { formatter: v => "$" + v.toLocaleString() } }
    }).render();
  }

  if (chartExists("ecomOrderDonut")) {
    new ApexCharts(document.getElementById("ecomOrderDonut"), {
      chart: { type: "donut", height: 200, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [4284, 1412, 648, 148],
      labels: ["Completed", "Processing", "Pending", "Cancelled"],
      colors: ["#5D87FF", "#13DEB9", "#FFAE1F", "#FA896B"],
      legend: { show: false },
      dataLabels: { enabled: false },
      plotOptions: { pie: { donut: { size: "72%", labels: { show: true, total: { show: true, label: "Total Orders", fontSize: "11px", color: "#7C8FAC", formatter: () => "6,492" }, value: { fontSize: "18px", fontWeight: "800", color: "#1e2a3a" } } } } },
      stroke: { width: 2, colors: ["#ffffff"] }
    }).render();
  }

  if (chartExists("ecomCategoryChart")) {
    new ApexCharts(document.getElementById("ecomCategoryChart"), {
      chart: { type: "bar", height: 330, toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [{ name: "Revenue", data: [48240, 36890, 28120, 18430, 12680, 9840] }],
      plotOptions: { bar: { borderRadius: 7, horizontal: true, barHeight: "52%", distributed: true } },
      colors: ["#5D87FF", "#FC185A", "#7460EE", "#13DEB9", "#FFAE1F", "#539BFF"],
      xaxis: { categories: ["Electronics","Footwear","Tablets","Audio","Wearables","Cameras"], labels: { formatter: v => "$" + (v / 1000).toFixed(0) + "k", style: { colors: "#7C8FAC", fontSize: "11px" } }, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { style: { colors: "#7C8FAC", fontSize: "12px" } } },
      dataLabels: { enabled: true, formatter: v => "$" + (v / 1000).toFixed(0) + "k", style: { fontSize: "11px", fontWeight: "700", colors: ["#fff"] }, offsetX: -8 },
      legend: { show: false },
      grid: { borderColor: "#eaecf4", strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
      tooltip: { y: { formatter: v => "$" + v.toLocaleString() } }
    }).render();
  }

  if (chartExists("ecomCustomerChart")) {
    new ApexCharts(document.getElementById("ecomCustomerChart"), {
      chart: { type: "area", height: 310, stacked: true, toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans', sans-serif", zoom: { enabled: false } },
      series: [
        { name: "New Customers",       data: [280,340,310,420,390,480,450,540,510,620,590,680] },
        { name: "Returning Customers", data: [640,720,680,810,760,890,840,960,910,1040,980,1120] }
      ],
      colors: ["#5D87FF", "#13DEB9"],
      fill: { type: "gradient", gradient: { opacityFrom: 0.45, opacityTo: 0.05, shadeIntensity: 0.3 } },
      stroke: { width: [2.5, 2.5], curve: "smooth" },
      xaxis: { categories: ["Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec","Jan"], axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: "#7C8FAC", fontSize: "12px" } } },
      yaxis: { labels: { formatter: v => v >= 1000 ? (v / 1000).toFixed(1) + "k" : v, style: { colors: "#7C8FAC", fontSize: "12px" } } },
      dataLabels: { enabled: false },
      grid: { borderColor: "#eaecf4", strokeDashArray: 4 },
      legend: { position: "top", horizontalAlign: "right", labels: { colors: "#7C8FAC" } },
      tooltip: { shared: true, intersect: false, y: { formatter: v => v.toLocaleString() + " customers" } }
    }).render();
  }

  /* ============================================================
     3.3 GENERAL ANALYTICS DASHBOARD — dashboard-general.html
     ============================================================ */
  if (chartExists("gnlVisitorArea")) {
    new ApexCharts(document.getElementById("gnlVisitorArea"), {
      chart: { type: "area", height: 300, toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans', sans-serif", zoom: { enabled: false } },
      series: [
        { name: "Unique Visitors", data: [31000,40000,28000,51000,42000,59000,48000,70000,61000,78000,68000,84250] },
        { name: "Sessions",        data: [62000,81000,57000,103000,86000,119000,97000,142000,124000,157000,137000,170000] }
      ],
      colors: ["#5D87FF", "#13DEB9"],
      fill: { type: "gradient", gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.04 } },
      stroke: { curve: "smooth", width: 2.5 },
      xaxis: { categories: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"], labels: { style: { colors: "#7C8FAC", fontSize: "11px" } }, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { formatter: v => (v/1000).toFixed(0) + "K", style: { colors: "#7C8FAC", fontSize: "12px" } } },
      grid: { borderColor: "#eaecf4", strokeDashArray: 4 },
      dataLabels: { enabled: false },
      legend: { position: "top", horizontalAlign: "right", labels: { colors: "#7C8FAC" } },
      tooltip: { shared: true, intersect: false, y: { formatter: v => v.toLocaleString() } }
    }).render();
  }

  if (chartExists("gnlDevicePie")) {
    new ApexCharts(document.getElementById("gnlDevicePie"), {
      chart: { type: "donut", height: 180, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [55, 30, 15],
      labels: ["Desktop", "Mobile", "Tablet"],
      colors: ["#5D87FF", "#13DEB9", "#FFAE1F"],
      plotOptions: { pie: { donut: { size: "68%", labels: { show: true, total: { show: true, label: "Devices", fontSize: "11px", color: "#7C8FAC", formatter: () => "3 types" } } } } },
      dataLabels: { enabled: false },
      legend: { show: false },
      stroke: { show: false }
    }).render();
  }

  if (chartExists("gnlGoalRadial")) {
    new ApexCharts(document.getElementById("gnlGoalRadial"), {
      chart: { type: "radialBar", height: 200, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [84, 72, 91, 68],
      labels: ["Users", "Conv.", "Revenue", "Retain"],
      colors: ["#5D87FF", "#13DEB9", "#FFAE1F", "#FA896B"],
      plotOptions: { radialBar: { hollow: { size: "22%" }, track: { background: "#eaecf4" }, dataLabels: { name: { fontSize: "10px" }, value: { fontSize: "11px", fontWeight: 700 }, total: { show: true, label: "Avg", fontSize: "11px", color: "#7C8FAC", formatter: () => "79%" } } } },
      stroke: { lineCap: "round" }
    }).render();
  }

  if (chartExists("gnlFunnelBar")) {
    new ApexCharts(document.getElementById("gnlFunnelBar"), {
      chart: { type: "bar", height: 280, toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [{ name: "Visitors", data: [84250, 42100, 28900, 18400, 9600, 4800] }],
      plotOptions: { bar: { borderRadius: 6, horizontal: true, barHeight: "58%", distributed: true } },
      colors: ["#5D87FF", "#13DEB9", "#FFAE1F", "#7460EE", "#FA896B", "#FC185A"],
      xaxis: { categories: ["Organic Search","Direct","Social Media","Email","Referral","Paid Ads"], labels: { formatter: v => (v/1000).toFixed(0) + "K", style: { colors: "#7C8FAC", fontSize: "11px" } }, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { style: { colors: "#7C8FAC", fontSize: "12px" } } },
      grid: { borderColor: "#eaecf4", strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
      dataLabels: { enabled: true, formatter: v => (v/1000).toFixed(1) + "K", style: { fontSize: "11px", fontWeight: 700, colors: ["#fff"] }, offsetX: -6 },
      legend: { show: false },
      tooltip: { y: { formatter: v => v.toLocaleString() + " visitors" } }
    }).render();
  }

  /* Live counter animation */
  (function () {
    var el = document.getElementById("gnlLiveCount");
    if (!el) return;
    var base = 1842;
    setInterval(function () {
      base += Math.round((Math.random() - 0.48) * 12);
      if (base < 800) base = 800;
      el.textContent = base.toLocaleString();
    }, 2000);
  })();

  /* ============================================================
     3.4 CRYPTO DASHBOARD — dashboard-crypto.html
     ============================================================ */
  if (chartExists("cryCandleChart")) {
    new ApexCharts(document.getElementById("cryCandleChart"), {
      chart: { type: "candlestick", height: 300, toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [{ data: [
        { x: new Date("2024-01-01"), y: [62100,66400,61200,64800] },
        { x: new Date("2024-02-01"), y: [64800,68200,63100,67400] },
        { x: new Date("2024-03-01"), y: [67400,72100,65800,70200] },
        { x: new Date("2024-04-01"), y: [70200,74800,68400,73100] },
        { x: new Date("2024-05-01"), y: [73100,75900,70600,72400] },
        { x: new Date("2024-06-01"), y: [72400,76200,69800,74900] },
        { x: new Date("2024-07-01"), y: [74900,79400,73200,77800] },
        { x: new Date("2024-08-01"), y: [77800,82100,76400,80200] },
        { x: new Date("2024-09-01"), y: [80200,84600,78100,82800] },
        { x: new Date("2024-10-01"), y: [82800,87200,81400,85600] },
        { x: new Date("2024-11-01"), y: [85600,89400,84100,87900] },
        { x: new Date("2024-12-01"), y: [87900,92400,86200,90800] }
      ]}],
      plotOptions: { candlestick: { colors: { upward: "#13DEB9", downward: "#FA896B" }, wick: { useFillColor: true } } },
      xaxis: { type: "datetime", labels: { style: { colors: "#7C8FAC", fontSize: "11px" } }, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { formatter: v => "$" + (v/1000).toFixed(0) + "K", style: { colors: "#7C8FAC", fontSize: "12px" } } },
      grid: { borderColor: "#eaecf4", strokeDashArray: 4 },
      tooltip: { theme: "light" }
    }).render();
  }

  if (chartExists("cryPortfolioPie")) {
    new ApexCharts(document.getElementById("cryPortfolioPie"), {
      chart: { type: "donut", height: 220, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [42, 28, 15, 10, 5],
      labels: ["Bitcoin", "Ethereum", "Solana", "BNB", "Others"],
      colors: ["#FFAE1F", "#5D87FF", "#13DEB9", "#FA896B", "#7460EE"],
      plotOptions: { pie: { donut: { size: "70%", labels: { show: true, total: { show: true, label: "Total", formatter: () => "$84,290", style: { fontSize: "13px", fontWeight: 700, color: "#1e2a3a" } } } } } },
      dataLabels: { enabled: false },
      legend: { show: false },
      stroke: { show: false }
    }).render();
  }

  if (chartExists("cryFearRadial")) {
    new ApexCharts(document.getElementById("cryFearRadial"), {
      chart: { type: "radialBar", height: 200, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [74],
      plotOptions: { radialBar: { startAngle: -135, endAngle: 135, hollow: { size: "62%" }, track: { background: "#eaecf4" }, dataLabels: { name: { show: true, offsetY: -10, color: "#7C8FAC", fontSize: "12px" }, value: { show: true, fontSize: "28px", fontWeight: 800, color: "#1e2a3a", formatter: v => v } } } },
      colors: ["#13DEB9"],
      labels: ["Fear & Greed"],
      fill: { type: "gradient", gradient: { shade: "dark", type: "horizontal", colorFrom: "#FFAE1F", colorTo: "#13DEB9", stops: [0, 100] } }
    }).render();
  }

  if (chartExists("cryMarketBar")) {
    new ApexCharts(document.getElementById("cryMarketBar"), {
      chart: { type: "bar", height: 280, toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [{ name: "Market Cap ($B)", data: [1340, 461, 84, 61, 21, 18, 14] }],
      plotOptions: { bar: { borderRadius: 6, columnWidth: "52%", distributed: true } },
      colors: ["#FFAE1F", "#5D87FF", "#13DEB9", "#FA896B", "#7460EE", "#FC185A", "#539BFF"],
      xaxis: { categories: ["BTC","ETH","SOL","BNB","ADA","DOT","LINK"], labels: { style: { colors: "#7C8FAC", fontSize: "11px" } }, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { formatter: v => "$" + (v >= 1000 ? (v/1000).toFixed(1) + "T" : v + "B"), style: { colors: "#7C8FAC", fontSize: "11px" } } },
      grid: { borderColor: "#eaecf4", strokeDashArray: 4 },
      dataLabels: { enabled: false },
      legend: { show: false },
      tooltip: { y: { formatter: v => "$" + v.toLocaleString() + "B" } }
    }).render();
  }

  /* ============================================================
     3.5 APEX CHARTS PAGE
     ============================================================ */
  if (chartExists("apexLineChart")) {
    new ApexCharts(document.getElementById("apexLineChart"), {
      chart: { type: "line", height: 300, toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [
        { name: "Series A", data: [45, 52, 38, 24, 33, 26, 21, 20, 6, 8, 15, 10] },
        { name: "Series B", data: [26, 21, 20, 6, 8, 15, 10, 13, 35, 42, 57, 62] }
      ],
      colors: ["#5D87FF", "#13DEB9"],
      stroke: { curve: "smooth", width: 3 },
      markers: { size: 4, strokeWidth: 2, strokeColors: "#fff" },
      xaxis: { categories: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"], labels: { style: { colors: "#7C8FAC" } }, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { style: { colors: "#7C8FAC" } } },
      grid: { borderColor: "#eaecf4", strokeDashArray: 4 },
      dataLabels: { enabled: false },
      legend: { position: "top" }
    }).render();
  }

  if (chartExists("apexAreaChart")) {
    new ApexCharts(document.getElementById("apexAreaChart"), {
      chart: { type: "area", height: 300, toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [
        { name: "South", data: [31, 40, 28, 51, 42, 109, 100] },
        { name: "North", data: [11, 32, 45, 32, 34, 52, 41] }
      ],
      colors: ["#5D87FF", "#FFAE1F"],
      fill: { type: "gradient", gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05 } },
      stroke: { curve: "smooth", width: 2 },
      xaxis: { type: "datetime", categories: ["2024-01-01","2024-01-02","2024-01-03","2024-01-04","2024-01-05","2024-01-06","2024-01-07"], axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { style: { colors: "#7C8FAC" } } },
      grid: { borderColor: "#eaecf4", strokeDashArray: 4 },
      dataLabels: { enabled: false },
      legend: { position: "top" },
      tooltip: { x: { format: "dd MMM yyyy" } }
    }).render();
  }

  if (chartExists("apexBarChart")) {
    new ApexCharts(document.getElementById("apexBarChart"), {
      chart: { type: "bar", height: 300, toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [
        { name: "Net Profit", data: [44, 55, 57, 56, 61, 58, 63, 60, 66] },
        { name: "Revenue", data: [76, 85, 101, 98, 87, 105, 91, 114, 94] }
      ],
      colors: ["#5D87FF", "#13DEB9"],
      plotOptions: { bar: { borderRadius: 5, columnWidth: "50%", grouped: true } },
      xaxis: { categories: ["Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct"], labels: { style: { colors: "#7C8FAC" } }, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { style: { colors: "#7C8FAC" } } },
      grid: { borderColor: "#eaecf4", strokeDashArray: 4 },
      dataLabels: { enabled: false },
      legend: { position: "top" }
    }).render();
  }

  if (chartExists("apexPieChart")) {
    new ApexCharts(document.getElementById("apexPieChart"), {
      chart: { type: "pie", height: 300, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [44, 55, 13, 43, 22],
      labels: ["Team A","Team B","Team C","Team D","Team E"],
      colors: ["#5D87FF","#13DEB9","#FFAE1F","#7460EE","#FA896B"],
      legend: { position: "bottom" },
      dataLabels: { formatter: v => v.toFixed(1) + "%" },
      stroke: { show: false }
    }).render();
  }

  if (chartExists("apexDonutChart")) {
    new ApexCharts(document.getElementById("apexDonutChart"), {
      chart: { type: "donut", height: 300, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [44, 55, 41, 17, 15],
      labels: ["Monday","Tuesday","Wednesday","Thursday","Friday"],
      colors: ["#5D87FF","#13DEB9","#FFAE1F","#7460EE","#FA896B"],
      plotOptions: { pie: { donut: { size: "65%" } } },
      legend: { position: "bottom" },
      dataLabels: { enabled: false },
      stroke: { show: false }
    }).render();
  }

  if (chartExists("apexRadialChart")) {
    new ApexCharts(document.getElementById("apexRadialChart"), {
      chart: { type: "radialBar", height: 300, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [67, 84, 97, 61],
      plotOptions: { radialBar: { dataLabels: { name: { fontSize: "13px" }, value: { fontSize: "14px", fontWeight: 700 }, total: { show: true, label: "Total", formatter: () => "75%" } }, hollow: { size: "45%" } } },
      colors: ["#5D87FF","#13DEB9","#FFAE1F","#FA896B"],
      labels: ["Apples","Mangos","Oranges","Grapes"]
    }).render();
  }

  if (chartExists("apexRadarChart")) {
    new ApexCharts(document.getElementById("apexRadarChart"), {
      chart: { type: "radar", height: 300, toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [{ name: "Series 1", data: [80, 50, 30, 40, 100, 20] }, { name: "Series 2", data: [20, 30, 40, 80, 20, 80] }],
      colors: ["#5D87FF", "#FA896B"],
      xaxis: { categories: ["Jan","Feb","Mar","Apr","May","Jun"] },
      stroke: { width: 2 },
      fill: { opacity: 0.15 },
      markers: { size: 4 }
    }).render();
  }

  if (chartExists("apexCandlestick")) {
    new ApexCharts(document.getElementById("apexCandlestick"), {
      chart: { type: "candlestick", height: 300, toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [{ data: [
        { x: new Date(2024,0,1), y: [6629.81,6650.5,6623.04,6633.33] },
        { x: new Date(2024,0,2), y: [6632.01,6643.59,6620,6630.11] },
        { x: new Date(2024,0,3), y: [6630.71,6648.95,6623.34,6635.65] },
        { x: new Date(2024,0,4), y: [6635.65,6651,6629.67,6629.81] },
        { x: new Date(2024,0,5), y: [6629.81,6637.98,6617.38,6623.04] },
        { x: new Date(2024,0,6), y: [6623.04,6628,6606,6612] },
        { x: new Date(2024,0,7), y: [6612,6624.12,6608.43,6622.95] },
        { x: new Date(2024,0,8), y: [6623.91,6673.99,6608.67,6665.79] },
        { x: new Date(2024,0,9), y: [6665.79,6832.15,6629.12,6820.82] },
        { x: new Date(2024,0,10), y: [6820.82,6832.02,6799.12,6810] }
      ]}],
      xaxis: { type: "datetime", labels: { style: { colors: "#7C8FAC" } }, axisBorder: { show: false } },
      yaxis: { tooltip: { enabled: true }, labels: { style: { colors: "#7C8FAC" } } },
      grid: { borderColor: "#eaecf4", strokeDashArray: 4 },
      plotOptions: { candlestick: { colors: { upward: "#13DEB9", downward: "#FA896B" } } }
    }).render();
  }

  if (chartExists("apexHeatmap")) {
    const generateData = (count, range) => {
      const arr = [];
      for (let i = 0; i < count; i++) arr.push({ x: "W" + (i+1), y: Math.floor(Math.random() * (range.max - range.min + 1)) + range.min });
      return arr;
    };
    new ApexCharts(document.getElementById("apexHeatmap"), {
      chart: { type: "heatmap", height: 300, toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [
        { name: "Metric 1", data: generateData(12, { min: 0, max: 90 }) },
        { name: "Metric 2", data: generateData(12, { min: 0, max: 90 }) },
        { name: "Metric 3", data: generateData(12, { min: 0, max: 90 }) },
        { name: "Metric 4", data: generateData(12, { min: 0, max: 90 }) }
      ],
      colors: ["#5D87FF"],
      dataLabels: { enabled: false },
      stroke: { width: 2, colors: ["#fff"] }
    }).render();
  }

  /* ============================================================
     3.6 CALENDAR — apps-calendar.html
     ============================================================ */
  const calGrid = document.getElementById("calGrid");
  if (calGrid) {
    renderCalendar(new Date());
  }

  function renderCalendar(date) {
    const calGrid = document.getElementById("calGrid");
    const calTitle = document.getElementById("calTitle");
    if (!calGrid) return;
    const year = date.getFullYear(), month = date.getMonth();
    const months = ["January","February","March","April","May","June","July","August","September","October","November","December"];
    if (calTitle) calTitle.textContent = months[month] + " " + year;
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();
    const events = { 5: "Team Meeting", 12: "Product Launch", 18: "Client Call", 24: "Sprint Review", 28: "Board Meeting" };
    let html = "";
    for (let i = 0; i < firstDay; i++) html += '<div class="cal-day empty"></div>';
    for (let d = 1; d <= daysInMonth; d++) {
      const isToday = d === today.getDate() && month === today.getMonth() && year === today.getFullYear();
      const hasEvent = events[d];
      html += `<div class="cal-day${isToday ? " today" : ""}${hasEvent ? " has-event" : ""}">
        <span class="cal-date">${d}</span>
        ${hasEvent ? `<div class="cal-event-dot" title="${hasEvent}"></div>` : ""}
      </div>`;
    }
    calGrid.innerHTML = html;
    // Click handler
    calGrid.querySelectorAll(".cal-day:not(.empty)").forEach(day => {
      day.addEventListener("click", function () {
        calGrid.querySelectorAll(".cal-day").forEach(d => d.classList.remove("selected"));
        day.classList.add("selected");
      });
    });
    // Store current date for prev/next
    calGrid.dataset.year  = year;
    calGrid.dataset.month = month;
  }

  const calPrev = document.getElementById("calPrev");
  const calNext = document.getElementById("calNext");
  if (calPrev && calNext && calGrid) {
    calPrev.addEventListener("click", function () {
      const d = new Date(parseInt(calGrid.dataset.year), parseInt(calGrid.dataset.month) - 1, 1);
      renderCalendar(d);
    });
    calNext.addEventListener("click", function () {
      const d = new Date(parseInt(calGrid.dataset.year), parseInt(calGrid.dataset.month) + 1, 1);
      renderCalendar(d);
    });
  }

  /* ============================================================
     3.7 CHAT — apps-chat.html
     ============================================================ */
  const chatForm = document.getElementById("chatForm");
  if (chatForm) {
    chatForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const input = document.getElementById("chatInput");
      const msgs  = document.getElementById("chatMessages");
      if (!input || !msgs || !input.value.trim()) return;
      const msg = document.createElement("div");
      msg.className = "chat-msg sent";
      msg.innerHTML = `<div class="chat-bubble">${input.value.trim()}</div><span class="chat-time">Just now</span>`;
      msgs.appendChild(msg);
      msgs.scrollTop = msgs.scrollHeight;
      input.value = "";
      // Simulate reply
      setTimeout(() => {
        const reply = document.createElement("div");
        reply.className = "chat-msg received";
        reply.innerHTML = `<img class="chat-ava" src="https://ui-avatars.com/api/?name=Sarah+Wilson&background=5D87FF&color=fff" alt=""><div><div class="chat-bubble">Got it! I'll look into that right away.</div><span class="chat-time">Just now</span></div>`;
        msgs.appendChild(reply);
        msgs.scrollTop = msgs.scrollHeight;
      }, 1200);
    });
    // Sidebar contacts click
    document.querySelectorAll(".chat-contact").forEach(c => {
      c.addEventListener("click", function () {
        document.querySelectorAll(".chat-contact").forEach(x => x.classList.remove("active"));
        c.classList.add("active");
      });
    });
  }

  /* ============================================================
     3.8 EMAIL — apps-email.html
     ============================================================ */
  document.querySelectorAll(".email-item").forEach(item => {
    item.addEventListener("click", function () {
      document.querySelectorAll(".email-item").forEach(x => x.classList.remove("active"));
      item.classList.add("active");
      item.classList.remove("unread");
    });
  });

  /* ============================================================
     3.9 NOTES — apps-notes.html
     ============================================================ */
  const addNoteBtn = document.getElementById("addNoteBtn");
  const notesList  = document.getElementById("notesList");
  if (addNoteBtn && notesList) {
    addNoteBtn.addEventListener("click", function () {
      const colors = ["#5D87FF","#13DEB9","#FFAE1F","#7460EE","#FA896B"];
      const c = colors[Math.floor(Math.random() * colors.length)];
      const note = document.createElement("div");
      note.className = "note-card fade-in-up";
      note.style.borderTop = "4px solid " + c;
      note.innerHTML = `
        <div class="note-header"><input class="note-title-input" value="New Note" /><button class="note-del-btn"><i class="bi bi-trash"></i></button></div>
        <textarea class="note-body" rows="5" placeholder="Write something..."></textarea>
        <div class="note-footer"><span style="color:${c}">●</span> <small class="text-muted">Just now</small></div>`;
      note.querySelector(".note-del-btn").addEventListener("click", () => note.remove());
      notesList.prepend(note);
      note.querySelector("textarea").focus();
    });
  }

  /* ============================================================
     3.10 PRICING PAGE
     ============================================================ */
  const priceToggle = document.getElementById("pricingToggle");
  if (priceToggle) {
    priceToggle.addEventListener("change", function () {
      const monthly  = document.querySelectorAll(".price-monthly");
      const yearly   = document.querySelectorAll(".price-yearly");
      const badge    = document.getElementById("pricingBadge");
      if (this.checked) {
        monthly.forEach(el => el.style.display = "none");
        yearly.forEach(el => el.style.display = "inline");
        if (badge) badge.style.display = "inline-block";
      } else {
        monthly.forEach(el => el.style.display = "inline");
        yearly.forEach(el => el.style.display = "none");
        if (badge) badge.style.display = "none";
      }
    });
  }

  /* ============================================================
     3.11 FAQ PAGE
     ============================================================ */
  document.querySelectorAll(".faq-cat-btn").forEach(btn => {
    btn.addEventListener("click", function () {
      document.querySelectorAll(".faq-cat-btn").forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      const cat = btn.getAttribute("data-cat");
      document.querySelectorAll(".faq-item").forEach(item => {
        if (cat === "all" || item.getAttribute("data-cat") === cat) {
          item.style.display = "";
        } else {
          item.style.display = "none";
        }
      });
    });
  });

  /* ============================================================
     3.12 DATATABLES (Custom)
     ============================================================ */
  const dtTable = document.getElementById("dataTable");
  if (dtTable) {
    const search  = document.getElementById("dtSearch");
    const perPage = document.getElementById("dtPerPage");
    const info    = document.getElementById("dtInfo");
    const prev    = document.getElementById("dtPrev");
    const next    = document.getElementById("dtNext");
    let rows = Array.from(dtTable.querySelectorAll("tbody tr"));
    let page = 1, per = 10, query = "";

    function filterSort() {
      return rows.filter(r => r.textContent.toLowerCase().includes(query.toLowerCase()));
    }
    function render() {
      const filtered = filterSort();
      const total = filtered.length;
      const totalPages = Math.max(1, Math.ceil(total / per));
      if (page > totalPages) page = totalPages;
      const start = (page - 1) * per;
      rows.forEach(r => r.style.display = "none");
      filtered.slice(start, start + per).forEach(r => r.style.display = "");
      if (info) info.textContent = `Showing ${Math.min(start+1, total)}–${Math.min(start+per, total)} of ${total} entries`;
      if (prev) prev.disabled = page <= 1;
      if (next) next.disabled = page >= totalPages;
    }
    if (search) search.addEventListener("input", e => { query = e.target.value; page = 1; render(); });
    if (perPage) perPage.addEventListener("change", e => { per = parseInt(e.target.value); page = 1; render(); });
    if (prev) prev.addEventListener("click", () => { page--; render(); });
    if (next) next.addEventListener("click", () => { page++; render(); });

    // Column sort
    dtTable.querySelectorAll("thead th[data-sort]").forEach(th => {
      th.style.cursor = "pointer";
      th.addEventListener("click", function () {
        const col = Array.from(th.parentElement.children).indexOf(th);
        const asc = th.dataset.order !== "asc";
        th.dataset.order = asc ? "asc" : "desc";
        rows.sort((a, b) => {
          const av = a.cells[col].textContent.trim();
          const bv = b.cells[col].textContent.trim();
          const an = parseFloat(av.replace(/[^0-9.-]/g, "")), bn = parseFloat(bv.replace(/[^0-9.-]/g, ""));
          if (!isNaN(an) && !isNaN(bn)) return asc ? an - bn : bn - an;
          return asc ? av.localeCompare(bv) : bv.localeCompare(av);
        });
        const tbody = dtTable.querySelector("tbody");
        rows.forEach(r => tbody.appendChild(r));
        render();
      });
    });
    render();
  }

  /* ============================================================
     3.13 FORM VALIDATION
     ============================================================ */
  document.querySelectorAll("form.needs-validation").forEach(form => {
    form.addEventListener("submit", function (e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      form.classList.add("was-validated");
    });
  });

  /* ============================================================
     3.14 KANBAN — apps-kanban.html
     ============================================================ */
  const kanbanCols = document.querySelectorAll(".kanban-col");
  if (kanbanCols.length) {
    let dragging = null;
    document.querySelectorAll(".kanban-card").forEach(card => {
      card.setAttribute("draggable", "true");
      card.addEventListener("dragstart", () => { dragging = card; setTimeout(() => card.classList.add("dragging"), 0); });
      card.addEventListener("dragend", () => { card.classList.remove("dragging"); dragging = null; });
    });
    kanbanCols.forEach(col => {
      const list = col.querySelector(".kanban-list");
      if (!list) return;
      list.addEventListener("dragover", e => { e.preventDefault(); const after = getDragAfterElement(list, e.clientY); if (!after) list.appendChild(dragging); else list.insertBefore(dragging, after); });
    });
    function getDragAfterElement(container, y) {
      const els = [...container.querySelectorAll(".kanban-card:not(.dragging)")];
      return els.reduce((closest, el) => {
        const box = el.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        return (offset < 0 && offset > closest.offset) ? { offset, element: el } : closest;
      }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
  }

  /* ============================================================
     3.15 ACCOUNT SETTINGS TABS
     ============================================================ */
  document.querySelectorAll(".settings-nav-link").forEach(link => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      document.querySelectorAll(".settings-nav-link").forEach(l => l.classList.remove("active"));
      link.classList.add("active");
      const target = link.getAttribute("data-tab");
      document.querySelectorAll(".settings-tab").forEach(tab => {
        tab.style.display = tab.id === target ? "" : "none";
      });
    });
  });
  // Show first tab by default
  const firstSettingsTab = document.querySelector(".settings-nav-link");
  if (firstSettingsTab) firstSettingsTab.click();

  /* ============================================================
     3.16 MUSIC DASHBOARD — dashboard-music.html
     ============================================================ */
  const playBtn = document.getElementById("musicPlayBtn");
  if (playBtn) {
    playBtn.addEventListener("click", function () {
      const icon = playBtn.querySelector("i");
      if (icon.classList.contains("bi-play-circle-fill")) {
        icon.className = "bi bi-pause-circle-fill";
      } else {
        icon.className = "bi bi-play-circle-fill";
      }
    });
  }

  if (chartExists("musStreamArea")) {
    new ApexCharts(document.getElementById("musStreamArea"), {
      chart: { type: "area", height: 350, toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans', sans-serif", zoom: { enabled: false } },
      series: [
        { name: "Total Streams", data: [120000000,145000000,132000000,168000000,142000000,175000000,165000000,190000000,178000000,212000000,198000000,240000000] },
        { name: "Monthly Listeners", data: [8200000,9800000,8900000,11200000,9600000,12100000,11400000,13800000,12400000,15600000,14200000,18200000] }
      ],
      colors: ["#5D87FF", "#FC185A"],
      fill: { type: "gradient", gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.04 } },
      stroke: { curve: "smooth", width: 2.5 },
      xaxis: { categories: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"], labels: { style: { colors: "#7C8FAC", fontSize: "11px" } }, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { formatter: v => v >= 1000000 ? (v/1000000).toFixed(0) + "M" : (v/1000).toFixed(0) + "K", style: { colors: "#7C8FAC", fontSize: "12px" } } },
      grid: { borderColor: "#eaecf4", strokeDashArray: 4 },
      dataLabels: { enabled: false },
      legend: { position: "top", horizontalAlign: "right", labels: { colors: "#7C8FAC" } },
      tooltip: { shared: true, intersect: false, y: { formatter: v => v >= 1000000 ? (v/1000000).toFixed(1) + "M" : v.toLocaleString() } }
    }).render();
  }

  if (chartExists("musPlatformBar")) {
    new ApexCharts(document.getElementById("musPlatformBar"), {
      chart: { type: "donut", height: 180, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [42, 28, 18, 8, 4],
      labels: ["Spotify", "Apple Music", "YouTube", "Amazon", "Others"],
      colors: ["#13DEB9", "#FA896B", "#FC185A", "#FFAE1F", "#7460EE"],
      plotOptions: { pie: { donut: { size: "66%", labels: { show: true, total: { show: true, label: "Platforms", fontSize: "10px", color: "#7C8FAC", formatter: () => "5 total" } } } } },
      dataLabels: { enabled: false },
      legend: { show: false },
      stroke: { show: false }
    }).render();
  }

  /* ============================================================
     3.17 NFT DASHBOARD — dashboard-nft.html
     ============================================================ */
  if (chartExists("nftVolumeArea")) {
    new ApexCharts(document.getElementById("nftVolumeArea"), {
      chart: { type: "area", height: 300, toolbar: { show: false }, fontFamily: "'Plus Jakarta Sans', sans-serif", zoom: { enabled: false } },
      series: [
        { name: "Sales Volume (ETH)", data: [820,1050,940,1420,1180,1680,1520,2100,1840,2480,2140,3200] },
        { name: "Transactions",        data: [380,490,420,610,540,780,680,920,840,1080,960,1284] }
      ],
      colors: ["#7460EE", "#FC185A"],
      fill: { type: "gradient", gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.04 } },
      stroke: { curve: "smooth", width: 2.5 },
      xaxis: { categories: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"], labels: { style: { colors: "#7C8FAC", fontSize: "11px" } }, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: [
        { labels: { formatter: v => v + " ETH", style: { colors: "#7C8FAC", fontSize: "11px" } } },
        { opposite: true, labels: { formatter: v => v + " txns", style: { colors: "#7C8FAC", fontSize: "11px" } } }
      ],
      grid: { borderColor: "#eaecf4", strokeDashArray: 4 },
      dataLabels: { enabled: false },
      legend: { position: "top", horizontalAlign: "right", labels: { colors: "#7C8FAC" } },
      tooltip: { shared: true, intersect: false }
    }).render();
  }

  if (chartExists("nftRarityDonut")) {
    new ApexCharts(document.getElementById("nftRarityDonut"), {
      chart: { type: "donut", height: 220, fontFamily: "'Plus Jakarta Sans', sans-serif" },
      series: [38, 28, 18, 10, 6],
      labels: ["Art", "Collectibles", "Gaming", "Music", "Other"],
      colors: ["#7460EE", "#5D87FF", "#13DEB9", "#FFAE1F", "#FA896B"],
      plotOptions: { pie: { donut: { size: "70%", labels: { show: true, total: { show: true, label: "Categories", fontSize: "11px", color: "#7C8FAC", formatter: () => "5 types" } } } } },
      dataLabels: { enabled: false },
      legend: { show: false },
      stroke: { show: false }
    }).render();
  }

  /* NFT Auction countdown timers */
  (function () {
    function tick(hEl, mEl, sEl) {
      if (!hEl || !mEl || !sEl) return;
      var h = parseInt(hEl.textContent, 10);
      var m = parseInt(mEl.textContent, 10);
      var s = parseInt(sEl.textContent, 10);
      s--;
      if (s < 0) { s = 59; m--; }
      if (m < 0) { m = 59; h--; }
      if (h < 0) { h = 0; m = 0; s = 0; }
      hEl.textContent = String(h).padStart(2, "0");
      mEl.textContent = String(m).padStart(2, "0");
      sEl.textContent = String(s).padStart(2, "0");
    }
    var pairs = [
      ["nftTimer1H","nftTimer1M","nftTimer1S"],
      ["nftTimer2H","nftTimer2M","nftTimer2S"],
      ["nftTimer3H","nftTimer3M","nftTimer3S"]
    ];
    var allExist = pairs.every(function(p) { return document.getElementById(p[0]); });
    if (allExist) {
      setInterval(function () {
        pairs.forEach(function (p) {
          tick(document.getElementById(p[0]), document.getElementById(p[1]), document.getElementById(p[2]));
        });
      }, 1000);
    }
  })();

  /* ============================================================
     3.18 TOOLTIP INIT (Bootstrap)
     ============================================================ */
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
  });
  document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
    new bootstrap.Popover(el);
  });

  /* ---- Header Dropdowns — open on hover ---- */
  document.querySelectorAll(".app-header .dropdown").forEach(function (wrap) {
    var toggle  = wrap.querySelector("[data-bs-toggle='dropdown']");
    var hideTimer = null;

    function getDropdown() {
      return bootstrap.Dropdown.getOrCreateInstance(toggle);
    }

    wrap.addEventListener("mouseenter", function () {
      clearTimeout(hideTimer);
      getDropdown().show();
    });

    wrap.addEventListener("mouseleave", function () {
      hideTimer = setTimeout(function () {
        getDropdown().hide();
      }, 180);
    });
  });

  /* ---- 2.99 Collapsed Sidebar Flyout ---- */
  (function () {
    var flyout    = null;
    var hideTimer = null;

    function removeFlyout() {
      if (flyout) { flyout.remove(); flyout = null; }
    }

    function showFlyout(item) {
      if (!document.body.classList.contains("sidebar-collapsed")) return;
      clearTimeout(hideTimer);
      removeFlyout();

      var link    = item.querySelector(":scope > .sidebar-link");
      var labelEl = link ? link.querySelector(".hide-menu") : null;
      if (!labelEl) return;

      var label      = labelEl.textContent.trim();
      var href       = (link.tagName === "A") ? link.getAttribute("href") : null;
      var iconEl     = link.querySelector("i");
      var iconClass  = iconEl ? iconEl.className : "";
      var isItemActive = item.classList.contains("active");
      var subLinks   = item.querySelectorAll(":scope > .collapse li a");

      var fly = document.createElement("div");
      fly.className = "sidebar-flyout";

      /* Title row */
      var titleEl = document.createElement(href && href !== "#" ? "a" : "div");
      titleEl.className = "flyout-title" + (isItemActive ? " active" : "");
      if (href && href !== "#") titleEl.setAttribute("href", href);

      if (iconClass) {
        var iEl = document.createElement("i");
        iEl.className = iconClass;
        titleEl.appendChild(iEl);
      }
      titleEl.appendChild(document.createTextNode(label));
      fly.appendChild(titleEl);

      /* Submenu list */
      if (subLinks.length) {
        var divider = document.createElement("div");
        divider.className = "flyout-divider";
        fly.appendChild(divider);

        var ul = document.createElement("ul");
        ul.className = "flyout-list";
        subLinks.forEach(function (a) {
          var li  = document.createElement("li");
          var newA = document.createElement("a");
          newA.setAttribute("href", a.getAttribute("href") || "#");
          newA.textContent = a.textContent.trim();
          if (a.classList.contains("active") || a.closest(".sidebar-item") && a.closest(".sidebar-item").classList.contains("active")) {
            newA.classList.add("active");
          }
          li.appendChild(newA);
          ul.appendChild(li);
        });
        fly.appendChild(ul);
      }

      document.body.appendChild(fly);
      flyout = fly;

      /* Position: align top with item, flip if near viewport bottom */
      var rect = item.getBoundingClientRect();
      var flyH = fly.offsetHeight;
      var winH = window.innerHeight;
      var top  = rect.top;
      if (top + flyH > winH - 12) top = Math.max(12, winH - flyH - 12);
      fly.style.top = top + "px";

      fly.addEventListener("mouseenter", function () { clearTimeout(hideTimer); });
      fly.addEventListener("mouseleave", function () { hideTimer = setTimeout(removeFlyout, 120); });
    }

    var sidebar = document.getElementById("appSidebar");
    if (!sidebar) return;

    sidebar.querySelectorAll(".sidebar-menu > .sidebar-item").forEach(function (item) {
      item.addEventListener("mouseenter", function () { showFlyout(item); });
      item.addEventListener("mouseleave", function () { hideTimer = setTimeout(removeFlyout, 120); });
    });

    /* Close flyout when sidebar expands */
    document.addEventListener("click", function (e) {
      if (e.target.closest("#sidebarToggle") || e.target.closest("#sidebarToggle2")) {
        setTimeout(removeFlyout, 50);
      }
    });
  })();

  /* ============================================================
     3.20 CALENDAR REDESIGN — apps-calendar.html (cal- prefix)
     ============================================================ */
  // View toggle buttons
  document.querySelectorAll(".cal-view-btn").forEach(btn => {
    btn.addEventListener("click", function () {
      document.querySelectorAll(".cal-view-btn").forEach(b => b.classList.remove("cal-view-active"));
      btn.classList.add("cal-view-active");
    });
  });
  // Week strip day selection
  document.querySelectorAll(".cal-week-day").forEach(day => {
    day.addEventListener("click", function () {
      document.querySelectorAll(".cal-week-day").forEach(d => d.classList.remove("cal-week-selected"));
      day.classList.add("cal-week-selected");
    });
  });
  // Schedule items hover handled by CSS, click for expand
  document.querySelectorAll(".cal-sch-item").forEach(item => {
    item.style.cursor = "pointer";
  });

  /* ============================================================
     3.21 CHAT REDESIGN — apps-chat.html (cht- prefix)
     ============================================================ */
  // Contact item selection
  document.querySelectorAll(".cht-contact-item").forEach(item => {
    item.addEventListener("click", function () {
      document.querySelectorAll(".cht-contact-item").forEach(i => i.classList.remove("cht-contact-active"));
      item.classList.add("cht-contact-active");
    });
  });
  // Tab selection
  document.querySelectorAll(".cht-tab").forEach(tab => {
    tab.addEventListener("click", function () {
      document.querySelectorAll(".cht-tab").forEach(t => t.classList.remove("cht-tab-active"));
      tab.classList.add("cht-tab-active");
    });
  });
  // Send button
  const chtSendBtn = document.querySelector(".cht-send-btn");
  const chtInput   = document.querySelector(".cht-input-field");
  const chtMsgs    = document.querySelector(".cht-messages");
  if (chtSendBtn && chtInput && chtMsgs) {
    function chtSend() {
      const val = chtInput.value.trim();
      if (!val) return;
      const row = document.createElement("div");
      row.className = "cht-msg-row cht-msg-outgoing";
      row.innerHTML = `<div class="cht-msg-group"><div class="cht-bubble cht-bubble-out">${val}</div><div class="cht-msg-time cht-time-out">Just now · <i class="bi bi-check2-all cht-read-icon"></i></div></div>`;
      chtMsgs.appendChild(row);
      chtMsgs.scrollTop = chtMsgs.scrollHeight;
      chtInput.value = "";
      // Typing indicator
      const typing = document.createElement("div");
      typing.className = "cht-msg-row cht-msg-incoming";
      typing.innerHTML = `<div class="cht-msg-av cht-av-blue">SR</div><div class="cht-typing-bubble"><span class="cht-typing-dot"></span><span class="cht-typing-dot"></span><span class="cht-typing-dot"></span></div>`;
      chtMsgs.appendChild(typing);
      chtMsgs.scrollTop = chtMsgs.scrollHeight;
      setTimeout(() => {
        typing.remove();
        const reply = document.createElement("div");
        reply.className = "cht-msg-row cht-msg-incoming";
        reply.innerHTML = `<div class="cht-msg-av cht-av-blue">SR</div><div class="cht-msg-group"><div class="cht-bubble cht-bubble-in">Got it! Thanks for the update. 👍</div><div class="cht-msg-time">Just now</div></div>`;
        chtMsgs.appendChild(reply);
        chtMsgs.scrollTop = chtMsgs.scrollHeight;
      }, 1800);
    }
    chtSendBtn.addEventListener("click", chtSend);
    chtInput.addEventListener("keydown", function (e) { if (e.key === "Enter") chtSend(); });
  }

  /* ============================================================
     3.22 EMAIL REDESIGN — apps-email.html (eml- prefix)
     ============================================================ */
  // Email item selection
  document.querySelectorAll(".eml-email-item").forEach(item => {
    item.addEventListener("click", function () {
      document.querySelectorAll(".eml-email-item").forEach(i => { i.classList.remove("eml-email-active"); });
      item.classList.add("eml-email-active");
      item.classList.remove("eml-email-unread");
    });
  });
  // Folder item selection
  document.querySelectorAll(".eml-folder-item").forEach(item => {
    item.addEventListener("click", function () {
      document.querySelectorAll(".eml-folder-item").forEach(i => i.classList.remove("eml-folder-active"));
      item.classList.add("eml-folder-active");
    });
  });

  /* ============================================================
     3.23 CONTACT TABLE — apps-contact.html (ctb- prefix)
     ============================================================ */
  // Department pill selection
  document.querySelectorAll(".ctb-dept-pill").forEach(pill => {
    pill.addEventListener("click", function () {
      document.querySelectorAll(".ctb-dept-pill").forEach(p => p.classList.remove("ctb-dept-active"));
      pill.classList.add("ctb-dept-active");
    });
  });
  // Select all checkbox
  const ctbSelectAll = document.getElementById("selectAll");
  if (ctbSelectAll) {
    ctbSelectAll.addEventListener("change", function () {
      document.querySelectorAll(".ctb-table input[type=checkbox]").forEach(cb => { cb.checked = ctbSelectAll.checked; });
    });
  }

  /* ============================================================
     3.24 CONTACT LIST — apps-contact2.html (ctl- prefix)
     ============================================================ */
  // Filter chip selection
  document.querySelectorAll(".ctl-filter-chip").forEach(chip => {
    chip.addEventListener("click", function () {
      document.querySelectorAll(".ctl-filter-chip").forEach(c => c.classList.remove("ctl-filter-active"));
      chip.classList.add("ctl-filter-active");
    });
  });

  /* ============================================================
     3.25 INVOICE — apps-invoice.html (inv- prefix)
     ============================================================ */
  // Invoice filter select visual feedback (handled natively by <select>)
  // Monthly bar tooltips on hover — CSS handles hover color, no extra JS needed

  /* ============================================================
     3.26 PRICING — pages-pricing.html (pri- prefix)
     ============================================================ */
  // Billing toggle (Monthly / Annual)
  const priToggle = document.getElementById('billingToggle');
  if (priToggle) {
    const track = priToggle.querySelector('.pri-toggle-track');
    priToggle.addEventListener('click', function () {
      const isAnnual = track.classList.toggle('pri-annual');
      document.querySelectorAll('[data-monthly]').forEach(el => {
        el.textContent = isAnnual ? el.dataset.annual : el.dataset.monthly;
      });
    });
  }

  /* ============================================================
     3.27 FAQ — pages-faq.html (faq- prefix)
     ============================================================ */
  // Category filter pills
  document.querySelectorAll('.faq-cat-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.faq-cat-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const cat = btn.dataset.cat;
      document.querySelectorAll('.faq-group').forEach(group => {
        if (cat === 'all' || group.dataset.cat === cat) {
          group.style.display = '';
        } else {
          group.style.display = 'none';
        }
      });
    });
  });
  // FAQ search
  const faqSearch = document.getElementById('faqSearch');
  if (faqSearch) {
    faqSearch.addEventListener('input', function () {
      const term = faqSearch.value.toLowerCase().trim();
      document.querySelectorAll('.faq-item').forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = (!term || text.includes(term)) ? '' : 'none';
      });
    });
  }
  // Vote buttons
  document.querySelectorAll('.faq-vote-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const row = btn.closest('.faq-vote-row');
      row.querySelectorAll('.faq-vote-btn').forEach(b => b.classList.remove('voted'));
      btn.classList.add('voted');
    });
  });

  /* ============================================================
     3.28 ACCOUNT SETTINGS — pages-account-settings.html (acc- prefix)
     ============================================================ */
  // Vertical icon nav tab switching
  document.querySelectorAll('.acc-nav-item').forEach(navItem => {
    navItem.addEventListener('click', function () {
      document.querySelectorAll('.acc-nav-item').forEach(n => n.classList.remove('acc-nav-active'));
      navItem.classList.add('acc-nav-active');
      const target = navItem.dataset.accTab;
      document.querySelectorAll('.acc-tab-content').forEach(pane => pane.classList.add('d-none'));
      const pane = document.getElementById('acc-tab-' + target);
      if (pane) pane.classList.remove('d-none');
    });
  });

  /* ============================================================
     3.29 APP WIDGETS — widgets-apps.html (wap- prefix)
     ============================================================ */
  // Category filter
  document.querySelectorAll('.wap-cat-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.wap-cat-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const cat = btn.dataset.cat;
      document.querySelectorAll('.wap-app-item').forEach(item => {
        item.style.display = (cat === 'all' || item.dataset.cat === cat) ? '' : 'none';
      });
    });
  });
  // Install / Uninstall toggle
  document.querySelectorAll('.wap-install-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      if (btn.classList.contains('wap-installed')) {
        btn.classList.remove('wap-installed');
        btn.textContent = 'Install';
      } else {
        btn.classList.add('wap-installed');
        btn.textContent = 'Installed';
      }
    });
  });

  /* ============================================================
     3.30 BANNER WIDGETS — widgets-banners.html (wbn- prefix)
     ============================================================ */
  // Format selector
  document.querySelectorAll('.wbn-fmt-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.wbn-fmt-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const label = document.getElementById('wbnFmtLabel');
      if (label) label.textContent = btn.querySelector('.wbn-fmt-name').textContent + ' – ' + btn.dataset.dims;
      const banner = document.getElementById('wbnActiveBanner');
      if (banner) {
        banner.className = banner.className.replace(/wbn-banner-\S+/g, '').trim();
        banner.classList.add('wbn-preview-banner', 'wbn-banner-' + btn.dataset.fmt);
        // Restore current palette theme
        const activePal = document.querySelector('.wbn-palette-swatch.active');
        if (activePal) banner.classList.add('wbn-theme-' + activePal.dataset.palette);
      }
    });
  });
  // Palette switcher
  document.querySelectorAll('.wbn-palette-swatch').forEach(swatch => {
    swatch.addEventListener('click', function () {
      document.querySelectorAll('.wbn-palette-swatch').forEach(s => s.classList.remove('active'));
      swatch.classList.add('active');
      const banner = document.getElementById('wbnActiveBanner');
      if (banner) {
        banner.className = banner.className.replace(/wbn-theme-\S+/g, '').trim();
        banner.classList.add('wbn-theme-' + swatch.dataset.palette);
      }
    });
  });
  // Template gallery filter
  document.querySelectorAll('.wbn-gallery-filter').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.wbn-gallery-filter').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const filter = btn.dataset.filter;
      document.querySelectorAll('.wbn-tpl-item').forEach(item => {
        item.style.display = (filter === 'all' || item.dataset.filter === filter) ? '' : 'none';
      });
    });
  });

  /* ============================================================
     3.31 CARD WIDGETS — widgets-cards.html (wcd- prefix)
     ============================================================ */
  // Type navigation
  document.querySelectorAll('.wcd-type-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.wcd-type-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const type = btn.dataset.type;
      const titleEl = document.getElementById('wcdGalleryTitle');
      const countText = btn.querySelector('.wcd-type-text small').textContent;
      const typeName = btn.querySelector('.wcd-type-text span').textContent;
      if (titleEl) titleEl.textContent = typeName + ' — ' + countText;
      document.querySelectorAll('.wcd-card-item').forEach(item => {
        item.style.display = (item.dataset.type === type) ? '' : 'none';
      });
    });
  });
  // Complexity filter
  document.querySelectorAll('.wcd-cplx-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.wcd-cplx-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const cplx = btn.dataset.cplx;
      // Also respect active type
      const activeType = document.querySelector('.wcd-type-btn.active');
      document.querySelectorAll('.wcd-card-item').forEach(item => {
        const typeMatch = !activeType || item.dataset.type === activeType.dataset.type;
        const cplxMatch = cplx === 'all' || item.dataset.cplx === cplx;
        item.style.display = (typeMatch && cplxMatch) ? '' : 'none';
      });
    });
  });
  // Copy button feedback
  document.querySelectorAll('.wcd-copy-btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      const orig = btn.innerHTML;
      btn.innerHTML = '<i class="bi bi-check-lg"></i>';
      setTimeout(() => { btn.innerHTML = orig; }, 1200);
    });
  });

  /* ============================================================
     3.32 CHART WIDGETS — widgets-charts.html (wch- prefix)
     ============================================================ */
  if (document.getElementById('wchMainChart')) {

    // ── state ────────────────────────────────────────────────
    let mainChart   = null;
    let curType     = 'line';
    let curScheme   = 'primary';
    let curPeriod   = 7;
    let showGrid    = true;
    let showLegend  = true;
    let smoothCurve = true;
    let showAnim    = true;

    // ── color palettes ───────────────────────────────────────
    const schemes = {
      primary : ['#5D87FF','#7460EE'],
      success : ['#13DEB9','#00b5a0'],
      purple  : ['#7460EE','#5D87FF'],
      warning : ['#FFAE1F','#FA896B'],
      danger  : ['#FA896B','#FF2D2D'],
      multi   : ['#5D87FF','#13DEB9','#FFAE1F','#7460EE','#FA896B']
    };

    // ── data by period ───────────────────────────────────────
    const datasets = {
      7  : { cats:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'], rev:[42,68,55,81,73,96,88],       exp:[30,45,38,52,47,61,55] },
      30 : { cats:['W1','W2','W3','W4'],                       rev:[240,310,280,390],             exp:[180,220,195,260] },
      90 : { cats:['Jan','Feb','Mar'],                          rev:[820,960,1100],               exp:[600,720,850] },
      365: { cats:['Q1','Q2','Q3','Q4'],                        rev:[2800,3200,3600,4100],        exp:[2100,2400,2700,3100] }
    };

    // ── build ApexCharts options by type ─────────────────────
    function buildOpts(type, scheme, period) {
      const colors = schemes[scheme] || schemes.primary;
      const ds     = datasets[period] || datasets[7];
      const base   = {
        chart  : { height:450, toolbar:{ show:false }, animations:{ enabled:showAnim } },
        colors,
        grid   : { show:showGrid, borderColor:'#f0f2f8' },
        legend : { show:showLegend, position:'top' },
        xaxis  : { categories:ds.cats },
        tooltip: { y:{ formatter: v => '$' + v + 'K' } }
      };
      switch (type) {
        case 'bar':
          return { ...base, chart:{...base.chart,type:'bar'},
            plotOptions:{ bar:{ borderRadius:4, columnWidth:'55%' } },
            series:[{ name:'Revenue', data:ds.rev },{ name:'Expenses', data:ds.exp }] };
        case 'area':
          return { ...base, chart:{...base.chart,type:'area'},
            stroke:{ curve:smoothCurve?'smooth':'straight', width:2 },
            fill:{ type:'gradient', gradient:{ opacityFrom:.35, opacityTo:.05 } },
            series:[{ name:'Revenue', data:ds.rev },{ name:'Expenses', data:ds.exp }] };
        case 'pie':
          return { chart:{ type:'pie', height:450, animations:{ enabled:showAnim } },
            labels:ds.cats, series:ds.rev, colors:schemes.multi,
            legend:{ show:showLegend, position:'bottom' } };
        case 'donut':
          return { chart:{ type:'donut', height:450, animations:{ enabled:showAnim } },
            labels:ds.cats, series:ds.rev, colors:schemes.multi,
            plotOptions:{ pie:{ donut:{ size:'65%' } } },
            legend:{ show:showLegend, position:'bottom' } };
        case 'radar':
          return { chart:{ type:'radar', height:450, toolbar:{ show:false }, animations:{ enabled:showAnim } },
            colors, legend:{ show:showLegend, position:'top' }, xaxis:{ categories:ds.cats },
            series:[{ name:'Revenue', data:ds.rev },{ name:'Expenses', data:ds.exp }] };
        case 'scatter':
          return { ...base, chart:{...base.chart,type:'scatter'}, xaxis:{ type:'numeric' },
            series:[{ name:'Revenue', data:ds.rev.map((v,i)=>({ x:i*10+5, y:v })) }] };
        case 'heatmap':
          return { chart:{ type:'heatmap', height:450, toolbar:{ show:false }, animations:{ enabled:showAnim } },
            colors:[colors[0]], legend:{ show:showLegend },
            series:[
              { name:'Revenue', data:ds.cats.map((c,i)=>({ x:c, y:ds.rev[i]||0 })) },
              { name:'Expenses',data:ds.cats.map((c,i)=>({ x:c, y:ds.exp[i]||0 })) }
            ] };
        case 'candlestick':
          return { chart:{ type:'candlestick', height:450, toolbar:{ show:false }, animations:{ enabled:showAnim } },
            grid:{ show:showGrid, borderColor:'#f0f2f8' },
            series:[{ data:ds.cats.map((c,i)=>{
              const b=ds.rev[i]||50; return { x:c, y:[b-8,b+15,b-3,b+10] };
            }) }] };
        case 'bubble':
          return { ...base, chart:{...base.chart,type:'bubble'}, xaxis:{ type:'numeric' },
            series:[{ name:'Revenue', data:ds.rev.map((v,i)=>({ x:i*15+10, y:v, z:ds.exp[i]||v/2 })) }] };
        case 'radialbar':
          return { chart:{ type:'radialBar', height:450, animations:{ enabled:showAnim } },
            series:ds.rev.slice(0,4).map(v=>Math.round((v/Math.max(...ds.rev))*100)),
            labels:ds.cats.slice(0,4), colors,
            legend:{ show:showLegend, position:'bottom' },
            plotOptions:{ radialBar:{ dataLabels:{ total:{ show:true, label:'Avg' } } } } };
        case 'treemap':
          return { chart:{ type:'treemap', height:450, toolbar:{ show:false }, animations:{ enabled:showAnim } },
            series:[{ data:ds.cats.map((c,i)=>({ x:c, y:ds.rev[i]||0 })) }],
            colors, legend:{ show:false } };
        case 'polararea':
          return { chart:{ type:'polarArea', height:450, animations:{ enabled:showAnim } },
            series:ds.rev.slice(0,5), labels:ds.cats.slice(0,5), colors:schemes.multi,
            legend:{ show:showLegend, position:'bottom' } };
        case 'rangearea':
          return { ...base, chart:{...base.chart,type:'rangeArea'},
            stroke:{ curve:smoothCurve?'smooth':'straight', width:0 }, fill:{ opacity:.3 },
            series:[{ name:'Revenue Range',
              data:ds.cats.map((c,i)=>({ x:c, y:[ds.exp[i]||0, ds.rev[i]||0] })) }] };
        case 'funnel':
          return { chart:{ type:'bar', height:450, toolbar:{ show:false }, animations:{ enabled:showAnim } },
            plotOptions:{ bar:{ horizontal:true, borderRadius:2, isFunnel:true } },
            colors, series:[{ name:'Funnel', data:[...ds.rev].sort((a,b)=>b-a) }],
            xaxis:{ categories:ds.cats }, legend:{ show:false },
            grid:{ show:showGrid, borderColor:'#f0f2f8' } };
        case 'mixed':
          return { ...base, chart:{...base.chart,type:'line'},
            stroke:{ width:[3,0,2], curve:'smooth', dashArray:[0,0,5] },
            series:[
              { name:'Revenue',  type:'line',   data:ds.rev },
              { name:'Expenses', type:'column', data:ds.exp },
              { name:'Trend',    type:'line',   data:ds.rev.map((v,i)=>Math.round((v+(ds.exp[i]||0))/2)) }
            ] };
        default: // line
          return { ...base, chart:{...base.chart,type:'line'},
            stroke:{ curve:smoothCurve?'smooth':'straight', width:3 },
            series:[{ name:'Revenue', data:ds.rev },{ name:'Expenses', data:ds.exp }] };
      }
    }

    // ── render / re-render ───────────────────────────────────
    function renderMain() {
      const el = document.getElementById('wchMainChart');
      if (!el) return;
      if (mainChart) { mainChart.destroy(); mainChart = null; }
      mainChart = new ApexCharts(el, buildOpts(curType, curScheme, curPeriod));
      mainChart.render();
    }
    renderMain();

    // ── type tiles ───────────────────────────────────────────
    document.querySelectorAll('.wch-type-tile').forEach(tile => {
      tile.addEventListener('click', function () {
        document.querySelectorAll('.wch-type-tile').forEach(t => t.classList.remove('active'));
        tile.classList.add('active');
        curType = tile.dataset.chart;
        const titleEl = document.getElementById('wchDemoTitle');
        if (titleEl) titleEl.textContent = tile.querySelector('.wch-tile-label').textContent;
        renderMain();
      });
    });

    // ── color scheme ─────────────────────────────────────────
    document.querySelectorAll('.wch-scheme-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.wch-scheme-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        curScheme = btn.dataset.scheme;
        renderMain();
      });
    });

    // ── period ───────────────────────────────────────────────
    document.querySelectorAll('.wch-period-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.wch-period-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        curPeriod = parseInt(btn.dataset.period);
        renderMain();
      });
    });

    // ── toggles ──────────────────────────────────────────────
    const gridEl = document.getElementById('wchToggleGrid');
    if (gridEl)   gridEl.addEventListener('change',   () => { showGrid    = gridEl.checked;   renderMain(); });
    const lgndEl = document.getElementById('wchToggleLegend');
    if (lgndEl)   lgndEl.addEventListener('change',   () => { showLegend  = lgndEl.checked;   renderMain(); });
    const crvEl  = document.getElementById('wchToggleCurve');
    if (crvEl)    crvEl.addEventListener('change',    () => { smoothCurve = crvEl.checked;    renderMain(); });
    const animEl = document.getElementById('wchToggleAnim');
    if (animEl)   animEl.addEventListener('change',   () => { showAnim    = animEl.checked;   renderMain(); });

    // ── refresh ──────────────────────────────────────────────
    const refreshEl = document.getElementById('wchRefreshDemo');
    if (refreshEl) refreshEl.addEventListener('click', () => renderMain());

    // ── download SVG ─────────────────────────────────────────
    const dlEl = document.getElementById('wchDownloadSvg');
    if (dlEl) dlEl.addEventListener('click', () => { if (mainChart) mainChart.exports.downloadSVG(); });

    // ── copy code ────────────────────────────────────────────
    const copyEl = document.getElementById('wchCopyCode');
    if (copyEl) copyEl.addEventListener('click', () => {
      const code = JSON.stringify(buildOpts(curType, curScheme, curPeriod), null, 2);
      navigator.clipboard && navigator.clipboard.writeText(code).then(() => {
        const orig = copyEl.innerHTML;
        copyEl.innerHTML = '<i class="bi bi-check me-1"></i>Copied!';
        setTimeout(() => { copyEl.innerHTML = orig; }, 1500);
      });
    });

    // ── mini charts ──────────────────────────────────────────
    const mkMini = (color, data) => ({
      chart:{ type:'line', height:90, sparkline:{ enabled:true }, animations:{ enabled:true } },
      stroke:{ curve:'smooth', width:2 }, series:[{ data }], colors:[color],
      tooltip:{ fixed:{ enabled:false } }
    });
    if (document.getElementById('wchMini1'))
      new ApexCharts(document.getElementById('wchMini1'), mkMini('#5D87FF',[28,42,38,55,48,62,58,74,68,82])).render();
    if (document.getElementById('wchMini2'))
      new ApexCharts(document.getElementById('wchMini2'), { ...mkMini('#13DEB9',[12,18,15,22,28,24,32,38,42,48]), chart:{ type:'bar', height:90, sparkline:{ enabled:true } } }).render();
    if (document.getElementById('wchMini3'))
      new ApexCharts(document.getElementById('wchMini3'), { ...mkMini('#FFAE1F',[8,14,12,20,24,18,28,34,30,42]), series:[{ data:[8,14,12,20,24,18,28,34,30,42] }], chart:{ type:'area', height:90, sparkline:{ enabled:true } } }).render();
    if (document.getElementById('wchMini4'))
      new ApexCharts(document.getElementById('wchMini4'), {
        chart:{ type:'donut', height:90, sparkline:{ enabled:true } },
        series:[35,25,20,12,8], colors:['#5D87FF','#13DEB9','#FFAE1F','#7460EE','#FA896B'],
        legend:{ show:false }, tooltip:{ fixed:{ enabled:false } }
      }).render();
  }

  /* ============================================================
     3.33 DATA WIDGETS — widgets-data.html (wdt- prefix)
     ============================================================ */
  // Style nav tab selection
  document.querySelectorAll('.wdt-style-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.wdt-style-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const table = document.getElementById('wdtMainTable');
      if (!table) return;
      const style = btn.dataset.style;
      table.className = 'table table-hover mb-0 wdt-main-table';
      if (style === 'striped') table.classList.add('table-striped');
      if (style === 'bordered') table.classList.add('table-bordered');
      if (style === 'compact') table.classList.add('table-sm');
      if (style === 'darkheader') {
        const thead = table.querySelector('thead');
        if (thead) thead.style.background = '#2a3547';
      }
    });
  });
  // Table search
  const wdtSearch = document.getElementById('wdtSearch');
  if (wdtSearch) {
    wdtSearch.addEventListener('input', function () {
      const term = wdtSearch.value.toLowerCase().trim();
      document.querySelectorAll('#wdtTbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = (!term || text.includes(term)) ? '' : 'none';
      });
    });
  }
  // Select all checkbox
  const wdtSelectAll = document.getElementById('wdtSelectAll');
  if (wdtSelectAll) {
    wdtSelectAll.addEventListener('change', function () {
      document.querySelectorAll('.wdt-row-check').forEach(cb => { cb.checked = wdtSelectAll.checked; });
    });
  }

  /* ============================================================
     3.34 FEED WIDGETS — widgets-feeds.html (wfd- prefix)
     ============================================================ */
  // Format tabs
  document.querySelectorAll('.wfd-fmt-tab').forEach(tab => {
    tab.addEventListener('click', function () {
      document.querySelectorAll('.wfd-fmt-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      // Update sidebar format info highlight
      document.querySelectorAll('.wfd-fmt-info-item').forEach(item => item.classList.remove('wfd-fmt-active'));
      const fmtId = 'wfdFmt' + tab.dataset.fmt.charAt(0).toUpperCase() + tab.dataset.fmt.slice(1);
      const infoItem = document.getElementById(fmtId);
      if (infoItem) infoItem.classList.add('wfd-fmt-active');
    });
  });
  // Filter chips
  document.querySelectorAll('.wfd-chip').forEach(chip => {
    chip.addEventListener('click', function () {
      document.querySelectorAll('.wfd-chip').forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      const filter = chip.dataset.chip;
      document.querySelectorAll('.wfd-post').forEach(post => {
        post.style.display = (filter === 'all' || post.dataset.chip === filter) ? '' : 'none';
      });
    });
  });
  // Load more button
  const wfdLoadMore = document.getElementById('wfdLoadMore');
  if (wfdLoadMore) {
    wfdLoadMore.addEventListener('click', function () {
      const orig = wfdLoadMore.innerHTML;
      wfdLoadMore.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading...';
      wfdLoadMore.disabled = true;
      setTimeout(() => {
        wfdLoadMore.innerHTML = orig;
        wfdLoadMore.disabled = false;
      }, 1400);
    });
  }

}); // END DOMContentLoaded

/* ============================================================
   Section 3.35: AUTH PAGES — password toggle & strength check
   (auth-login.html, auth-register.html)
   ============================================================ */
(function () {
  // togglePwd — used by auth-login, auth-register, form-wizard
  if (typeof window.togglePwd === 'undefined') {
    window.togglePwd = function (id, btn) {
      var inp = document.getElementById(id);
      if (!inp) return;
      var icon = btn.querySelector('i');
      if (inp.type === 'password') { inp.type = 'text'; if (icon) icon.className = 'bi bi-eye-slash'; }
      else { inp.type = 'password'; if (icon) icon.className = 'bi bi-eye'; }
    };
  }

  // checkStrength — auth-register password strength bar
  if (document.getElementById('strengthFill')) {
    window.checkStrength = function (val) {
      var fill = document.getElementById('strengthFill');
      var text = document.getElementById('strengthText');
      if (!fill) return;
      if (!val) { fill.style.width = '0%'; if (text) text.textContent = ''; return; }
      var score = 0;
      if (val.length >= 8) score++;
      if (/[A-Z]/.test(val)) score++;
      if (/[0-9]/.test(val)) score++;
      if (/[^A-Za-z0-9]/.test(val)) score++;
      var colors  = ['#FA896B', '#FFAE1F', '#13DEB9', '#5D87FF'];
      var labels  = ['Weak', 'Fair', 'Good', 'Strong'];
      var widths  = ['25%', '50%', '75%', '100%'];
      fill.style.width      = widths[score - 1]  || '0%';
      fill.style.background = colors[score - 1]  || '#eef0f7';
      if (text) {
        text.textContent = labels[score - 1] ? 'Password strength: ' + labels[score - 1] : '';
        text.style.color = colors[score - 1] || '#a8b4cc';
      }
    };
  }
}());

/* ============================================================
   Section 3.36: FORM — BASIC  (form-basic.html)
   ============================================================ */
(function () {
  if (!document.getElementById('loginPwd') && !document.querySelector('.star-rating')) return;

  // Password toggle (login pwd on form-basic)
  window.toggleLoginPwd = function () {
    var pwd  = document.getElementById('loginPwd');
    var icon = document.getElementById('loginPwdIcon');
    if (pwd) {
      pwd.type = pwd.type === 'password' ? 'text' : 'password';
      if (icon) icon.className = pwd.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    }
  };

  // Password strength (register section on form-basic)
  window.regStrength = function (val) {
    var bar = document.getElementById('regStrBar');
    if (!bar) return;
    var score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    var widths = ['0%', '25%', '50%', '75%', '100%'];
    var colors = ['', 'bg-danger', 'bg-warning', 'bg-info', 'bg-success'];
    bar.style.width = widths[score];
    bar.className   = 'progress-bar ' + (colors[score] || '');
  };

  // Credit card number formatter
  window.formatCardNum = function (el) {
    var val = el.value.replace(/\D/g, '').substring(0, 16);
    el.value = val.replace(/(.{4})/g, '$1  ').trim();
  };

  // Expiry date formatter
  window.formatExpiry = function (el) {
    var val = el.value.replace(/\D/g, '');
    if (val.length >= 2) val = val.substring(0, 2) + '/' + val.substring(2, 4);
    el.value = val;
  };

  // Bootstrap tooltips
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    if (typeof bootstrap !== 'undefined') new bootstrap.Tooltip(el);
  });
}());

/* ============================================================
   Section 3.37: FORM — FILE UPLOAD  (form-file.html)
   ============================================================ */
(function () {
  if (!document.getElementById('mainDropzone')) return;

  window.handleDrop = function (e) {
    e.preventDefault();
    var dz = document.getElementById('mainDropzone');
    if (dz) dz.classList.remove('dragover');
    handleFileSelect(e.dataTransfer.files);
  };

  window.handleFileSelect = function (files) {
    var list = document.getElementById('dzFileList');
    if (!list) return;
    list.innerHTML = '';
    Array.from(files).forEach(function (file) {
      var size = file.size > 1024 * 1024
        ? (file.size / 1024 / 1024).toFixed(1) + ' MB'
        : (file.size / 1024).toFixed(0) + ' KB';
      var iconClass = file.type.includes('image') ? 'file-icon-image'
        : file.type.includes('pdf') ? 'file-icon-pdf'
        : file.name.includes('.xlsx') ? 'file-icon-excel' : 'file-icon-word';
      var iconName = file.type.includes('image') ? 'bi-image'
        : file.type.includes('pdf') ? 'bi-file-earmark-pdf' : 'bi-file-earmark';
      list.innerHTML += '<div class="file-list-item">'
        + '<div class="file-icon ' + iconClass + '"><i class="bi ' + iconName + '"></i></div>'
        + '<div class="flex-grow-1">'
        + '<div class="d-flex justify-content-between"><span class="fw-medium small">' + file.name + '</span><small class="text-muted">' + size + '</small></div>'
        + '<div class="progress mt-1" style="height:4px;"><div class="progress-bar bg-success" style="width:100%;"></div></div>'
        + '</div>'
        + '<button class="btn btn-sm" onclick="this.closest(\'.file-list-item\').remove()"><i class="bi bi-x-lg text-muted"></i></button>'
        + '</div>';
    });
  };

  window.previewImage = function (input) {
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      var file   = input.files[0];
      reader.onload = function (e) {
        var img         = document.getElementById('imgPreviewEl');
        var container   = document.getElementById('imgPreviewContainer');
        var placeholder = document.getElementById('imgPreviewPlaceholder');
        var info        = document.getElementById('imgPreviewInfo');
        if (img)         img.src = e.target.result;
        if (container)   container.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
        if (info)        info.textContent = file.name + ' · ' + (file.size / 1024).toFixed(0) + ' KB';
      };
      reader.readAsDataURL(file);
    }
  };

  window.clearPreview = function () {
    var c = document.getElementById('imgPreviewContainer');
    var p = document.getElementById('imgPreviewPlaceholder');
    var i = document.getElementById('imgPreviewInput');
    if (c) c.style.display = 'none';
    if (p) p.style.display = 'block';
    if (i) i.value = '';
  };

  var currentSlot = 0;
  window.triggerSlotUpload = function (idx) {
    currentSlot = idx;
    var si = document.getElementById('slotInput');
    if (si) si.click();
  };

  window.loadSlotImage = function (input) {
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      reader.onload = function (e) {
        var slots = document.querySelectorAll('.img-upload-slot');
        if (slots[currentSlot]) {
          slots[currentSlot].innerHTML = '<img src="' + e.target.result + '" alt="">'
            + '<div class="remove-img" onclick="event.stopPropagation(); clearSlot(' + currentSlot + ')"><i class="bi bi-x"></i></div>';
        }
      };
      reader.readAsDataURL(input.files[0]);
      input.value = '';
    }
  };

  window.clearSlot = function (idx) {
    var slots = document.querySelectorAll('.img-upload-slot');
    if (slots[idx]) {
      slots[idx].innerHTML = '<i class="bi bi-plus fs-4 text-muted"></i>';
      slots[idx].onclick = function () { triggerSlotUpload(idx); };
    }
  };

  window.previewAvatar = function (input) {
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      reader.onload = function (e) {
        var av = document.getElementById('avatarPreview');
        if (av) av.src = e.target.result;
      };
      reader.readAsDataURL(input.files[0]);
    }
  };

  window.resetAvatar = function () {
    var av = document.getElementById('avatarPreview');
    var ai = document.getElementById('avatarInput');
    if (av) av.src = 'https://ui-avatars.com/api/?name=Admin+User&background=5D87FF&color=fff&size=120';
    if (ai) ai.value = '';
  };

  var uploadInterval;
  window.simulateUpload = function () {
    var fileInput  = document.getElementById('progressFile');
    var area       = document.getElementById('uploadProgressArea');
    var bar        = document.getElementById('uploadBar');
    var percent    = document.getElementById('uploadPercent');
    var status     = document.getElementById('uploadStatus');
    var name       = document.getElementById('uploadFileName');
    var successMsg = document.getElementById('uploadSuccessMsg');
    if (area)       area.style.display = 'block';
    if (successMsg) successMsg.style.display = 'none';
    var fileName = fileInput && fileInput.files.length ? fileInput.files[0].name : 'example_file.pdf';
    if (name)    name.textContent = fileName;
    var progress = 0;
    clearInterval(uploadInterval);
    if (bar)     bar.style.width = '0%';
    if (percent) percent.textContent = '0%';
    if (status)  status.textContent = 'Uploading...';
    uploadInterval = setInterval(function () {
      progress += Math.random() * 15;
      if (progress >= 100) {
        progress = 100;
        clearInterval(uploadInterval);
        if (bar)     { bar.style.width = '100%'; bar.className = 'progress-bar bg-success'; }
        if (percent) percent.textContent = '100%';
        if (status)  status.textContent = 'Upload complete!';
        setTimeout(function () {
          if (area)       area.style.display = 'none';
          if (successMsg) successMsg.style.removeProperty('display');
        }, 1000);
      } else {
        if (bar)     bar.style.width = Math.round(progress) + '%';
        if (percent) percent.textContent = Math.round(progress) + '%';
        if (status) {
          if (progress < 30)       status.textContent = 'Connecting to server...';
          else if (progress < 70)  status.textContent = 'Uploading...';
          else                     status.textContent = 'Processing file...';
        }
      }
    }, 200);
  };

  window.cancelUpload = function () {
    clearInterval(uploadInterval);
    var area = document.getElementById('uploadProgressArea');
    if (area) area.style.display = 'none';
  };
}());

/* ============================================================
   Section 3.38: UI — ALERTS  (ui-alerts.html)
   showToast(id) — triggers Bootstrap toast by element ID
   ============================================================ */
window.showToast = function (id) {
  var el = document.getElementById(id);
  if (el && typeof bootstrap !== 'undefined') {
    bootstrap.Toast.getOrCreateInstance(el).show();
  }
};

/* ============================================================
   Section 3.39: ECO — ADD / EDIT PRODUCT  (eco-add-product.html, eco-edit-product.html)
   Upload zone click delegation
   ============================================================ */
(function () {
  document.querySelectorAll('.upload-zone').forEach(function (zone) {
    zone.addEventListener('click', function () {
      var inp = zone.querySelector('input[type="file"]');
      if (inp) inp.click();
    });
  });
}());

/* ============================================================
   Section 3.40: FORM WIZARD  (form-wizard.html)
   ============================================================ */
(function () {
  if (!document.getElementById('w1ind1')) return;

  /* Shared helpers */
  window.togglePwd = window.togglePwd || function (id, btn) {
    var el = document.getElementById(id);
    if (!el) return;
    el.type = el.type === 'password' ? 'text' : 'password';
    btn.querySelector('i').className = el.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
  };

  window.wPwdStrength = function (val, barId) {
    var bar = document.getElementById(barId);
    if (!bar) return;
    var s = 0;
    if (val.length >= 8) s++;
    if (/[A-Z]/.test(val)) s++;
    if (/[0-9]/.test(val)) s++;
    if (/[^A-Za-z0-9]/.test(val)) s++;
    bar.style.width = ['0%', '25%', '50%', '75%', '100%'][s];
    bar.className   = 'progress-bar ' + (['', 'bg-danger', 'bg-warning', 'bg-info', 'bg-success'][s]);
  };

  function gv(id) { var e = document.getElementById(id); return e ? e.value || '—' : '—'; }
  function gt(id) { var e = document.getElementById(id); return (e && e.selectedIndex > 0) ? e.options[e.selectedIndex].text : '—'; }

  /* ===== WIZARD 1 ===== */
  var w1 = 1, W1_TOTAL = 4;
  var W1_ICONS  = ['bi-person-fill', 'bi-gear-fill', 'bi-sliders', 'bi-check2-all'];
  var W1_LABELS = ['Personal Info', 'Account Setup', 'Preferences', 'Review & Submit'];

  window.w1Next = function () {
    if (w1 < W1_TOTAL) {
      document.getElementById('w1ind' + w1).classList.replace('active', 'done');
      document.getElementById('w1ind' + w1).querySelector('.w1-dot').innerHTML = '<i class="bi bi-check2"></i>';
      document.getElementById('w1p' + w1).classList.remove('active');
      w1++;
      document.getElementById('w1ind' + w1).classList.add('active');
      document.getElementById('w1p' + w1).classList.add('active');
      w1Update();
      if (w1 === 4) w1Review();
    } else {
      var btn = document.getElementById('w1next');
      btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Creating...';
      btn.disabled = true;
      setTimeout(function () { btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Done!'; btn.className = 'btn btn-success'; }, 1000);
    }
  };

  window.w1Prev = function () {
    if (w1 > 1) {
      document.getElementById('w1ind' + w1).classList.remove('active');
      document.getElementById('w1p' + w1).classList.remove('active');
      w1--;
      var ind = document.getElementById('w1ind' + w1);
      ind.classList.remove('done');
      ind.classList.add('active');
      ind.querySelector('.w1-dot').innerHTML = '<i class="bi ' + W1_ICONS[w1 - 1] + '"></i>';
      document.getElementById('w1p' + w1).classList.add('active');
      w1Update();
    }
  };

  window.w1Update = function () {
    var pct = Math.round((w1 / W1_TOTAL) * 100);
    document.getElementById('w1Bar').style.width = pct + '%';
    document.getElementById('w1Pct').textContent = pct + '%';
    document.getElementById('w1StepLbl').textContent = 'Step ' + w1 + ' of ' + W1_TOTAL + ' — ' + W1_LABELS[w1 - 1];
    document.getElementById('w1counter').textContent = 'Step ' + w1 + ' of ' + W1_TOTAL;
    document.getElementById('w1prev').disabled = w1 === 1;
    var nb = document.getElementById('w1next');
    if (w1 === W1_TOTAL) { nb.innerHTML = '<i class="bi bi-check-circle me-1"></i>Create Account'; nb.className = 'btn btn-success'; }
    else                 { nb.innerHTML = 'Next<i class="bi bi-arrow-right ms-1"></i>'; nb.className = 'btn btn-primary'; }
  };

  window.w1Review = function () {
    document.getElementById('rv1_fn').textContent   = gv('w1fn');
    document.getElementById('rv1_ln').textContent   = gv('w1ln');
    document.getElementById('rv1_em').textContent   = gv('w1em');
    document.getElementById('rv1_ph').textContent   = gv('w1ph') !== '—' ? gv('w1ph') : 'Not provided';
    document.getElementById('rv1_gen').textContent  = gt('w1gen');
    document.getElementById('rv1_un').textContent   = gv('w1un');
    document.getElementById('rv1_role').textContent = gt('w1role');
    document.getElementById('rv1_tz').textContent   = gt('pf_tz');
    document.getElementById('rv1_lang').textContent = gt('pf_lang');
    var n = [];
    if (document.getElementById('pf_email').checked) n.push('Email');
    if (document.getElementById('pf_sms').checked)   n.push('SMS');
    if (document.getElementById('pf_push').checked)  n.push('Push');
    document.getElementById('rv1_notifs').textContent = n.join(', ') || 'None';
  };

  var w1Theme = 'light';
  window.pickTheme = function (t) {
    w1Theme = t;
    ['light', 'dark', 'auto', 'hc'].forEach(function (k) {
      var el = document.getElementById('tp_' + k);
      if (el) el.classList.toggle('sel', k === t);
    });
    var names = { light: 'Light', dark: 'Dark', auto: 'Auto', hc: 'High Contrast' };
    var rv = document.getElementById('rv1_theme');
    if (rv) rv.textContent = names[t];
  };

  /* ===== WIZARD 2 ===== */
  var w2 = 1, W2_TOTAL = 4;
  var W2_ICONS = ['bi-cart3', 'bi-geo-alt', 'bi-credit-card', 'bi-check-circle'];

  window.w2Next = function () {
    if (w2 < W2_TOTAL) {
      document.getElementById('w2si' + w2).classList.replace('active', 'done');
      document.getElementById('w2si' + w2).querySelector('.w2-sdot').innerHTML = '<i class="bi bi-check2"></i>';
      document.getElementById('w2p' + w2).classList.remove('active');
      w2++;
      document.getElementById('w2si' + w2).classList.add('active');
      document.getElementById('w2p' + w2).classList.add('active');
      w2Update();
    } else {
      alert('Order successfully confirmed! Thank you.');
    }
  };

  window.w2Prev = function () {
    if (w2 > 1) {
      document.getElementById('w2si' + w2).classList.remove('active');
      document.getElementById('w2p' + w2).classList.remove('active');
      w2--;
      var si = document.getElementById('w2si' + w2);
      si.classList.remove('done');
      si.classList.add('active');
      si.querySelector('.w2-sdot').innerHTML = '<i class="bi ' + W2_ICONS[w2 - 1] + '"></i>';
      document.getElementById('w2p' + w2).classList.add('active');
      w2Update();
    }
  };

  window.w2Update = function () {
    document.getElementById('w2counter').textContent = 'Step ' + w2 + ' of ' + W2_TOTAL;
    document.getElementById('w2prev').disabled = w2 === 1;
    var nb = document.getElementById('w2next');
    if      (w2 === W2_TOTAL) { nb.innerHTML = '<i class="bi bi-lock me-1"></i>Place Order'; nb.className = 'btn btn-success'; }
    else if (w2 === 3)        { nb.innerHTML = '<i class="bi bi-lock me-1"></i>Pay $212.76'; nb.className = 'btn btn-success'; }
    else                      { nb.innerHTML = 'Continue<i class="bi bi-arrow-right ms-1"></i>'; nb.className = 'btn btn-success'; }
  };

  /* ===== WIZARD 3 ===== */
  var w3 = 1, W3_TOTAL = 4;
  var W3_TITLES = ['Welcome to StackWave', 'Build Your Profile', 'Choose Your Goals', "You're All Set!"];
  var W3_SUBS   = ["Let's get your account set up in just a few steps", 'Tell us a bit about yourself', "Select what you'll use StackWave for", 'Welcome to the team — start exploring now'];

  window.w3Next = function () {
    if (w3 < W3_TOTAL) {
      document.getElementById('w3p' + w3).classList.remove('active');
      document.getElementById('w3d' + w3).classList.replace('active', 'done');
      var l = document.getElementById('w3l' + w3);
      if (l) l.classList.add('done');
      w3++;
      document.getElementById('w3p' + w3).classList.add('active');
      document.getElementById('w3d' + w3).classList.add('active');
      w3Update();
    } else {
      var btn = document.getElementById('w3next');
      btn.innerHTML = '<i class="bi bi-check me-1"></i>Done!';
      btn.style.background = '#13DEB9';
      btn.style.borderColor = '#13DEB9';
      btn.disabled = true;
    }
  };

  window.w3Prev = function () {
    if (w3 > 1) {
      document.getElementById('w3p' + w3).classList.remove('active');
      document.getElementById('w3d' + w3).classList.remove('active');
      var ln = document.getElementById('w3l' + (w3 - 1));
      if (ln) ln.classList.remove('done');
      w3--;
      document.getElementById('w3p' + w3).classList.add('active');
      document.getElementById('w3d' + w3).classList.remove('done');
      document.getElementById('w3d' + w3).classList.add('active');
      w3Update();
    }
  };

  window.w3Update = function () {
    document.getElementById('w3title').textContent = W3_TITLES[w3 - 1];
    document.getElementById('w3sub').textContent   = W3_SUBS[w3 - 1];
    document.getElementById('w3prev').disabled = w3 === 1;
    var nb = document.getElementById('w3next');
    nb.innerHTML = w3 === W3_TOTAL ? '<i class="bi bi-rocket-takeoff me-1"></i>Get Started!' : 'Next<i class="bi bi-arrow-right ms-1"></i>';
    for (var i = 1; i <= W3_TOTAL; i++) {
      var pip = document.getElementById('w3pip' + i);
      if (pip) pip.style.background = i === w3 ? '#7460EE' : '#dee2e6';
    }
  };

  window.pickAvatar = function (el) {
    document.querySelectorAll('.avatar-opt').forEach(function (a) { a.classList.remove('sel'); });
    el.classList.add('sel');
  };

  /* ===== WIZARD 4 ===== */
  var w4 = 1, W4_TOTAL = 4;

  window.w4Next = function () {
    if (w4 < W4_TOTAL) {
      document.getElementById('w4t' + w4).classList.replace('active', 'done');
      document.getElementById('w4p' + w4).classList.remove('active');
      w4++;
      document.getElementById('w4t' + w4).classList.add('active');
      document.getElementById('w4p' + w4).classList.add('active');
      w4Update();
      if (w4 === 4) w4Review();
    } else {
      var btn = document.getElementById('w4next');
      btn.innerHTML = '<i class="bi bi-rocket-takeoff me-1"></i>Launching...';
      btn.disabled = true;
      setTimeout(function () { btn.innerHTML = '<i class="bi bi-check me-1"></i>Launched!'; btn.className = 'btn btn-success'; }, 1000);
    }
  };

  window.w4Prev = function () {
    if (w4 > 1) {
      document.getElementById('w4t' + w4).classList.remove('active');
      document.getElementById('w4p' + w4).classList.remove('active');
      w4--;
      document.getElementById('w4t' + w4).classList.remove('done');
      document.getElementById('w4t' + w4).classList.add('active');
      document.getElementById('w4p' + w4).classList.add('active');
      w4Update();
    }
  };

  window.w4Update = function () {
    document.getElementById('w4counter').textContent = 'Step ' + w4 + ' of ' + W4_TOTAL;
    document.getElementById('w4prev').disabled = w4 === 1;
    var nb = document.getElementById('w4next');
    if (w4 === W4_TOTAL) { nb.innerHTML = '<i class="bi bi-rocket-takeoff me-1"></i>Launch Project'; nb.className = 'btn btn-warning text-white'; }
    else                 { nb.innerHTML = 'Next<i class="bi bi-arrow-right ms-1"></i>'; nb.className = 'btn btn-warning text-white'; }
  };

  window.w4Review = function () {
    document.getElementById('rv4_name').textContent = gv('w4pname');
    document.getElementById('rv4_type').textContent = gt('w4ptype');
    var s = document.getElementById('w4start');
    var e = document.getElementById('w4end');
    var b = document.getElementById('w4budget');
    document.getElementById('rv4_start').textContent  = s && s.value ? s.value : '—';
    document.getElementById('rv4_end').textContent    = e && e.value ? e.value : '—';
    document.getElementById('rv4_budget').textContent = b ? '$' + Number(b.value).toLocaleString() : '—';
  };

  window.addMember = function () {
    var inp = document.getElementById('w4memberInput');
    var val = inp ? inp.value.trim() : '';
    if (!val) return;
    var name     = val.split('@')[0].replace(/[^a-zA-Z ]/g, '').trim() || val;
    var initials = name.substring(0, 2).toUpperCase();
    var colors   = ['5D87FF', '13DEB9', 'FFAE1F', '7460EE', 'FC185A'];
    var col      = colors[Math.floor(Math.random() * colors.length)];
    var tag      = document.createElement('span');
    tag.className = 'member-tag';
    tag.innerHTML = '<img src="https://ui-avatars.com/api/?name=' + encodeURIComponent(initials) + '&background=' + col + '&color=fff" alt="">'
      + name + ' <span onclick="this.parentElement.remove()" style="cursor:pointer;opacity:.6;font-size:14px;">&times;</span>';
    document.getElementById('w4members').appendChild(tag);
    inp.value = '';
  };

  // Tooltips
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    if (typeof bootstrap !== 'undefined') new bootstrap.Tooltip(el);
  });
}());

/* ============================================================
   3.41: charts-area.html
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('areaBasic')) return;

  const chartFont = "'Plus Jakarta Sans', sans-serif";
  const palette = { primary:'#5D87FF', success:'#13DEB9', warning:'#FFAE1F', danger:'#FA896B', purple:'#7460EE', pink:'#FC185A', teal:'#00BCD4', orange:'#FA8C16' };

  // 1. Basic Area Chart
  new ApexCharts(document.getElementById('areaBasic'), {
    chart: { type: 'area', height: 320, fontFamily: chartFont, toolbar: { show: true }, zoom: { enabled: false } },
    series: [{ name: 'Page Views', data: [14200,16800,15400,18900,22100,19800,24600,27300,25100,28900,31400,29700,33200,35800,32100,37600,41200,38900,42700,45100,43800,47300,50200,48100,51800,54600,52300,57100,61400,58900] }],
    xaxis: { categories: Array.from({length:30},(_,i)=>`Mar ${i+1}`) },
    colors: [palette.primary],
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'solid', opacity: 0.4 },
    markers: { size: 0 },
    yaxis: { labels: { formatter: v => Math.round(v/1000)+'k' } },
    grid: { borderColor: '#f1f1f1' },
    tooltip: { y: { formatter: v => v.toLocaleString()+' views' } }
  }).render();

  // 2. Stacked Area Chart
  new ApexCharts(document.getElementById('areaStacked'), {
    chart: { type: 'area', height: 320, fontFamily: chartFont, stacked: true, toolbar: { show: true } },
    series: [
      { name: 'Product Sales', data: [52000,58000,61000,67000,72000,78000,84000,91000,95000,102000,108000,115000] },
      { name: 'Services', data: [18000,21000,24000,26000,29000,31000,34000,37000,40000,43000,46000,50000] },
      { name: 'Subscriptions', data: [8000,9500,11000,12500,14000,15800,17500,19200,21000,23000,25200,27500] }
    ],
    xaxis: { categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] },
    colors: [palette.primary, palette.success, palette.warning],
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.6, opacityTo: 0.2 } },
    legend: { position: 'top' },
    yaxis: { labels: { formatter: v => '$'+Math.round(v/1000)+'k' } },
    grid: { borderColor: '#f1f1f1' },
    tooltip: { shared: true, intersect: false, y: { formatter: v => '$'+v.toLocaleString() } }
  }).render();

  // 3. Negative Area Chart (P&L)
  new ApexCharts(document.getElementById('areaNegative'), {
    chart: { type: 'area', height: 320, fontFamily: chartFont, toolbar: { show: false } },
    series: [{ name: 'Net Profit / Loss', data: [15000,22000,-8000,18000,31000,25000,-12000,8000,34000,41000,-5000,48000] }],
    xaxis: { categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] },
    colors: [palette.success],
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { shade: 'light', type: 'vertical', shadeIntensity: 0.5, gradientToColors: [palette.danger], inverseColors: true, opacityFrom: 0.65, opacityTo: 0.5, stops: [0,100], colorStops: [{ offset:0,color:palette.success,opacity:0.5 },{ offset:50,color:palette.success,opacity:0.2 },{ offset:51,color:palette.danger,opacity:0.2 },{ offset:100,color:palette.danger,opacity:0.5 }] } },
    yaxis: { labels: { formatter: v => '$'+Math.round(v/1000)+'k' } },
    grid: { borderColor: '#f1f1f1' },
    annotations: { yaxis: [{ y:0, borderColor:'#999', strokeDashArray:4 }] },
    tooltip: { y: { formatter: v => (v>=0?'+':'')+('$'+v.toLocaleString()) } }
  }).render();

  // 4. Area with Missing Data
  new ApexCharts(document.getElementById('areaMissing'), {
    chart: { type: 'area', height: 320, fontFamily: chartFont, toolbar: { show: false } },
    series: [
      { name: 'Sensor A', data: [34,41,null,57,52,null,68,71,null,65,78,82,null,91,88] },
      { name: 'Sensor B', data: [28,null,39,45,null,61,58,64,null,72,null,79,85,null,93] }
    ],
    xaxis: { categories: Array.from({length:15},(_,i)=>`Day ${i*2+1}`) },
    colors: [palette.primary, palette.warning],
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'solid', opacity: 0.3 },
    markers: { size: 5, hover: { sizeOffset: 2 } },
    legend: { position: 'top' },
    grid: { borderColor: '#f1f1f1' },
    tooltip: { shared: false }
  }).render();

  // 5. Datetime Area Chart
  (function() {
    const stockData = [];
    let price = 185;
    const base = new Date('2024-01-01').getTime();
    for(let i=0;i<90;i++){
      price += (Math.random()-0.48)*3.5;
      if(price<150) price=150;
      stockData.push([base+i*86400000, parseFloat(price.toFixed(2))]);
    }
    new ApexCharts(document.getElementById('areaDatetime'), {
      chart: { type: 'area', height: 320, fontFamily: chartFont, zoom: { enabled: true, type: 'x' }, toolbar: { autoSelected: 'zoom' } },
      series: [{ name: 'Stock Price', data: stockData }],
      xaxis: { type: 'datetime' },
      yaxis: { labels: { formatter: v => '$'+v.toFixed(2) } },
      colors: [palette.teal],
      stroke: { curve: 'smooth', width: 2 },
      fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0,90,100] } },
      grid: { borderColor: '#f1f1f1' },
      tooltip: { x: { format: 'dd MMM yyyy' }, y: { formatter: v => '$'+v.toFixed(2) } }
    }).render();
  })();

  // 6. Spline Area with Gradients
  new ApexCharts(document.getElementById('areaGradient'), {
    chart: { type: 'area', height: 320, fontFamily: chartFont, toolbar: { show: false } },
    series: [
      { name: 'Customer Satisfaction', data: [72,75,68,78,82,79,85,88,84,91,89,93] },
      { name: 'Employee Engagement', data: [58,62,65,61,69,73,70,76,80,77,83,87] },
      { name: 'Product Quality Score', data: [81,83,79,86,88,85,90,92,89,94,96,95] }
    ],
    xaxis: { categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] },
    colors: [palette.primary, palette.success, palette.purple],
    stroke: { curve: 'smooth', width: 2.5 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0,100] } },
    markers: { size: 3 },
    legend: { position: 'top' },
    yaxis: { min: 40, max: 100, labels: { formatter: v => v+'%' } },
    grid: { borderColor: '#f1f1f1' },
    tooltip: { shared: true, intersect: false, y: { formatter: v => v+'%' } }
  }).render();
});

/* ============================================================
   3.42: charts-bar.html
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('barBasic')) return;

  const chartFont = "'Plus Jakarta Sans', sans-serif";
  const palette = { primary:'#5D87FF', success:'#13DEB9', warning:'#FFAE1F', danger:'#FA896B', purple:'#7460EE', pink:'#FC185A', teal:'#00BCD4', orange:'#FA8C16' };

  // 1. Basic Horizontal Bar
  new ApexCharts(document.getElementById('barBasic'), {
    chart: { type: 'bar', height: 340, fontFamily: chartFont, toolbar: { show: false } },
    plotOptions: { bar: { horizontal: true, borderRadius: 4, dataLabels: { position: 'top' } } },
    series: [{ name: 'Units Sold', data: [4820,4130,3870,3620,3290,3010,2760,2480,2190,1940] }],
    xaxis: { categories: ['Pro Wireless Headset','4K Monitor 27"','Gaming Chair Pro','Mechanical Keyboard','USB-C Hub 10-in-1','Smart Webcam HD','Vertical Mouse Ergo','Desk Lamp LED','Cable Management Kit','Laptop Stand Adjust'] },
    colors: [palette.primary],
    dataLabels: { enabled: true, formatter: v => v.toLocaleString(), offsetX: 5, style: { fontSize: '11px', fontFamily: chartFont } },
    grid: { borderColor: '#f1f1f1' },
    tooltip: { y: { formatter: v => v.toLocaleString()+' units' } }
  }).render();

  // 2. Grouped Bar
  new ApexCharts(document.getElementById('barGrouped'), {
    chart: { type: 'bar', height: 340, fontFamily: chartFont, toolbar: { show: false } },
    series: [
      { name: '2022', data: [42000,58000,51000,67000] },
      { name: '2023', data: [55000,71000,63000,82000] },
      { name: '2024', data: [68000,87000,79000,101000] }
    ],
    xaxis: { categories: ['Q1','Q2','Q3','Q4'] },
    colors: [palette.teal, palette.primary, palette.purple],
    plotOptions: { bar: { borderRadius: 4, columnWidth: '65%' } },
    legend: { position: 'top' },
    yaxis: { labels: { formatter: v => '$'+Math.round(v/1000)+'k' } },
    grid: { borderColor: '#f1f1f1' },
    tooltip: { shared: true, intersect: false, y: { formatter: v => '$'+v.toLocaleString() } }
  }).render();

  // 3. Stacked Bar
  new ApexCharts(document.getElementById('barStacked'), {
    chart: { type: 'bar', height: 340, fontFamily: chartFont, stacked: true, toolbar: { show: false } },
    series: [
      { name: 'Salary', data: [121000,121000,125000,125000,128000,128000,131000,131000,134000,134000,138000,142000] },
      { name: 'Marketing', data: [28000,31000,26000,35000,29000,38000,33000,41000,36000,44000,39000,47000] },
      { name: 'Infrastructure', data: [18000,18500,19000,19000,20000,21000,21500,22000,22500,23000,24000,25000] },
      { name: 'Other', data: [8500,9200,8100,10500,9800,11200,10100,12300,11500,13200,12100,14500] }
    ],
    xaxis: { categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] },
    colors: [palette.primary, palette.warning, palette.success, palette.danger],
    plotOptions: { bar: { borderRadius: 3, columnWidth: '60%' } },
    legend: { position: 'top' },
    yaxis: { labels: { formatter: v => '$'+Math.round(v/1000)+'k' } },
    grid: { borderColor: '#f1f1f1' },
    tooltip: { shared: true, intersect: false, y: { formatter: v => '$'+v.toLocaleString() } }
  }).render();

  // 4. 100% Stacked Bar
  new ApexCharts(document.getElementById('barStacked100'), {
    chart: { type: 'bar', height: 340, fontFamily: chartFont, stacked: true, stackType: '100%', toolbar: { show: false } },
    series: [
      { name: 'Enterprise', data: [38,41,35,43,47,44] },
      { name: 'SMB', data: [29,27,31,26,24,28] },
      { name: 'Consumer', data: [22,20,24,19,18,17] },
      { name: 'OEM', data: [11,12,10,12,11,11] }
    ],
    xaxis: { categories: ['Q1 2023','Q2 2023','Q3 2023','Q4 2023','Q1 2024','Q2 2024'] },
    colors: [palette.primary, palette.success, palette.warning, palette.purple],
    plotOptions: { bar: { borderRadius: 3, columnWidth: '60%' } },
    legend: { position: 'top' },
    yaxis: { labels: { formatter: v => v+'%' } },
    grid: { borderColor: '#f1f1f1' },
    tooltip: { shared: true, intersect: false, y: { formatter: v => v.toFixed(1)+'%' } }
  }).render();

  // 5. Bar with Negative Values
  new ApexCharts(document.getElementById('barNegative'), {
    chart: { type: 'bar', height: 340, fontFamily: chartFont, toolbar: { show: false } },
    series: [{ name: 'Net Profit ($k)', data: [18,25,-6,32,29,-14,21,38,41,-4,35,52] }],
    xaxis: { categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] },
    colors: [palette.primary],
    plotOptions: { bar: { borderRadius: 4, columnWidth: '55%', colors: { ranges: [{ from:-100, to:0, color:palette.danger },{ from:1, to:1000, color:palette.success }] } } },
    yaxis: { labels: { formatter: v => (v>=0?'+':'')+v+'k' } },
    grid: { borderColor: '#f1f1f1' },
    annotations: { yaxis: [{ y:0, borderColor:'#999', strokeDashArray:0 }] },
    tooltip: { y: { formatter: v => (v>=0?'+':'')+v+'k' } }
  }).render();

  // 6. Gradient Bars by Region
  new ApexCharts(document.getElementById('barGradient'), {
    chart: { type: 'bar', height: 340, fontFamily: chartFont, toolbar: { show: false } },
    series: [{ name: 'Sales ($k)', data: [142,98,215,167,89,134,188,71] }],
    xaxis: { categories: ['North America','Europe','Asia Pacific','Latin America','Middle East','South Asia','Sub-Saharan Africa','Oceania'] },
    colors: [palette.primary,palette.success,palette.warning,palette.danger,palette.purple,palette.pink,palette.teal,palette.orange],
    fill: { type: 'gradient', gradient: { shade:'light', type:'vertical', shadeIntensity:0.4, opacityFrom:1, opacityTo:0.7, stops:[0,100] } },
    plotOptions: { bar: { horizontal:true, borderRadius:6, distributed:true, dataLabels:{ position:'top' } } },
    dataLabels: { enabled:true, formatter: v => '$'+v+'k', offsetX:5, style:{ fontSize:'11px', fontFamily:chartFont } },
    legend: { show: false },
    grid: { borderColor: '#f1f1f1' },
    tooltip: { y: { formatter: v => '$'+v+'k' } }
  }).render();

  // 7. Range Bar (Gantt-style Project Timeline)
  new ApexCharts(document.getElementById('barRange'), {
    chart: { type: 'rangeBar', height: 360, fontFamily: chartFont, toolbar: { show: false } },
    series: [
      { name:'Design', data:[{ x:'Phase 1', y:[new Date('2024-01-05').getTime(),new Date('2024-01-28').getTime()] },{ x:'Phase 2', y:[new Date('2024-04-10').getTime(),new Date('2024-04-30').getTime()] }] },
      { name:'Development', data:[{ x:'Phase 1', y:[new Date('2024-01-29').getTime(),new Date('2024-03-15').getTime()] },{ x:'Phase 2', y:[new Date('2024-05-01').getTime(),new Date('2024-07-12').getTime()] }] },
      { name:'Testing', data:[{ x:'Phase 1', y:[new Date('2024-03-16').getTime(),new Date('2024-04-09').getTime()] },{ x:'Phase 2', y:[new Date('2024-07-13').getTime(),new Date('2024-08-20').getTime()] }] },
      { name:'Deployment', data:[{ x:'Phase 1', y:[new Date('2024-04-10').getTime(),new Date('2024-04-20').getTime()] },{ x:'Phase 2', y:[new Date('2024-08-21').getTime(),new Date('2024-09-05').getTime()] }] },
      { name:'Post-Launch', data:[{ x:'Phase 1', y:[new Date('2024-04-21').getTime(),new Date('2024-05-31').getTime()] },{ x:'Phase 2', y:[new Date('2024-09-06').getTime(),new Date('2024-10-31').getTime()] }] }
    ],
    plotOptions: { bar: { horizontal:true, borderRadius:5, rangeBarGroupRows:true } },
    colors: [palette.primary,palette.success,palette.warning,palette.danger,palette.purple],
    xaxis: { type: 'datetime' },
    legend: { position: 'top' },
    tooltip: { x: { format: 'dd MMM yyyy' } },
    grid: { borderColor: '#f1f1f1' }
  }).render();
});

/* ============================================================
   3.43: charts-candlestick.html
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('candleBasic')) return;

  const chartFont = "'Plus Jakarta Sans', sans-serif";
  const palette = { primary:'#5D87FF', success:'#13DEB9', warning:'#FFAE1F', danger:'#FA896B', purple:'#7460EE', pink:'#FC185A', teal:'#00BCD4', orange:'#FA8C16' };

  function generateCandleData(count, startPrice, startDate) {
    var data = []; var price = startPrice || 100;
    var base = startDate || new Date('2024-01-01').getTime();
    for(var i=0; i<count; i++){
      var open = parseFloat(price.toFixed(2));
      var close = parseFloat((open + (Math.random()-0.48)*8).toFixed(2));
      var high = parseFloat((Math.max(open,close) + Math.random()*4).toFixed(2));
      var low = parseFloat((Math.min(open,close) - Math.random()*4).toFixed(2));
      data.push({ x: new Date(base + i*86400000), y: [open,high,low,close] });
      price = close;
    }
    return data;
  }

  function calcSMA(candleData, period) {
    return candleData.map(function(d, i) {
      if(i < period-1) return { x: d.x, y: null };
      var sum = 0;
      for(var j=i-period+1; j<=i; j++) sum += candleData[j].y[3];
      return { x: d.x, y: parseFloat((sum/period).toFixed(2)) };
    });
  }

  var c30 = generateCandleData(30, 148, new Date('2024-03-01').getTime());
  var c60 = generateCandleData(60, 220, new Date('2024-01-01').getTime());
  var sma7 = calcSMA(c60, 7);
  var sma14 = calcSMA(c60, 14);
  var c45ohlc = generateCandleData(45, 88, new Date('2024-02-01').getTime());

  // 1. Basic Candlestick
  new ApexCharts(document.getElementById('candleBasic'), {
    chart: { type:'candlestick', height:380, fontFamily:chartFont, toolbar:{ show:true, autoSelected:'zoom' }, zoom:{ enabled:true } },
    series: [{ name:'SWVE', data:c30 }],
    xaxis: { type:'datetime', labels:{ format:'dd MMM' } },
    yaxis: { tooltip:{ enabled:true }, labels:{ formatter: v => '$'+v.toFixed(2) } },
    plotOptions: { candlestick: { colors:{ upward:palette.success, downward:palette.danger }, wick:{ useFillColor:true } } },
    grid: { borderColor:'#f1f1f1' },
    tooltip: { x:{ format:'dd MMM yyyy' } }
  }).render();

  // 2. Candlestick + Volume combo
  var candleVolumeChart = new ApexCharts(document.getElementById('candleVolume'), {
    chart: { id:'candle', group:'financial', type:'candlestick', height:260, fontFamily:chartFont, toolbar:{ show:true, autoSelected:'zoom' }, zoom:{ enabled:true } },
    series: [{ name:'Price', data:c60 }],
    xaxis: { type:'datetime', labels:{ show:false } },
    yaxis: { labels:{ formatter: v => '$'+v.toFixed(2) }, tooltip:{ enabled:true } },
    plotOptions: { candlestick:{ colors:{ upward:palette.success, downward:palette.danger }, wick:{ useFillColor:true } } },
    grid: { borderColor:'#f1f1f1', padding:{ bottom:0 } }
  });
  candleVolumeChart.render();

  var volSeriesData = c60.map(function(c) {
    return { x:c.x, y:Math.floor(500000 + Math.random()*2500000) };
  });

  var volumeChart = new ApexCharts(document.getElementById('volumeBar'), {
    chart: { id:'volume', group:'financial', type:'bar', height:130, fontFamily:chartFont, toolbar:{ show:false }, brush:{ enabled:true, target:'candle' }, selection:{ enabled:true, xaxis:{ min:c60[0].x.getTime(), max:c60[29].x.getTime() } } },
    series: [{ name:'Volume', data:volSeriesData }],
    xaxis: { type:'datetime', labels:{ format:'dd MMM' } },
    yaxis: { labels:{ formatter: v => Math.round(v/1000000)+'M' }, tickAmount:2 },
    colors: [palette.primary],
    plotOptions: { bar:{ columnWidth:'80%' } },
    dataLabels: { enabled:false },
    grid: { borderColor:'#f1f1f1' },
    fill: { opacity:0.75 }
  });
  volumeChart.render();

  // 3. Combo: Candlestick + Moving Averages
  new ApexCharts(document.getElementById('candleCombo'), {
    chart: { type:'candlestick', height:360, fontFamily:chartFont, toolbar:{ show:true }, zoom:{ enabled:true } },
    series: [{ name:'SWVE', type:'candlestick', data:c60 },{ name:'SMA 7', type:'line', data:sma7 },{ name:'SMA 14', type:'line', data:sma14 }],
    xaxis: { type:'datetime', labels:{ format:'dd MMM' } },
    yaxis: { labels:{ formatter: v => '$'+v.toFixed(2) }, tooltip:{ enabled:true } },
    colors: [palette.primary, palette.warning, palette.purple],
    stroke: { width:[1,2,2] },
    plotOptions: { candlestick:{ colors:{ upward:palette.success, downward:palette.danger }, wick:{ useFillColor:true } } },
    legend: { show:true, position:'top' },
    grid: { borderColor:'#f1f1f1' },
    tooltip: { shared:true, intersect:false, x:{ format:'dd MMM yyyy' } }
  }).render();

  // 4. Traditional OHLC
  new ApexCharts(document.getElementById('ohlcBar'), {
    chart: { type:'candlestick', height:360, fontFamily:chartFont, toolbar:{ show:true } },
    series: [{ name:'SWVE OHLC', data:c45ohlc }],
    xaxis: { type:'datetime', labels:{ format:'dd MMM' } },
    yaxis: { labels:{ formatter: v => '$'+v.toFixed(2) }, tooltip:{ enabled:true } },
    plotOptions: { candlestick:{ colors:{ upward:'#26a69a', downward:'#ef5350' }, wick:{ useFillColor:false } } },
    grid: { borderColor:'#f1f1f1' },
    tooltip: { x:{ format:'dd MMM yyyy' } }
  }).render();
});

/* ============================================================
   3.44: charts-heatmap.html
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('heatmapBasic')) return;

  const chartFont = "'Plus Jakarta Sans', sans-serif";
  const palette = { primary:'#5D87FF', success:'#13DEB9', warning:'#FFAE1F', danger:'#FA896B', purple:'#7460EE', pink:'#FC185A', teal:'#00BCD4', orange:'#FA8C16' };

  function rnd(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }

  // 1. User Activity Heatmap
  (function() {
    var days = ['Sunday','Saturday','Friday','Thursday','Wednesday','Tuesday','Monday'];
    var series = days.map(function(day, dayIdx) {
      var data = [];
      for(var h=0; h<24; h++){
        var base = (dayIdx >= 5) ? 20 : 30;
        var peakMorning = (h >= 9 && h <= 11) ? 40 : 0;
        var peakAfternoon = (h >= 14 && h <= 17) ? 35 : 0;
        var peakEvening = (h >= 19 && h <= 22) ? 25 : 0;
        var nightDip = (h < 6) ? -20 : 0;
        var val = Math.max(0, Math.min(100, base + peakMorning + peakAfternoon + peakEvening + nightDip + rnd(-15,15)));
        data.push({ x: h+'h', y: val });
      }
      return { name: day, data: data };
    });
    new ApexCharts(document.getElementById('heatmapBasic'), {
      chart: { type:'heatmap', height:340, fontFamily:chartFont, toolbar:{ show:false } },
      series: series,
      colors: [palette.primary],
      dataLabels: { enabled:false },
      plotOptions: { heatmap:{ shadeIntensity:0.75, colorScale:{ inverse:false } } },
      xaxis: { type:'category', labels:{ style:{ fontSize:'10px' } } },
      tooltip: { y:{ formatter: v => v+' active users' } },
      title: { text:'Peak Hours: 9\u201311am, 2\u20135pm (Weekdays)', align:'left', style:{ fontSize:'11px', fontFamily:chartFont, color:'#888' } }
    }).render();
  })();

  // 2. Sales Heatmap
  (function() {
    var categories = ['Electronics','Apparel','Home & Garden','Sports','Beauty','Books','Software','Toys'];
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var seasonalBoost = { 'Electronics':[1,1,1,1,1,1,1,1,2,2,4,5], 'Apparel':[1,1,3,4,3,2,2,2,2,3,2,3], 'Home & Garden':[1,1,2,4,5,4,3,3,3,2,2,2], 'Sports':[1,1,2,3,4,4,4,4,3,2,2,2], 'Beauty':[2,3,3,3,3,3,2,2,2,2,3,4], 'Books':[2,2,2,2,2,2,2,3,4,3,3,5], 'Software':[5,4,3,3,3,2,2,2,3,3,3,4], 'Toys':[1,1,1,1,1,1,1,1,2,3,5,8] };
    var series = categories.map(function(cat) {
      var boost = seasonalBoost[cat];
      return { name:cat, data:months.map(function(m,i){ return { x:m, y:rnd(8*boost[i],20*boost[i]) }; }) };
    });
    new ApexCharts(document.getElementById('heatmapSales'), {
      chart: { type:'heatmap', height:360, fontFamily:chartFont, toolbar:{ show:false } },
      series: series,
      colors: [palette.warning],
      dataLabels: { enabled:false },
      plotOptions: { heatmap:{ shadeIntensity:0.8, radius:2 } },
      tooltip: { y:{ formatter: v => '$'+v+'k sales' } }
    }).render();
  })();

  // 3. Custom Color Range Heatmap
  (function() {
    var rows = ['Server Load','API Latency','Error Rate','Cache Hit Rate','DB Queries','Queue Depth'];
    var cols = Array.from({length:16}, function(_,i){ return 'W'+(i+1); });
    var series = rows.map(function(row) {
      return { name:row, data:cols.map(function(w){ return { x:w, y:rnd(5,98) }; }) };
    });
    new ApexCharts(document.getElementById('heatmapColor'), {
      chart: { type:'heatmap', height:360, fontFamily:chartFont, toolbar:{ show:false } },
      series: series,
      dataLabels: { enabled:false },
      plotOptions: { heatmap:{ shadeIntensity:0.5, radius:3, colorScale:{ ranges:[{ from:0,to:25,color:'#b3d9ff',name:'Low (0\u201325)' },{ from:26,to:50,color:'#5D87FF',name:'Medium (26\u201350)' },{ from:51,to:75,color:'#FFAE1F',name:'High (51\u201375)' },{ from:76,to:100,color:'#FA896B',name:'Critical (76\u2013100)' }] } } },
      legend: { show:true, position:'top' },
      tooltip: { y:{ formatter: v => v+'%' } }
    }).render();
  })();

  // 4. Multi-Color Shaded Heatmap
  (function() {
    var metrics = ['Revenue','Leads','Conversions','Bounce Rate','Avg Session','Page Depth'];
    var quarters = ['Q1 2022','Q2 2022','Q3 2022','Q4 2022','Q1 2023','Q2 2023','Q3 2023','Q4 2023','Q1 2024','Q2 2024'];
    var seriesColors = [palette.primary,palette.success,palette.warning,palette.danger,palette.purple,palette.teal];
    var series = metrics.map(function(m) {
      return { name:m, data:quarters.map(function(q){ return { x:q, y:rnd(10,100) }; }) };
    });
    new ApexCharts(document.getElementById('heatmapMultiColor'), {
      chart: { type:'heatmap', height:360, fontFamily:chartFont, toolbar:{ show:false } },
      series: series,
      colors: seriesColors,
      dataLabels: { enabled:false },
      plotOptions: { heatmap:{ shadeIntensity:0.65, distributed:true, radius:3 } },
      tooltip: { y:{ formatter: v => v+' pts' } },
      legend: { show:false }
    }).render();
  })();

  // 5. GitHub-style Contribution Graph
  (function() {
    var dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    var weekLabels = Array.from({length:53}, function(_,i){
      var d = new Date('2024-01-01');
      d.setDate(d.getDate() + i*7);
      return d.toLocaleString('default',{month:'short'}) + ' ' + d.getDate();
    });
    var series = dayNames.map(function(day, di) {
      var isWeekend = (di===0||di===6);
      return { name:day, data:weekLabels.map(function(w) {
        var base = isWeekend ? rnd(0,3) : rnd(0,8);
        var burst = (Math.random() > 0.7) ? rnd(5,12) : 0;
        var val = Math.min(15, base + burst);
        if(Math.random() > 0.85) val = 0;
        return { x:w, y:val };
      }) };
    });
    new ApexCharts(document.getElementById('heatmapLarge'), {
      chart: { type:'heatmap', height:360, fontFamily:chartFont, toolbar:{ show:false } },
      series: series,
      colors: ['#216e39'],
      dataLabels: { enabled:false },
      plotOptions: { heatmap:{ shadeIntensity:0.9, radius:2, colorScale:{ ranges:[{ from:0,to:0,color:'#ebedf0',name:'No activity' },{ from:1,to:3,color:'#9be9a8',name:'Low (1\u20133)' },{ from:4,to:7,color:'#40c463',name:'Medium (4\u20137)' },{ from:8,to:11,color:'#30a14e',name:'High (8\u201311)' },{ from:12,to:15,color:'#216e39',name:'Peak (12+)' }] } } },
      xaxis: { labels:{ rotate:-45, style:{ fontSize:'9px' } } },
      tooltip: { y:{ formatter: v => v===0 ? 'No commits' : v+' commit'+(v>1?'s':'') } },
      legend: { show:true, position:'bottom', fontSize:'10px' }
    }).render();
  })();
});

/* ============================================================
   3.45: charts-line.html
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('lineBasic')) return;

  const chartFont = "'Plus Jakarta Sans', sans-serif";
  const palette = { primary:'#5D87FF', success:'#13DEB9', warning:'#FFAE1F', danger:'#FA896B', purple:'#7460EE', pink:'#FC185A', teal:'#00BCD4', orange:'#FA8C16' };

  // 1. Basic Line Chart
  new ApexCharts(document.getElementById('lineBasic'), {
    chart: { type:'line', height:320, fontFamily:chartFont, toolbar:{ show:true }, zoom:{ enabled:false } },
    series: [{ name:'Monthly Revenue', data:[42000,55000,49000,67000,72000,65000,80000,91000,87000,103000,115000,128000] }],
    xaxis: { categories:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] },
    yaxis: { labels:{ formatter: v => '$'+Math.round(v/1000)+'k' } },
    colors: [palette.primary],
    stroke: { curve:'smooth', width:3 },
    markers: { size:4, hover:{ sizeOffset:3 } },
    tooltip: { y:{ formatter: v => '$'+v.toLocaleString() } },
    grid: { borderColor:'#f1f1f1' },
    title: { text:'Annual Revenue 2024', align:'left', style:{ fontSize:'13px', fontWeight:600 } }
  }).render();

  // 2. Multi-Series Line Chart
  new ApexCharts(document.getElementById('lineMulti'), {
    chart: { type:'line', height:320, fontFamily:chartFont, toolbar:{ show:true } },
    series: [{ name:'New Users', data:[3200,4100,3800,5100,4700,6200] },{ name:'Active Users', data:[18500,19200,21000,22400,24100,26800] },{ name:'Churned Users', data:[820,940,760,1100,890,1050] }],
    xaxis: { categories:['January','February','March','April','May','June'] },
    colors: [palette.primary, palette.success, palette.danger],
    stroke: { curve:'smooth', width:[3,3,2] },
    markers: { size:5 },
    legend: { position:'top' },
    grid: { borderColor:'#f1f1f1' },
    tooltip: { shared:true, intersect:false }
  }).render();

  // 3. Dashed Line Chart
  new ApexCharts(document.getElementById('lineDashed'), {
    chart: { type:'line', height:320, fontFamily:chartFont, toolbar:{ show:true } },
    series: [{ name:'Online Sales', data:[84000,92000,88000,105000,118000,132000,128000,145000,152000,141000,167000,182000] },{ name:'In-Store Sales', data:[61000,58000,65000,63000,72000,68000,75000,71000,69000,78000,83000,91000] },{ name:'Wholesale', data:[28000,35000,31000,38000,42000,39000,45000,48000,44000,51000,55000,60000] }],
    xaxis: { categories:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] },
    colors: [palette.primary, palette.warning, palette.purple],
    stroke: { curve:'smooth', width:[3,3,3], dashArray:[0,8,5] },
    markers: { size:4 },
    legend: { position:'top' },
    yaxis: { labels:{ formatter: v => '$'+Math.round(v/1000)+'k' } },
    grid: { borderColor:'#f1f1f1' },
    tooltip: { shared:true, intersect:false, y:{ formatter: v => '$'+v.toLocaleString() } }
  }).render();

  // 4. Line with Data Labels
  new ApexCharts(document.getElementById('lineLabels'), {
    chart: { type:'line', height:320, fontFamily:chartFont, toolbar:{ show:false }, zoom:{ enabled:false } },
    series: [{ name:'Revenue ($k)', data:[210,285,310,398] },{ name:'Profit ($k)', data:[42,68,91,124] }],
    xaxis: { categories:['Q1 2024','Q2 2024','Q3 2024','Q4 2024'] },
    colors: [palette.primary, palette.success],
    stroke: { curve:'smooth', width:3 },
    markers: { size:6 },
    dataLabels: { enabled:true, formatter: v => '$'+v+'k', style:{ fontSize:'11px', fontFamily:chartFont } },
    legend: { position:'top' },
    grid: { borderColor:'#f1f1f1' },
    yaxis: { labels:{ formatter: v => '$'+v+'k' } }
  }).render();

  // 5. Step Line Chart
  new ApexCharts(document.getElementById('lineStep'), {
    chart: { type:'line', height:320, fontFamily:chartFont, toolbar:{ show:false } },
    series: [{ name:'Server A Uptime %', data:[100,100,100,95,100,100,100,100,98,100,100,100,100,100,88,100,100,100,100,97,100,100,100,100] },{ name:'Server B Uptime %', data:[100,100,92,100,100,100,100,100,100,100,85,100,100,100,100,100,100,96,100,100,100,100,90,100] }],
    xaxis: { categories: Array.from({length:24},(_,i)=>`Day ${i+1}`) },
    colors: [palette.success, palette.warning],
    stroke: { curve:'stepline', width:2 },
    markers: { size:3 },
    legend: { position:'top' },
    yaxis: { min:80, max:100, labels:{ formatter: v => v+'%' } },
    grid: { borderColor:'#f1f1f1' },
    tooltip: { shared:true, y:{ formatter: v => v+'%' } },
    annotations: { yaxis:[{ y:99, borderColor:palette.danger, label:{ text:'SLA Threshold', style:{ color:'#fff', background:palette.danger } } }] }
  }).render();

  // 6. Gradient Line Chart
  new ApexCharts(document.getElementById('lineGradient'), {
    chart: { type:'area', height:320, fontFamily:chartFont, toolbar:{ show:true }, zoom:{ enabled:true } },
    series: [{ name:'Page Views', data:[12400,18200,15600,22100,26800,24300,31200,28900,35400,39100,42800,47300,51600,48200,54900] }],
    xaxis: { categories:['Jan 1','Jan 8','Jan 15','Jan 22','Feb 1','Feb 8','Feb 15','Feb 22','Mar 1','Mar 8','Mar 15','Mar 22','Apr 1','Apr 8','Apr 15'] },
    colors: [palette.primary],
    stroke: { curve:'smooth', width:3 },
    fill: { type:'gradient', gradient:{ shadeIntensity:1, opacityFrom:0.5, opacityTo:0, stops:[0,90,100] } },
    markers: { size:0 },
    yaxis: { labels:{ formatter: v => Math.round(v/1000)+'k' } },
    grid: { borderColor:'#f1f1f1' },
    tooltip: { y:{ formatter: v => v.toLocaleString()+' views' } }
  }).render();
});

/* ============================================================
   3.46: charts-pie.html
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('pieBasic')) return;

  const chartFont = "'Plus Jakarta Sans', sans-serif";
  const palette = { primary:'#5D87FF', success:'#13DEB9', warning:'#FFAE1F', danger:'#FA896B', purple:'#7460EE', pink:'#FC185A', teal:'#00BCD4', orange:'#FA8C16' };
  const allColors = [palette.primary,palette.success,palette.warning,palette.danger,palette.purple,palette.teal,palette.pink,palette.orange];

  // 1. Basic Pie Chart
  new ApexCharts(document.getElementById('pieBasic'), {
    chart: { type:'pie', height:300, fontFamily:chartFont },
    series: [32400,28100,19600,14800,9200,6900],
    labels: ['Electronics','Apparel','Home & Garden','Sports','Beauty','Books'],
    colors: allColors,
    legend: { position:'bottom' },
    tooltip: { y:{ formatter: v => '$'+v.toLocaleString() } }
  }).render();

  // 2. Basic Donut Chart
  new ApexCharts(document.getElementById('donutBasic'), {
    chart: { type:'donut', height:300, fontFamily:chartFont },
    series: [38,22,17,13,10],
    labels: ['Organic Search','Direct','Social Media','Email','Referral'],
    colors: [palette.primary,palette.success,palette.warning,palette.purple,palette.teal],
    legend: { position:'bottom' },
    plotOptions: { pie:{ donut:{ size:'65%' } } },
    tooltip: { y:{ formatter: v => v+'%' } }
  }).render();

  // 3. Donut with Total Label
  new ApexCharts(document.getElementById('donutTotal'), {
    chart: { type:'donut', height:300, fontFamily:chartFont },
    series: [44200,28700,18900,12400,8100],
    labels: ['Product A','Product B','Product C','Product D','Product E'],
    colors: [palette.primary,palette.success,palette.warning,palette.danger,palette.purple],
    plotOptions: { pie:{ donut:{ size:'70%', labels:{ show:true, total:{ show:true, label:'Total Revenue', formatter: function(w){ return '$'+w.globals.seriesTotals.reduce((a,b)=>a+b,0).toLocaleString(); } } } } } },
    legend: { position:'bottom' },
    tooltip: { y:{ formatter: v => '$'+v.toLocaleString() } }
  }).render();

  // 4. Semi-Circle Donut
  new ApexCharts(document.getElementById('donutSemi'), {
    chart: { type:'donut', height:280, fontFamily:chartFont },
    series: [42,28,18,12],
    labels: ['Completed','In Progress','Pending','On Hold'],
    colors: [palette.success,palette.primary,palette.warning,palette.danger],
    plotOptions: { pie:{ startAngle:-90, endAngle:90, offsetY:10, donut:{ size:'70%' } } },
    legend: { position:'bottom' },
    grid: { padding:{ bottom:-80 } },
    tooltip: { y:{ formatter: v => v+'%' } }
  }).render();

  // 5. Custom Label Formatter
  new ApexCharts(document.getElementById('pieLabels'), {
    chart: { type:'pie', height:300, fontFamily:chartFont },
    series: [8200,5600,4100,3200,1900],
    labels: ['Mobile','Desktop','Tablet','Smart TV','Other'],
    colors: [palette.primary,palette.warning,palette.success,palette.purple,palette.teal],
    dataLabels: { enabled:true, formatter: function(val,opts){ return opts.w.config.labels[opts.seriesIndex]+': '+val.toFixed(1)+'%'; }, style:{ fontSize:'11px', fontFamily:chartFont } },
    legend: { position:'bottom' },
    tooltip: { y:{ formatter: v => v.toLocaleString()+' sessions' } }
  }).render();

  // 6. Gradient Pie Chart
  new ApexCharts(document.getElementById('pieGradient'), {
    chart: { type:'pie', height:300, fontFamily:chartFont },
    series: [35,25,20,12,8],
    labels: ['North America','Europe','Asia','LATAM','ROW'],
    colors: [palette.primary,palette.success,palette.warning,palette.danger,palette.purple],
    fill: { type:'gradient' },
    legend: { position:'bottom' },
    tooltip: { y:{ formatter: v => v+'%' } }
  }).render();

  // 7. KPI Mini Donuts
  function kpiDonut(id, val, color, label) {
    new ApexCharts(document.getElementById(id), {
      chart: { type:'donut', height:200, fontFamily:chartFont, sparkline:{ enabled:false } },
      series: [val, 100-val],
      labels: [label, 'Remaining'],
      colors: [color, '#f3f4f7'],
      plotOptions: { pie:{ donut:{ size:'78%', labels:{ show:true, value:{ show:true, fontSize:'22px', fontWeight:700, color:color, offsetY:-5 }, total:{ show:true, label:label, fontSize:'10px', color:'#aaa', formatter: w => w.globals.series[0]+'%' } } } } },
      dataLabels: { enabled:false },
      legend: { show:false },
      tooltip: { enabled:false },
      stroke: { width:0 }
    }).render();
  }
  kpiDonut('kpiDonut1', 87, palette.success, 'CSAT');
  kpiDonut('kpiDonut2', 73, palette.primary, 'Target');
  kpiDonut('kpiDonut3', 91, palette.warning, 'Efficiency');
  kpiDonut('kpiDonut4', 68, palette.purple, 'Resolution');
});

/* ============================================================
   3.47: charts-radar.html
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('radarBasic')) return;

  const chartFont = "'Plus Jakarta Sans', sans-serif";
  const palette = { primary:'#5D87FF', success:'#13DEB9', warning:'#FFAE1F', danger:'#FA896B', purple:'#7460EE', pink:'#FC185A', teal:'#00BCD4', orange:'#FA8C16' };

  // 1. Basic Radar
  new ApexCharts(document.getElementById('radarBasic'), {
    chart: { type:'radar', height:340, fontFamily:chartFont, toolbar:{ show:false } },
    series: [{ name:'John Doe', data:[82,75,90,68,88,78] }],
    xaxis: { categories:['Leadership','Communication','Technical','Creativity','Teamwork','Problem-Solving'] },
    colors: [palette.primary],
    fill: { opacity:0.2 },
    stroke: { width:2 },
    markers: { size:4 },
    yaxis: { show:false, min:0, max:100 },
    plotOptions: { radar:{ polygons:{ strokeColors:'#e8e8e8', fill:{ colors:['#f8f9fa','#fff'] } } } },
    tooltip: { y:{ formatter: v => v+'/100' } }
  }).render();

  // 2. Multi-Series Radar
  new ApexCharts(document.getElementById('radarMulti'), {
    chart: { type:'radar', height:340, fontFamily:chartFont, toolbar:{ show:false } },
    series: [{ name:'Team Alpha', data:[88,72,85,91,78,83] },{ name:'Team Beta', data:[75,90,70,68,95,72] }],
    xaxis: { categories:['Velocity','Quality','Collaboration','Innovation','Reliability','Delivery'] },
    colors: [palette.primary, palette.success],
    fill: { opacity:0.25 },
    stroke: { width:2 },
    markers: { size:4 },
    legend: { position:'top' },
    yaxis: { show:false, min:0, max:100 },
    plotOptions: { radar:{ polygons:{ strokeColors:'#e8e8e8', fill:{ colors:['#f8f9fa','#fff'] } } } }
  }).render();

  // 3. Polygonal Radar
  new ApexCharts(document.getElementById('radarPoly'), {
    chart: { type:'radar', height:340, fontFamily:chartFont, toolbar:{ show:false } },
    series: [{ name:'Actual', data:[71,84,63,92,77,68,88] },{ name:'Benchmark', data:[80,80,80,80,80,80,80] }],
    xaxis: { categories:['Security','Scalability','Performance','Usability','Maintainability','Availability','Documentation'] },
    colors: [palette.purple, palette.warning],
    fill: { opacity:0.15 },
    stroke: { width:2, dashArray:[0,5] },
    markers: { size:5 },
    legend: { position:'top' },
    yaxis: { show:false, min:0, max:100 },
    plotOptions: { radar:{ polygons:{ strokeColors:'#dee2e6', connectorColors:'#dee2e6', fill:{ colors:['#f8f9fa','#fff'] } } } }
  }).render();

  // 4. Filled Radar
  new ApexCharts(document.getElementById('radarFilled'), {
    chart: { type:'radar', height:340, fontFamily:chartFont, toolbar:{ show:false } },
    series: [{ name:'Q4 2024', data:[91,78,85,72,94,81] }],
    xaxis: { categories:['Revenue','Retention','Acquisition','Satisfaction','Efficiency','Innovation'] },
    colors: [palette.teal],
    fill: { opacity:0.55 },
    stroke: { width:2 },
    markers: { size:5, hover:{ sizeOffset:2 } },
    yaxis: { show:false, min:0, max:100 },
    plotOptions: { radar:{ polygons:{ strokeColors:'#e0f2f1', fill:{ colors:['#e0faf7','#fff'] } } } },
    tooltip: { y:{ formatter: v => v+'/100' } }
  }).render();

  // 5. Product Comparison Radar
  new ApexCharts(document.getElementById('radarProduct'), {
    chart: { type:'radar', height:340, fontFamily:chartFont, toolbar:{ show:false } },
    series: [{ name:'Product Pro', data:[92,85,78,95,70,88,82] },{ name:'Product Standard', data:[75,92,88,72,90,68,95] },{ name:'Product Lite', data:[60,70,95,58,85,78,72] }],
    xaxis: { categories:['Performance','Reliability','Ease of Use','Features','Affordability','Support','Integrations'] },
    colors: [palette.primary, palette.warning, palette.success],
    fill: { opacity:0.2 },
    stroke: { width:2 },
    markers: { size:4 },
    legend: { position:'top' },
    yaxis: { show:false, min:0, max:100 },
    plotOptions: { radar:{ polygons:{ strokeColors:'#e8e8e8', fill:{ colors:['#f8f9fa','#fff'] } } } }
  }).render();

  // 6. Radar with Custom Labels & Colors
  new ApexCharts(document.getElementById('radarCustom'), {
    chart: { type:'radar', height:340, fontFamily:chartFont, toolbar:{ show:false } },
    series: [{ name:'Current Year', data:[88,93,76,85,91,79] },{ name:'Previous Year', data:[72,80,68,75,83,70] }],
    xaxis: { categories:['Market Share','Brand Awareness','Customer Loyalty','Operational Excellence','Digital Maturity','Revenue Growth'], labels:{ style:{ colors:[palette.primary,palette.danger,palette.warning,palette.success,palette.purple,palette.teal], fontSize:'11px', fontFamily:chartFont, fontWeight:600 } } },
    colors: [palette.pink, palette.orange],
    fill: { opacity:0.25 },
    stroke: { width:2.5 },
    markers: { size:5 },
    legend: { position:'top' },
    yaxis: { show:false, min:0, max:100 },
    plotOptions: { radar:{ size:130, polygons:{ strokeColors:'#eee', connectorColors:'#ddd', fill:{ colors:['#fafafa','#fff'] } } } }
  }).render();
});

/* ============================================================
   3.48: charts-radial.html
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('radialBasic')) return;

  const chartFont = "'Plus Jakarta Sans', sans-serif";
  const palette = { primary:'#5D87FF', success:'#13DEB9', warning:'#FFAE1F', danger:'#FA896B', purple:'#7460EE', pink:'#FC185A', teal:'#00BCD4', orange:'#FA8C16' };

  // 1. Basic Radial Bar
  new ApexCharts(document.getElementById('radialBasic'), {
    chart: { type:'radialBar', height:300, fontFamily:chartFont },
    series: [75],
    labels: ['Performance'],
    colors: [palette.primary],
    plotOptions: { radialBar:{ hollow:{ size:'65%' }, dataLabels:{ name:{ fontSize:'16px', fontFamily:chartFont, color:'#888', offsetY:-10 }, value:{ fontSize:'28px', fontFamily:chartFont, fontWeight:700, offsetY:5, formatter: v => v+'%' } } } }
  }).render();

  // 2. Multiple Radials
  new ApexCharts(document.getElementById('radialMultiple'), {
    chart: { type:'radialBar', height:300, fontFamily:chartFont },
    series: [65,82,45,91],
    labels: ['CPU','Memory','Storage','Network'],
    colors: [palette.primary,palette.danger,palette.warning,palette.success],
    plotOptions: { radialBar:{ dataLabels:{ name:{ fontSize:'11px' }, value:{ fontSize:'13px', formatter: v => v+'%' }, total:{ show:true, label:'Avg Util.', formatter: w => Math.round(w.globals.seriesTotals.reduce((a,b)=>a+b,0)/w.globals.series.length)+'%' } } } },
    legend: { show:true, position:'bottom', fontSize:'12px' }
  }).render();

  // 3. Semi-Circle Radial
  new ApexCharts(document.getElementById('radialSemi'), {
    chart: { type:'radialBar', height:300, fontFamily:chartFont },
    series: [68,84,57],
    labels: ['Sales','Leads','Conversion'],
    colors: [palette.primary,palette.success,palette.warning],
    plotOptions: { radialBar:{ startAngle:-135, endAngle:135, hollow:{ size:'40%' }, dataLabels:{ name:{ show:true }, value:{ show:true, formatter: v => v+'%' }, total:{ show:true, label:'Overall', formatter: () => '70%' } } } },
    legend: { show:true, position:'bottom' }
  }).render();

  // 4. Radial with Center Text
  new ApexCharts(document.getElementById('radialImage'), {
    chart: { type:'radialBar', height:300, fontFamily:chartFont },
    series: [88],
    labels: ['NPS Score'],
    colors: [palette.success],
    plotOptions: { radialBar:{ hollow:{ size:'70%', background:'transparent' }, track:{ background:'#e8f5e9' }, dataLabels:{ name:{ show:true, fontSize:'13px', color:'#555', offsetY:25 }, value:{ show:true, fontSize:'34px', fontWeight:700, color:palette.success, offsetY:-10, formatter: v => v } } } }
  }).render();

  // 5. Custom Angle Radial
  new ApexCharts(document.getElementById('radialAngle'), {
    chart: { type:'radialBar', height:300, fontFamily:chartFont },
    series: [72,58,91,44],
    labels: ['Design','Frontend','Backend','DevOps'],
    colors: [palette.purple,palette.pink,palette.teal,palette.orange],
    plotOptions: { radialBar:{ offsetY:0, startAngle:0, endAngle:270, hollow:{ margin:5, size:'30%', background:'transparent' }, track:{ show:true, background:'#f5f5f5', strokeWidth:'97%', opacity:1, margin:5 }, dataLabels:{ name:{ show:true, fontSize:'11px' }, value:{ show:true, fontSize:'12px', formatter: v => v+'%' }, total:{ show:true, label:'Team Skills', formatter: () => '66%' } } } },
    legend: { show:true, position:'bottom', fontSize:'11px' }
  }).render();

  // 6. Radial with Gradient Fill
  new ApexCharts(document.getElementById('radialGradient'), {
    chart: { type:'radialBar', height:300, fontFamily:chartFont },
    series: [76,63,89],
    labels: ['Revenue','Satisfaction','Efficiency'],
    fill: { type:'gradient', gradient:{ shade:'dark', type:'horizontal', shadeIntensity:0.5, gradientToColors:[palette.purple,palette.teal,palette.warning], inverseColors:true, opacityFrom:1, opacityTo:1, stops:[0,100] } },
    colors: [palette.primary,palette.success,palette.danger],
    plotOptions: { radialBar:{ dataLabels:{ name:{ fontSize:'11px' }, value:{ formatter: v => v+'%' }, total:{ show:true, label:'KPI Avg.', formatter: () => '76%' } } } },
    legend: { show:true, position:'bottom' }
  }).render();

  // 7. Circular Gauge
  new ApexCharts(document.getElementById('radialGauge'), {
    chart: { type:'radialBar', height:320, fontFamily:chartFont },
    series: [73],
    labels: ['System Load'],
    colors: [palette.warning],
    plotOptions: { radialBar:{ startAngle:-135, endAngle:135, hollow:{ size:'65%' }, track:{ background:'#f1f1f1', startAngle:-135, endAngle:135 }, dataLabels:{ name:{ offsetY:-10, color:'#666', fontSize:'14px' }, value:{ color:palette.warning, fontSize:'36px', fontWeight:700, show:true, offsetY:5, formatter: v => v+'%' } } } },
    fill: { type:'gradient', gradient:{ shade:'dark', type:'horizontal', colorStops:[{ offset:0, color:palette.success, opacity:1 },{ offset:60, color:palette.warning, opacity:1 },{ offset:100, color:palette.danger, opacity:1 }] } },
    stroke: { lineCap:'round' }
  }).render();
});

/* ============================================================
   3.49: form-checkbox-radio.html
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('chkIndet') && !document.getElementById('selectAll') && !document.getElementById('planFree')) return;

  // Indeterminate checkbox
  const indetCb = document.getElementById('chkIndet');
  if (indetCb) indetCb.indeterminate = true;

  // Select All logic
  const selectAllCb = document.getElementById('selectAll');
  if (selectAllCb) {
    selectAllCb.addEventListener('change', function() {
      document.querySelectorAll('.item-check').forEach(cb => cb.checked = this.checked);
      updateSelectedCount();
    });
    document.querySelectorAll('.item-check').forEach(cb => {
      cb.addEventListener('change', updateSelectedCount);
    });
  }

  function updateSelectedCount() {
    const count = document.querySelectorAll('.item-check:checked').length;
    const countEl = document.getElementById('selectedCount');
    if (countEl) countEl.textContent = count;
  }

  window.selectAllFn = function(val) {
    document.querySelectorAll('.item-check').forEach(cb => cb.checked = val);
    if (selectAllCb) selectAllCb.checked = val;
    updateSelectedCount();
  };

  // Radio card selection
  const planNames = { planFree:'Free Plan ($0/mo)', planPro:'Pro Plan ($29/mo)', planEnt:'Enterprise Plan ($99/mo)' };
  window.selectPlan = function(id) {
    document.querySelectorAll('.radio-card').forEach(c => c.classList.remove('selected'));
    const card = document.getElementById(id);
    if (card) { card.classList.add('selected'); card.querySelector('input').checked = true; }
    const planEl = document.getElementById('selectedPlan');
    if (planEl) planEl.textContent = planNames[id] || 'Unknown';
  };
  window.selectPlan('planFree');
});

/* ============================================================
   3.50: form-inputs.html
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('rangeInput') && !document.getElementById('pwdToggle')) return;

  // Range slider
  const rangeInput = document.getElementById('rangeInput');
  const rangeVal = document.getElementById('rangeVal');
  if (rangeInput) { rangeInput.addEventListener('input', () => rangeVal.textContent = rangeInput.value); }

  // Password toggle
  const pwdToggle = document.getElementById('pwdToggle');
  if (pwdToggle) {
    pwdToggle.addEventListener('click', function() {
      const pwd = document.getElementById('pwdInput');
      const icon = document.getElementById('pwdEyeIcon');
      if (pwd.type === 'password') { pwd.type = 'text'; icon.className = 'bi bi-eye-slash'; }
      else { pwd.type = 'password'; icon.className = 'bi bi-eye'; }
    });
  }

  // Char counter
  window.updateCount = function(el) {
    document.getElementById('charCount').textContent = el.value.length;
  };

  // Password strength
  window.checkStrength = window.checkStrength || function(val) {
    const bar = document.getElementById('strengthBar');
    const txt = document.getElementById('strengthText');
    if (!bar) return;
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['bg-danger', 'bg-danger', 'bg-warning', 'bg-info', 'bg-success'];
    const widths = ['0%', '25%', '50%', '75%', '100%'];
    bar.style.width = widths[score];
    bar.className = 'progress-bar ' + (colors[score] || '');
    txt.textContent = val ? (levels[score] || 'Weak') + ' password' : 'Password strength';
  };
});

/* ============================================================
   3.51: form-select.html
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('countrySelect')) return;

  const stateData = {
    us: ['California','Texas','New York','Florida','Illinois'],
    uk: ['England','Scotland','Wales','Northern Ireland'],
    ca: ['Ontario','Quebec','British Columbia','Alberta','Manitoba']
  };
  const cityData = {
    'California':['Los Angeles','San Francisco','San Diego'],
    'Texas':['Houston','Dallas','Austin'],
    'New York':['New York City','Buffalo','Albany'],
    'Florida':['Miami','Orlando','Tampa'],
    'Illinois':['Chicago','Springfield','Naperville'],
    'England':['London','Manchester','Birmingham'],
    'Scotland':['Edinburgh','Glasgow','Aberdeen'],
    'Wales':['Cardiff','Swansea','Newport'],
    'Northern Ireland':['Belfast','Derry','Lisburn'],
    'Ontario':['Toronto','Ottawa','Hamilton'],
    'Quebec':['Montreal','Quebec City','Laval'],
    'British Columbia':['Vancouver','Victoria','Kelowna'],
    'Alberta':['Calgary','Edmonton','Red Deer'],
    'Manitoba':['Winnipeg','Brandon','Steinbach']
  };

  window.updateStates = function() {
    const country = document.getElementById('countrySelect').value;
    const stateEl = document.getElementById('stateSelect');
    const cityEl = document.getElementById('citySelect');
    stateEl.innerHTML = '<option value="">Select state...</option>';
    cityEl.innerHTML = '<option value="">Select city...</option>';
    stateEl.disabled = !country;
    cityEl.disabled = true;
    if (country && stateData[country]) {
      stateData[country].forEach(s => { stateEl.innerHTML += `<option value="${s}">${s}</option>`; });
    }
    updateCascadeResult();
  };

  window.updateCities = function() {
    const state = document.getElementById('stateSelect').value;
    const cityEl = document.getElementById('citySelect');
    cityEl.innerHTML = '<option value="">Select city...</option>';
    cityEl.disabled = !state;
    if (state && cityData[state]) {
      cityData[state].forEach(c => { cityEl.innerHTML += `<option value="${c}">${c}</option>`; });
    }
    updateCascadeResult();
  };

  function updateCascadeResult() {
    const country = document.getElementById('countrySelect');
    const state = document.getElementById('stateSelect');
    const city = document.getElementById('citySelect');
    const result = document.getElementById('cascadeResult');
    const parts = [];
    if (country && country.value) parts.push(country.options[country.selectedIndex].text);
    if (state && state.value) parts.push(state.value);
    if (city && city.value) parts.push(city.value);
    if (result) result.textContent = parts.length ? parts.join(', ') : 'No selection made';
  }
  const citySelectEl = document.getElementById('citySelect');
  if (citySelectEl) citySelectEl.addEventListener('change', updateCascadeResult);

  window.toggleMultiMenu = function() {
    const menu = document.getElementById('multiMenu');
    if (menu) menu.classList.toggle('show');
  };
  document.addEventListener('click', function(e) {
    const trigger = document.getElementById('multiTrigger');
    const menu = document.getElementById('multiMenu');
    if (trigger && menu && !trigger.contains(e.target) && !menu.contains(e.target)) {
      menu.classList.remove('show');
    }
  });
  function updateMultiDisplay() {
    const checked = Array.from(document.querySelectorAll('.multi-opt:checked')).map(c => c.value);
    const display = document.getElementById('multiDisplay');
    if (display) { display.textContent = checked.length ? checked.join(', ') : 'Choose skills...'; display.className = checked.length ? '' : 'text-muted'; }
  }
  updateMultiDisplay();

  const priorityColors = { critical:'danger', high:'warning', medium:'primary', low:'success' };
  const priorityLabels = { critical:'Critical Priority', high:'High Priority', medium:'Medium Priority', low:'Low Priority' };
  window.updatePriorityBadge = function() {
    const val = document.getElementById('prioritySelector').value;
    const badge = document.getElementById('priorityBadge');
    if (badge && val) { badge.innerHTML = `<span class="badge bg-${priorityColors[val]}-lt text-${priorityColors[val]}">${priorityLabels[val]}</span>`; }
  };

  window.selectBtn = function(el) {
    document.querySelectorAll('.btn-select-group .btn-sel').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
  };
});

/* ============================================================
   3.52: form-validation.html
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('bsValidForm')) return;

  window.handleBsForm = function(e) {
    e.preventDefault();
    const form = document.getElementById('bsValidForm');
    form.classList.add('was-validated');
    if (form.checkValidity()) { alert('Form submitted successfully!'); form.reset(); form.classList.remove('was-validated'); }
  };

  window.setFieldStatus = function(fieldId, statusId, msgId, valid, msg) {
    const f = document.getElementById(fieldId), s = document.getElementById(statusId), m = document.getElementById(msgId);
    if (!f) return;
    f.className = 'form-control pe-5 ' + (f.value ? (valid ? 'is-valid' : 'is-invalid') : '');
    if (s) s.innerHTML = f.value ? (valid ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>') : '';
    if (m) { m.textContent = msg; m.className = 'form-text ' + (f.value ? (valid ? 'text-success' : 'text-danger') : 'text-muted'); }
  };

  window.validateUsername = function(el) {
    const val = el.value, valid = /^[a-zA-Z0-9_]{3,20}$/.test(val);
    const msg = !val ? 'Enter 3-20 alphanumeric characters.' : valid ? 'Username looks great!' : val.length < 3 ? 'Too short (min 3 chars).' : val.length > 20 ? 'Too long (max 20 chars).' : 'Only letters, numbers, underscores allowed.';
    el.className = 'form-control pe-5 ' + (val ? (valid ? 'is-valid' : 'is-invalid') : '');
    const s = document.getElementById('rtUsernameStatus'); if (s) s.innerHTML = val ? (valid ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>') : '';
    const m = document.getElementById('rtUsernameMsg'); if (m) { m.textContent = msg; m.className = 'form-text ' + (val ? (valid ? 'text-success' : 'text-danger') : 'text-muted'); }
  };

  window.validateEmail = function(el) {
    const val = el.value, valid = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val);
    const msg = !val ? 'Enter a valid email address.' : valid ? 'Valid email format!' : 'Please enter a valid email (user@domain.com).';
    el.className = 'form-control pe-5 ' + (val ? (valid ? 'is-valid' : 'is-invalid') : '');
    const s = document.getElementById('rtEmailStatus'); if (s) s.innerHTML = val ? (valid ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>') : '';
    const m = document.getElementById('rtEmailMsg'); if (m) { m.textContent = msg; m.className = 'form-text ' + (val ? (valid ? 'text-success' : 'text-danger') : 'text-muted'); }
  };

  window.validateUrl = function(el) {
    const val = el.value, valid = /^https?:\/\/.+\..+/.test(val);
    const msg = !val ? 'Must start with http:// or https://' : valid ? 'Valid URL!' : 'URL must start with http:// or https://';
    el.className = 'form-control pe-5 ' + (val ? (valid ? 'is-valid' : 'is-invalid') : '');
    const s = document.getElementById('rtUrlStatus'); if (s) s.innerHTML = val ? (valid ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>') : '';
    const m = document.getElementById('rtUrlMsg'); if (m) { m.textContent = msg; m.className = 'form-text ' + (val ? (valid ? 'text-success' : 'text-danger') : 'text-muted'); }
  };

  window.validatePhone = function(el) {
    const val = el.value, valid = /^[\+]?[\d\s\-\(\)]{7,15}$/.test(val);
    const msg = !val ? 'International or US format accepted.' : valid ? 'Valid phone number!' : 'Enter 7-15 digits with optional +, spaces, dashes.';
    el.className = 'form-control pe-5 ' + (val ? (valid ? 'is-valid' : 'is-invalid') : '');
    const s = document.getElementById('rtPhoneStatus'); if (s) s.innerHTML = val ? (valid ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>') : '';
    const m = document.getElementById('rtPhoneMsg'); if (m) { m.textContent = msg; m.className = 'form-text ' + (val ? (valid ? 'text-success' : 'text-danger') : 'text-muted'); }
  };

  window.validateDateRange = function() {
    const start = document.getElementById('dateStart').value, end = document.getElementById('dateEnd').value;
    const msg = document.getElementById('dateRangeMsg'), endEl = document.getElementById('dateEnd');
    if (!start || !end) { if (msg) { msg.textContent = 'Select both dates.'; msg.className = 'form-text text-muted mt-1'; } return; }
    const valid = new Date(end) > new Date(start);
    if (msg) { msg.textContent = valid ? 'Valid date range: ' + Math.round((new Date(end)-new Date(start))/(1000*60*60*24)) + ' days.' : 'End date must be after start date.'; msg.className = 'form-text mt-1 ' + (valid ? 'text-success' : 'text-danger'); }
    if (endEl) endEl.className = 'form-control ' + (valid ? 'is-valid' : 'is-invalid');
  };

  const reqs = [
    { id:'req-len', test: v => v.length >= 8 },
    { id:'req-upper', test: v => /[A-Z]/.test(v) },
    { id:'req-lower', test: v => /[a-z]/.test(v) },
    { id:'req-num', test: v => /[0-9]/.test(v) },
    { id:'req-special', test: v => /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(v) },
    { id:'req-len16', test: v => v.length >= 16 }
  ];
  const strLabels = ['','Very Weak','Weak','Fair','Good','Strong','Very Strong'];
  const strColors = ['','bg-danger','bg-danger','bg-warning','bg-info','bg-success','bg-success'];

  window.checkAllReqs = function(val) {
    let score = 0;
    reqs.forEach(r => { const met = r.test(val); const el = document.getElementById(r.id); if (el) el.className = 'req-item' + (met ? ' met' : ''); if (met) score++; });
    const bar = document.getElementById('strBar'), label = document.getElementById('strLabel'), scoreEl = document.getElementById('strScore');
    const pct = Math.round((score / reqs.length) * 100);
    if (bar) { bar.style.width = pct + '%'; bar.className = 'progress-bar ' + (strColors[score]||''); }
    if (label) label.textContent = val ? strLabels[score] || 'Very Weak' : 'No password';
    if (scoreEl) { scoreEl.textContent = val ? score + '/' + reqs.length + ' criteria' : ''; scoreEl.className = 'fw-medium ' + (score >= 4 ? 'text-success' : score >= 2 ? 'text-warning' : 'text-danger'); }
  };

  window.checkMatch = function() {
    const p1 = document.getElementById('strongPwd').value, p2 = document.getElementById('confirmPwd').value;
    const msg = document.getElementById('matchMsg'), f = document.getElementById('confirmPwd');
    if (!p2) { if (msg) { msg.textContent = 'Enter the same password again.'; msg.className = 'form-text text-muted'; } f.className = 'form-control'; return; }
    const match = p1 === p2 && p2.length > 0;
    f.className = 'form-control ' + (match ? 'is-valid' : 'is-invalid');
    if (msg) { msg.textContent = match ? 'Passwords match!' : 'Passwords do not match.'; msg.className = 'form-text ' + (match ? 'text-success' : 'text-danger'); }
  };

  function luhn(num) {
    const digits = num.replace(/\D/g,'').split('').reverse().map(Number);
    let sum = 0;
    digits.forEach((d,i) => { if (i%2===1) { d*=2; if(d>9) d-=9; } sum+=d; });
    return sum%10===0 && digits.length>=13;
  }

  window.validateCC = function(el) {
    let val = el.value.replace(/\D/g,'').substring(0,16);
    el.value = val.replace(/(.{4})/g,'$1 ').trim();
    const clean = val, msg = document.getElementById('ccMsg');
    if (!clean) { el.className = 'form-control'; if (msg) { msg.textContent = 'Hint: try 4111 1111 1111 1111 (valid Visa test)'; msg.className = 'form-text text-muted'; } return; }
    const valid = clean.length >= 13 && luhn(clean);
    el.className = 'form-control ' + (valid ? 'is-valid' : 'is-invalid');
    if (msg) { msg.textContent = valid ? 'Valid card number (Luhn check passed).' : clean.length < 13 ? 'Card number too short.' : 'Invalid card number (Luhn check failed).'; msg.className = 'form-text ' + (valid ? 'text-success' : 'text-danger'); }
  };

  window.validateEmailStrict = function(el) {
    const val = el.value, valid = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/.test(val);
    el.className = 'form-control ' + (val ? (valid ? 'is-valid' : 'is-invalid') : '');
    const icon = document.getElementById('emailStrictIcon');
    if (icon) icon.innerHTML = val ? (valid ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>') : '<i class="bi bi-dash text-muted"></i>';
    const msg = document.getElementById('emailStrictMsg');
    if (msg) { msg.textContent = !val ? 'Must include @ and a valid domain.' : valid ? 'Email format is valid.' : 'Invalid email format.'; msg.className = 'form-text ' + (val ? (valid ? 'text-success' : 'text-danger') : 'text-muted'); }
  };

  window.crossMatch = function() {
    const p1 = document.getElementById('matchPwd1').value, p2 = document.getElementById('matchPwd2').value;
    const msg = document.getElementById('crossMatchMsg'), f2 = document.getElementById('matchPwd2');
    if (!p1 || !p2) { if (msg) { msg.textContent = 'Both passwords must be identical.'; msg.className = 'form-text text-muted'; } f2.className = 'form-control'; return; }
    const match = p1 === p2;
    f2.className = 'form-control ' + (match ? 'is-valid' : 'is-invalid');
    if (msg) { msg.textContent = match ? 'Passwords match!' : 'Passwords do not match.'; msg.className = 'form-text ' + (match ? 'text-success' : 'text-danger'); }
  };

  const postalPatterns = { us:/^\d{5}(-\d{4})?$/, uk:/^[A-Z]{1,2}\d[A-Z\d]?\s*\d[A-Z]{2}$/i, ca:/^[A-Z]\d[A-Z]\s*\d[A-Z]\d$/i };
  const postalHints = { us:'US format: 5 digits (e.g. 10001)', uk:'UK format: e.g. SW1A 1AA', ca:'CA format: e.g. K1A 0B1' };
  window.validatePostal = function() {
    const country = document.getElementById('postalCountry').value, f = document.getElementById('postalCode'), msg = document.getElementById('postalMsg');
    if (!f.value) { f.className = 'form-control'; if (msg) { msg.textContent = postalHints[country]; msg.className = 'form-text text-muted'; } return; }
    const valid = postalPatterns[country].test(f.value.trim());
    f.className = 'form-control ' + (valid ? 'is-valid' : 'is-invalid');
    if (msg) { msg.textContent = valid ? 'Valid postal code!' : 'Invalid format. ' + postalHints[country]; msg.className = 'form-text ' + (valid ? 'text-success' : 'text-danger'); }
  };
  const postalCountryEl = document.getElementById('postalCountry');
  if (postalCountryEl) postalCountryEl.addEventListener('change', () => { document.getElementById('postalCode').value = ''; document.getElementById('postalCode').className = 'form-control'; window.validatePostal(); });

  window.toggleFieldPwd = function(id, btn) {
    const el = document.getElementById(id); if (!el) return;
    el.type = el.type === 'password' ? 'text' : 'password';
    const icon = btn.querySelector('i'); if (icon) icon.className = el.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
  };
});

/* ============================================================
   3.53: table-datatable-advanced.html
   ============================================================ */
(function () {
  if (!document.getElementById('ordersTable')) return;
  if (typeof $ === 'undefined' || !$.fn || !$.fn.DataTable) return;
  $(document).ready(function() {
    var ordersTable = $('#ordersTable').DataTable({
      dom: '<"row mb-3"<"col-sm-6"B><"col-sm-6 text-end"f>><"row"<"col-sm-12"tr>><"row mt-3"<"col-sm-5"i><"col-sm-7"p>>',
      buttons: [
        { extend:'colvis', text:'<i class="bi bi-layout-three-columns me-1"></i>Columns', className:'btn btn-sm btn-secondary' },
        { extend:'copy', text:'<i class="bi bi-clipboard me-1"></i>Copy', className:'btn btn-sm btn-info' },
        { extend:'excel', text:'<i class="bi bi-file-earmark-excel me-1"></i>Excel', className:'btn btn-sm btn-success', exportOptions:{ columns:[1,2,3,4,5,6,7] } },
        { extend:'pdf', text:'<i class="bi bi-file-earmark-pdf me-1"></i>PDF', className:'btn btn-sm btn-danger', exportOptions:{ columns:[1,2,3,4,5,6,7] } },
        { extend:'print', text:'<i class="bi bi-printer me-1"></i>Print', className:'btn btn-sm btn-warning', exportOptions:{ columns:[1,2,3,4,5,6,7] } }
      ],
      pageLength:10, lengthMenu:[5,10,25,30], ordering:true, searching:true, fixedHeader:true, order:[[1,'asc']],
      columnDefs:[{ orderable:false, targets:[0,8] },{ visible:true, targets:'_all' }],
      language:{ search:"_INPUT_", searchPlaceholder:"Search orders...", info:"Showing _START_ to _END_ of _TOTAL_ orders", infoFiltered:"(filtered from _MAX_ total)", paginate:{ next:'<i class="bi bi-chevron-right"></i>', previous:'<i class="bi bi-chevron-left"></i>' } },
      initComplete: function() { var $w=$(this.api().table().container()); $w.find('.row').eq(1).before('<div class="dt-adv-sep"></div>'); }
    });
    $('#statusFilter').on('change', function() { var val=$.fn.dataTable.util.escapeRegex($(this).val()); ordersTable.column(6).search(val?val:'',true,false).draw(); });
    $('#paymentFilter').on('change', function() { var val=$.fn.dataTable.util.escapeRegex($(this).val()); ordersTable.column(7).search(val?val:'',true,false).draw(); });
    $('#clearFilters').on('click', function() { $('#statusFilter').val(''); $('#paymentFilter').val(''); ordersTable.columns().search('').draw(); });
    $('#selectAll').on('change', function() {
      var checked=$(this).is(':checked');
      $('.row-check').prop('checked',checked);
      if(checked){ $('#ordersTable tbody tr').addClass('row-selected'); } else { $('#ordersTable tbody tr').removeClass('row-selected'); }
      updateSelectedCount();
    });
    $('#ordersTable tbody').on('change','.row-check',function() {
      var row=$(this).closest('tr');
      if($(this).is(':checked')){ row.addClass('row-selected'); } else { row.removeClass('row-selected'); $('#selectAll').prop('checked',false); }
      updateSelectedCount();
    });
    function updateSelectedCount() {
      var count=$('.row-check:checked').length;
      if(count>0){ $('#selectedCount').text(count+' row'+(count>1?'s':'')+' selected').removeClass('d-none'); } else { $('#selectedCount').addClass('d-none'); }
    }
  });
})();

/* ============================================================
   3.54: table-datatable-basic.html
   ============================================================ */
(function () {
  if (!document.getElementById('employeeTable')) return;
  if (typeof $ === 'undefined' || !$.fn || !$.fn.DataTable) return;
  $(document).ready(function() {
    $('#employeeTable').DataTable({
      pageLength:10, lengthMenu:[5,10,25,50], order:[[0,'asc']],
      language:{ search:"_INPUT_", searchPlaceholder:"Search employees...", lengthMenu:"Show _MENU_ per page", info:"Showing _START_ \u2013 _END_ of _TOTAL_ employees", infoFiltered:"(filtered from _MAX_ total)" },
      initComplete: function() {
        $(this).closest('.dataTables_wrapper').find('table.dataTable').before('<div class="dt-sep"></div>');
        $(this).closest('.dataTables_wrapper').find('.dataTables_info').before('<div class="dt-bot-sep"></div>');
      }
    });
    $('#productsTable').DataTable({
      pageLength:10, lengthMenu:[5,10,20], order:[[4,'desc']],
      language:{ search:"_INPUT_", searchPlaceholder:"Search products...", lengthMenu:"Show _MENU_ per page", info:"Showing _START_ \u2013 _END_ of _TOTAL_ products", infoFiltered:"(filtered from _MAX_ total)" },
      initComplete: function() {
        $(this).closest('.dataTables_wrapper').find('table.dataTable').before('<div class="dt-sep"></div>');
        $(this).closest('.dataTables_wrapper').find('.dataTables_info').before('<div class="dt-bot-sep"></div>');
      }
    });
  });
})();

/* ============================================================
   3.55: ui-card-weather.html
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('precipChart')) return;

  // Precipitation Chart
  new ApexCharts(document.querySelector('#precipChart'), {
    chart: { type:'bar', height:160, toolbar:{ show:false }, sparkline:{ enabled:false } },
    series: [{ name:'Precipitation (mm)', data:[2,0,8,15,4,1,0] }],
    xaxis: { categories:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'], labels:{ style:{ fontSize:'11px' } } },
    colors: ['#49BEFF'],
    plotOptions: { bar:{ borderRadius:4, columnWidth:'60%' } },
    dataLabels: { enabled:false },
    grid: { strokeDashArray:4 },
    yaxis: { labels:{ style:{ fontSize:'11px' } } }
  }).render();

  // Temperature Trend Chart
  new ApexCharts(document.querySelector('#tempTrendChart'), {
    chart: { type:'line', height:220, toolbar:{ show:false } },
    series: [{ name:'High', data:[24,21,17,15,19,22,25] },{ name:'Low', data:[18,15,12,11,14,16,19] }],
    xaxis: { categories:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] },
    colors: ['#5D87FF','#13DEB9'],
    stroke: { curve:'smooth', width:3 },
    fill: { type:'gradient', gradient:{ shade:'light', type:'vertical', opacityFrom:0.15, opacityTo:0.01 } },
    dataLabels: { enabled:false },
    grid: { strokeDashArray:4 },
    markers: { size:5 },
    yaxis: { labels:{ formatter: v => v+'\u00b0C' } },
    tooltip: { y:{ formatter: v => v+'\u00b0C' } }
  }).render();
});

/* ============================================================
   3.56: ui-progress.html
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('radial1')) return;

  var tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltips.forEach(function(t){ if(typeof bootstrap !== 'undefined') new bootstrap.Tooltip(t); });

  function makeRadial(id, val, color, label) {
    new ApexCharts(document.querySelector('#'+id), {
      series: [val],
      chart: { type:'radialBar', height:200, sparkline:{ enabled:true } },
      plotOptions: { radialBar:{ startAngle:-135, endAngle:135, hollow:{ size:'65%' }, dataLabels:{ name:{ show:false }, value:{ fontSize:'22px', fontWeight:700, color:color, fontFamily:'Plus Jakarta Sans', offsetY:8, formatter: function(v){ return v+'%'; } } } } },
      fill: { type:'gradient', gradient:{ shade:'dark', type:'horizontal', shadeIntensity:0.4, stops:[0,100] } },
      stroke: { lineCap:'round' },
      colors: [color]
    }).render();
  }
  makeRadial('radial1', 68, '#5D87FF', 'CPU');
  makeRadial('radial2', 82, '#13DEB9', 'Memory');
  makeRadial('radial3', 91, '#FFAE1F', 'Goals');
  makeRadial('radial4', 72, '#FA896B', 'NPS');
});

/* ============================================================
   3.57: ui-spinners.html
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('pgType')) return;

  window.toggleBtnLoad = function(btn) {
    const origHTML = btn.dataset.origHtml || btn.innerHTML;
    if (!btn.dataset.origHtml) btn.dataset.origHtml = origHTML;
    if (btn.disabled) {
      btn.disabled = false;
      btn.innerHTML = origHTML;
    } else {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading\u2026';
      setTimeout(() => { btn.disabled = false; btn.innerHTML = origHTML; }, 3000);
    }
  };

  window.toggleOverlay = function() {
    const overlay = document.getElementById('demoOverlay');
    overlay.classList.toggle('d-none');
    if (!overlay.classList.contains('d-none')) {
      setTimeout(() => overlay.classList.add('d-none'), 3000);
    }
  };

  window.updatePlayground = function() {
    const type=document.getElementById('pgType').value, color=document.getElementById('pgColor').value;
    const size=document.getElementById('pgSize').value, label=document.getElementById('pgLabel').checked;
    const inBtn=document.getElementById('pgInBtn').checked;
    const preview=document.getElementById('pgPreview'), codeEl=document.getElementById('pgCode');
    const sizeMap={ sm:'1rem', md:'2rem', lg:'3rem', xl:'4.5rem' };
    const sizePx=sizeMap[size], btnSm=(size==='sm');
    let spinnerHTML='', codeStr='';
    if(type==='border'){ spinnerHTML=`<div class="spinner-border text-${color}" style="width:${sizePx};height:${sizePx};" role="status"><span class="visually-hidden">Loading...</span></div>`; codeStr=spinnerHTML; }
    else if(type==='grow'){ spinnerHTML=`<div class="spinner-grow text-${color}" style="width:${sizePx};height:${sizePx};" role="status"><span class="visually-hidden">Loading...</span></div>`; codeStr=spinnerHTML; }
    else if(type==='ring'){ const rs={ sm:'28px', md:'46px', lg:'64px', xl:'80px' }[size]; spinnerHTML=`<div class="ring-spinner" style="width:${rs};height:${rs};"></div>`; codeStr=`<!-- Add .ring-spinner CSS -->\n${spinnerHTML}`; }
    else if(type==='dual'){ const rs={ sm:'28px', md:'46px', lg:'64px', xl:'80px' }[size]; spinnerHTML=`<div class="dual-ring" style="width:${rs};height:${rs};"></div>`; codeStr=spinnerHTML; }
    else if(type==='dots'){ spinnerHTML='<div class="dot-pulse"><span></span><span></span><span></span></div>'; codeStr=spinnerHTML; }
    else if(type==='bars'){ spinnerHTML='<div class="bar-pulse"><span></span><span></span><span></span><span></span><span></span></div>'; codeStr=spinnerHTML; }
    let labelHTML='';
    if(label && type!=='dots' && type!=='bars'){ labelHTML='<span class="text-muted small mt-2">Loading...</span>'; }
    let finalHTML='';
    if(inBtn && (type==='border'||type==='grow')){
      const btnSize=btnSm?' btn-sm':'';
      const innerSpinner=type==='border'?'<span class="spinner-border spinner-border-sm me-2" role="status"></span>':'<span class="spinner-grow spinner-grow-sm me-2" role="status"></span>';
      finalHTML=`<button class="btn btn-${color}${btnSize}" type="button" disabled>${innerSpinner}Loading\u2026</button>`;
      codeStr=finalHTML;
    } else { finalHTML=`<div class="d-flex flex-column align-items-center">${spinnerHTML}${labelHTML}</div>`; }
    preview.innerHTML=finalHTML;
    codeEl.textContent=codeStr;
  };
});

/* ============================================================
   3.58: ui-tooltip.html
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  if (typeof bootstrap === 'undefined') return;
  // Initialize all tooltips
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) { new bootstrap.Tooltip(el); });
  // Initialize all popovers
  document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function(el) { new bootstrap.Popover(el); });
  // Manual popover control
  var manualPopEl = document.getElementById('manualPop');
  if (manualPopEl) {
    var manualPop = new bootstrap.Popover(manualPopEl, { trigger:'manual' });
    manualPopEl.addEventListener('click', function() { manualPop.toggle(); });
  }
});
