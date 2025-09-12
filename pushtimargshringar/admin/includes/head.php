<?php
// admin/includes/head.php
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin — Pushtimarg Shringar</title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Chart.js for charts -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Custom admin theme CSS -->
  <style>
    /*
      Theme variables — update here to tweak site palette
    */
    :root{
      --pm-maroon:  #8b1717;   /* deep maroon for sidebar & headings */
      --pm-gold:    #c28b2b;   /* gold accent for primary actions */
      --pm-cream:   #fbefe6;   /* page background */
      --pm-border:  #ecd9cc;   /* borders, card outlines */
      --pm-dark:    #2b2b2b;   /* primary text */
      --pm-muted:   #7b6b66;   /* muted text / captions */
      --pm-white:   #ffffff;
      --radius-md:  10px;
      --radius-sm:  6px;
      --shadow-sm:  0 1px 0 rgba(0,0,0,0.03);
    }

    /* Base */
    * { box-sizing: border-box; }
    html,body { height:100%; }
    body {
      font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: var(--pm-cream);
      color: var(--pm-dark);
      margin: 0;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }

    /* Sidebar (left) */
    .admin-sidebar {
      background: var(--pm-maroon);
      color: var(--pm-white);
      min-height: 100vh;
      padding: 2rem 1rem;
    }
    .admin-sidebar .logo { display:flex; align-items:center; gap:.75rem; margin-bottom:1rem; }
    .admin-sidebar .logo .circle { width:44px; height:44px; border-radius:50%; background: rgba(255,255,255,0.08); display:flex; align-items:center; justify-content:center; font-weight:700; color:var(--pm-white); }
    .admin-sidebar .site-title { font-family: "Merriweather", serif; color: #fff; font-weight:700; font-size:1.05rem; }
    .admin-sidebar .nav-link {
      color: rgba(255,255,255,0.95);
      display:flex; gap:.8rem; align-items:center;
      padding:.7rem .9rem; border-radius: .6rem; margin-bottom:.25rem;
      text-decoration:none;
    }
    .admin-sidebar .nav-link:hover { background: rgba(255,255,255,0.06); }
    .admin-sidebar .nav-link.active { background: rgba(0,0,0,0.12); font-weight:600; }

    /* Top bar */
    header.admin-top {
      background: transparent;
      border-bottom: 1px solid rgba(0,0,0,0.04);
      padding: .75rem 1.25rem;
    }

    /* Card / Panel */
    .card, .panel {
      background: var(--pm-white);
      border-radius: var(--radius-md);
      padding: 1.25rem;
      box-shadow: var(--shadow-sm);
      border: 1px solid var(--pm-border);
    }
    .stat-card { padding:1rem; border-radius:8px; background:var(--pm-white); border:1px solid var(--pm-border); box-shadow: var(--shadow-sm); }

    .card-title { font-family: "Merriweather", serif; color: var(--pm-maroon); font-weight:700; font-size:1.125rem; margin-bottom:.5rem; }

    /* Buttons */
    .btn {
      display:inline-block;
      background: var(--pm-white);
      color: var(--pm-dark);
      border:1px solid var(--pm-border);
      padding:.55rem .9rem;
      border-radius: .6rem;
      cursor:pointer;
      font-weight:600;
    }
    .btn:hover { filter:brightness(.98); }

    .btn-primary {
      background: var(--pm-gold);
      color: var(--pm-dark);
      border:1px solid rgba(0,0,0,0.06);
    }
    .btn-primary:hover { filter:brightness(.95); }

    .btn-ghost {
      background: transparent;
      border:1px solid rgba(0,0,0,0.06);
      padding:.45rem .75rem;
      border-radius:.6rem;
    }

    .btn-danger {
      background: var(--pm-maroon);
      color: #fff;
      border: 1px solid rgba(0,0,0,0.06);
    }
    .btn-danger:hover { filter:brightness(.9); }

    /* Form controls */
    .input, input[type="text"], input[type="email"], input[type="password"], select, textarea {
      width:100%;
      padding:.6rem .75rem;
      border-radius: .6rem;
      border:1px solid var(--pm-border);
      background: var(--pm-white);
      color: var(--pm-dark);
      font-size: .95rem;
    }
    label { display:block; margin-bottom:.35rem; color:var(--pm-muted); font-size:.9rem; }

    .field-row { display:flex; gap:1rem; }
    .field-row > * { flex:1; }

    /* Small helpers */
    .small-muted { color: var(--pm-muted); font-size:.92rem; }
    .text-maroon { color: var(--pm-maroon); }
    .text-muted { color: var(--pm-muted); }
    .logo-preview {
      width:72px; height:72px; border-radius:8px; display:flex; align-items:center; justify-content:center;
      background:var(--pm-white); border:1px dashed var(--pm-border); overflow:hidden;
    }
    .logo-preview img { width:100%; height:100%; object-fit:contain; display:block; }

    /* Table */
    table { width:100%; border-collapse:collapse; }
    .table thead th { text-align:left; padding:.75rem 0; color:var(--pm-muted); border-bottom:1px solid var(--pm-border); }
    .table tbody td { padding:.75rem 0; border-bottom:1px solid #f3e8e1; color:var(--pm-dark); }

    /* Charts container */
    .chart-card { min-height: 240px; display:flex; align-items:center; justify-content:center; }

    /* Responsive */
    @media (max-width: 1024px) {
      .admin-sidebar { display:none; }
      .field-row { flex-direction:column; }
    }

    /* Minor theme niceties */
    .rounded-sm { border-radius: var(--radius-sm); }
    .muted-box { background: #fff; border-radius:8px; padding:.75rem; border:1px solid var(--pm-border); color:var(--pm-muted); }

  </style>
</head>
<body>
