<?php /* dashboard.php */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard | AgroSync</title>

  <!-- Your Tailwind build -->
  <link href="../../public/app.css" rel="stylesheet" />

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>

  <style>
    /* Sidebar transitions */
    .sidebar {
      width: 16rem;
      transition: width 0.25s ease-in-out;
    }
    .sidebar.collapsed { width: 5rem; }

    /* Hide labels when collapsed */
    .menu-label { transition: opacity 0.15s ease-in-out; }
    .sidebar.collapsed .menu-label { opacity: 0; width: 0; overflow: hidden; }
    .sidebar.collapsed .logo-text { display: none; }

    .chevron-icon { transition: transform 0.25s ease-in-out; }
    .rotate-180 { transform: rotate(180deg); }

    /* Keep button behavior sane; let utilities control visuals */
    .menu-item {
      background-color: transparent;
      border: none;
      width: 100%;
      text-align: left;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.75rem; /* equals Tailwind gap-3 */
      padding: 0.75rem 1rem; /* equals Tailwind px-4 py-3 */
      border-radius: 0.5rem; /* equals Tailwind rounded */
      color: rgb(55 65 81); /* equals text-gray-700 */
    }
    /* Safety net in case Tailwind isn’t applied for some reason */
    .menu-item:hover { background-color: rgb(243 244 246); } /* gray-100 */
    a{
       background-color: transparent;
      border: none;
      width: 100%;
      text-align: left;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.75rem; /* equals Tailwind gap-3 */
      padding: 0.75rem 1rem; /* equals Tailwind px-4 py-3 */
      border-radius: 0.5rem; /* equals Tailwind rounded */
      color: rgb(55 65 81); /* equals text-gray-700 */
      margin-top: 135px;
    }
    a.active{
      background-color: rgb(243 244 246);
      
    }

    /* Active state (Dashboard gray when on dashboard page) */
    .menu-item.active {
      background-color: rgb(107 114 128); /* gray-500 */
      color: white;
    }
    .menu-item.active i { color: inherit; }
  </style>
</head>

<body class="min-h-screen bg-gray-50 text-gray-800">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar bg-white border-r border-gray-200 flex flex-col">
      <!-- Header -->
      <div class="p-4 flex items-center justify-between border-b border-gray-200">
        <div class="flex items-center overflow-hidden">
          <span class="logo-text font-bold text-xl text-gray-800 whitespace-nowrap">AgroSync</span>
        </div>
        <button id="toggleBtn"
                class="flex-shrink-0 p-2 hover:bg-gray-100 rounded"
                aria-label="Toggle sidebar"
                aria-expanded="true"
                aria-controls="sidebar">
          <i class="bi bi-list text-2xl text-gray-700"></i>
        </button>
      </div>

      <!-- Menu -->
      <nav class="flex-1 py-3 overflow-y-auto" id="menuList">
        <button class="menu-item hover:bg-gray-100"
                data-page="dashboard"
                onclick="navigateTo(this, '../dashboard/index.php')">
          <i class="bi bi-house text-lg"></i>
          <span class="menu-label">Dashboard</span>
        </button>

        <button class="menu-item hover:bg-gray-100"
                data-page="calendar"
                onclick="navigateTo(this, '../calendar/index.php')">
          <i class="bi bi-calendar text-lg"></i>
          <span class="menu-label">Calendar</span>
        </button>

        <button class="menu-item hover:bg-gray-100"
                data-page="aira"
                onclick="navigateTo(this, '../aira/index.php')">
          <i class="bi bi-chat-left-text text-lg"></i>
          <span class="menu-label">Aira.ai</span>
        </button>

        <button class="menu-item hover:bg-gray-100"
                data-page="farm"
                onclick="navigateTo(this, '../farm/index.php')">
          <i class="bi bi-grid-3x3-gap text-lg"></i>
          <span class="menu-label">Farm</span>
        </button>

        <button class="menu-item hover:bg-gray-100"
                data-page="sell"
                onclick="navigateTo(this, '../sells/index.php')">
          <i class="bi bi-bag text-lg"></i>
          <span class="menu-label">Sell</span>
        </button>

        <button class="menu-item hover:bg-gray-100"
                data-page="stock"
                onclick="navigateTo(this, '../stock/index.php')">
          <i class="bi bi-box-seam text-lg"></i>
          <span class="menu-label">Stock</span>
        </button>

        <a class=" hover:bg-red-100"
                onclick="navigateTo(this, '/index.php')">
          <i class="bi bi-box-arrow-right text-lg text-red-600" ></i>
          <span class="menu-label text-red-600">Sign Out</span>
        </a>
      </nav>

      <!-- Footer -->
      <div class="border-t border-gray-200 p-3">
        <button class="w-full flex items-center gap-3 px-3 py-2 text-gray-600 hover:bg-gray-50 rounded"
                id="collapseBtnAlt" aria-label="Collapse sidebar">
          <i class="bi bi-chevron-left text-base chevron-icon"></i>
        </button>
      </div>
    </aside>
   
  </div>

  <!-- Working JS (NOT commented out) -->
  <script>
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleBtn');
    const collapseAlt = document.getElementById('collapseBtnAlt');
    const menuItems = Array.from(document.querySelectorAll('.menu-item'));

    // Sidebar collapse/expand
    function toggleSidebar() {
      sidebar.classList.toggle('collapsed');
      document.querySelectorAll('.chevron-icon').forEach(i => i.classList.toggle('rotate-180'));
      toggleBtn.setAttribute('aria-expanded', !sidebar.classList.contains('collapsed'));
    }
    toggleBtn.addEventListener('click', toggleSidebar);
    collapseAlt.addEventListener('click', toggleSidebar);

    // Navigate + mark active
    function navigateTo(button, link) {
      const page = button.getAttribute('data-page');
      try { localStorage.setItem('agrosync_active_page', page); } catch (e) {}
      setActive(page);
      window.location.href = link;
    }
    window.navigateTo = navigateTo; // expose for inline onclick

    // Infer active page from URL if storage missing
    function inferPageFromPath() {
      const path = window.location.pathname.toLowerCase();
      if (path.includes('/dashboard/')) return 'dashboard';
      if (path.includes('/calendar/')) return 'calendar';
      if (path.includes('/aira/'))     return 'aira';
      if (path.includes('/farm/'))     return 'farm';
      if (path.includes('/sells/'))    return 'sell';
      if (path.includes('/stock/'))    return 'stock';
      return null;
    }

    function setActive(page) {
      menuItems.forEach(btn => {
        const isActive = btn.getAttribute('data-page') === page;
        btn.classList.toggle('active', isActive);
        if (isActive) {
          btn.setAttribute('aria-current', 'page');
        } else {
          btn.removeAttribute('aria-current');
        }
      });
    }

    // Initialize active state on load
    (function initActive() {
      let page = null;
      try { page = localStorage.getItem('agrosync_active_page'); } catch (e) {}
      if (!page) page = inferPageFromPath();
      if (page) setActive(page);
    })();
  </script>
</body>
</html>
