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
    :root{
      --brand-red: #7e0707;        /* deep red sidebar */
      --brand-cream: #fbf6f0;      /* main background */
      --panel-bg: #fff6f0;         /* panel light */
      --accent-gold: #c78b2b;      /* gold accent */
      --muted: #6b6b6b;
    }

    body {
      font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: var(--brand-cream);
      color: #222;
    }

    /* Sidebar */
    .admin-sidebar {
      background: var(--brand-red);
      color: white;
      min-height: 100vh;
    }
    .admin-sidebar .logo {
      display: flex;
      align-items:center;
      gap:.75rem;
    }
    .admin-sidebar .nav-link {
      color: rgba(255,255,255,0.95);
      display:flex;
      gap:.8rem;
      align-items:center;
      padding:.7rem 1rem;
      border-radius: .5rem;
    }
    .admin-sidebar .nav-link:hover { background: rgba(255,255,255,0.06); }
    .admin-sidebar .nav-link.active { background: rgba(0,0,0,0.12); font-weight:600; }

    /* Topbar */
    header.admin-top {
      background: transparent;
      border-bottom: 1px solid rgba(0,0,0,0.04);
    }

    /* Cards */
    .stat-card { background: #fff; border-radius: 10px; padding: 1rem 1.25rem; box-shadow: 0 1px 0 rgba(0,0,0,0.03); }
    .panel { background: white; border-radius: 8px; padding: 1rem; box-shadow: 0 1px 0 rgba(0,0,0,0.03); }

    /* Buttons */
    .btn-ghost { background: transparent; border:1px solid rgba(0,0,0,0.06); padding:.5rem .85rem; border-radius: .6rem; }
    .btn-primary { background: var(--accent-gold); color: white; padding:.5rem .85rem; border-radius:.6rem; }

    /* small tweaks */
    .small-muted { color: var(--muted); font-size:.92rem; }
    .card-title { font-family: "Merriweather", serif; color: var(--brand-red); font-weight:700; }

    /* responsive adjustments */
    @media (max-width: 1024px) {
      .admin-sidebar { display:none; }
    }
  </style>
</head>
<body>
