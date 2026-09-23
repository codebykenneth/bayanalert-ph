<?php
$bp = base_path();
$user = current_user();
$db = getDB();
$unread = $user ? unread_notification_count($db, $user['id']) : 0;
$role = $user['role'] ?? null;
?>
<nav class="navbar navbar-expand-lg navbar-dark bayan-navbar sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="<?= e($bp) ?><?= $user ? e(role_dashboard_path($role)) : 'index.php' ?>">
      <i class="fa-solid fa-triangle-exclamation me-1"></i> BayanAlert <span class="text-warning">PH</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <?php if ($role === 'citizen'): ?>
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>citizen/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>citizen/map.php"><i class="fa-solid fa-map-location-dot"></i> Map</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>citizen/alerts.php"><i class="fa-solid fa-bell"></i> Alerts</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>citizen/reports.php"><i class="fa-solid fa-file-lines"></i> My Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>citizen/evacuation.php"><i class="fa-solid fa-house-chimney"></i> Evacuation</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>citizen/facilities.php"><i class="fa-solid fa-hospital"></i> Facilities</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>citizen/prepare.php"><i class="fa-solid fa-book-open"></i> Learn &amp; Prepare</a></li>
        <li class="nav-item"><a class="nav-link text-warning fw-bold" href="<?= e($bp) ?>citizen/sos.php"><i class="fa-solid fa-triangle-exclamation"></i> SOS</a></li>
      </ul>
      <?php elseif ($role === 'admin'): ?>
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>admin/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>admin/reports.php"><i class="fa-solid fa-file-lines"></i> Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>admin/alerts.php"><i class="fa-solid fa-bell"></i> Alerts</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>admin/evacuation.php"><i class="fa-solid fa-house-chimney"></i> Evacuation</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>admin/facilities.php"><i class="fa-solid fa-hospital"></i> Facilities</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>admin/announcements.php"><i class="fa-solid fa-bullhorn"></i> Announcements</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>admin/users.php"><i class="fa-solid fa-users"></i> Users</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>admin/settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
      </ul>
      <?php elseif ($role === 'responder'): ?>
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>responder/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>responder/reports.php"><i class="fa-solid fa-file-lines"></i> Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>responder/incidents.php"><i class="fa-solid fa-siren"></i> Incidents</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>responder/evacuation.php"><i class="fa-solid fa-house-chimney"></i> Evacuation</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>admin/announcements.php"><i class="fa-solid fa-bullhorn"></i> Announcements</a></li>
      </ul>
      <?php endif; ?>

      <?php if ($user): ?>
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <?php if ($role === 'citizen'): ?>
        <li class="nav-item me-2">
          <a class="nav-link position-relative" href="<?= e($bp) ?>citizen/notifications.php">
            <i class="fa-solid fa-bell"></i>
            <?php if ($unread > 0): ?><span class="badge rounded-pill bg-danger notif-badge"><?= (int)$unread ?></span><?php endif; ?>
          </a>
        </li>
        <?php endif; ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <i class="fa-solid fa-circle-user"></i> <?= e($user['name']) ?>
            <span class="badge bg-light text-dark text-uppercase small ms-1"><?= e($role) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <?php if ($role === 'citizen'): ?>
            <li><a class="dropdown-item" href="<?= e($bp) ?>citizen/profile.php"><i class="fa-solid fa-user-pen"></i> Profile</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= e($bp) ?>logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
          </ul>
        </li>
      </ul>
      <?php else: ?>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="<?= e($bp) ?>login.php">Login</a></li>
        <li class="nav-item"><a class="btn btn-warning btn-sm ms-lg-2 px-3 fw-bold" href="<?= e($bp) ?>register.php">Register</a></li>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</nav>
