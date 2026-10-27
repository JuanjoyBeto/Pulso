<?php
$inicial = inicial_usuario_actual();
$nombre_completo = nombre_usuario_actual();
$es_admin = (rol_actual() === 'administrador');
$qr_link = $es_admin ? 'admin_escanear_qr.php' : 'qr.php';
?>
<div class="app-layout">
<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="<?= gym_logo() ?>" alt="<?= gym_nombre() ?>" style="height:36px;width:auto;object-fit:contain;display:block">
  </div>
  <a href="acceso_alumno.php" class="sidebar-item <?= ($pagina_activa==='home') ? 'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Inicio
  </a>
  <a href="clases.php" class="sidebar-item <?= ($pagina_activa==='clases') ? 'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Clases
  </a>
  <a href="<?= $qr_link ?>" class="sidebar-item <?= ($pagina_activa==='qr') ? 'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3M18 14v.01M14 18h.01M18 18h.01"/></svg>
    <?= $es_admin ? 'Lector QR' : 'Mi QR' ?>
  </a>
  <a href="notificaciones.php" class="sidebar-item <?= ($pagina_activa==='notif') ? 'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg> Notificaciones
  </a>
  <a href="perfil.php" class="sidebar-item <?= ($pagina_activa==='perfil') ? 'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Mi Perfil
  </a>
  <?php if ($es_admin): ?>
  <div class="sidebar-footer">
    <a href="acceso_administrador.php" class="sidebar-item <?= ($pagina_activa==='admin') ? 'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Panel Admin
    </a>
    <a href="admin_pagos.php" class="sidebar-item <?= ($pagina_activa==='pagos') ? 'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg> Pagos
    </a>
    <a href="admin_aptos.php" class="sidebar-item <?= ($pagina_activa==='aptos') ? 'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 2h6v4H9z"/><rect x="4" y="4" width="16" height="18" rx="2"/><path d="M12 11v6M9 14h6"/></svg> Aptos
    </a>
    <a href="admin_configuracion.php" class="sidebar-item <?= ($pagina_activa==='config') ? 'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg> Configuración
    </a>
  </div>
  <?php endif; ?>
</aside>

<div class="main-content">
<header class="app-header">
  <img src="<?= gym_logo() ?>" alt="<?= gym_nombre() ?>" style="height:28px;width:auto;object-fit:contain">
  <a href="perfil.php" class="avatar-sm"><?= htmlspecialchars($inicial) ?></a>
</header>
