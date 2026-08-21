<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'Kaizen Tracker')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            try {
                if (localStorage.getItem('kaizen-sidebar-collapsed') === '1') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        })();
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Custom scrollbar for high-density Notion sidebar */
        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* ---------------------------------------------------- */
        /* REUSABLE UI PRIMITIVES (High Density Industrial)     */
        /* ---------------------------------------------------- */

        /* Reusable Table Primitives */
        .table-container {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .table-dense {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 11px;
        }
        .table-dense th {
            background-color: #f8fafb;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 6px 12px;
        }
        .table-dense td {
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            padding: 8px 12px;
            vertical-align: middle;
        }
        .table-dense tr:last-child td {
            border-bottom: none;
        }
        .table-dense tbody tr:hover {
            background-color: #f8fafb;
            cursor: pointer;
        }

        /* Reusable Status Badges */
        .badge-status {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-radius: 9999px;
            border-width: 1px;
        }
        .badge-not-started {
            background-color: #f1f5f9;
            color: #475569;
            border-color: #cbd5e1;
        }
        .badge-in-progress {
            background-color: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }
        .badge-blocked {
            background-color: #fffbeb;
            color: #b45309;
            border-color: #fde68a;
        }
        .badge-completed {
            background-color: #f0fdf4;
            color: #15803d;
            border-color: #bbf7d0;
        }
        .badge-cancelled {
            background-color: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }
        .badge-open {
            background-color: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }
        .badge-resolved {
            background-color: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }
        .badge-closed {
            background-color: #f1f5f9;
            color: #475569;
            border-color: #cbd5e1;
        }

        /* Reusable Filter Controls */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 2px;
            padding: 6px 12px;
            font-size: 11px;
        }
        .filter-control {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 2px;
            padding: 3px 8px;
            font-size: 11px;
            outline: none;
            transition: border-color 0.15s ease-in-out;
        }
        .filter-control:focus {
            border-color: #0058be;
            box-shadow: 0 0 0 1px #0058be;
        }

        /* Collapsible sidebar */
        #app-sidebar { transition: width .2s ease; }
        #app-header { transition: left .2s ease; }
        #app-main { transition: margin-left .2s ease; }

        html.sidebar-collapsed #app-sidebar { width: 4rem; }
        html.sidebar-collapsed #app-header { left: 4rem; }
        html.sidebar-collapsed #app-main { margin-left: 4rem; }

        html.sidebar-collapsed #app-sidebar .sidebar-brand-text,
        html.sidebar-collapsed #app-sidebar .sidebar-section-label,
        html.sidebar-collapsed #app-sidebar .sidebar-cta-label,
        html.sidebar-collapsed #app-sidebar .sidebar-user-text { display: none; }

        html.sidebar-collapsed #app-sidebar nav a span:not(.material-symbols-outlined) { display: none; }

        html.sidebar-collapsed #app-sidebar .sidebar-header { padding-left: .5rem; padding-right: .5rem; }
        html.sidebar-collapsed #app-sidebar .sidebar-brand-row,
        html.sidebar-collapsed #app-sidebar .sidebar-user-row { justify-content: center; }
        html.sidebar-collapsed #app-sidebar nav a { justify-content: center; padding-left: 0; padding-right: 0; }
    </style>
    @yield('head')
</head>
<body class="bg-background text-on-surface antialiased flex min-h-screen">

<!-- SideNavBar -->
<aside id="app-sidebar" class="flex flex-col fixed top-0 left-0 h-screen w-[240px] border-r border-white/15 bg-[#0066B3] text-white text-xs font-medium z-30 select-none">
    <!-- Header -->
    <div class="p-4 border-b border-white/15 sidebar-header">
        <div class="flex items-center gap-2.5 sidebar-brand-row">
            <div class="w-6 h-6 bg-white/20 flex items-center justify-center rounded">
                <span class="material-symbols-outlined text-white text-sm" style="font-variation-settings: 'FILL' 1;">precision_manufacturing</span>
            </div>
            <div class="min-w-0 sidebar-brand-text">
                <h1 class="text-sm font-bold text-white leading-none truncate">Kaizen Tracker</h1>
                <p class="text-[9px] uppercase tracking-wider font-semibold text-white/70 mt-1 truncate">PT. Peroni Karya Sentra</p>
            </div>
        </div>
    </div>

    <!-- Quick Action / New Item (if admin) -->
    @if(auth()->user()->isAdmin())
    <div class="p-3 border-b border-white/15 bg-white/5">
        <a href="{{ route('weekly-plans.create') }}" title="Rencana Baru" class="flex items-center justify-center gap-1.5 w-full bg-white text-[#0066B3] py-1.5 rounded text-[11px] font-bold tracking-wide hover:bg-slate-50 active:scale-[0.98] transition-all shadow-sm">
            <span class="material-symbols-outlined text-sm font-bold">add</span>
            <span class="sidebar-cta-label">RENCANA BARU</span>
        </a>
    </div>
    @endif

    <!-- Navigation Scroll Area -->
    <div class="flex-1 overflow-y-auto p-3 space-y-4">
        <!-- Section: Views -->
        <div>
            <span class="px-2 text-[9px] font-bold text-white/60 uppercase tracking-widest block mb-1.5 sidebar-section-label">TAMPILAN</span>
            <nav class="space-y-0.5">
                <a href="{{ Route::has('dashboard.index') ? route('dashboard.index') : '#' }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-white/80 hover:text-white hover:bg-white/8 transition-colors {{ request()->routeIs('dashboard.index') ? 'bg-white/16 text-white border-l-2 border-white font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">monitoring</span>
                        <span>Dashboard</span>
                    </div>
                </a>
                <a href="{{ Route::has('work-items.today') ? route('work-items.today') : '#' }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-white/80 hover:text-white hover:bg-white/8 transition-colors {{ request()->routeIs('work-items.today') ? 'bg-white/16 text-white border-l-2 border-white font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">today</span>
                        <span>Hari Ini</span>
                    </div>
                </a>
                <a href="{{ Route::has('work-items.this-week') ? route('work-items.this-week') : '#' }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-white/80 hover:text-white hover:bg-white/8 transition-colors {{ request()->routeIs('work-items.this-week') ? 'bg-white/16 text-white border-l-2 border-white font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">calendar_view_week</span>
                        <span>Minggu Ini</span>
                    </div>
                </a>
                <a href="{{ Route::has('work-items.plan') ? route('work-items.plan') : '#' }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-white/80 hover:text-white hover:bg-white/8 transition-colors {{ request()->routeIs('work-items.plan') ? 'bg-white/16 text-white border-l-2 border-white font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">assignment</span>
                        <span>Rencana</span>
                    </div>
                </a>
                <a href="{{ Route::has('work-items.progress') ? route('work-items.progress') : '#' }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-white/80 hover:text-white hover:bg-white/8 transition-colors {{ request()->routeIs('work-items.progress') ? 'bg-white/16 text-white border-l-2 border-white font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">trending_up</span>
                        <span>Sedang Berjalan</span>
                    </div>
                </a>
                <a href="{{ Route::has('work-items.overdue') ? route('work-items.overdue') : '#' }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-white/80 hover:text-white hover:bg-white/8 transition-colors {{ request()->routeIs('work-items.overdue') ? 'bg-white/16 text-white border-l-2 border-white font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">warning</span>
                        <span>Terlambat</span>
                    </div>
                </a>
                <a href="{{ Route::has('work-items.completed') ? route('work-items.completed') : '#' }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-white/80 hover:text-white hover:bg-white/8 transition-colors {{ request()->routeIs('work-items.completed') ? 'bg-white/16 text-white border-l-2 border-white font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                        <span>Selesai</span>
                    </div>
                </a>
                <a href="{{ route('work-items.calendar') }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-white/80 hover:text-white hover:bg-white/8 transition-colors {{ request()->routeIs('work-items.calendar') ? 'bg-white/16 text-white border-l-2 border-white font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">calendar_month</span>
                        <span>Kalender</span>
                    </div>
                </a>
                <a href="{{ route('issues.index') }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-white/80 hover:text-white hover:bg-white/8 transition-colors {{ request()->routeIs('issues.index') ? 'bg-white/16 text-white border-l-2 border-white font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">error</span>
                        <span>Kendala</span>
                    </div>
                </a>
            </nav>
        </div>

        <!-- Section: Slices -->
        <div>
            <span class="px-2 text-[9px] font-bold text-white/60 uppercase tracking-widest block mb-1.5 sidebar-section-label">ANALISIS</span>
            <nav class="space-y-0.5">
                <a href="{{ route('work-items.person') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded text-white/80 hover:text-white hover:bg-white/8 transition-colors {{ request()->routeIs('work-items.person') ? 'bg-white/16 text-white border-l-2 border-white font-semibold pl-2' : '' }}">
                    <span class="material-symbols-outlined text-[18px]">person</span>
                    <span>Personel</span>
                </a>
                <a href="{{ route('work-items.area') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded text-white/80 hover:text-white hover:bg-white/8 transition-colors {{ request()->routeIs('work-items.area') ? 'bg-white/16 text-white border-l-2 border-white font-semibold pl-2' : '' }}">
                    <span class="material-symbols-outlined text-[18px]">precision_manufacturing</span>
                    <span>Area</span>
                </a>
            </nav>
        </div>

        <!-- Section: Operations -->
        <div>
            <span class="px-2 text-[9px] font-bold text-white/60 uppercase tracking-widest block mb-1.5 sidebar-section-label">OPERASI</span>
            <nav class="space-y-0.5">
                @if(auth()->user()->isAdmin() || auth()->user()->role === 'director')
                <a href="{{ Route::has('daily-reports.index') ? route('daily-reports.index') : '#' }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded text-white/80 hover:text-white hover:bg-white/8 transition-colors {{ request()->routeIs('daily-reports.*') ? 'bg-white/16 text-white border-l-2 border-white font-semibold pl-2' : '' }}">
                    <span class="material-symbols-outlined text-[18px]">rule_folder</span>
                    <span>Pusat Kendali</span>
                </a>
                @endif
                <a href="{{ Route::has('dashboard') ? route('dashboard') : '#' }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded text-white/80 hover:text-white hover:bg-white/8 transition-colors {{ request()->routeIs('dashboard') ? 'bg-white/16 text-white border-l-2 border-white font-semibold pl-2' : '' }}">
                    <span class="material-symbols-outlined text-[18px]">space_dashboard</span>
                    <span>Rencana Mingguan</span>
                </a>
                @if(auth()->user()->isAdmin())
                <a href="{{ Route::has('weekly-plans.closing') ? route('weekly-plans.closing') : '#' }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded text-white/80 hover:text-white hover:bg-white/8 transition-colors {{ request()->routeIs('weekly-plans.closing') ? 'bg-white/16 text-white border-l-2 border-white font-semibold pl-2' : '' }}">
                    <span class="material-symbols-outlined text-[18px]">assignment_turned_in</span>
                    <span>Penutupan</span>
                </a>
                @endif
                <a href="{{ Route::has('rankings') ? route('rankings') : '#' }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded text-white/80 hover:text-white hover:bg-white/8 transition-colors {{ request()->routeIs('rankings') ? 'bg-white/16 text-white border-l-2 border-white font-semibold pl-2' : '' }}">
                    <span class="material-symbols-outlined text-[18px]">leaderboard</span>
                    <span>Peringkat</span>
                </a>
            </nav>
        </div>
    </div>

    <!-- Footer -->
    <div class="p-3 border-t border-white/15 bg-white/5 mt-auto">
        <div class="flex items-center gap-2 px-1 sidebar-user-row">
            <x-avatar class="w-6 h-6 rounded grayscale" :name="auth()->user()->name ?? 'User'" background="0058be" color="fff"/>
            <div class="min-w-0 flex-1 sidebar-user-text">
                <p class="text-[10px] font-bold text-white truncate leading-normal">{{ auth()->user()->name ?? 'Guest' }}</p>
                <p class="text-[8px] text-white/70 font-semibold uppercase tracking-wider leading-none">{{ match(auth()->user()->role ?? 'spv') { 'admin' => 'Admin', 'director' => 'Direktur', 'manager' => 'Manager', 'kabag' => 'Kabag', 'spv' => 'SPV', default => auth()->user()->role } }}</p>
            </div>
            <form action="{{ route('logout') }}" method="POST" id="logout-form" class="hidden">@csrf</form>
            <a onclick="document.getElementById('logout-form').submit()" class="text-white/70 hover:text-red-400 cursor-pointer flex items-center shrink-0">
                <span class="material-symbols-outlined text-[16px]">logout</span>
            </a>
        </div>
    </div>
</aside>

<!-- Top App Bar -->
<header id="app-header" class="flex justify-between items-center fixed top-0 left-[240px] right-0 h-[40px] px-4 bg-white/95 backdrop-blur border-b border-slate-200 z-20 select-none">
    <div class="flex items-center gap-2">
        <button id="sidebar-toggle" type="button" aria-label="Collapse sidebar" title="Collapse sidebar" class="flex items-center justify-center w-7 h-7 rounded text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary shrink-0">
            <span class="material-symbols-outlined text-[18px]" id="sidebar-toggle-icon">chevron_left</span>
        </button>
        <span class="material-symbols-outlined text-slate-400 text-base">search</span>
        <input type="text" placeholder="Cari data..." class="w-48 bg-transparent border-none text-xs outline-none focus:ring-0 placeholder-slate-400 p-0"/>
    </div>
    <div class="flex items-center gap-3">
        <button class="text-slate-400 hover:text-slate-600 flex items-center">
            <span class="material-symbols-outlined text-base">notifications</span>
        </button>
        <div class="h-3 w-[1px] bg-slate-200"></div>
        <span class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded text-[9px] font-bold text-slate-500 uppercase tracking-widest">
            {{ match(auth()->user()->role ?? 'admin') { 'admin' => 'Admin', 'director' => 'Direktur', 'manager' => 'Manager', 'kabag' => 'Kabag', 'spv' => 'SPV', default => auth()->user()->role } }}
        </span>
    </div>
</header>

<!-- Main content area -->
<main id="app-main" class="flex-1 min-w-0 ml-[240px] pt-[40px] min-h-screen bg-background">
    @if(session('status'))
    <div class="px-6 pt-4">
        <div class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-800 text-xs font-semibold rounded-sm px-4 py-2.5">
            <span class="material-symbols-outlined text-[16px]">check_circle</span>
            <span>{{ session('status') }}</span>
        </div>
    </div>
    @endif
    @yield('content')
</main>

@yield('scripts')
<script>
    (function () {
        var KEY = 'kaizen-sidebar-collapsed';
        var root = document.documentElement;
        var toggle = document.getElementById('sidebar-toggle');
        var icon = document.getElementById('sidebar-toggle-icon');
        var links = document.querySelectorAll('#app-sidebar nav a');

        function apply(collapsed) {
            if (toggle) {
                toggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
                toggle.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            }
            if (icon) {
                icon.textContent = collapsed ? 'chevron_right' : 'chevron_left';
            }
            links.forEach(function (a) {
                if (collapsed) {
                    var label = a.querySelector('span:not(.material-symbols-outlined)');
                    if (label) a.title = label.textContent.trim();
                } else {
                    a.removeAttribute('title');
                }
            });
        }

        apply(root.classList.contains('sidebar-collapsed'));

        if (toggle) {
            toggle.addEventListener('click', function () {
                var collapsed = root.classList.toggle('sidebar-collapsed');
                try { localStorage.setItem(KEY, collapsed ? '1' : '0'); } catch (e) {}
                apply(collapsed);
            });
        }
    })();
</script>
</body>
</html>
