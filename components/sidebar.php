<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard | AgroSync</title>

  <!-- Tailwind build -->
  <link href="../../public/app.css" rel="stylesheet" />

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>

  <style>
  /* Sidebar transitions */
  .sidebar {
    width: 13rem; /* smaller than before */
    transition: width 0.25s ease-in-out;
  }
  .sidebar.collapsed {
    width: 5rem;
  }

  /* Hide text when collapsed */
  .menu-label {
    transition: opacity 0.15s ease-in-out;
    font-size: 0.9rem;
  }
  .sidebar.collapsed .menu-label {
    opacity: 0;
    width: 0;
    overflow: hidden;
  }
  .sidebar.collapsed .logo-text {
    display: none;
  }

  /* Icon centering when collapsed */
  .sidebar.collapsed .menu-item {
    justify-content: center;
  }

  /* Button look */
  .menu-item {
    background-color: transparent;
    border: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.65rem 0.9rem;
    border-radius: 0.5rem;
    color: rgb(55 65 81); /* gray-700 */
    transition: background-color 0.2s ease, color 0.2s ease;
    font-size: 0.9rem;
  }

  .menu-item i {
    font-size: 1.1rem; /* slightly smaller icons */
  }

  .menu-item:hover {
    background-color: rgb(243 244 246); /* gray-100 */
  }

  /* Active item: blue background & white icon/text */
  .menu-item.active {
    background-color: #3b82f6; /* Tailwind blue-500 */
    color: white;
  }

  .menu-item.active i {
    color: white;
  }

  /* Footer link (Sign Out) */
  .footer-link {
    background-color: transparent;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    padding: 0.45rem 0.75rem;
    border-radius: 0.5rem;
    color: rgb(220 38 38); /* red-600 */
    text-decoration: none;
    transition: background-color 0.2s ease;
    font-size: 0.9rem;
  }

  .footer-link i {
    font-size: 1.1rem;
  }

  .footer-link:hover {
    background-color: rgb(254 226 226); /* red-100 */
  }

  /* When expanded, align label and icon */
  .sidebar:not(.collapsed) .footer-link {
    justify-content: flex-start;
  }
</style>

</head>

<body class="min-h-screen bg-gray-50 text-gray-800">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar bg-white border-r border-gray-200 flex flex-col">
      <!-- Header -->
      <div class="p-4 flex items-center justify-between border-b border-gray-200">
        <div class="flex items-center overflow-hidden">
          <span class="logo-text font-bold text-lg text-gray-800 whitespace-nowrap">AgroSync</span>
        </div>
        <button id="toggleBtn"
                class="flex-shrink-0 p-2 hover:bg-gray-100 rounded"
                aria-label="Toggle sidebar"
                aria-expanded="true"
                aria-controls="sidebar">
          <i class="bi bi-list text-xl text-gray-700"></i>
        </button>
      </div>

      <!-- Menu -->
      <nav class="flex-1 py-3 overflow-y-auto" id="menuList">
        <button class="menu-item hover:bg-gray-100"
                data-page="dashboard"
                onclick="navigateTo(this, '../dashboard/index.php')">
          <i class="bi bi-house"></i>
          <span class="menu-label">Dashboard</span>
        </button>

        <button class="menu-item hover:bg-gray-100"
                data-page="calendar"
                onclick="navigateTo(this, '../calendar/index.php')">
          <i class="bi bi-calendar"></i>
          <span class="menu-label">Calendar</span>
        </button>

        <button class="menu-item hover:bg-gray-100"
                data-page="aira"
                onclick="navigateTo(this, '../aira/index.php')">
          <i class="bi bi-chat-left-text"></i>
          <span class="menu-label">Aira.ai</span>
        </button>

        <button class="menu-item hover:bg-gray-100"
                data-page="farm"
                onclick="navigateTo(this, '../farm/index.php')">
          <i class="bi bi-grid-3x3-gap"></i>
          <span class="menu-label">Farm</span>
        </button>

        <button class="menu-item hover:bg-gray-100"
                data-page="sell"
                onclick="navigateTo(this, '../sells/index.php')">
          <i class="bi bi-bag"></i>
          <span class="menu-label">Sell</span>
        </button>

        <button class="menu-item hover:bg-gray-100"
                data-page="stock"
                onclick="navigateTo(this, '../stock/index.php')">
          <i class="bi bi-box-seam"></i>
          <span class="menu-label">Stock</span>
        </button>
      </nav>

      <!-- Footer -->
      <div class="border-t border-gray-200 p-3">
        <a class="footer-link" href="../../auth/logout.php" rel="nofollow">
          <i class="bi bi-box-arrow-right" style="color:#dc2626"></i>
          <span class="menu-label" style="color:#dc2626">Sign Out</span>
        </a>
      </div>
    </aside>
  </div>

  <!-- JS -->
  <script>
    const sidebar   = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleBtn');
    const menuItems = Array.from(document.querySelectorAll('.menu-item'));

    function toggleSidebar() {
      if (!sidebar) return;
      sidebar.classList.toggle('collapsed');
      if (toggleBtn) {
        toggleBtn.setAttribute('aria-expanded', String(!sidebar.classList.contains('collapsed')));
      }
    }
    if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);

    function navigateTo(button, link) {
      const page = button?.getAttribute('data-page');
      try { if (page) localStorage.setItem('agrosync_active_page', page); } catch (e) {}
      if (page) setActive(page);
      window.location.href = link;
    }
    window.navigateTo = navigateTo;

    function inferPageFromPath() {
      const path = window.location.pathname.toLowerCase();
      if (path.includes('/dashboard/')) return 'dashboard';
      if (path.includes('/calendar/'))  return 'calendar';
      if (path.includes('/aira/'))      return 'aira';
      if (path.includes('/farm/'))      return 'farm';
      if (path.includes('/sells/'))     return 'sell';
      if (path.includes('/stock/'))     return 'stock';
      return null;
    }

    function setActive(page) {
      menuItems.forEach(btn => {
        const isActive = btn.getAttribute('data-page') === page;
        btn.classList.toggle('active', isActive);
        if (isActive) btn.setAttribute('aria-current', 'page');
        else btn.removeAttribute('aria-current');
      });
    }

    (function initActive() {
      let page = null;
      try { page = localStorage.getItem('agrosync_active_page'); } catch (e) {}
      if (!page) page = inferPageFromPath();
      if (page) setActive(page);
    })();
  </script>
</body>
</html>
