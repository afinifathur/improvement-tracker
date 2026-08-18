@extends('layouts.app')

@section('title', 'Kaizen Tracker | Kalender')

@section('content')
@php
    $monthStartCarbon = \Carbon\Carbon::parse($monthStart);
    $prevMonth = $monthStartCarbon->copy()->subMonth()->toDateString();
    $nextMonth = $monthStartCarbon->copy()->addMonth()->toDateString();
    $weeks = array_chunk($days, 7);
    $statusColors = [
        'not_started' => 'bg-slate-400',
        'in_progress' => 'bg-blue-500',
        'blocked' => 'bg-amber-500',
        'completed' => 'bg-green-500',
        'cancelled' => 'bg-red-400',
    ];
@endphp
<div class="p-6 space-y-6">
    <!-- Title & Month Navigation Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-200 pb-4 gap-4">
        <div>
            <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                <span>TAMPILAN KALENDER</span>
                <span>/</span>
                <span class="text-secondary">BULANAN</span>
            </div>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-800 mt-1">
                {{ strtoupper($monthStartCarbon->format('F Y')) }}
            </h2>
        </div>

        <!-- Month Controls -->
        <div class="flex items-center gap-1.5">
            <a href="{{ route('work-items.calendar', array_merge(request()->query(), ['date' => $prevMonth])) }}" class="flex items-center justify-center px-2 h-7 bg-white border border-slate-200 rounded text-[11px] font-bold text-slate-600 hover:bg-slate-50 transition-colors uppercase tracking-wider gap-1">
                <span class="material-symbols-outlined text-[16px]">chevron_left</span> Bulan Lalu
            </a>
            <a href="{{ route('work-items.calendar', array_merge(request()->query(), ['date' => \Carbon\Carbon::today()->toDateString()])) }}" class="px-2.5 h-7 flex items-center justify-center bg-white border border-slate-200 rounded text-[11px] font-bold text-slate-600 hover:bg-slate-50 transition-colors uppercase tracking-wider">
                Hari Ini
            </a>
            <a href="{{ route('work-items.calendar', array_merge(request()->query(), ['date' => $nextMonth])) }}" class="flex items-center justify-center px-2 h-7 bg-white border border-slate-200 rounded text-[11px] font-bold text-slate-600 hover:bg-slate-50 transition-colors uppercase tracking-wider gap-1">
                Bulan Depan <span class="material-symbols-outlined text-[16px]">chevron_right</span>
            </a>
            <form method="GET" action="{{ route('work-items.calendar') }}" class="flex items-center gap-1.5">
                @foreach(request()->except(['date', 'page']) as $key => $val)
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}"/>
                @endforeach
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="h-7 px-2 border border-slate-200 rounded text-[11px] font-medium text-slate-600 outline-none focus:border-secondary focus:ring-0 cursor-pointer"/>
            </form>
        </div>
    </div>

    <!-- Top Metrics Row -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">PEKERJAAN</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['work_items']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-blue-500">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">AKTIF</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['active']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-red-500">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">TERLAMBAT</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['overdue']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-amber-500">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">TERBLOKIR</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['blocked']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-green-500">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">SELESAI</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['completed']) }}</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('work-items.calendar') }}" class="filter-bar flex-wrap gap-3">
        <!-- Retain target date -->
        <input type="hidden" name="date" value="{{ $date }}"/>

        <!-- Text Search -->
        <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded px-2.5 py-1">
            <span class="material-symbols-outlined text-[16px] text-slate-400">search</span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari pekerjaan..." class="bg-transparent border-none text-[11px] p-0 outline-none w-32 focus:ring-0 placeholder-slate-400"/>
        </div>

        <!-- Department Filter -->
        <select name="department_id" onchange="this.form.submit()" class="filter-control">
            <option value="">Semua Departemen</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                    {{ $dept->name }}
                </option>
            @endforeach
        </select>

        <!-- Area Filter -->
        <select name="area_id" onchange="this.form.submit()" class="filter-control">
            <option value="">Semua Area</option>
            @foreach($areas as $ar)
                <option value="{{ $ar->id }}" {{ request('area_id') == $ar->id ? 'selected' : '' }}>
                    {{ $ar->name }} ({{ $ar->code }})
                </option>
            @endforeach
        </select>

        <!-- Person Filter -->
        <select name="owner_id" onchange="this.form.submit()" class="filter-control">
            <option value="">Semua Penanggung Jawab</option>
            @foreach($users as $usr)
                <option value="{{ $usr->id }}" {{ request('owner_id') == $usr->id ? 'selected' : '' }}>
                    {{ $usr->name }}
                </option>
            @endforeach
        </select>

        <!-- Status Filter -->
        <select name="status" onchange="this.form.submit()" class="filter-control">
            <option value="">Semua Status</option>
            <option value="not_started" {{ request('status') === 'not_started' ? 'selected' : '' }}>Belum Mulai</option>
            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>Berjalan</option>
            <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Terblokir</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
        </select>

        <!-- Reset Button -->
        @if(request()->anyFilled(['search', 'department_id', 'area_id', 'owner_id', 'status']))
            <a href="{{ route('work-items.calendar', ['date' => $date]) }}" class="text-[10px] font-bold text-red-500 hover:text-red-700 flex items-center gap-1 uppercase tracking-wider ml-auto">
                <span class="material-symbols-outlined text-[14px]">close</span> Hapus Filter
            </a>
        @endif
    </form>

    <!-- Calendar Grid -->
    <div class="table-container">
        <!-- Weekday header -->
        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
            @foreach(['SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB', 'MIN'] as $dayName)
                <div class="px-2 py-1.5 text-center text-[9px] font-bold text-slate-400 uppercase tracking-widest border-r border-slate-100 last:border-r-0">{{ $dayName }}</div>
            @endforeach
        </div>

        @foreach($weeks as $week)
            <div class="grid grid-cols-7 border-b border-slate-100 last:border-b-0">
                @foreach($week as $day)
                    <div class="min-h-[88px] p-1 border-r border-slate-100 last:border-r-0 {{ $day['inMonth'] ? 'bg-white' : 'bg-slate-50/60' }}">
                        <div class="flex items-center justify-between px-1 mb-0.5">
                            <span class="text-[10px] font-bold {{ $day['inMonth'] ? 'text-slate-700' : 'text-slate-300' }}">{{ $day['date']->format('j') }}</span>
                            @if($day['date']->isToday())
                                <span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>
                            @endif
                        </div>
                        <div class="space-y-0.5">
                            @foreach($day['items'] as $item)
                                <div class="flex items-center gap-1 px-1 py-0.5 rounded-sm {{ in_array($item->status->value, ['completed', 'cancelled']) ? 'opacity-50' : '' }}">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $statusColors[$item->status->value] ?? 'bg-slate-400' }}"></span>
                                    <span class="text-[9px] font-medium text-slate-600 truncate {{ in_array($item->status->value, ['completed', 'cancelled']) ? 'line-through' : '' }}" title="{{ $item->title }}">{{ $item->title }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    @if(empty(array_filter($days, fn($d) => $d['items']->isNotEmpty())))
        <div class="text-center py-12 bg-white border border-slate-200 rounded-sm">
            <span class="material-symbols-outlined text-slate-300 text-4xl mb-2">event_busy</span>
            <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Tidak ada pekerjaan yang dijadwalkan bulan ini</p>
        </div>
    @endif
</div>
@endsection
