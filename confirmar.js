/* =============================================
   Ventana de confirmación propia (reemplaza al confirm() del navegador)
   Uso: <form data-confirm-texto="..." data-confirm-titulo="..." data-confirm-tipo="peligro|aviso|ok"
              data-confirm-boton="Sí, borrar" data-confirm-nota="...">
   ============================================= */
(function () {
  var modal, form, boton;
  // Estilos propios: la ventana se ve bien aunque el navegador tenga styles.css viejo en caché
  (function(){ if (document.getElementById('cf-estilos')) return; var st=document.createElement('style'); st.id='cf-estilos'; st.textContent='.cf-fondo { position:fixed; inset:0; z-index:1000; background:rgba(0,0,0,.65); backdrop-filter:blur(3px);   display:none; align-items:center; justify-content:center; padding:20px; } .cf-fondo.cf-abierto { display:flex; animation:cf-in .15s ease; } @keyframes cf-in { from { opacity:0; } to { opacity:1; } } .cf-caja { width:100%; max-width:400px; background:var(--card,#161616); border:1px solid var(--border,#2a2a2a); border-radius:20px;   padding:26px 22px 20px; text-align:center; box-shadow:0 30px 80px -20px rgba(0,0,0,.8); animation:cf-up .18s ease; } @keyframes cf-up { from { transform:translateY(12px) scale(.98); } to { transform:none; } } .cf-icono { width:60px; height:60px; margin:0 auto 14px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:28px; } .cf-aviso .cf-icono { background:rgba(250,204,21,.14); } .cf-peligro .cf-icono { background:rgba(248,113,113,.14); } .cf-ok .cf-icono { background:rgba(74,222,128,.14); } .cf-titulo { font-size:19px; font-weight:800; color:var(--text,#fff); margin-bottom:8px; } .cf-texto { font-size:14px; color:var(--text2,#a3a3a3); line-height:1.55; margin:0 0 14px; } .cf-nota { font-size:13px; font-weight:600; border-radius:12px; padding:10px 12px; margin-bottom:18px; } .cf-ok .cf-nota { background:rgba(74,222,128,.1); color:var(--green,#4ade80); border:1px solid rgba(74,222,128,.3); } .cf-aviso .cf-nota { background:rgba(250,204,21,.08); color:#facc15; border:1px solid rgba(250,204,21,.3); } .cf-peligro .cf-nota { background:rgba(248,113,113,.08); color:var(--red,#ef4444); border:1px solid rgba(248,113,113,.3); } .cf-botones { display:flex; gap:10px; } .cf-botones button { flex:1; padding:13px 10px; border-radius:12px; font-family:inherit; font-size:14px; font-weight:700; cursor:pointer; transition:.15s; } .cf-no { background:transparent; border:1px solid var(--border,#2a2a2a); color:var(--text,#fff); } .cf-no:hover { background:var(--bg3,#222); } .cf-si { border:none; color:#fff; background:var(--red,#ef4444); } .cf-si:hover { filter:brightness(1.1); } .cf-ok .cf-si { background:var(--blue,#3b82f6); } .cf-si:disabled { opacity:.6; cursor:default; } '; document.head.appendChild(st); })();


  function crear() {
    modal = document.createElement('div');
    modal.className = 'cf-fondo';
    modal.innerHTML =
      '<div class="cf-caja" role="dialog" aria-modal="true" aria-labelledby="cf-titulo">' +
        '<div class="cf-icono"></div>' +
        '<div class="cf-titulo" id="cf-titulo"></div>' +
        '<p class="cf-texto"></p>' +
        '<div class="cf-nota"></div>' +
        '<div class="cf-botones">' +
          '<button type="button" class="cf-no">Volver</button>' +
          '<button type="button" class="cf-si"></button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(modal);
    modal.addEventListener('click', function (e) { if (e.target === modal) cerrar(); });
    modal.querySelector('.cf-no').addEventListener('click', cerrar);
    modal.querySelector('.cf-si').addEventListener('click', aceptar);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal.classList.contains('cf-abierto')) cerrar(); });
  }

  function abrir(f, sub) {
    if (!modal) crear();
    form = f; boton = sub;
    var tipo = f.dataset.confirmTipo || 'aviso';
    var iconos = { peligro: '🗑️', aviso: '⚠️', ok: '✅' };
    modal.querySelector('.cf-caja').className = 'cf-caja cf-' + tipo;
    modal.querySelector('.cf-icono').textContent = iconos[tipo] || '⚠️';
    modal.querySelector('.cf-titulo').textContent = f.dataset.confirmTitulo || '¿Estás seguro?';
    modal.querySelector('.cf-texto').textContent = f.dataset.confirmTexto;
    var nota = modal.querySelector('.cf-nota');
    nota.textContent = f.dataset.confirmNota || '';
    nota.style.display = f.dataset.confirmNota ? 'block' : 'none';
    modal.querySelector('.cf-si').textContent = f.dataset.confirmBoton || 'Sí, confirmar';
    modal.classList.add('cf-abierto');
    setTimeout(function () { modal.querySelector('.cf-no').focus(); }, 30);
  }

  function cerrar() { modal.classList.remove('cf-abierto'); form = null; }

  function aceptar() {
    var f = form, b = boton;
    modal.querySelector('.cf-si').disabled = true;
    f.dataset.confirmado = '1';
    // Mandar también el nombre del botón que se tocó (ej: name="cancelar")
    if (b && b.name) {
      var h = document.createElement('input');
      h.type = 'hidden'; h.name = b.name; h.value = b.value || '1';
      f.appendChild(h);
    }
    f.submit();
  }

  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.dataset || !f.dataset.confirmTexto || f.dataset.confirmado) return;
    e.preventDefault();
    abrir(f, e.submitter);
  }, true);
})();
