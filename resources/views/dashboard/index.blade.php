@extends('layouts.app')

@section('title', 'Kaizen Tracker | Dashboard Manajemen')

@section('content')
<div class="p-6 space-y-6">
    <!-- Title & Top Filter Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between border-b border-slate-200 pb-4 gap-4">
        <div>
            <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                <span>MANAJEMEN</span>
                <span>/</span>
                <span class="text-secondary">PUSAT DIAGNOSTIK & KONTROL</span>
            </div>
            <h2 class="text-xl font-bold tracking-tight text-slate-800 mt-1">Dashboard Kontrol Manajemen</h2>
            <p class="text-xs text-slate-400 mt-0.5">Monitoring beban kerja aktual, aliran tugas, keterlambatan, dan kepatuhan pelaporan</p>
        </div>

        <!-- Global Filters (Date Navigation & Dept/Area Filter) -->
        <form method="GET" action="{{ route('dashboard.index') }}" class="flex flex-wrap items-center gap-2 text-xs">
            <!-- Retain current filters during date navigation -->
            <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}"/>

            <!-- Department Filter -->
            <select name="department_id" onchange="this.form.submit()" class="filter-control h-7 text-[11px]">
                <option value="">Semua Departemen</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ $selectedDepartmentId == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>

            <!-- Area Filter -->
            <select name="area_id" onchange="this.form.submit()" class="filter-control h-7 text-[11px]">
                <option value="">Semua Area</option>
                @foreach($areas as $ar)
                    <option value="{{ $ar->id }}" {{ $selectedAreaId == $ar->id ? 'selected' : '' }}>
                        {{ $ar->name }} ({{ $ar->code }})
                    </option>
                @endforeach
            </select>

            <!-- Week Navigator -->
            <div class="flex items-center gap-1 bg-white border border-slate-200 rounded p-0.5">
                <a href="{{ route('dashboard.index', array_merge(request()->except('date'), ['date' => $prevWeekDate])) }}" title="Minggu Sebelumnya" class="p-1 hover:bg-slate-100 rounded text-slate-600 flex items-center">
                    <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                </a>
                <span class="px-1.5 text-[10px] font-bold text-slate-700 tabular-nums uppercase">
                    {{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}
                </span>
                <a href="{{ route('dashboard.index', array_merge(request()->except('date'), ['date' => $nextWeekDate])) }}" title="Minggu Berikutnya" class="p-1 hover:bg-slate-100 rounded text-slate-600 flex items-center">
                    <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                </a>
            </div>

            @if($selectedDepartmentId || $selectedAreaId || $selectedDate->toDateString() !== $today->toDateString())
                <a href="{{ route('dashboard.index') }}" class="text-[10px] font-bold text-red-500 hover:text-red-700 uppercase tracking-wider flex items-center gap-0.5 ml-1">
                    <span class="material-symbols-outlined text-[14px]">close</span> Reset
                </a>
            @endif
        </form>
    </div>

    <!-- SECTION 1: MANAGEMENT SUMMARY (DIAGNOSTIC CARDS) -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <!-- 1. Reporting Compliance Today -->
        <div class="bg-white p-3.5 border border-slate-200 rounded-sm shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">KEPATUHAN LAPOR (HARI INI)</span>
                <span class="material-symbols-outlined text-[16px] text-blue-500">fact_check</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold tracking-tight text-slate-800">
                    {{ $complianceToday['percent'] }}%
                </span>
                <span class="text-[11px] font-semibold text-slate-500 tabular-nums">
                    {{ $complianceToday['submitted'] }}/{{ $complianceToday['total'] }}
                </span>
            </div>
        </div>

        <!-- 2. Remaining Workload -->
        <div class="bg-white p-3.5 border border-slate-200 rounded-sm shadow-xs flex flex-col justify-between border-l-4 border-l-blue-600">
            <div class="flex items-center justify-between">
                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500">SISA BEBAN KERJA (REMAINING)</span>
                <span class="material-symbols-outlined text-[16px] text-blue-600">pending_actions</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold tracking-tight text-blue-700">{{ number_format($remainingWorkload) }}</span>
                <span class="text-[10px] text-slate-400 font-medium">Akumulasi aktif</span>
            </div>
        </div>

        <!-- 3. Overdue Work (Management Grace Period) -->
        <a href="{{ route('work-items.overdue') }}" class="bg-white p-3.5 border border-slate-200 rounded-sm shadow-xs flex flex-col justify-between border-l-4 border-l-red-500 hover:bg-red-50/20 transition-colors group">
            <div class="flex items-center justify-between">
                <span class="text-[9px] font-bold uppercase tracking-wider text-red-600">TERLAMBAT (OVERDUE)</span>
                <span class="material-symbols-outlined text-[16px] text-red-500 group-hover:translate-x-0.5 transition-transform">warning</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold tracking-tight text-red-700">{{ number_format($overdueCount) }}</span>
                <span class="text-[10px] text-red-600 font-bold group-hover:underline">Lihat &rarr;</span>
            </div>
        </a>

        <!-- 4. Blocked Work -->
        <div class="bg-white p-3.5 border border-slate-200 rounded-sm shadow-xs flex flex-col justify-between border-l-4 border-l-amber-500">
            <div class="flex items-center justify-between">
                <span class="text-[9px] font-bold uppercase tracking-wider text-amber-700">TERBLOKIR (KENDALA)</span>
                <span class="material-symbols-outlined text-[16px] text-amber-600">block</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold tracking-tight text-amber-700">{{ number_format($blockedCount) }}</span>
                <span class="text-[10px] text-slate-400 font-medium">Butuh solusi</span>
            </div>
        </div>

        <!-- 5. Net Flow Today -->
        <div class="bg-white p-3.5 border border-slate-200 rounded-sm shadow-xs flex flex-col justify-between border-l-4 {{ $netToday > 0 ? 'border-l-amber-400' : 'border-l-emerald-500' }} col-span-2 lg:col-span-1">
            <div class="flex items-center justify-between">
                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500">NET ALIRAN (HARI INI)</span>
                <span class="material-symbols-outlined text-[16px] text-slate-400">sync_alt</span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold tracking-tight {{ $netToday > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                    {{ $netToday > 0 ? '+'.$netToday : $netToday }}
                </span>
                <span class="text-[10px] text-slate-500 tabular-nums">
                    +{{ $newToday }} / -{{ $completedToday }}
                </span>
            </div>
        </div>
    </div>

    <!-- SECTION 2: PERINGKAT SISA BEBAN KERJA PER PERSONEL (STACKED BAR CHART) -->
    <div class="bg-white border border-slate-200 rounded-sm shadow-xs p-4 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-2 border-b border-slate-100 gap-2">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-700 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base text-secondary">leaderboard</span>
                    PERINGKAT SISA BEBAN KERJA PER PERSONEL
                </h3>
                <p class="text-[10px] text-slate-400 mt-0.5">Urutan personel berdasarkan jumlah pekerjaan terbuka. Klik batang untuk melihat detail pekerjaan.</p>
            </div>

            <!-- Legends & Count -->
            <div class="flex items-center gap-3 text-[10px] font-semibold">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 bg-blue-600 rounded-xs inline-block"></span>
                    <span class="text-slate-600">Belum Overdue</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 bg-red-500 rounded-xs inline-block"></span>
                    <span class="text-slate-600">Terlambat (Overdue)</span>
                </div>
                <span class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded text-slate-600 text-[9px] font-bold">
                    {{ $personnelWorkloads->count() }} Personel
                </span>
            </div>
        </div>

        <!-- Scrollable Stacked Bar Chart Area -->
        <div class="overflow-x-auto pb-2">
            <div class="min-w-max flex flex-col pt-6">
                <!-- Stacked Bars Row -->
                <div class="flex items-end gap-2.5 h-44 pb-1">
                    @forelse($personnelWorkloads as $pw)
                        @php
                            $totalHeight = $pw['remaining'] > 0 ? max(8, (int) round($pw['remaining'] / $maxRemaining * 160)) : 2;
                            $overdueHeight = $pw['remaining'] > 0 ? (int) round($pw['overdue'] / $pw['remaining'] * $totalHeight) : 0;
                            $onTimeHeight = max(0, $totalHeight - $overdueHeight);
                        @endphp
                        <div class="w-10 flex flex-col items-center justify-end h-full group relative cursor-pointer"
                             onclick="window.location='{{ route('work-items.person', ['person' => $pw['user']->id]) }}'">
                            
                            <!-- Total Number on Top -->
                            <span class="text-[10px] font-bold {{ $pw['overdue'] > 0 ? 'text-red-700' : 'text-slate-700' }} tabular-nums leading-none mb-1 group-hover:scale-110 transition-transform">
                                {{ $pw['remaining'] }}
                            </span>

                            <!-- Stacked Bar Column -->
                            <div class="w-5 rounded-t-xs flex flex-col justify-end overflow-hidden transition-all duration-150 group-hover:brightness-90 group-hover:shadow-sm"
                                 style="height: {{ $totalHeight }}px;">
                                @if($pw['remaining'] > 0)
                                    <!-- Red Segment on Top (Overdue open) -->
                                    @if($overdueHeight > 0)
                                        <div class="w-full bg-red-500" style="height: {{ $overdueHeight }}px;"></div>
                                    @endif
                                    <!-- Blue Segment on Bottom (On-time open) -->
                                    @if($onTimeHeight > 0)
                                        <div class="w-full bg-blue-600" style="height: {{ $onTimeHeight }}px;"></div>
                                    @endif
                                @else
                                    <div class="w-full bg-slate-200 h-full"></div>
                                @endif
                            </div>

                            <!-- Rich Hover Tooltip -->
                            <div class="hidden group-hover:flex absolute bottom-full mb-6 z-30 bg-slate-900 text-white text-[10px] p-2.5 rounded-sm shadow-xl flex-col gap-1 pointer-events-none min-w-[150px] border border-slate-700">
                                <div class="font-bold text-white border-b border-slate-700 pb-1 flex items-center justify-between">
                                    <span>{{ $pw['user']->name }}</span>
                                    <span class="text-blue-400 font-mono text-[9px]">Detail &rarr;</span>
                                </div>
                                <div class="grid grid-cols-2 gap-x-2 text-[9px] pt-0.5">
                                    <span class="text-slate-400">Total Sisa:</span>
                                    <span class="font-bold text-white tabular-nums">{{ $pw['remaining'] }}</span>
                                    
                                    <span class="text-slate-400">On Time:</span>
                                    <span class="font-semibold text-blue-300 tabular-nums">{{ $pw['on_time'] }}</span>
                                    
                                    <span class="text-slate-400">Overdue:</span>
                                    <span class="font-semibold {{ $pw['overdue'] > 0 ? 'text-red-400 font-bold' : 'text-slate-300' }} tabular-nums">{{ $pw['overdue'] }}</span>
                                    
                                    <span class="text-slate-400">Terblokir:</span>
                                    <span class="font-semibold {{ $pw['blocked'] > 0 ? 'text-amber-400' : 'text-slate-300' }} tabular-nums">{{ $pw['blocked'] }}</span>
                                    
                                    <span class="text-slate-400">Selesai:</span>
                                    <span class="font-semibold text-emerald-400 tabular-nums">{{ $pw['completed'] }}</span>
                                </div>
                                <div class="text-[8px] text-slate-400 border-t border-slate-800 pt-1 mt-0.5">
                                    <span>{{ $pw['department_name'] }} &bull; {{ $pw['area_names'] }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center text-slate-400 italic text-xs w-full">
                            Tidak ada data personel operasional.
                        </div>
                    @endforelse
                </div>

                <!-- Chart Baseline -->
                <div class="border-t border-slate-300"></div>

                <!-- X-Axis Labels (Person Names) -->
                <div class="flex gap-2.5 pt-2">
                    @foreach($personnelWorkloads as $pw)
                        <div class="w-10 flex items-start justify-center h-28 cursor-pointer group"
                             onclick="window.location='{{ route('work-items.person', ['person' => $pw['user']->id]) }}'">
                            <span class="text-[9px] font-semibold text-slate-600 whitespace-nowrap group-hover:text-blue-600 group-hover:font-bold transition-colors"
                                  style="writing-mode: vertical-rl; transform: rotate(180deg);"
                                  title="{{ $pw['user']->name }} (Klik untuk detail)">
                                {{ $pw['user']->name }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 3 & 4: WORKLOAD TREND & FLOW DYNAMICS -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Workload Trend Chart (7 Days of Selected Week) -->
        <div class="lg:col-span-8 bg-white border border-slate-200 rounded-sm shadow-xs p-4 space-y-3">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-700 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base text-secondary">trending_up</span>
                        TREN SISA BEBAN KERJA (REMAINING WORKLOAD TREND)
                    </h3>
                    <p class="text-[10px] text-slate-400">Rekonstruksi deterministik sisa tugas terbuka per akhir hari</p>
                </div>
                <span class="text-[10px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                    Minggu {{ $weekStart->format('W') }}
                </span>
            </div>

            <!-- Trend Bars & Dynamics -->
            <div class="overflow-x-auto pt-2">
                <div class="flex items-end justify-between gap-2 min-w-[500px] h-36 pb-2 border-b border-slate-200">
                    @foreach($trendDays as $td)
                        <div class="flex-1 flex flex-col items-center justify-end h-full group relative">
                            <!-- Value Label -->
                            <span class="text-[10px] font-bold {{ $td['isToday'] ? 'text-blue-700' : 'text-slate-600' }} tabular-nums leading-none mb-1">
                                {{ $td['isFuture'] ? '—' : $td['remaining'] }}
                            </span>

                            <!-- Bar Column -->
                            @if(!$td['isFuture'])
                                <div class="w-full max-w-[36px] rounded-t-xs transition-all duration-200 {{ $td['isToday'] ? 'bg-blue-600 shadow-xs' : 'bg-slate-300 hover:bg-slate-400' }}"
                                     style="height: {{ max(6, (int) round($td['remaining'] / $maxTrendRemaining * 95)) }}px;"></div>
                            @else
                                <div class="w-full max-w-[36px] h-1 bg-slate-100 rounded-t-xs"></div>
                            @endif

                            <!-- Hover Tooltip -->
                            @if(!$td['isFuture'])
                                <div class="hidden group-hover:flex absolute -top-12 z-20 bg-slate-800 text-white text-[9px] font-semibold py-1 px-2 rounded shadow-lg flex-col items-center whitespace-nowrap pointer-events-none">
                                    <span>Remaining: {{ $td['remaining'] }}</span>
                                    <span class="text-slate-300 text-[8px] font-normal">Masuk: +{{ $td['new'] }} | Selesai: -{{ $td['completed'] }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Date Labels & Inflow/Outflow below bars -->
                <div class="flex items-start justify-between gap-2 min-w-[500px] pt-2 text-center">
                    @foreach($trendDays as $td)
                        <div class="flex-1">
                            <span class="text-[10px] font-bold block leading-tight {{ $td['isToday'] ? 'text-blue-700 font-extrabold' : 'text-slate-700' }}">
                                {{ $td['weekday'] }}
                            </span>
                            <span class="text-[9px] text-slate-400 block">{{ $td['dayLabel'] }}</span>
                            @if(!$td['isFuture'])
                                <span class="text-[8px] font-semibold block mt-0.5 tabular-nums {{ $td['net'] > 0 ? 'text-amber-600' : ($td['net'] < 0 ? 'text-emerald-600' : 'text-slate-400') }}">
                                    {{ $td['net'] > 0 ? '+'.$td['net'] : $td['net'] }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Work Flow Dynamics (Inflow vs Throughput) -->
        <div class="lg:col-span-4 bg-white border border-slate-200 rounded-sm shadow-xs p-4 flex flex-col justify-between space-y-3">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-700 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base text-secondary">swap_vert</span>
                    ALIRAN TUGAS (WORK FLOW)
                </h3>
                <p class="text-[10px] text-slate-400">Kecepatan masuk vs penyelesaian tugas</p>
            </div>

            <!-- Flow Cards: Today & This Week -->
            <div class="space-y-2.5">
                <!-- Today Flow Box -->
                <div class="bg-slate-50 border border-slate-200 rounded p-2.5 space-y-1.5">
                    <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block">Hari Ini ({{ $today->format('d M') }})</span>
                    <div class="grid grid-cols-3 gap-1 text-center">
                        <div class="bg-white p-1.5 rounded border border-slate-100">
                            <span class="text-[8px] font-bold uppercase text-slate-400 block">Masuk</span>
                            <span class="text-xs font-bold text-slate-800 tabular-nums">+{{ $newToday }}</span>
                        </div>
                        <div class="bg-white p-1.5 rounded border border-slate-100">
                            <span class="text-[8px] font-bold uppercase text-slate-400 block">Selesai</span>
                            <span class="text-xs font-bold text-emerald-700 tabular-nums">-{{ $completedToday }}</span>
                        </div>
                        <div class="bg-white p-1.5 rounded border border-slate-100">
                            <span class="text-[8px] font-bold uppercase text-slate-400 block">Net Backlog</span>
                            <span class="text-xs font-bold {{ $netToday > 0 ? 'text-amber-700' : 'text-emerald-700' }} tabular-nums">
                                {{ $netToday > 0 ? '+'.$netToday : $netToday }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- This Week Flow Box -->
                <div class="bg-slate-50 border border-slate-200 rounded p-2.5 space-y-1.5">
                    <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block">Minggu Ini (Senin – Minggu)</span>
                    <div class="grid grid-cols-3 gap-1 text-center">
                        <div class="bg-white p-1.5 rounded border border-slate-100">
                            <span class="text-[8px] font-bold uppercase text-slate-400 block">Masuk</span>
                            <span class="text-xs font-bold text-slate-800 tabular-nums">+{{ $newThisWeek }}</span>
                        </div>
                        <div class="bg-white p-1.5 rounded border border-slate-100">
                            <span class="text-[8px] font-bold uppercase text-slate-400 block">Selesai</span>
                            <span class="text-xs font-bold text-emerald-700 tabular-nums">-{{ $completedThisWeek }}</span>
                        </div>
                        <div class="bg-white p-1.5 rounded border border-slate-100">
                            <span class="text-[8px] font-bold uppercase text-slate-400 block">Net Backlog</span>
                            <span class="text-xs font-bold {{ $netThisWeek > 0 ? 'text-amber-700' : 'text-emerald-700' }} tabular-nums">
                                {{ $netThisWeek > 0 ? '+'.$netThisWeek : $netThisWeek }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 text-[10px] text-slate-500 italic">
                @if($netThisWeek > 0)
                    <span class="text-amber-700 font-semibold">&bull; Backlog bertambah:</span> Tugas baru masuk lebih cepat daripada tugas yang dituntaskan.
                @elseif($netThisWeek < 0)
                    <span class="text-emerald-700 font-semibold">&bull; Backlog berkurang:</span> Tim berhasil menuntaskan lebih banyak tugas daripada yang masuk.
                @else
                    <span class="text-slate-600 font-semibold">&bull; Aliran seimbang:</span> Kecepatan masuk sama dengan kecepatan penyelesaian.
                @endif
            </div>
        </div>
    </div>

    <!-- SECTION 5: DAILY PLAN COMPLIANCE MATRIX -->
    <div class="bg-white border border-slate-200 rounded-sm shadow-xs p-4 space-y-3">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-700 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base text-secondary">fact_check</span>
                    MATRIKS KEPATUHAN RENCANA HARIAN
                </h3>
                <p class="text-[10px] text-slate-400">Pemeriksaan pengumpulan laporan harian per hari berdasarkan penugasan aktif pada tanggal tersebut</p>
            </div>

            <div class="flex items-center gap-2 text-[10px] font-semibold">
                <span class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded text-slate-600 tabular-nums">
                    {{ $weekStart->format('d M') }} — {{ $weekEnd->format('d M Y') }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th class="text-left align-bottom">PERSONEL</th>
                        @foreach($complianceDays as $day)
                        <th class="text-center align-bottom {{ $day['isToday'] ? 'bg-blue-50' : '' }}">
                            <div class="leading-tight">{{ $day['weekday'] }}</div>
                            <div class="text-[9px] font-normal normal-case leading-tight">{{ $day['dayLabel'] }}</div>
                            <div class="text-[11px] leading-tight font-bold">{{ $day['percent'] }}%</div>
                            <div class="text-[8px] font-normal normal-case text-slate-400 leading-tight">{{ $day['submitted'] }}/{{ $day['total'] }}</div>
                            @if($day['isToday'])
                            <div class="text-[8px] font-bold text-blue-700 mt-0.5">HARI INI</div>
                            @endif
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($matrixPersonnel as $person)
                    <tr>
                        <td class="font-semibold text-slate-700">{{ $person->name }}</td>
                        @foreach($complianceDays as $day)
                        <td class="text-center {{ $day['isToday'] ? 'bg-blue-50/50' : '' }}">
                            @if(isset($submittedByDate[$day['dateStr']][$person->id]))
                            <span class="material-symbols-outlined text-[16px] text-emerald-600" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                            @else
                            <span class="material-symbols-outlined text-[16px] text-slate-300">circle</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($matrixPersonnel->isNotEmpty())
            @if($missingToday->isNotEmpty())
            <div class="flex items-start gap-2 text-[10px] font-bold uppercase tracking-wide text-red-700 bg-red-50 border border-red-200 rounded-sm px-3 py-2">
                <span class="material-symbols-outlined text-sm">warning</span>
                <span>BELUM MENGUMPULKAN HARI INI ({{ $missingToday->count() }}): <span class="font-semibold">{{ $missingToday->implode(', ') }}</span></span>
            </div>
            @else
            <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wide text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-sm px-3 py-2">
                <span class="material-symbols-outlined text-sm">check_circle</span>
                <span>SEMUA PERSONEL SUDAH MENGUMPULKAN RENCANA HARI INI</span>
            </div>
            @endif
        @endif
    </div>

    <!-- SECTION 6: ATTENTION REQUIRED (TOP OVERDUE & BLOCKED TASKS) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Overdue Tasks -->
        <div class="bg-white border border-slate-200 rounded-sm shadow-xs p-4 space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-widest text-red-700 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">warning</span>
                        PEKERJAAN TERLAMBAT (OVERDUE)
                    </h3>
                    <p class="text-[10px] text-slate-400">Pekerjaan terbuka melewati batas hari kerja yang paling mendesak</p>
                </div>
                <a href="{{ route('work-items.overdue') }}" class="text-[10px] font-bold text-red-600 hover:underline uppercase">
                    Lihat Semua ({{ $overdueCount }}) &rarr;
                </a>
            </div>

            <div class="table-container">
                <table class="table-dense">
                    <thead>
                        <tr>
                            <th>PEKERJAAN</th>
                            <th class="w-32">PENANGGUNG JAWAB</th>
                            <th class="w-24 text-center">BATAS WAKTU</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attentionOverdue as $item)
                            <tr>
                                <td>
                                    <p class="font-bold text-slate-800 truncate max-w-xs">{{ $item->title }}</p>
                                    <p class="text-[9px] text-slate-400">{{ $item->area?->name ?? $item->department?->name ?? '—' }}</p>
                                </td>
                                <td>
                                    <span class="font-semibold text-slate-700 text-[10px]">{{ $item->owner?->name ?? '—' }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="px-1.5 py-0.5 bg-red-100 text-red-800 rounded font-bold text-[9px] tabular-nums">
                                        {{ $item->planned_end_date ? $item->planned_end_date->format('d M') : '—' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-6 text-emerald-600 font-semibold text-xs">
                                    <span class="material-symbols-outlined text-base align-middle">check_circle</span> Tidak ada pekerjaan terlambat!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Blocked Tasks -->
        <div class="bg-white border border-slate-200 rounded-sm shadow-xs p-4 space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-widest text-amber-700 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">block</span>
                        PEKERJAAN TERBLOKIR (KENDALA)
                    </h3>
                    <p class="text-[10px] text-slate-400">Pekerjaan dengan kendala material, mesin, atau approval</p>
                </div>
                <a href="{{ route('issues.index') }}" class="text-[10px] font-bold text-amber-700 hover:underline uppercase">
                    Lihat Kendala &rarr;
                </a>
            </div>

            <div class="table-container">
                <table class="table-dense">
                    <thead>
                        <tr>
                            <th>PEKERJAAN</th>
                            <th class="w-32">PENANGGUNG JAWAB</th>
                            <th class="w-36">ALASAN KENDALA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attentionBlocked as $item)
                            <tr>
                                <td>
                                    <p class="font-bold text-slate-800 truncate max-w-xs">{{ $item->title }}</p>
                                    <p class="text-[9px] text-slate-400">{{ $item->area?->name ?? '—' }}</p>
                                </td>
                                <td>
                                    <span class="font-semibold text-slate-700 text-[10px]">{{ $item->owner?->name ?? '—' }}</span>
                                </td>
                                <td>
                                    <span class="badge-status badge-blocked text-[8px] truncate max-w-[130px] block">
                                        {{ $item->blocked_reason?->value ?? 'Terblokir' }}
                                    </span>
                                    @if($item->blocked_reason_note)
                                        <span class="text-[8px] text-slate-400 truncate max-w-[130px] block mt-0.5">{{ $item->blocked_reason_note }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-6 text-emerald-600 font-semibold text-xs">
                                    <span class="material-symbols-outlined text-base align-middle">check_circle</span> Tidak ada pekerjaan terblokir!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECTION 7: WEEKLY PLANS PROGRESS -->
    <div class="bg-white border border-slate-200 rounded-sm shadow-xs p-4 space-y-3">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-widest text-slate-700 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base text-secondary">assignment</span>
                    KEMAJUAN SASARAN MINGGUAN (WEEKLY PLANS PROGRESS)
                </h3>
                <p class="text-[10px] text-slate-400">Pencicilan tugas terhubung pada sasaran kaizen minggu {{ $weekStart->format('W') }}</p>
            </div>
            <span class="text-[10px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 border border-slate-200 rounded">
                {{ $weeklyPlans->count() }} Sasaran Terdaftar
            </span>
        </div>

        <div class="table-container">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th class="w-8 text-center">#</th>
                        <th class="w-28">JENIS</th>
                        <th class="w-36">PENANGGUNG JAWAB</th>
                        <th>JUDUL SASARAN / RENCANA</th>
                        <th class="w-1/3">TARGET SASARAN</th>
                        <th class="w-36">PROGRESS</th>
                        <th class="w-28 text-center">SELESAI / TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($weeklyPlans as $wp)
                        <tr>
                            <td class="text-center font-bold text-slate-400 text-[10px]">
                                {{ $loop->iteration }}
                            </td>
                            <td>
                                @php
                                    $cat = strtolower($wp->plan->category);
                                    $badgeClass = match($cat) {
                                        'improvement' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'problem' => 'bg-red-100 text-red-800 border-red-200',
                                        'maintenance' => 'bg-amber-100 text-amber-800 border-amber-200',
                                        default => 'bg-slate-100 text-slate-800 border-slate-200',
                                    };
                                @endphp
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider border {{ $badgeClass }} inline-block">
                                    {{ $wp->plan->category }}
                                </span>
                            </td>
                            <td>
                                <span class="font-semibold text-slate-800 text-[11px] block truncate">
                                    {{ $wp->plan->user?->name ?? '—' }}
                                </span>
                            </td>
                            <td>
                                <p class="font-bold text-slate-800 text-[11px] leading-snug">
                                    {{ $wp->plan->title }}
                                </p>
                            </td>
                            <td>
                                @if($wp->plan->expected_output)
                                    <p class="text-[10px] text-slate-600 leading-relaxed line-clamp-2" title="{{ $wp->plan->expected_output }}">
                                        {{ $wp->plan->expected_output }}
                                    </p>
                                @else
                                    <span class="text-slate-400 text-[10px] italic">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="w-full flex items-center gap-2">
                                    <span class="font-bold text-[10px] tabular-nums shrink-0 w-8 text-right {{ $wp->progress_percent == 100 ? 'text-emerald-700' : 'text-slate-700' }}">
                                        {{ $wp->progress_percent }}%
                                    </span>
                                    <div class="flex-1 bg-slate-200 rounded-full h-2 overflow-hidden">
                                        <div class="{{ $wp->progress_percent == 100 ? 'bg-emerald-600' : 'bg-blue-600' }} h-full rounded-full" style="width: {{ $wp->progress_percent }}%;"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="px-1.5 py-0.5 bg-slate-100 text-slate-700 rounded font-bold text-[10px] tabular-nums border border-slate-200">
                                    {{ $wp->completed_items }} / {{ $wp->total_items }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400 italic text-xs">
                                Belum ada sasaran mingguan yang terdaftar untuk minggu ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
