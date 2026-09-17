<!-- NAV MOBILE -->
<nav class="nav-mobile">
  <a href="acceso_alumno.php" class="nav-item <?= ($pagina_activa==='home') ? 'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
    Inicio
  </a>
  <a href="clases.php" class="nav-item <?= ($pagina_activa==='clases') ? 'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    Clases
  </a>
  <a href="<?= $qr_link ?>" class="nav-item <?= ($pagina_activa==='qr') ? 'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    <?= $es_admin ? 'Lector QR' : 'Mi QR' ?>
  </a>
  <a href="notificaciones.php" class="nav-item <?= ($pagina_activa==='notif') ? 'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
    Notif
  </a>
  <?php if ($es_admin): ?>
  <a href="acceso_administrador.php" class="nav-item <?= ($pagina_activa==='admin') ? 'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    Admin
  </a>
  <?php else: ?>
  <a href="perfil.php" class="nav-item <?= ($pagina_activa==='perfil') ? 'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    Perfil
  </a>
  <?php endif; ?>
</nav>
</div>
</div>
