<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $title ?? config('app.name') }}</title>
        
        <!-- Bootstrap 5 CSS CDN -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons CDN -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

        
        @livewireStyles
        
        <style>
            * {
                font-family: 'Inter', sans-serif;
            }
            
            body {
                background: #f0f2f5;
                overflow-x: hidden;
            }
            
            /* Fixed Sidebar Styles */
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                width: 280px;
                background: linear-gradient(135deg, #1e2a3a 0%, #0f172a 100%);
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
                transition: transform 0.3s ease;
                overflow-y: auto;
                overflow-x: hidden;
                z-index: 1000;
                transform: translateX(0);
            }
            
            /* Sidebar closed state for mobile */
            .sidebar.closed {
                transform: translateX(-100%);
            }
            
            /* Main Content Wrapper */
            .main-content {
                margin-left: 280px;
                min-height: 100vh;
                width: calc(100% - 280px);
                transition: margin-left 0.3s ease, width 0.3s ease;
            }
            
            /* Main content when sidebar is closed */
            .main-content.expanded {
                margin-left: 0;
                width: 100%;
            }
            
            /* Sidebar Links */
            .sidebar .nav-link,
            .sidebar .sidebar-link {
                color: #cbd5e1;
                padding: 10px 16px;
                margin: 3px 12px;
                border-radius: 10px;
                transition: all 0.3s ease;
                font-weight: 500;
                font-size: 0.85rem;
                display: flex;
                align-items: center;
                cursor: pointer;
                text-decoration: none;
            }
            
            .sidebar .nav-link:hover,
            .sidebar .sidebar-link:hover {
                background: rgba(255,255,255,0.1);
                color: white;
                transform: translateX(5px);
            }
            
            .sidebar .nav-link.active,
            .sidebar .sidebar-link.active {
                background: linear-gradient(135deg, #3b82f6, #2563eb);
                color: white;
                box-shadow: 0 4px 6px rgba(59,130,246,0.3);
            }
            
            .sidebar .nav-link i,
            .sidebar .sidebar-link i {
                margin-right: 12px;
                font-size: 1.1rem;
                min-width: 20px;
            }
            
            /* Dropdown Toggle Button */
            .sidebar .dropdown-toggle-btn {
                background: transparent;
                border: none;
                width: 100%;
                text-align: left;
                display: flex;
                align-items: center;
                justify-content: space-between;
                cursor: pointer;
            }
            
            .sidebar .dropdown-toggle-btn .arrow {
                transition: transform 0.3s ease;
                font-size: 0.8rem;
                color: #cbd5e1;
            }
            
            .sidebar .dropdown-toggle-btn[aria-expanded="true"] .arrow {
                transform: rotate(90deg);
            }
            
            /* Dropdown Menu Inside Sidebar */
            .sidebar .dropdown-menu-inner {
                list-style: none;
                padding-left: 45px;
                margin: 0;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease;
            }
            
            .sidebar .dropdown-menu-inner.show {
                max-height: 500px;
            }
            
            .sidebar .dropdown-item-custom {
                color: #cbd5e1;
                padding: 8px 16px;
                margin: 2px 0;
                border-radius: 8px;
                transition: all 0.2s;
                font-size: 0.8rem;
                display: flex;
                align-items: center;
                text-decoration: none;
                cursor: pointer;
            }
            
            .sidebar .dropdown-item-custom:hover {
                background: rgba(255,255,255,0.08);
                color: white;
                transform: translateX(3px);
            }
            
            .sidebar .dropdown-item-custom.active {
                background: rgba(59,130,246,0.3);
                color: white;
            }
            
            .sidebar .dropdown-item-custom i {
                margin-right: 10px;
                font-size: 0.85rem;
                min-width: 20px;
            }
            
            /* Custom Scrollbar for Sidebar */
            .sidebar::-webkit-scrollbar {
                width: 5px;
            }
            
            .sidebar::-webkit-scrollbar-track {
                background: rgba(255,255,255,0.1);
            }
            
            .sidebar::-webkit-scrollbar-thumb {
                background: rgba(255,255,255,0.3);
                border-radius: 5px;
            }
            
            /* Stats Cards */
            .stat-card {
                border: none;
                border-radius: 20px;
                transition: all 0.3s ease;
                cursor: pointer;
                overflow: hidden;
            }
            
            .stat-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            }
            
            .stat-icon {
                font-size: 2.5rem;
                opacity: 0.8;
            }
            
            /* Top Navbar */
            .top-navbar {
                background: white;
                border-radius: 15px;
                margin: 15px 20px;
                padding: 10px 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            }
            
            /* Menu Toggle Button */
            .menu-toggle {
                display: none;
                background: transparent;
                border: none;
                font-size: 1.5rem;
                color: #1e2a3a;
                cursor: pointer;
                padding: 5px;
            }
            
            /* Overlay for mobile */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .sidebar-overlay.show {
                display: block;
                opacity: 1;
            }
            
            /* Content Area */
            .content-area {
                padding: 20px;
            }
            
            /* Table Styles */
            .table-custom {
                background: white;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }
            
            .table-custom thead {
                background: #f8fafc;
            }
            
            /* Badge Styles */
            .badge-stock {
                background: #dcfce7;
                color: #166534;
                padding: 5px 12px;
                border-radius: 20px;
                font-weight: 500;
            }
            
            .badge-low-stock {
                background: #fee2e2;
                color: #991b1b;
                padding: 5px 12px;
                border-radius: 20px;
                font-weight: 500;
            }
            
            /* Logo Styles */
            .sidebar-logo {
                font-size: 1.3rem;
                font-weight: 700;
                letter-spacing: -0.5px;
            }
            
            .sidebar-logo i {
                font-size: 1.5rem;
            }
            
            /* Account Section at Bottom */
            .sidebar-bottom {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, #1e2a3a 0%, #0f172a 100%);
                padding: 15px 12px;
                border-top: 1px solid rgba(255,255,255,0.1);
            }
            
            /* Make sidebar content scrollable without overlapping bottom */
            .sidebar-content {
                padding-bottom: 100px;
            }
            
            /* Responsive Styles */
            @media (max-width: 768px) {
                .sidebar {
                    transform: translateX(-100%);
                }
                
                .sidebar.open {
                    transform: translateX(0);
                }
                
                .main-content {
                    margin-left: 0;
                    width: 100%;
                }
                
                .menu-toggle {
                    display: block;
                }
                
                .top-navbar {
                    margin: 10px 15px;
                    padding: 8px 15px;
                }
                
                .content-area {
                    padding: 15px;
                }
                
                .stat-icon {
                    font-size: 1.8rem;
                }
                
                .table-custom {
                    font-size: 0.8rem;
                }
                
                .table-custom th,
                .table-custom td {
                    padding: 8px;
                }
            }
            
            @media (max-width: 576px) {
                .top-navbar h5 {
                    font-size: 0.9rem;
                }
                
                .top-navbar small {
                    font-size: 0.7rem;
                }
                
                .stat-card h2 {
                    font-size: 1.3rem;
                }
                
                .stat-card h6 {
                    font-size: 0.7rem;
                }
                
                .content-area {
                    padding: 10px;
                }
            }
        </style>
    </head>
    <body>
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" onclick="closeSidebar()"></div>
        
        <!-- Fixed Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-content">
                <div class="p-4">
                    <h3 class="text-white mb-4 sidebar-logo">
                        <i class="bi bi-box-seam"></i> Inventory
                    </h3>
                </div>
                
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" onclick="closeSidebar()">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                
                <!-- Products Dropdown -->
                <div class="dropdown-container">
                    <button class="sidebar-link dropdown-toggle-btn" onclick="toggleDropdown(this)" aria-expanded="false">
                        <span style="display: flex; align-items: center;">
                            <i class="bi bi-box"></i> Products
                        </span>
                        <i class="bi bi-chevron-right arrow"></i>
                    </button>
                    <ul class="dropdown-menu-inner" id="productsMenu">
                        <li>
                            <a href="{{ route('products.all') }}" class="dropdown-item-custom" onclick="closeSidebar()">
                                <i class="bi bi-grid-3x3-gap-fill"></i> All Products
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('products.create') }}" class="dropdown-item-custom" onclick="closeSidebar()">
                                <i class="bi bi-plus-circle"></i> Create Product
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Suppliers Dropdown -->
                <div class="dropdown-container">
                    <button class="sidebar-link dropdown-toggle-btn" onclick="toggleDropdown(this)" aria-expanded="false">
                        <span style="display: flex; align-items: center;">
                            <i class="bi bi-truck"></i> Suppliers
                        </span>
                        <i class="bi bi-chevron-right arrow"></i>
                    </button>
                    <ul class="dropdown-menu-inner" id="suppliersMenu">
                        <li>
                            <a href="{{ route('supplier.create') }}" class="dropdown-item-custom" onclick="closeSidebar()">
                                <i class="bi bi-person-plus"></i> Add Supplier
                            </a>
                        </li>
                    </ul>
                </div>
                            
                <!-- Categories -->
                <a href="{{ route('categories') }}" class="sidebar-link {{ request()->routeIs('categories') ? 'active' : '' }}" onclick="closeSidebar()">
                    <i class="bi bi-tags"></i> Categories
                </a>
                  <!-- brand -->
                <a href="{{ route('brand') }}" class="sidebar-link {{ request()->routeIs('brand') ? 'active' : '' }}" onclick="closeSidebar()">
                    <i class="bi bi-list"></i> Brand
                </a>
                  <a href="{{ route('customers') }}" class="sidebar-link {{ request()->routeIs('brand') ? 'active' : '' }}" onclick="closeSidebar()">
                    <i class="bi bi-people-fill"></i> Customers
                </a>
                <!-- Stock Management Dropdown -->
                <div class="dropdown-container">
                    <button class="sidebar-link dropdown-toggle-btn" onclick="toggleDropdown(this)" aria-expanded="false">
                        <span style="display: flex; align-items: center;">
                            <i class="bi bi-arrow-left-right"></i> Stock Management
                        </span>
                        <i class="bi bi-chevron-right arrow"></i>
                    </button>
                    <ul class="dropdown-menu-inner" id="stockMenu">
                        <li>
                            <a href="{{ route('stock.details') }}" class="dropdown-item-custom" onclick="closeSidebar()">
                                <i class="bi bi-plus-circle"></i> Stock In
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('stock.history') }}" class="dropdown-item-custom" onclick="closeSidebar()">
                                <i class="bi bi-clock-history"></i> Stock History
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Sales Dropdown -->
                <div class="dropdown-container">
                    <button class="sidebar-link dropdown-toggle-btn" onclick="toggleDropdown(this)" aria-expanded="false">
                        <span style="display: flex; align-items: center;">
                            <i class="bi bi-cart-check"></i> Sales
                        </span>
                        <i class="bi bi-chevron-right arrow"></i>
                    </button>
                    <ul class="dropdown-menu-inner" id="salesMenu">
                        <li>
                            <a href="{{ route('sales.sales') }}" class="dropdown-item-custom" onclick="closeSidebar()">
                                <i class="bi bi-list-ul"></i> All Sales
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('sales.create') }}" class="dropdown-item-custom" onclick="closeSidebar()">
                                <i class="bi bi-plus-circle"></i> Create Sale
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('sales.invoice') }}" class="dropdown-item-custom" onclick="closeSidebar()">
                                <i class="bi bi-receipt"></i> Invoices
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('sales.history') }}" class="dropdown-item-custom" onclick="closeSidebar()">
                                <i class="bi bi-clock-history"></i> Sales History
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Reports Dropdown -->
                <div class="dropdown-container">
                    <button class="sidebar-link dropdown-toggle-btn" onclick="toggleDropdown(this)" aria-expanded="false">
                        <span style="display: flex; align-items: center;">
                            <i class="bi bi-graph-up"></i> Reports
                        </span>
                        <i class="bi bi-chevron-right arrow"></i>
                    </button>
                    <ul class="dropdown-menu-inner" id="reportsMenu">
                        <li>
                            <a href="{{ route('reports.sales') }}" class="dropdown-item-custom" onclick="closeSidebar()">
                                <i class="bi bi-bar-chart"></i> Sales Reports
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('reports.stock') }}" class="dropdown-item-custom" onclick="closeSidebar()">
                                <i class="bi bi-box-seam"></i> Stock Reports
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('reports.supplier') }}" class="dropdown-item-custom" onclick="closeSidebar()">
                                <i class="bi bi-truck"></i> Supplier Reports
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Logout -->
                <a href="#" class="sidebar-link" onclick="closeSidebar()">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content (with margin-left for fixed sidebar) -->
        <div class="main-content" id="mainContent">
            <!-- Top Navbar -->
            <div class="top-navbar d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <button class="menu-toggle" onclick="toggleSidebar()">
                        <i class="bi bi-list"></i>
                    </button>
                    <div>
                        <h5 class="mb-0 fw-bold">Welcome back, {{ auth()->user()->name ?? 'Asad Mukhtar' }}!</h5>
                        <small class="text-muted">{{ now()->format('l, F j, Y') }}</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <a href="#" class="text-decoration-none position-relative" data-bs-toggle="dropdown">
                            <i class="bi bi-bell fs-5 text-muted"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                3
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-envelope"></i> New sale order</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-exclamation-triangle"></i> Low stock alert</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#">View all notifications</a></li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'Asad Mukhtar') }}&background=3b82f6&color=fff&rounded=true&bold=true&size=40" width="40" height="40" class="rounded-circle">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-shield-lock"></i> Privacy</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-question-circle"></i> Help</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Dynamic Content -->
            <div class="content-area">
                {{ $slot }}
            </div>
        </div>
        
        <!-- Bootstrap JS CDN -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        @livewireScripts
        
        <script>
            // Toggle dropdown function
            function toggleDropdown(button) {
                const isExpanded = button.getAttribute('aria-expanded') === 'true';
                const menu = button.nextElementSibling;
                
                if (isExpanded) {
                    button.setAttribute('aria-expanded', 'false');
                    menu.classList.remove('show');
                } else {
                    button.setAttribute('aria-expanded', 'true');
                    menu.classList.add('show');
                }
            }
            
            // Toggle sidebar on mobile
            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                const mainContent = document.getElementById('mainContent');
                
                sidebar.classList.toggle('open');
                overlay.classList.toggle('show');
            }
            
            // Close sidebar
            function closeSidebar() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('show');
                }
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('open');
                    sidebar.classList.remove('closed');
                    overlay.classList.remove('show');
                }
            });
            
            // Set active state for current page in dropdown items
            document.addEventListener('DOMContentLoaded', function() {
                const currentUrl = window.location.pathname;
                const dropdownItems = document.querySelectorAll('.dropdown-item-custom');
                
                dropdownItems.forEach(item => {
                    const href = item.getAttribute('href');
                    if (href === currentUrl) {
                        item.classList.add('active');
                        
                        // Expand parent dropdown
                        const parentMenu = item.closest('.dropdown-menu-inner');
                        if (parentMenu) {
                            parentMenu.classList.add('show');
                            const parentButton = parentMenu.previousElementSibling;
                            if (parentButton && parentButton.classList.contains('dropdown-toggle-btn')) {
                                parentButton.setAttribute('aria-expanded', 'true');
                            }
                        }
                    }
                });
            });
        </script>
    </body>
</html>