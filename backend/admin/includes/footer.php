    </div>
  </div>
</div>
<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
    document.body.classList.toggle('sidebar-locked');
  }
  function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
    document.body.classList.remove('sidebar-locked');
  }
  // Funga menyu kiotomatiki mtumiaji akibofya kiungo (kwenye simu)
  document.querySelectorAll('.sidebar nav a').forEach(function (link) {
    link.addEventListener('click', closeSidebar);
  });

  // ---- Kumbuka mahali sidebar ilipokuwa imesogezwa (scroll) ----
  // Bila hii, kila ukurasa mpya unapopakia, sidebar hurudi juu kabisa
  // hata kama kiungo ulichobofya kilikuwa chini. Sasa inabaki pale pale.
  (function () {
    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    var savedScroll = sessionStorage.getItem('fs_sidebar_scroll');
    if (savedScroll !== null) {
      sidebar.scrollTop = parseInt(savedScroll, 10) || 0;
    }

    sidebar.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        sessionStorage.setItem('fs_sidebar_scroll', sidebar.scrollTop);
      });
    });

    // Pia hifadhi scroll ya ukurasa mzima (content), isipofungwa kiungo cha sidebar
    var savedPageScroll = sessionStorage.getItem('fs_page_scroll_' + window.location.pathname);
    if (savedPageScroll !== null) {
      window.scrollTo(0, parseInt(savedPageScroll, 10) || 0);
    }
    window.addEventListener('beforeunload', function () {
      sessionStorage.setItem('fs_page_scroll_' + window.location.pathname, window.scrollY);
    });
  })();
</script>
</body>
</html>
