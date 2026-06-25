    </main>
    <footer class="footer">
      &copy; <?= date('Y') ?> <?= APP_FULL ?> &middot; <?= CLINIC_NAME ?>
    </footer>
  </div>
</div>

<script>
var SIDEBAR_MQ = window.matchMedia('(max-width:768px)');
function isMobileSidebar(){ return SIDEBAR_MQ.matches; }
function setSidebarOpen(open){
  var l = document.getElementById('appLayout');
  var btn = document.getElementById('menuToggle');
  l.classList.toggle('sidebar-open', open);
  document.body.classList.toggle('sidebar-open', open);
  if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
}
function closeSidebar(){ if (isMobileSidebar()) setSidebarOpen(false); }
function toggleSidebar(){
  var l = document.getElementById('appLayout');
  if (isMobileSidebar()) { setSidebarOpen(!l.classList.contains('sidebar-open')); return; }
  l.classList.toggle('collapsed');
  localStorage.setItem('sidebar', l.classList.contains('collapsed') ? 'collapsed' : 'expanded');
  // Lebar konten berubah -> selaraskan ulang kolom DataTables (mode scrollX)
  if (window.__simAdjustTables) setTimeout(window.__simAdjustTables, 320);
}
// Buka/tutup sub-menu sidebar (dropdown)
function toggleNavGroup(btn){
  var g = btn.closest('.nav-group');
  // tutup grup lain biar rapi (accordion)
  document.querySelectorAll('.nav-group.open').forEach(function(o){ if(o!==g) o.classList.remove('open'); });
  g.classList.toggle('open');
}
// Dropdown menu akun (Profil / Logout) di topbar
function toggleUserMenu(e){
  e.stopPropagation();
  var d = document.getElementById('userDropdown');
  var open = d.classList.toggle('open');
  d.querySelector('.user-chip').setAttribute('aria-expanded', open ? 'true' : 'false');
}
document.addEventListener('click', function(e){
  var d = document.getElementById('userDropdown');
  if (d && d.classList.contains('open') && !d.contains(e.target)){
    d.classList.remove('open');
    d.querySelector('.user-chip').setAttribute('aria-expanded','false');
  }
});
document.addEventListener('keydown', function(e){
  if (e.key === 'Escape'){
    closeSidebar();
    var d = document.getElementById('userDropdown');
    if (d && d.classList.contains('open')){
      d.classList.remove('open');
      d.querySelector('.user-chip').setAttribute('aria-expanded','false');
    }
  }
});
SIDEBAR_MQ.addEventListener('change', function(e){ if (!e.matches) closeSidebar(); });
(function(){
  var nav = document.querySelector('.sidebar nav');
  if (!nav) return;
  nav.addEventListener('click', function(e){
    if (isMobileSidebar() && e.target.closest('a')) closeSidebar();
  });
  var foot = document.querySelector('.sidebar-foot .logout-link');
  if (foot) foot.addEventListener('click', function(){ if (isMobileSidebar()) closeSidebar(); });
})();
// Mode siang/malam (light/dark)
(function(){
  var btn = document.getElementById('themeToggle');
  if (btn) btn.addEventListener('click', function(){
    var cur = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', cur);
    localStorage.setItem('theme', cur);
  });
  // Layar penuh
  var fs = document.getElementById('fullscreenToggle');
  if (fs) fs.addEventListener('click', function(){
    if (!document.fullscreenElement) document.documentElement.requestFullscreen();
    else document.exitFullscreen();
  });
})();
</script>
<script src="<?= legacy_url('assets/vendor/jquery.min.js') ?>"></script>
<script src="<?= legacy_url('assets/vendor/datatables.min.js') ?>"></script>
<script>
$(function () {
  // Tambahkan kolom "No" otomatis ke setiap tabel .datatable yang belum punya
  $('table.datatable').each(function () {
    var $t = $(this);
    if ($t.hasClass('no-auto-num')) return; // tabel minta tanpa kolom No otomatis
    var $headRow = $t.find('thead tr').first();
    var firstHead = $.trim($headRow.children().first().text()).toLowerCase();
    if (firstHead === 'no') return; // sudah ada kolom No, lewati
    $headRow.prepend('<th class="no-sort col-no">No</th>');
    $t.find('tbody tr').each(function () {
      if ($(this).children('td').length) $(this).prepend('<td class="col-no"></td>');
    });
    // Geser juga baris footer (TOTAL dll) agar tetap sejajar dgn kolom No.
    // Lewati sel ber-colspan supaya tidak merusak baris yang sengaja merentang.
    $t.find('tfoot tr').each(function () {
      if ($(this).children().first().attr('colspan')) return;
      $(this).prepend('<td class="col-no"></td>');
    });
  });

  // Opsi dasar DataTables (dipakai semua tabel .datatable)
  var dtOptions = {
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100],
    pagingType: 'full_numbers',   // Alternative Pagination (Awal/angka/Akhir)
    order: [],                     // pertahankan urutan dari server
    columnDefs: [
      { orderable: false, targets: 'no-sort' },
      { searchable: false, targets: 'col-no' }   // kolom No tak ikut pencarian
    ],
    language: {
      search: 'Cari:',
      searchPlaceholder: 'ketik untuk mencari...',
      lengthMenu: 'Tampilkan _MENU_ data',
      info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
      infoEmpty: 'Tidak ada data',
      infoFiltered: '(disaring dari _MAX_ total data)',
      zeroRecords: 'Data tidak ditemukan',
      emptyTable: 'Belum ada data',
      paginate: { first: 'Awal', previous: 'Sebelumnya', next: 'Berikutnya', last: 'Akhir' }
    }
  };

  // Init per-tabel agar scrollX bisa dimatikan per tabel. Mode scrollX memisah
  // header & body menjadi dua tabel sehingga rawan melenceng (badge Status
  // terlihat geser ke kanan) terutama pada tabel pendek. Tabel ber-class
  // 'dt-noscroll' dirender sebagai satu tabel (header & isi selalu lurus) dan
  // mengandalkan kontainernya untuk scroll horizontal.
  var apis = [];
  $('table.datatable').each(function () {
    var $t = $(this);
    var api = $t.DataTable($.extend({}, dtOptions, { scrollX: !$t.hasClass('dt-noscroll') }));
    apis.push(api);

    // Isi nomor urut kolom "No" (1,2,3...) & perbarui saat sort/cari/ganti
    // halaman; nomor lanjut antar halaman (hal. 2 melanjutkan, bukan dari 1).
    if ($(api.table().header()).find('th.col-no').length) {
      api.on('draw.dt', function () {
        var start = api.page.info().start;
        api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
          cell.innerHTML = start + i + 1;
        });
      });
    }
    api.draw(false);
  });

  // Tabel scrollX: lebar kolom dihitung sekali saat init -> selaraskan ulang
  // saat lebar kontainer berubah (window resize / sidebar dilipat).
  function adjustTables(){ apis.forEach(function (a) { a.columns.adjust(); }); }
  window.__simAdjustTables = adjustTables;
  setTimeout(adjustTables, 60);
  $(window).on('resize', adjustTables);
});
// Search di topbar -> filter MENU di sidebar (bukan tabel)
(function(){
  var box = document.getElementById('menuSearch');
  if (!box) return;
  var nav = document.querySelector('.sidebar nav');
  box.addEventListener('keyup', function(){
    var q = this.value.trim().toLowerCase();
    nav.querySelectorAll('a').forEach(function(a){
      var hit = a.textContent.toLowerCase().indexOf(q) !== -1;
      a.style.display = hit ? '' : 'none';
    });
    // sembunyikan label grup saat sedang mencari
    nav.querySelectorAll('.label').forEach(function(l){ l.style.display = q ? 'none' : ''; });
  });
})();
</script>
</body>
</html>
