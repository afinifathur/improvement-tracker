<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'Kaizen Tracker')</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#0058be",
                        "secondary": "#0058be",
                        "tertiary-fixed": "#fadfb8",
                        "on-secondary-fixed-variant": "#004395",
                        "background": "#f8fafb",
                        "surface": "#ffffff",
                        "error": "#9f403d",
                        // MD3 Tokens for backward compatibility
                        "surface-container-low": "#f0f4f6",
                        "surface-container-high": "#e1eaec",
                        "on-surface-variant": "#566164",
                        "inverse-surface": "#0b0f10",
                        "on-surface": "#2a3437",
                        "outline-variant": "#a9b4b7"
                    },
                    fontFamily: {
                        "sans": ["Inter", "sans-serif"],
                    },
                },
            },
        }
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
    </style>
    @yield('head')
</head>
<body class="bg-background text-on-surface antialiased flex min-h-screen">

<!-- SideNavBar -->
<aside class="flex flex-col fixed top-0 left-0 h-screen w-[240px] border-r border-slate-200 bg-white text-xs font-medium z-30 select-none">
    <!-- Header -->
    <div class="p-4 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
            <div class="w-6 h-6 bg-secondary flex items-center justify-center rounded">
                <span class="material-symbols-outlined text-white text-sm" style="font-variation-settings: 'FILL' 1;">precision_manufacturing</span>
            </div>
            <div class="min-w-0">
                <h1 class="text-sm font-bold text-slate-800 leading-none truncate">Kaizen Tracker</h1>
                <p class="text-[9px] uppercase tracking-wider font-semibold text-slate-400 mt-1 truncate">PT. Peroni Karya Sentra</p>
            </div>
        </div>
    </div>

    <!-- Quick Action / New Item (if admin) -->
    @if(auth()->user()->isAdmin())
    <div class="p-3 border-b border-slate-100 bg-slate-50/50">
        <a href="{{ route('weekly-plans.create') }}" class="flex items-center justify-center gap-1.5 w-full bg-secondary text-white py-1.5 rounded text-[11px] font-bold tracking-wide hover:brightness-110 active:scale-[0.98] transition-all shadow-sm">
            <span class="material-symbols-outlined text-sm font-bold">add</span>
            NEW PLAN
        </a>
    </div>
    @endif

    <!-- Navigation Scroll Area -->
    <div class="flex-1 overflow-y-auto p-3 space-y-4">
        <!-- Section: Views -->
        <div>
            <span class="px-2 text-[9px] font-bold text-slate-400 uppercase tracking-widest block mb-1.5">VIEWS</span>
            <nav class="space-y-0.5">
                <a href="{{ Route::has('work-items.today') ? route('work-items.today') : '#' }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors {{ request()->routeIs('work-items.today') ? 'bg-slate-100/80 text-slate-900 border-l-2 border-secondary font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">today</span>
                        <span>Today</span>
                    </div>
                </a>
                <a href="{{ Route::has('work-items.this-week') ? route('work-items.this-week') : '#' }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors {{ request()->routeIs('work-items.this-week') ? 'bg-slate-100/80 text-slate-900 border-l-2 border-secondary font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">calendar_view_week</span>
                        <span>This Week</span>
                    </div>
                </a>
                <a href="{{ Route::has('work-items.plan') ? route('work-items.plan') : '#' }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors {{ request()->routeIs('work-items.plan') ? 'bg-slate-100/80 text-slate-900 border-l-2 border-secondary font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">assignment</span>
                        <span>Plan</span>
                    </div>
                </a>
                <a href="{{ Route::has('work-items.progress') ? route('work-items.progress') : '#' }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors {{ request()->routeIs('work-items.progress') ? 'bg-slate-100/80 text-slate-900 border-l-2 border-secondary font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">trending_up</span>
                        <span>Progress</span>
                    </div>
                </a>
                <a href="{{ Route::has('work-items.overdue') ? route('work-items.overdue') : '#' }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors {{ request()->routeIs('work-items.overdue') ? 'bg-slate-100/80 text-slate-900 border-l-2 border-secondary font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">warning</span>
                        <span>Overdue</span>
                    </div>
                </a>
                <a href="{{ Route::has('work-items.completed') ? route('work-items.completed') : '#' }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors {{ request()->routeIs('work-items.completed') ? 'bg-slate-100/80 text-slate-900 border-l-2 border-secondary font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                        <span>Completed</span>
                    </div>
                </a>
                <a href="{{ route('work-items.calendar') }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors {{ request()->routeIs('work-items.calendar') ? 'bg-slate-100/80 text-slate-900 border-l-2 border-secondary font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">calendar_month</span>
                        <span>Calendar</span>
                    </div>
                </a>
                <a href="{{ route('issues.index') }}" class="flex items-center justify-between px-2.5 py-1.5 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors {{ request()->routeIs('issues.index') ? 'bg-slate-100/80 text-slate-900 border-l-2 border-secondary font-semibold pl-2' : '' }}">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px]">error</span>
                        <span>Issues</span>
                    </div>
                </a>
            </nav>
        </div>

        <!-- Section: Slices -->
        <div>
            <span class="px-2 text-[9px] font-bold text-slate-400 uppercase tracking-widest block mb-1.5">SLICES</span>
            <nav class="space-y-0.5">
                <a href="{{ route('work-items.person') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors {{ request()->routeIs('work-items.person') ? 'bg-slate-100/80 text-slate-900 border-l-2 border-secondary font-semibold pl-2' : '' }}">
                    <span class="material-symbols-outlined text-[18px]">person</span>
                    <span>Person</span>
                </a>
                <a href="{{ route('work-items.area') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors {{ request()->routeIs('work-items.area') ? 'bg-slate-100/80 text-slate-900 border-l-2 border-secondary font-semibold pl-2' : '' }}">
                    <span class="material-symbols-outlined text-[18px]">precision_manufacturing</span>
                    <span>Area</span>
                </a>
            </nav>
        </div>

        <!-- Section: Operations -->
        <div>
            <span class="px-2 text-[9px] font-bold text-slate-400 uppercase tracking-widest block mb-1.5">OPERATIONS</span>
            <nav class="space-y-0.5">
                @if(auth()->user()->isAdmin() || auth()->user()->role === 'director')
                <a href="{{ Route::has('daily-reports.index') ? route('daily-reports.index') : '#' }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors {{ request()->routeIs('daily-reports.*') ? 'bg-slate-100/80 text-slate-900 border-l-2 border-secondary font-semibold pl-2' : '' }}">
                    <span class="material-symbols-outlined text-[18px]">rule_folder</span>
                    <span>Control Center</span>
                </a>
                @endif
                <a href="{{ Route::has('dashboard') ? route('dashboard') : '#' }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors {{ request()->routeIs('dashboard') ? 'bg-slate-100/80 text-slate-900 border-l-2 border-secondary font-semibold pl-2' : '' }}">
                    <span class="material-symbols-outlined text-[18px]">space_dashboard</span>
                    <span>Weekly Plan</span>
                </a>
                @if(auth()->user()->isAdmin())
                <a href="{{ Route::has('weekly-plans.closing') ? route('weekly-plans.closing') : '#' }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors {{ request()->routeIs('weekly-plans.closing') ? 'bg-slate-100/80 text-slate-900 border-l-2 border-secondary font-semibold pl-2' : '' }}">
                    <span class="material-symbols-outlined text-[18px]">assignment_turned_in</span>
                    <span>Closing</span>
                </a>
                @endif
                <a href="{{ Route::has('rankings') ? route('rankings') : '#' }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors {{ request()->routeIs('rankings') ? 'bg-slate-100/80 text-slate-900 border-l-2 border-secondary font-semibold pl-2' : '' }}">
                    <span class="material-symbols-outlined text-[18px]">leaderboard</span>
                    <span>Ranking</span>
                </a>
            </nav>
        </div>
    </div>

    <!-- Footer -->
    <div class="p-3 border-t border-slate-200 bg-slate-50/50 mt-auto">
        <div class="flex items-center gap-2 px-1">
            <img alt="User Avatar" class="w-6 h-6 rounded object-cover grayscale" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=0058be&color=fff"/>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-bold text-slate-700 truncate leading-normal">{{ auth()->user()->name ?? 'Guest' }}</p>
                <p class="text-[8px] text-slate-400 font-semibold uppercase tracking-wider leading-none">{{ auth()->user()->role ?? 'Operator' }}</p>
            </div>
            <form action="{{ route('logout') }}" method="POST" id="logout-form" class="hidden">@csrf</form>
            <a onclick="document.getElementById('logout-form').submit()" class="text-slate-400 hover:text-red-600 cursor-pointer flex items-center shrink-0">
                <span class="material-symbols-outlined text-[16px]">logout</span>
            </a>
        </div>
    </div>
</aside>

<!-- Top App Bar -->
<header class="flex justify-between items-center fixed top-0 left-[240px] right-0 h-[40px] px-4 bg-white/95 backdrop-blur border-b border-slate-200 z-20 select-none">
    <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-slate-400 text-base">search</span>
        <input type="text" placeholder="Search datasets..." class="w-48 bg-transparent border-none text-xs outline-none focus:ring-0 placeholder-slate-400 p-0"/>
    </div>
    <div class="flex items-center gap-3">
        <button class="text-slate-400 hover:text-slate-600 flex items-center">
            <span class="material-symbols-outlined text-base">notifications</span>
        </button>
        <div class="h-3 w-[1px] bg-slate-200"></div>
        <span class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded text-[9px] font-bold text-slate-500 uppercase tracking-widest">
            {{ auth()->user()->role ?? 'ADMIN' }}
        </span>
    </div>
</header>

<!-- Main content area -->
<main class="flex-1 min-w-0 ml-[240px] pt-[40px] min-h-screen bg-background">
    @yield('content')
</main>

@yield('scripts')
</body>
</html>
