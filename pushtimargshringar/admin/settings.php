<?php
// admin/settings.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';

// Mapping $conn → $con
$con = $conn;

$title = "Settings";
$pageTitle = "Settings";
$active = "settings";

include __DIR__ . "/includes/head.php";
include __DIR__ . "/includes/start_layout.php";
?>

<section class="px-4 sm:px-6 lg:px-8 py-6 space-y-8">

  <!-- Account -->
  <div class="grid md:grid-cols-3 gap-6">
    <div class="card md:col-span-2">
      <h2 class="font-['Playfair_Display'] text-[var(--pm-maroon)] mb-4">Account</h2>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm block">Full Name</label>
          <input type="text" class="input" value="Admin">
        </div>
        <div>
          <label class="text-sm block">Username</label>
          <input type="text" class="input" value="admin">
        </div>
        <div>
          <label class="text-sm block">Email</label>
          <input type="email" class="input" value="admin@example.com">
        </div>
        <div>
          <label class="text-sm block">Phone</label>
          <input type="text" class="input" value="+91">
        </div>
      </div>
      <div class="mt-4 flex gap-3">
        <button class="btn btn-primary">Save Account</button>
        <button class="btn">Reset</button>
      </div>
    </div>

    <div class="card">
      <h2 class="font-['Playfair_Display'] text-[var(--pm-maroon)] mb-4">Security</h2>
      <div class="space-y-3">
        <div>
          <label class="text-sm block">Current Password</label>
          <input type="password" class="input" placeholder="••••••••">
        </div>
        <div>
          <label class="text-sm block">New Password</label>
          <input type="password" class="input" placeholder="••••••••">
        </div>
        <div>
          <label class="text-sm block">Confirm Password</label>
          <input type="password" class="input" placeholder="••••••••">
        </div>
        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox"> Enable 2-Factor Authentication
        </label>
        <button class="btn btn-danger w-full">Update Password</button>
      </div>
    </div>
  </div>

  <!-- Branding -->
  <div class="grid md:grid-cols-3 gap-6">
    <div class="card md:col-span-2">
      <h2 class="font-['Playfair_Display'] text-[var(--pm-maroon)] mb-4">Branding</h2>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm block">Store Name</label>
          <input type="text" class="input" value="Pushtimarg Shringar">
        </div>
        <div>
          <label class="text-sm block">Support Email</label>
          <input type="email" class="input" value="support@example.com">
        </div>
        <div class="sm:col-span-2">
          <label class="text-sm block">Support Message</label>
          <input type="text" class="input" value="We respond within 24 hours.">
        </div>
      </div>
      <div class="mt-4 flex gap-3">
        <button class="btn btn-primary">Save Branding</button>
        <button class="btn">Reset</button>
      </div>
    </div>

    <div class="card">
      <h2 class="font-['Playfair_Display'] text-[var(--pm-maroon)] mb-4">Notifications</h2>
      <label class="flex items-center gap-2 text-sm mb-2">
        <input type="checkbox" checked> New Order Email
      </label>
      <label class="flex items-center gap-2 text-sm mb-2">
        <input type="checkbox" checked> Low Stock Alert
      </label>
      <label class="flex items-center gap-2 text-sm mb-4">
        <input type="checkbox"> Weekly Report Email
      </label>
      <div class="flex gap-3">
        <button class="btn btn-primary">Save</button>
        <button class="btn">Send Test Email</button>
      </div>
    </div>
  </div>

  <!-- Danger Zone -->
  <div class="grid md:grid-cols-2 gap-6">
    <div class="card">
      <h2 class="font-['Playfair_Display'] text-[var(--pm-maroon)] mb-4">Danger Zone</h2>
      <p class="text-sm text-gray-600">Reset Settings — Saare settings default par lautenge.</p>
      <button class="btn btn-danger mt-3">Reset</button>
    </div>
    <div class="card">
      <h2 class="font-['Playfair_Display'] text-[var(--pm-maroon)] mb-4">Clear Admin Session</h2>
      <p class="text-sm text-gray-600">Demo me localStorage based session clear karega.</p>
      <button class="btn btn-danger mt-3">Clear</button>
    </div>
  </div>

  <!-- Backup -->
  <div class="card flex justify-between items-center">
    <h2 class="font-['Playfair_Display'] text-[var(--pm-maroon)]">Backup</h2>
    <div class="flex gap-3">
      <button class="btn">Export JSON</button>
      <button class="btn btn-primary">Import</button>
    </div>
  </div>
</section>

<?php include __DIR__ . "/includes/end_layout.php"; ?>
