/* =========================
   Caffora Front JS (DB-integrated)
   - Hanya boleh add to cart saat LOGIN (cek ke /backend/auth_status.php)
   - Render daftar menu + search + filter
   ========================= */

/* ===== Base paths & endpoints ===== */
const MENU_JSON_CANDIDATES = [
  "./menu.json",
  "../menu.json",
  "/caffora-app1/public/menu.json", // fallback absolut (XAMPP)
];

// cari prefix project → sebelum /public
const PUBLIC_SPLIT = "/public/";
const _idx = window.location.pathname.indexOf(PUBLIC_SPLIT);
const PROJECT_BASE = _idx > -1 ? window.location.pathname.slice(0, _idx) : "";

// API endpoint (DI BACKEND)
const MENU_API = PROJECT_BASE + "/backend/api/menu.php";
// Endpoint status login (WAJIB ada di backend, return: {logged_in:true/false, role?:string})
const AUTH_STATUS_URL = PROJECT_BASE + "/backend/auth_status.php";
// Halaman login (untuk redirect kalau mau)
const LOGIN_URL = PROJECT_BASE + "/public/login.php";

/* ===== State ===== */
let RAW_MENU = [];
let CURRENT_FILTER = "all";
let CURRENT_QUERY = "";

let INITIAL_LIMIT = getInitialLimit();
let CURRENT_LIMIT = INITIAL_LIMIT;

// cache auth
let AUTH_OK = null; // null = belum dicek; true/false = status terakhir
let AUTH_CHECK_AT = 0; // timestamp ms
const AUTH_TTL_MS = 60_000; // cache 1 menit

/* ===== Helpers ===== */
function getInitialLimit() {
  return window.matchMedia("(min-width: 992px)").matches ? 12 : 4;
}
function escapeHtml(s) {
  return (s ?? "").replace(
    /[&<>"']/g,
    (m) =>
      ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      }[m])
  );
}

/* ===== API & JSON fetch ===== */
async function fetchFromAPI(params = {}) {
  const url = new URL(MENU_API, window.location.origin);
  url.searchParams.set("status", params.status || "Ready");
  if (params.q) url.searchParams.set("q", params.q);
  if (params.category) url.searchParams.set("category", params.category);

  const res = await fetch(url.toString(), {
    cache: "no-store",
    credentials: "same-origin",
  });
  if (!res.ok) throw new Error("API menu.php tidak tersedia");
  const data = await res.json();
  return Array.isArray(data?.items) ? data.items : [];
}

async function fetchJSON(candidates) {
  for (const url of candidates) {
    try {
      const res = await fetch(url, { cache: "no-store" });
      if (res.ok) return await res.json();
    } catch (_) {}
  }
  throw new Error("menu.json tidak ditemukan.");
}

/* ===== Normalisasi item ===== */
function normalizeItem(d, i = 0) {
  const cat = String(d.category || "").toLowerCase();
  const priceInt = Number(d.price_int ?? d.price ?? 0) || 0;

  let img = d.image_url || d.image || d.img || "";
  if (!img) {
    img =
      "https://picsum.photos/seed/caffora" +
      Math.random().toString(36).slice(2, 6) +
      "/600/600";
  } else if (!/^https?:\/\//i.test(img) && !img.startsWith("data:")) {
    // kalau relatif (uploads/menu/xxx.jpg), jadikan absolute ke /public
    img = PROJECT_BASE + "/public/" + img.replace(/^\/+/, "");
  }

  return {
    id:
      d.id ??
      (d.name ? d.name.toLowerCase().replace(/\s+/g, "-") + "-" + i : i),
    name: d.name || "Menu",
    price: priceInt,
    price_int: priceInt,
    category: cat,
    image: img,
  };
}

/* ===== Card renderer ===== */
function cardHTML(item) {
  const priceK = Math.round((item.price || 0) / 1000) + "k";
  return `
  <div class="col-6 col-md-4 col-lg-2">
    <div class="card-menu h-100">
      <img class="thumb" src="${item.image}" alt="${escapeHtml(item.name)}"
           loading="lazy" style="aspect-ratio:1/1;object-fit:cover;">
      <div class="title">${escapeHtml(item.name)}</div>
      <div class="price">${priceK}</div>
      <button class="btn-add" aria-label="Tambah ke keranjang" data-id="${
        item.id
      }">
        <span class="plus">+</span>
      </button>
    </div>
  </div>`;
}

/* ===== Toast (HITAM) ===== */
function ensureToastHost() {
  if (!document.getElementById("toastHost")) {
    const host = document.createElement("div");
    host.id = "toastHost";
    host.style.position = "fixed";
    host.style.zIndex = "1080";
    host.style.right = "16px";
    host.style.bottom = "16px";
    document.body.appendChild(host);
  }
}
function showToastDark(msg) {
  ensureToastHost();
  const wrap = document.createElement("div");
  wrap.className = "toast align-items-center text-bg-dark border-0 show";
  wrap.setAttribute("role", "alert");
  wrap.style.minWidth = "280px";
  wrap.style.boxShadow = "0 8px 24px rgba(0,0,0,.12)";
  wrap.innerHTML =
    '<div class="d-flex">' +
    '<div class="toast-body">' +
    msg +
    "</div>" +
    '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
    "</div>";
  document.getElementById("toastHost").appendChild(wrap);
  setTimeout(() => {
    wrap.classList.remove("show");
    wrap.addEventListener("transitionend", () => wrap.remove(), { once: true });
    setTimeout(() => wrap.remove(), 300);
  }, 1800);
}

/* ===== Auth (cek ke server, cache 60s) ===== */
async function checkAuth(force = false) {
  const now = Date.now();
  if (!force && AUTH_OK !== null && now - AUTH_CHECK_AT < AUTH_TTL_MS) {
    return AUTH_OK;
  }
  try {
    const res = await fetch(AUTH_STATUS_URL, {
      credentials: "same-origin",
      cache: "no-store",
    });
    const js = await res.json().catch(() => ({}));
    AUTH_OK = !!js.logged_in;
    AUTH_CHECK_AT = now;
  } catch {
    AUTH_OK = false;
    AUTH_CHECK_AT = now;
  }
  return AUTH_OK;
}

/* ===== Cart ===== */
function updateCartBadge() {
  const badge = document.getElementById("cartBadge");
  if (!badge) return;
  const cart = JSON.parse(localStorage.getItem("caffora_cart") || "[]");
  const total = cart.reduce((a, c) => a + (c.qty || 0), 0);
  badge.style.display = total > 0 ? "inline-block" : "none";
  if (total > 0) badge.textContent = String(total);
}

// >>>> Satu-satunya pintu tambah keranjang
async function addToCart(id) {
  // CEK LOGIN DULU via server
  const ok = await checkAuth();
  if (!ok) {
    showToastDark("Silakan login terlebih dahulu.");
    return; // PENTING: JANGAN MENYENTUH localStorage kalau belum login
  }

  // Sudah login → baru boleh simpan
  const item = RAW_MENU.find((m) => String(m.id) === String(id));
  if (!item) return;
  const key = "caffora_cart";
  const cart = JSON.parse(localStorage.getItem(key) || "[]");
  const i = cart.findIndex((c) => String(c.id) === String(id));
  if (i > -1) cart[i].qty += 1;
  else cart.push({ id: item.id, name: item.name, price: item.price, qty: 1 });
  localStorage.setItem(key, JSON.stringify(cart));
  updateCartBadge();
  showToastDark("Ditambahkan ke keranjang.");
}

/* ===== Filter/Search/Render ===== */
function filteredData() {
  let data = [...RAW_MENU];
  if (["pastry", "drink", "food"].includes(CURRENT_FILTER)) {
    data = data.filter(
      (d) => (d.category || "").toLowerCase() === CURRENT_FILTER
    );
  }
  if (CURRENT_FILTER === "cheap")
    data.sort((a, b) => (a.price || 0) - (b.price || 0));
  else if (CURRENT_FILTER === "expensive")
    data.sort((a, b) => (b.price || 0) - (a.price || 0));

  const q = (CURRENT_QUERY || "").trim().toLowerCase();
  if (q) {
    data = data.filter(
      (d) =>
        (d.name || "").toLowerCase().includes(q) ||
        (d.category || "").toLowerCase().includes(q)
    );
  }
  return data;
}

function renderMenu() {
  const list = document.getElementById("list");
  if (!list) return;
  const data = filteredData();
  const showingAll = Boolean(CURRENT_QUERY) || CURRENT_FILTER !== "all";
  const slice = showingAll ? data : data.slice(0, CURRENT_LIMIT);
  list.innerHTML = slice.length
    ? slice.map(cardHTML).join("")
    : '<div class="text-center text-muted py-4">Menu tidak ditemukan.</div>';

  // Pasang handler tombol +
  list.querySelectorAll(".btn-add").forEach((btn) => {
    btn.addEventListener(
      "click",
      (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
        const id = btn.getAttribute("data-id");
        addToCart(id); // addToCart SUDAH meng-hard-stop kalau belum login
      },
      { passive: true }
    );
  });
}

/* ===== Init ===== */
async function initPage() {
  // Cek auth sekali di awal (biar badge tidak salah persepsi)
  await checkAuth().catch(() => {});
  updateCartBadge();

  // Ambil menu
  try {
    const apiItems = await fetchFromAPI({});
    RAW_MENU = apiItems.length
      ? apiItems.map((d, i) => normalizeItem(d, i))
      : [];
  } catch (e) {
    try {
      const raw = await fetchJSON(MENU_JSON_CANDIDATES);
      RAW_MENU = (raw || []).map((d, i) => normalizeItem(d, i));
    } catch (e2) {
      console.error("Gagal memuat data menu:", e.message, e2.message);
      RAW_MENU = [];
    }
  }

  renderMenu();

  // Search
  const q = document.getElementById("q");
  const btnCari = document.getElementById("btnCari");
  if (q) {
    q.addEventListener("keydown", (ev) => {
      if (ev.key === "Enter") {
        CURRENT_QUERY = q.value || "";
        renderMenu();
      }
    });
  }
  if (btnCari) {
    btnCari.addEventListener("click", () => {
      CURRENT_QUERY = q?.value || "";
      renderMenu();
    });
  }

  // Filter
  const filterMenu = document.getElementById("filterMenu");
  if (filterMenu) {
    filterMenu.querySelectorAll("[data-filter]").forEach((a) => {
      a.addEventListener("click", (ev) => {
        ev.preventDefault();
        CURRENT_FILTER = a.getAttribute("data-filter") || "all";
        renderMenu();
      });
    });
  }

  // Responsif (ubah jumlah default)
  const mql = window.matchMedia("(min-width: 992px)");
  const onChange = () => {
    const newLimit = mql.matches ? 12 : 4;
    if (
      newLimit !== INITIAL_LIMIT &&
      !CURRENT_QUERY &&
      CURRENT_FILTER === "all"
    ) {
      INITIAL_LIMIT = newLimit;
      CURRENT_LIMIT = INITIAL_LIMIT;
      renderMenu();
    }
  };
  if (typeof mql.addEventListener === "function")
    mql.addEventListener("change", onChange);
  else if (typeof mql.addListener === "function") mql.addListener(onChange);
}

document.addEventListener("DOMContentLoaded", initPage);
// Jika halaman ini adalah index.html, kosongkan keranjang utama
if (
  window.location.pathname.endsWith("index.html") ||
  window.location.pathname === "/"
) {
  console.log("🧹 Halaman index terdeteksi — keranjang dikosongkan.");
  localStorage.removeItem("caffora_cart");
  updateCartBadge(); // pastikan badge juga direset
}
