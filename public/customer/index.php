<?php    
// public/customer/index.php    
declare(strict_types=1);    
  
require_once __DIR__ . '/../../backend/auth_guard.php';    
require_login(['customer']); // wajib login    
  
$name  = $_SESSION['user_name']  ?? 'Customer';    
$email = $_SESSION['user_email'] ?? '';    
?>    
<!DOCTYPE html>    
<html lang="id">    
  <head>    
    <meta charset="utf-8" />    
    <title>Caffora — Coffee • Dessert • Space</title>    
    <meta name="viewport" content="width=device-width, initial-scale=1" />    
  
    <!-- Fonts -->    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />    
  
    <!-- Bootstrap & Icons -->    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />    
  
    <!-- Global CSS (kalau ada) -->    
    <link href="../assets/css/style.css" rel="stylesheet" />    
  
    <style>    
      :root {    
        --gold: #ffd54f;    
        --gold-200: #ffe883;    
        --tan: #daa85c;    
        --brown: #4b3f36;    
        --footer: #876f45;    
        --bg-soft: #f6f7f9;    
      }    
  
      body {    
        background: var(--bg-soft);    
        font-family: "Poppins", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;    
      }    
  /* Navbar lebih fleksibel di layar kecil */
.navbar {
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
}

.navbar-brand img {
  max-height: 40px;
}

.navbar-nav {
  text-align: center;
}

.icon-btn {
  font-size: 1.2rem;
  transition: 0.3s;
}

.icon-btn:hover {
  color: var(--gold);
  transform: scale(1.1);
}

/* Grid produk otomatis menyesuaikan */
.menu-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
  gap: 1.5rem;
  padding: 20px;
}

.card {
  border-radius: 16px;
  transition: transform 0.3s ease;
}

.card:hover {
  transform: translateY(-4px);
}

/* Pastikan toggle dan menu tidak tabrakan */
.navbar-toggler {
  border: none;
  outline: none;
}

       /* ---- Navbar ---- */
  .navbar {
    background: #fff !important;
  }
  .navbar .nav-link {
    color: var(--brown);
    font-weight: 500;
    transition: opacity 0.15s ease, color 0.15s ease;
    opacity: 0.9;
  }
  .navbar .nav-link:hover {
    opacity: 1;
    color: var(--tan);
  }
  .navbar .nav-link.active {
    color: var(--brown);
    font-weight: 700;
    opacity: 1;
  }

  .about-section {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 20px;
  padding: 40px 20px;
}

.about-section img {
  max-width: 100%;
  border-radius: 12px;
}

.about-section .text {
  flex: 1;
  text-align: justify;
}

/* Di HP: kolom jadi vertikal */
@media (max-width: 768px) {
  .about-section {
    flex-direction: column;
    text-align: center;
  }
}

.footer {
  text-align: center;
  padding: 20px 15px;
  font-size: 0.9rem;
  color: #fff;
  background: var(--brown);
}

.footer a {
  color: var(--gold);
  text-decoration: none;
}

.footer a:hover {
  text-decoration: underline;
}


  
      /* Cart icon */    
      #cartBtn .iconify { color: var(--brown); transition: color }    
      #cartBtn .badge-cart { background: !important; color: #fff; }    
      #cartBtn { border: none !important; border-radius: 0; padding: 0; background: transparent; }    
  
      /* Tombol brand */    
      .btn-brand {    
        background: var(--gold); color: var(--brown); border: 0; border-radius: 9999px;    
        font-weight: 600; padding: 8px 14px; font-size: .9rem; line-height: 1.2;    
      }    
      .btn-brand:hover { background: var(--gold-200); color: var(--brown); }    
      .btn-brand:focus { box-shadow: 0 0 0 0.16rem rgba(255,213,79,.35); }    
      a.btn-brand { text-decoration: none !important; }    
  
      /* PROFIL ICON (trigger) */    
      .icon-btn{ background:transparent; border:none; padding:0; line-height:1; color:var(--brown); }    
   
  
      /* === Dropdown desktop selaras sidebar mobile (font & ukuran ikon) === */  
      .dropdown-menu {  
        border-radius: 12px;  
        border-color: #eee;  
        padding: 6px 0;  
        font-family: "Poppins", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;  
      }  
      .dropdown-menu .dropdown-item{  
        font-size: 1.05rem;  
        font-weight: 600;  
        color: var(--brown);  
        padding: .6rem .9rem;  
        display: flex;  
        align-items: center;  
        gap: .5rem;  
      }  
      .dropdown-menu .dropdown-item:hover,  
      .dropdown-menu .dropdown-item:focus{  
        color: var(--tan);  
        background: transparent;  
      }  
      .dropdown-menu .dropdown-item .iconify{  
        width: 22px;  
        height: 22px;  
      }  
      .dropdown-menu .dropdown-divider{ margin: 6px 0; }  

@media (max-width: 600px) {
  .btn-primary-cf {
    width: 100%;
    padding: 12px 0;
    font-size: 1rem;
  }

  h1, h2, h3 {
    font-size: 1.4rem;
  }

  p {
    font-size: 0.95rem;
  }

  .container {
    padding: 10px;
  }
}

      /* ==== Responsif untuk toast di mobile ==== */
@media (max-width: 600px) {
  .toast {
    width: 100% !important;  /* biar tetap full */
    left: 0 !important;      /* pastikan rata kiri */
    right: 0 !important;     /* dan rata kanan */
    border-radius: 0;        /* opsional: biar full edge */
  }

  .toast-body,
  .toast-message {
    font-size: 0.8rem;       /* perkecil teks aja */
  }
   /* ==== Responsif untuk hero section (gambar + teks) di mobile ==== */
      @media (max-width: 600px) {
        /* Hero container agar tetap landscape */
        section.hero {
          position: relative;
          width: 100%;
          aspect-ratio: 16 / 9; /* tetap landscape */
          overflow: hidden;
        }

        /* Pastikan gambar di hero menyesuaikan rasio */
        section.hero::before {
          content: "";
          position: absolute;
          inset: 0;
          background-image: url("../img/hero.jpg"); /* ganti sesuai path gambar kamu */
          background-size: cover;
          background-position: center;
          z-index: -1;
        }

        /* Teks di dalam hero */
        section.hero h1 {
          font-size: 1.3rem;
          line-height: 1.2;
        }

        section.hero p {
          font-size: 0.9rem;
          line-height: 1.3;
        }
      }
      /* ==== Responsif hero di mobile ==== */
      @media (max-width: 600px) {
        section.hero {
          position: relative;
          display: flex;
          align-items: center; /* vertikal tengah */
          justify-content: center; /* horizontal tengah */
          text-align: center; /* teks ke tengah */
          aspect-ratio: 16 / 9; /* biar tetap landscape */
          overflow: hidden;
        }

        section.hero::before {
          content: "";
          position: absolute;
          inset: 0;
          background-image: url("../img/hero.jpg"); /* ganti sesuai path */
          background-size: cover;
          background-position: center;
          z-index: -1;
        }

        section.hero .container {
          position: relative;
          z-index: 2;
          padding: 0 1rem;
        }

        section.hero h1 {
          font-size: 1.3rem;
          line-height: 1.2;
          margin-bottom: 0.5rem;
        }

        section.hero p {
          font-size: 0.9rem;
          line-height: 1.4;
          margin: 0;
        }
      }
      /* ==== Responsif untuk halaman Tentang di mobile ==== */
      @media (max-width: 600px) {
        #about h2,
        #about h3 {
          font-size: 1.2rem; /* perkecil judul */
        }

        #about p {
          font-size: 0.9rem; /* perkecil teks isi */
          line-height: 1.4;
        }

        #about .container {
          padding: 0 1rem; /* kasih ruang sisi biar nggak mepet */
        }
      }
   /* === ABOUT: full image + no padding to footer (mobile) === */
      @media (max-width: 576px) {
        /* hilangkan padding bawaan py-5 di section about */
        #about {
          padding-top: 1.25rem !important; /* boleh disesuaikan */
          padding-bottom: 0 !important; /* no padding dengan footer */
          background: transparent;
        }

        /* buang padding container & gutter row */
        #about .container {
          padding-left: 0 !important;
          padding-right: 0 !important;
        }
        #about .row {
          --bs-gutter-x: 0;
          --bs-gutter-y: 0;
        }

        /* gambar full-bleed, tanpa radius & shadow */
        #about .about-img-wrap {
          margin: 0 !important;
        }
        #about .about-img {
          width: 100% !important;
          max-width: 100% !important;
          height: auto;
          display: block;
          border-radius: 0 !important;
          box-shadow: none !important;
        }

        /* rapikan teks atasnya sedikit */
        #about .about-title {
          margin: 0 1rem 0.5rem;
        }
        #about .about-desc {
          margin: 0 1rem 0.75rem;
        }
        #about .btn-view {
          margin: 0 1rem 1rem;
        }
      }

      /* ==== Footer overlap sedikit ke gambar About (tanpa radius) ==== */
      @media (max-width: 600px) {
        /* Pastikan gambar about tetap full dan menempel */
        #about {
          padding-bottom: 0 !important;
          margin-bottom: 0 !important;
          position: relative;
          z-index: 1;
        }

        #about .about-img {
          display: block;
          width: 100vw !important;
          max-width: 100vw !important;
          margin-left: calc(50% - 50vw);
          margin-right: calc(50% - 50vw);
          border-radius: 0 !important;
          box-shadow: none !important;
          margin-bottom: 0 !important;
          margin-top: 30px;
        }
      /* Footer naik sedikit tapi rata (tanpa border-radius) */
        footer {
            width: 100vw; /* penuh seluruh viewport */
  margin-left: calc(-1 * (100vw - 100%)/2); /* trik untuk netralisasi scrollbar gap */

          position: relative;
          z-index: 2;
          margin-top: -30px; /* overlap dikit ke atas gambar, bisa ubah 20–40px sesuai selera */
          background: var(--footer);
          border-top-left-radius: 0 !important;
          border-top-right-radius: 0 !important;
          padding: 28px 18px 20px; /* cukup ruang biar teks footer gak nempel */
        }

        /* Tata letak dalam footer */
        footer .container {
          padding: 0 !important;
        }

        footer .row.g-4 {
          --bs-gutter-y: 0.75rem;
          --bs-gutter-x: 0;
        }

        footer h5,
        footer h6 {
          font-size: 1rem;
          margin-bottom: 6px;
        }

        footer p,
        footer a,
        footer li {
          font-size: 0.85rem;
          line-height: 1.4;
          margin-bottom: 4px;
        }

        footer .footer-icons {
          margin-top: 8px;
        }

        footer .footer-icons a {
          font-size: 1.1rem;
          margin-right: 10px;
        }

        footer .footer-bottom {
          font-size: 0.8rem;
          margin-top: 12px;
          color: #d4c48a;
        }
      }
} 
      .custom-offcanvas{ width:72%; max-width:300px; border-left:1px solid rgba(0,0,0,.06); }    
      @media (max-width:360px){ .custom-offcanvas{ width:70%; max-width:280px; } }    
      .custom-offcanvas .nav-link{ font-size:1.05rem; padding:.6rem 0; color:var(--brown)!important; font-weight:600; opacity:1; }        
      .offcanvas-user{ color:var(--brown)!important; text-decoration:none!important; display:inline-block; margin:2px 0 12px; font-weight:600; }    
      .offcanvas-user .small{ font-weight:400; opacity:.8; }    
  
      /* ===== HERO ===== */    
      .hero .container{ padding:56px 0; }    
      .hero h1{ font-family:"Playfair Display", serif; font-weight:700; }    
  
      /* ===== MENU ===== */    
      #menuSection{ background:transparent; }    
      .search-box{ position:relative; width:100%; }    
      .search-input{ height:46px; border-radius:9999px; padding-left:16px; padding-right:44px; border:1px solid #e5e7eb; background:#fff; }    
      .btn-search{ position:absolute; right:6px; top:50%; transform:translateY(-50%); height:34px; width:34px; display:flex; align-items:center; justify-content:center; border:0; border-radius:50%; background:transparent; color:var(--brown); }    
      .btn-search:hover{ opacity:.8; }    
      .btn-filter{ background:var(--gold); color:var(--brown); border:0; border-radius:12px; font-weight:600; height:46px; width:46px; display:inline-flex; align-items:center; justify-content:center; }    
      .btn-filter:hover{ background:var(--gold-200); color:var(--brown); }    
  
      /* ===== ABOUT ===== */    
      #about{ background:transparent; }    
      #about .about-title{ font-family:"Playfair Display", serif; font-weight:700; font-size:clamp(26px,3vw,36px); line-height:1.25; color:#111; margin-bottom:10px; }    
      #about .about-desc{ color:#666; font-size:clamp(14px,1.5vw,16px); margin-bottom:16px; max-width:54ch; }    
      .btn-view{ background:var(--gold); color:var(--brown); border:0; border-radius:9999px; font-weight:500; padding:9px 15px; font-size:0.9rem; display:inline-flex; align-items:center; gap:8px; text-decoration:none!important; box-shadow:none; }    
      .btn-view:hover{ background:var(--gold-200); color:var(--brown); }    
      .about-img{ width:70%; max-width:70%; border-radius:24px; object-fit:cover; box-shadow:0 10px 22px rgba(0,0,0,.08); }    
      .about-img-wrap{ text-align:center; }    
      @media (max-width:992px){ .about-img{ max-width:75%; } }    
      @media (max-width:576px){    
        .hero .container{ padding:40px 0; }    
        .about-img{ max-width:60%; border-radius:20px; }    
        #about .about-title{ font-size:24px; }    
      }    
  
      /* ===== FOOTER ===== */    
      footer{ background:var(--footer); color:var(--gold-200); padding-top:36px; padding-bottom:20px; }    
      footer h5, footer h6{ color:var(--gold-200); font-weight:700; }    
      footer a{ color:var(--gold-200); text-decoration:none; }    
      footer a:hover{ color:var(--gold); text-decoration:underline; }    
      footer .footer-icons a{ color:var(--gold-200); font-size:20px; margin-right:12px; }    
      footer .border-secondary-subtle{ border-color:rgba(255,255,255,.18)!important; }    
  
      .icon-btn {
  border: none;
  background: transparent;
  padding: 4px;
}

      /* Toast mini */    
      .toast-mini{ position:fixed; right:16px; top:76px; z-index:1080; }    
      /* ==== Samakan tombol View More dengan Checkout di mobile ==== */
@media (max-width: 600px) {
  .btn-viewmore,
  .btn-primary-cf {
    display: block;
    width: 100%;
    padding: 10px 18px;
    font-size: 0.9rem;
    font-weight: 500; /* turunkan dari 600 agar tidak terlalu tebal */
    border-radius: 9999px;
    background: var(--gold);
    color: var(--brown);
    border: none;
    text-align: center;
    margin-top: 20px;
    margin-bottom: 40px;
  }

  .btn-viewmore:hover,
  .btn-primary-cf:hover {
    background: var(--gold-200);
    color: var(--brown);
  }

  #about .about-img-wrap {
    margin-top: 30px;
  }
}

/* === RAPIKAN POSISI HAMBURGER AGAR TIDAK DI TENGAH jam 11.44 === */
.navbar .container {
  align-items: center !important;
}

.navbar .right-actions {
  display: flex;
  align-items: center !important;
  justify-content: flex-end !important;
  gap: 12px;
}

.navbar .icon-btn.d-lg-none {
  top: 0 !important;
}

/* ====== PERBAIKAN WARNA MENU MOBILE ====== */

/* Warna default menu di offcanvas */
#mobileNav .nav-link {
  color: var(--brown) !important;
  font-weight: 600;
  opacity: 0.9;
  transition: color 0.25s ease, font-weight 0.25s ease;
}

/* Saat hover: sedikit lebih gelap */
#mobileNav .nav-link:hover {
  color: var(--tan) !important;
  opacity: 1 !important;
}

/* Saat aktif (diklik): tebal dan coklat tua */
#mobileNav .nav-link.active {
  color: var(--brown) !important;
  font-weight: 700 !important;
  opacity: 1 !important;
}


/* Tombol tidak berubah warna saat hover */
.btn-brand:hover,
.btn-filter:hover,
.btn-view:hover,
.btn-primary-cf:hover,
.btn-viewmore:hover {
  background: var(--gold) !important;
  color: var(--brown) !important;
}

/* Hilangkan underline atau highlight di link footer */
footer a:hover,
footer a:focus {
  color: var(--gold-200) !important;
  text-decoration: none !important;
}

/* === HAMBURGER ICON SEJAJAR DENGAN CART DAN LOGO === */
.navbar .right-actions {
  display: flex;
  align-items: center; /* sejajar vertikal */
  gap: 12px;
}

.navbar .icon-btn.d-lg-none {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 !important;
  margin: 0 !important;
  line-height: 1;
  position: relative;
  top: 1px; /* sejajarkan dengan logo dan cart */
}

.navbar .icon-btn.d-lg-none i {
  font-size: 30px;
  transition: none !important; /* tanpa animasi */
}

/* === RAPIHKAN ICON DI NAVBAR (CART & HAMBURGER SEJAJAR) === */
.navbar .icon-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4px;
  color: var(--brown);
  transform: none !important;
}
/* ===== Hilangkan efek hover/gerak pada ikon hamburger (mobile) ===== */
      @media (max-width: 991.98px) {
        /* pastikan semua tombol ikon di mobile tidak punya efek hover */
        .icon-btn,
        .icon-btn i.bi-list {
          transition: none !important;
          transform: none !important;
          color: var(--brown) !important;
        }

        .icon-btn:hover,
        .icon-btn:active,
        .icon-btn:focus,
        .icon-btn:hover i.bi-list,
        .icon-btn:active i.bi-list,
        .icon-btn:focus i.bi-list {
          transform: none !important;
          color: var(--brown) !important;
          opacity: 1 !important;
          box-shadow: none !important;
          outline: none !important;
        }

        /* khusus untuk ikon hamburger di header offcanvas */
        .offcanvas-header .bi-list:hover,
        .offcanvas-header .bi-list:active,
        .offcanvas-header .bi-list:focus {
          transform: none !important;
          color: var(--brown) !important;
          opacity: 1 !important;
        }
      }

    </style>        
  </head>    
  <body>    
    <!-- NAVBAR -->    
    <nav class="navbar sticky-top shadow-sm">    
      <div class="container d-flex align-items-center justify-content-between">    
        <a class="navbar-brand d-flex align-items-center gap-2" href="#hero">    
          <i class="bi bi-cup-hot-fill text-warning"></i>    
          <span class="brand-brown">Caffora</span>    
        </a>    
  
        <ul class="navbar-nav d-none d-lg-flex flex-row gap-3" id="mainNav">    
          <li class="nav-item"><a class="nav-link active" href="#hero">Home</a></li>    
          <li class="nav-item"><a class="nav-link" href="#menuSection">Product</a></li>    
          <li class="nav-item"><a class="nav-link" href="#about">Tentang Kami</a></li>    
        </ul>    
  
        <div class="right-actions d-flex align-items-center gap-3">    
          <!-- Cart -->    
          <a class="icon-btn position-relative me-lg-1" id="cartBtn" aria-label="Buka keranjang" href="/caffora-app1/public/customer/cart.php">    
            <span class="iconify" data-icon="mdi:cart-outline" data-width="24" data-height="24"></span>    
            <span class="badge-cart badge rounded-pill bg-danger" id="cartBadge" style="display:none">0</span>    
          </a>    
  
          <!-- Dropdown Profil -->    
          <div class="dropdown d-none d-lg-block">    
            <button class="icon-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Akun">    
              <span class="iconify" data-icon="mdi:account-circle-outline" data-width="26" data-height="26"></span>    
            </button>    
            <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width: 260px;">    
              <li class="px-3 py-2">    
                <div class="small text-muted">Masuk sebagai</div>    
                <div class="fw-semibold"><?= htmlspecialchars($name) ?></div>    
                <div class="small text-muted text-truncate" style="max-width:220px">    
                  <?= htmlspecialchars($email) ?>    
                </div>    
              </li>    
              <li><hr class="dropdown-divider"></li>    
              <li>    
                <a class="dropdown-item" href="profile.php">    
                  <span class="iconify" data-icon="mdi:account-outline"></span> Profile    
                </a>    
              </li>    
              <li>    
                <a class="dropdown-item" href="settings.php">    
                  <span class="iconify" data-icon="mdi:cog-outline"></span> Setting    
                </a>    
              </li>    
              <li>    
                <a class="dropdown-item" href="history.php">    
                  <span class="iconify" data-icon="mdi:receipt-text-outline"></span> Riwayat Pesanan    
                </a>    
              </li>    
              <li><hr class="dropdown-divider"></li>    
              <li>    
                <a class="dropdown-item text-danger" href="/caffora-app1/backend/logout.php">    
                  <span class="iconify" data-icon="mdi:logout"></span> Log out    
                </a>    
              </li>    
            </ul>    
          </div>    

<!-- Hamburger -->
          <button
            class="icon-btn d-lg-none"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#mobileNav"
            aria-controls="mobileNav"
            aria-label="Buka menu"
          >
            <i class="bi bi-list" style="font-size: 30px"></i>
          </button>

</div>
</div>
</nav>

<!-- ✅ OFFCANVAS NAV (mobile) -->
<div
  class="offcanvas offcanvas-end custom-offcanvas"
  tabindex="-1"
  id="mobileNav"
  aria-labelledby="mobileNavLabel"
>
<div
        class="offcanvas-header border-bottom d-flex justify-content-between align-items-center"
      >
        <h5 class="offcanvas-title fw-semibold m-0" id="mobileNavLabel">
          <i class="bi bi-list" style="font-size: 2rem"></i>
        </h5>
      </div>

  <div class="offcanvas-body d-flex flex-column gap-2">
   
    <!-- Info user -->
    <div class="offcanvas-user">
      <div class="small">Masuk sebagai</div>
      <div class="fw-semibold"><?= htmlspecialchars($name) ?></div>
      <div
        class="small text-truncate"
        style="max-width: 240px"
      >
        <?= htmlspecialchars($email) ?>
      </div>
    </div>

    <!-- Hanya menu akun -->
    <ul class="nav flex-column mt-1" id="mobileNavList">
      <li class="nav-item">
        <a
          class="nav-link d-flex align-items-center gap-2"
          href="profile.php"
        >
          <span
            class="iconify"
            data-icon="mdi:account-circle-outline"
            data-width="22"
            data-height="22"
          ></span>
          Profil
        </a>
      </li>
      <li class="nav-item">
        <a
          class="nav-link d-flex align-items-center gap-2"
          href="settings.php"
        >
          <span
            class="iconify"
            data-icon="mdi:cog-outline"
            data-width="22"
            data-height="22"
          ></span>
          Setting
        </a>
      </li>
      <li class="nav-item">
        <a
          class="nav-link d-flex align-items-center gap-2"
          href="history.php"
        >
          <span
            class="iconify"
            data-icon="mdi:receipt-text-outline"
            data-width="22"
            data-height="22"
          ></span>
          Riwayat pesanan
        </a>
      </li>
      <li><hr /></li>
      <li class="nav-item">
        <a
          class="nav-link d-flex align-items-center gap-2 text-danger"
          href="/caffora-app1/backend/logout.php"
        >
          <span
            class="iconify"
            data-icon="mdi:logout"
            data-width="22"
            data-height="22"
          ></span>
          Log out
        </a>
      </li>
    </ul>
  </div>
</div>

         
    <!-- HERO -->    
    <section class="hero" id="hero">    
      <div class="container">    
        <h1>Selamat Datang di Caffora</h1>    
        <p>Ngopi santai, kerja produktif, dan dessert favorit—semua dalam satu ruang.</p>    
      </div>    
    </section>    
  
    <!-- MENU (katalog) -->    
    <main id="menuSection" class="container py-5">    
      <h2 class="mb-3 text-center">Menu Kami</h2>    
      <p class="text-center text-muted mb-4">Cari menu favoritmu lalu tambahkan ke keranjang.</p>    
  
      <!-- Search + Filter -->    
      <div class="row gy-3 gx-2 mb-3 justify-content-center">    
        <div class="col-12 col-md-7 d-flex align-items-center">    
          <div class="search-box flex-grow-1">    
            <input id="q" class="form-control search-input" placeholder="Search..." />    
            <button id="btnCari" class="btn-search" aria-label="Cari">    
              <i class="bi bi-search"></i>    
            </button>    
          </div>    
          <div class="dropdown ms-2">    
            <button class="btn-filter" id="filterBtn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Filter">    
              <span class="iconify" data-icon="mdi:filter-variant" data-width="22" data-height="22"></span>    
            </button>    
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="filterBtn" id="filterMenu">    
              <li><a class="dropdown-item" href="#" data-filter="cheap">Harga: Termurah → Termahal</a></li>    
              <li><a class="dropdown-item" href="#" data-filter="expensive">Harga: Termahal → Termurah</a></li>    
              <li><a class="dropdown-item" href="#" data-filter="pastry">Pastry</a></li>    
              <li><a class="dropdown-item" href="#" data-filter="drink">Drink</a></li>    
              <li><a class="dropdown-item" href="#" data-filter="food">Food</a></li>    
              <!-- <li><hr class="dropdown-divider" /></li>     -->
              <li><a class="dropdown-item" href="#" data-filter="all">Tampilkan Semua</a></li>    
            </ul>    
          </div>    
        </div>    
      </div>    
  
      <!-- Grid Menu -->    
      <div id="list" class="row g-4"></div>    
    </main>    
  
    <!-- ABOUT -->    
    <section id="about" class="py-5">    
      <div class="container">    
        <div class="row g-4 align-items-center">    
          <div class="col-lg-6">    
            <h2 class="about-title">Caffora tempat Nyaman<br />untuk Setiap Momen</h2>    
            <p class="about-desc">    
              Nikmati pengalaman ngopi di tempat yang tenang dan nyaman. Ruang yang minimalis dengan sentuhan hangat ini    
              siap menemani kamu untuk bekerja, bersantai, atau sekadar melepas penat bersama orang terdekat.    
            </p>    
            <a href="#menuSection" class="btn-view">View more</a>    
          </div>    
          <div class="col-lg-6 about-img-wrap">    
            <img src="../assets/img/cafforaf.jpg" alt="Ruang Caffora yang nyaman" class="about-img" />    
          </div>    
        </div>    
      </div>    
    </section>    
  
    <!-- FOOTER -->    
    <footer>    
      <div class="container">    
        <div class="row g-4 align-items-start">    
          <div class="col-md-4 mb-4 mb-md-0">    
            <h5>Caffora</h5>    
            <p>    
              Coffee <br />    
              Dessert <br />    
              Space    
            </p>    
            <div class="footer-icons">    
              <a href="#"><i class="bi bi-instagram"></i></a>    
              <a href="#"><i class="bi bi-facebook"></i></a>    
              <a href="#"><i class="bi bi-whatsapp"></i></a>    
              <a href="#"><i class="bi bi-tiktok"></i></a>    
            </div>    
          </div>    
          <div class="col-md-4 mb-4 mb-md-0">    
            <h6>Menu</h6>    
            <ul class="list-unstyled m-0">    
              <li><a href="#menuSection">Semua Menu</a></li>    
              <li><a href="#about">Tentang Kami</a></li>    
            </ul>    
          </div>    
          <div class="col-md-4">    
            <h6>Kontak</h6>    
            <p>Jl. Kopi No. 123, Purwokerto</p>    
            <p>0822-1234-5678</p>    
            <p><a href="mailto:Cafforaproject@gmail.com">Cafforaproject@gmail.com</a></p>    
          </div>    
        </div>    
        <hr class="border-secondary-subtle my-4" />    
        <div class="d-flex flex-wrap justify-content-between align-items-center small">    
          <div>© 2025 Caffora — All rights reserved</div>    
          <div class="d-flex gap-3"><a href="#">Terms</a><a href="#">Privacy</a><a href="#">Policy</a></div>    
        </div>    
      </div>    
    </footer>    
  
    <!-- Toast login dulu -->    
    <div class="toast align-items-center text-bg-dark border-0 toast-mini" role="alert" aria-live="assertive" aria-atomic="true" id="loginToast">    
      <div class="d-flex">    
        <div class="toast-body">Silakan login terlebih dahulu untuk menambahkan ke keranjang.</div>    
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>    
      </div>    
    </div>    
  
    <!-- JS -->    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>    
    <script src="https://code.iconify.design/3/3.1.1/iconify.min.js"></script>    
    <script src="../assets/js/app.js"></script>    
  


    <!-- Nav active-on-click (desktop & mobile) -->    
    <script>    
      (function () {    
        const nav = document.getElementById("mainNav");    
        if (nav) {    
          nav.querySelectorAll(".nav-link").forEach((a) => {    
            a.addEventListener("click", function () {    
              nav.querySelectorAll(".nav-link").forEach((x) => x.classList.remove("active"));    
              this.classList.add("active");    
            });    
          });    
        }    
      })();    
    </script>    
  
    <!-- Badge keranjang (jika app.js belum mengelola) -->    
    <script>    
      (function () {    
        const badge = document.getElementById("cartBadge");    
        try {    
          const cart = JSON.parse(localStorage.getItem("caffora_cart") || "[]");    
          const total = cart.reduce((a, c) => a + (c.qty || 0), 0);    
          if (total > 0) {    
            badge.style.display = "inline-block";    
            badge.textContent = total;    
          }    
        } catch (e) {}    
      })();    
    </script>    

    <!-- ===== Cart Image Patch: pastikan image_url tersimpan absolut ke /public/uploads/menu ===== -->
    <script>
      (function(){
        var PROJECT_BASE = '/caffora-app1';
        var PUBLIC_BASE  = PROJECT_BASE + '/public';
        var UPLOADS_DIR  = '/uploads/menu/';   // relatif dari /public
        var KEY = 'caffora_cart';

        function toAbs(url){
          if (!url) return '';
          if (/^https?:\/\//i.test(url)) return url;
          if (url.indexOf(PUBLIC_BASE) === 0) return url;
          if (url.indexOf('/uploads/') === 0) return PUBLIC_BASE + url;
          if (url.indexOf('uploads/') === 0)  return PUBLIC_BASE + '/' + url;
          if (url[0] !== '/') return PUBLIC_BASE + UPLOADS_DIR + url;
          return PUBLIC_BASE + url;
        }
        function readCart(){ try{return JSON.parse(localStorage.getItem(KEY)||'[]');}catch(e){return[];} }
        function writeCart(c){ localStorage.setItem(KEY, JSON.stringify(c)); }

        function patchItemImage(id, candidate){
          var cart = readCart();
          for (var i=0;i<cart.length;i++){
            if (String(cart[i].id) === String(id)){
              // Jika sudah ada dan bukan placeholder, biarkan
              var cur = cart[i].image_url || cart[i].image || '';
              if (!cur || /placeholder/i.test(cur)){
                var src = candidate || cur;
                if (!src){ break; }
                cart[i].image_url = toAbs(src.replace(/\\/g,'/'));
                writeCart(cart);
              }
              break;
            }
          }
        }

        // Tangkap klik tombol "+" dari berbagai selector, lalu tambahkan gambar
        var SELECTORS = ['.btn-add','[data-add-to-cart]','[data-action="add"]','button.add-to-cart'];
        document.addEventListener('click', function(ev){
          var btn = null;
          for (var i=0;i<SELECTORS.length;i++){
            var t = ev.target.closest(SELECTORS[i]);
            if (t){ btn = t; break; }
          }
          if (!btn) return;

          // Ambil id & kandidat gambar
          var id = btn.getAttribute('data-id') || btn.dataset.id || '';
          var imgCandidate = btn.getAttribute('data-img') || btn.dataset.img || '';
          if (!imgCandidate){
            var card = btn.closest('.card-menu, .card, .product, .item');
            var imgEl = card ? card.querySelector('img') : null;
            if (imgEl) imgCandidate = imgEl.getAttribute('src') || '';
          }

          // Tunggu sejenak agar app.js sempat menambah item, lalu patch image_url
          setTimeout(function(){ patchItemImage(id, imgCandidate); }, 60);
        }, true);
      })();
    </script>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const offcanvasEl = document.getElementById("mobileNav");
    const bsOffcanvas = new bootstrap.Offcanvas(offcanvasEl);

    // Ambil ikon hamburger di dalam offcanvas
    const toggleBtnInside = offcanvasEl.querySelector(".offcanvas-header .bi-list");

    // Saat ikon di header sidebar diklik → tutup offcanvas
    if (toggleBtnInside) {
      toggleBtnInside.style.cursor = "pointer";
      toggleBtnInside.addEventListener("click", () => {
        bsOffcanvas.hide();
      });
    }

    // Tutup offcanvas juga saat user klik salah satu link navigasi di dalamnya
    document.querySelectorAll(".js-offlink").forEach((link) => {
      link.addEventListener("click", () => bsOffcanvas.hide());
    });
  });
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const mobileLinks = document.querySelectorAll('#mobileNav .nav-link');

  mobileLinks.forEach(link => {
    link.addEventListener('click', function() {
      // Hapus active di semua link
      mobileLinks.forEach(l => l.classList.remove('active'));
      // Tambahkan active di yang diklik
      this.classList.add('active');
    });
  });
});
</script>
  </body>    
</html>