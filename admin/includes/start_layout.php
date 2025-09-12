<?php
// admin/includes/start_layout.php
// expected to be included AFTER head.php
?>
<div class="flex min-h-screen">

  <!-- Sidebar -->
  <aside class="admin-sidebar w-64 p-6 hidden lg:block">
    <div class="logo mb-6">
      <div class="w-10 h-10 rounded-full bg-yellow-400 flex items-center justify-center text-2xl font-bold text-red-900">PS</div>
      <div>
        <div class="text-white font-semibold">Admin</div>
        <div class="text-yellow-100 text-sm">Pushtimarg Shringar</div>
      </div>
    </div>

    <nav class="space-y-1 mt-4">
      <a href="dashboard.php" class="nav-link active"><i class="fa fa-tachometer-alt w-4"></i> Dashboard</a>
      <a href="products.php" class="nav-link"><i class="fa fa-box w-4"></i> Products</a>
      <a href="orders.php" class="nav-link"><i class="fa fa-shopping-cart w-4"></i> Orders</a>
      <a href="users.php" class="nav-link"><i class="fa fa-users w-4"></i> Users</a>
      <a href="categories.php" class="nav-link"><i class="fa fa-tags w-4"></i> Categories</a>
      <a href="reports.php" class="nav-link"><i class="fa fa-chart-line w-4"></i> Reports</a>
      <a href="settings.php" class="nav-link"><i class="fa fa-cog w-4"></i> Settings</a>
      <a href="logout.php" class="nav-link"><i class="fa fa-sign-out-alt w-4"></i> Logout</a>
    </nav>
  </aside>

  <!-- Content area -->
  <div class="flex-1 min-h-screen">

    <!-- Topbar -->
    <header class="admin-top p-4 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <h1 class="text-2xl card-title">Dashboard</h1>
        <div class="small-muted">Overview & analytics</div>
      </div>

      <div class="flex items-center gap-3">
        <button class="btn-ghost"><i class="fa fa-plus mr-2"></i> Add Product</button>
        <button class="btn-ghost">View Orders</button>
        <div class="px-3 py-2 rounded bg-white text-sm shadow">Hello, <strong>Admin</strong></div>
      </div>
    </header>

    <main class="p-6">
