@extends('layouts.app')

@section('title', 'Kaizen Tracker | Today View')

@section('content')
@php
    $carbonDate = \Carbon\Carbon::parse($date);
    $prevDate = $carbonDate->copy()->subDay()->toDateString();
    $nextDate = $carbonDate->copy()->addDay()->toDateString();
@endphp
<div class="p-6 space-y-6">
    <!-- Title & Date Navigation Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-200 pb-4 gap-4">
        <div>
            <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                <span>DAILY VIEW</span>
                <span>/</span>
                <span class="text-secondary">TODAY</span>
            </div>
            <h2 class="text-xl font-bold tracking-tight text-slate-800 mt-1">
                {{ $carbonDate->format('l, d F Y') }}
            </h2>
        </div>

        <!-- Date Controls Form -->
        <form method="GET" action="{{ route('work-items.today') }}" id="date-nav-form" class="flex items-center gap-1.5">
            <!-- Retain current filters during date change -->
            @foreach(request()->except(['date', 'page']) as $key => $val)
                <input type="hidden" name="{{ $key }}" value="{{ $val }}"/>
            @endforeach

            <a href="{{ route('work-items.today', array_merge(request()->query(), ['date' => $prevDate])) }}" class="flex items-center justify-center w-7 h-7 bg-white border border-slate-200 rounded text-slate-600 hover:bg-slate-50 transition-colors">
                <span class="material-symbols-outlined text-[18px]">chevron_left</span>
            </a>
            <a href="{{ route('work-items.today', array_merge(request()->query(), ['date' => \Carbon\Carbon::today()->toDateString()])) }}" class="px-2.5 h-7 flex items-center justify-center bg-white border border-slate-200 rounded text-[11px] font-bold text-slate-600 hover:bg-slate-50 transition-colors uppercase tracking-wider">
                Today
            </a>
            <a href="{{ route('work-items.today', array_merge(request()->query(), ['date' => $nextDate])) }}" class="flex items-center justify-center w-7 h-7 bg-white border border-slate-200 rounded text-slate-600 hover:bg-slate-50 transition-colors">
                <span class="material-symbols-outlined text-[18px]">chevron_right</span>
            </a>
            <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="h-7 px-2 border border-slate-200 rounded text-[11px] font-medium text-slate-600 outline-none focus:border-secondary focus:ring-0 cursor-pointer"/>
        </form>
    </div>

    <!-- Top Metrics Row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">EXPECTED</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['expected']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">ACTIVE WORKLOAD</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['active']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-red-500">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">OVERDUE</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['overdue']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-amber-500">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">BLOCKED</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['blocked']) }}</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('work-items.today') }}" class="filter-bar flex-wrap gap-3">
        <!-- Retain target date -->
        <input type="hidden" name="date" value="{{ $date }}"/>

        <!-- Text Search -->
        <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded px-2.5 py-1">
            <span class="material-symbols-outlined text-[16px] text-slate-400">search</span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search work item..." class="bg-transparent border-none text-[11px] p-0 outline-none w-32 focus:ring-0 placeholder-slate-400"/>
        </div>

        <!-- Department Filter -->
        <select name="department_id" onchange="this.form.submit()" class="filter-control">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                    {{ $dept->name }}
                </option>
            @endforeach
        </select>

        <!-- Area Filter -->
        <select name="area_id" onchange="this.form.submit()" class="filter-control">
            <option value="">All Areas</option>
            @foreach($areas as $ar)
                <option value="{{ $ar->id }}" {{ request('area_id') == $ar->id ? 'selected' : '' }}>
                    {{ $ar->name }} ({{ $ar->code }})
                </option>
            @endforeach
        </select>

        <!-- Person Filter -->
        <select name="owner_id" onchange="this.form.submit()" class="filter-control">
            <option value="">All Responsible</option>
            @foreach($users as $usr)
                <option value="{{ $usr->id }}" {{ request('owner_id') == $usr->id ? 'selected' : '' }}>
                    {{ $usr->name }}
                </option>
            @endforeach
        </select>

        <!-- Status Filter -->
        <select name="status" onchange="this.form.submit()" class="filter-control">
            <option value="">Active States</option>
            <option value="not_started" {{ request('status') === 'not_started' ? 'selected' : '' }}>Not Started</option>
            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
            <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>

        <!-- Reset Button -->
        @if(request()->anyFilled(['search', 'department_id', 'area_id', 'owner_id', 'status']))
            <a href="{{ route('work-items.today', ['date' => $date]) }}" class="text-[10px] font-bold text-red-500 hover:text-red-700 flex items-center gap-1 uppercase tracking-wider ml-auto">
                <span class="material-symbols-outlined text-[14px]">close</span> Clear Filters
            </a>
        @endif
    </form>

    <!-- Grouped Areas Lists -->
    <div class="space-y-4">
        @php
            $hasGroupedItems = false;
        @endphp

        @foreach($areas as $area)
            @if($groupedItems->has($area->id))
                @php
                    $hasGroupedItems = true;
                    $areaItems = $groupedItems->get($area->id);
                @endphp
                <div>
                    <!-- Area Header Toggle -->
                    <div class="flex items-center justify-between bg-slate-50 border border-slate-200 px-4 py-2 select-none cursor-pointer hover:bg-slate-100/70 transition-colors rounded-t-sm" onclick="toggleArea('area-{{ $area->id }}')">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-slate-500 transition-transform" id="icon-area-{{ $area->id }}">expand_more</span>
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wide">{{ $area->name }}</span>
                            <span class="px-1.5 py-0.5 bg-slate-200/80 rounded text-[9px] font-bold text-slate-600">{{ $area->code }}</span>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $areaItems->count() }} items</span>
                    </div>

                    <!-- Table Container -->
                    <div id="area-{{ $area->id }}" class="table-container rounded-t-none">
                        <table class="table-dense">
                            <thead>
                                <tr>
                                    <th class="w-24">STATUS</th>
                                    <th>WORK</th>
                                    <th class="w-32">CLASSIFICATION</th>
                                    <th class="w-40">PERSON</th>
                                    <th class="w-40">DEPARTMENT</th>
                                    <th class="w-24">START</th>
                                    <th class="w-24">DUE</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($areaItems as $item)
                                    <tr>
                                        <td>
                                            @if($item->status->value === 'not_started')
                                                <span class="badge-status badge-not-started">Not Started</span>
                                            @elseif($item->status->value === 'in_progress')
                                                <span class="badge-status badge-in-progress">In Progress</span>
                                            @elseif($item->status->value === 'blocked')
                                                <span class="badge-status badge-blocked">Blocked</span>
                                            @elseif($item->status->value === 'completed')
                                                <span class="badge-status badge-completed">Completed</span>
                                            @elseif($item->status->value === 'cancelled')
                                                <span class="badge-status badge-cancelled">Cancelled</span>
                                            @endif
                                        </td>
                                        <td>
                                            <p class="font-bold text-slate-800">{{ $item->title }}</p>
                                            @if($item->description)
                                                <p class="text-[10px] text-slate-400 truncate max-w-lg mt-0.5">{{ $item->description }}</p>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->classification === 'overdue')
                                                <span class="px-1.5 py-0.5 bg-red-50 text-red-700 border border-red-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">OVERDUE</span>
                                            @elseif($item->classification === 'current')
                                                <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">CURRENT</span>
                                            @elseif($item->classification === 'future')
                                                <span class="px-1.5 py-0.5 bg-slate-50 text-slate-500 border border-slate-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">FUTURE</span>
                                            @else
                                                <span class="px-1.5 py-0.5 bg-slate-100 text-slate-400 border border-slate-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">HISTORICAL</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <img alt="Avatar" class="w-4 h-4 rounded object-cover grayscale" src="https://ui-avatars.com/api/?name={{ urlencode($item->owner->name ?? 'User') }}&background=f1f5f9&color=64748b&size=32"/>
                                                <span class="font-semibold text-slate-700">{{ $item->owner->name ?? 'Unassigned' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="font-medium text-slate-500">{{ $item->department->name ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="text-slate-500">{{ $item->planned_start_date ? $item->planned_start_date->format('d M Y') : '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="text-slate-500">{{ $item->planned_end_date ? $item->planned_end_date->format('d M Y') : '-' }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endforeach

        <!-- Fallback for Items without assigned area_id -->
        @if($groupedItems->has(null) || $groupedItems->has(''))
            @php
                $hasGroupedItems = true;
                $unassignedItems = $groupedItems->get(null) ?? $groupedItems->get('');
            @endphp
            <div>
                <!-- Unassigned Header Toggle -->
                <div class="flex items-center justify-between bg-slate-50 border border-slate-200 px-4 py-2 select-none cursor-pointer hover:bg-slate-100/70 transition-colors rounded-t-sm" onclick="toggleArea('area-unassigned')">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px] text-slate-500 transition-transform" id="icon-area-unassigned">expand_more</span>
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wide">Unassigned Area</span>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $unassignedItems->count() }} items</span>
                </div>

                <!-- Table Container -->
                <div id="area-unassigned" class="table-container rounded-t-none">
                    <table class="table-dense">
                        <thead>
                            <tr>
                                <th class="w-24">STATUS</th>
                                <th>WORK</th>
                                <th class="w-32">CLASSIFICATION</th>
                                <th class="w-40">PERSON</th>
                                <th class="w-40">DEPARTMENT</th>
                                <th class="w-24">START</th>
                                <th class="w-24">DUE</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unassignedItems as $item)
                                <tr>
                                    <td>
                                        @if($item->status->value === 'not_started')
                                            <span class="badge-status badge-not-started">Not Started</span>
                                        @elseif($item->status->value === 'in_progress')
                                            <span class="badge-status badge-in-progress">In Progress</span>
                                        @elseif($item->status->value === 'blocked')
                                            <span class="badge-status badge-blocked">Blocked</span>
                                        @elseif($item->status->value === 'completed')
                                            <span class="badge-status badge-completed">Completed</span>
                                        @elseif($item->status->value === 'cancelled')
                                            <span class="badge-status badge-cancelled">Cancelled</span>
                                        @endif
                                    </td>
                                    <td>
                                        <p class="font-bold text-slate-800">{{ $item->title }}</p>
                                        @if($item->description)
                                            <p class="text-[10px] text-slate-400 truncate max-w-lg mt-0.5">{{ $item->description }}</p>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->classification === 'overdue')
                                            <span class="px-1.5 py-0.5 bg-red-50 text-red-700 border border-red-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">OVERDUE</span>
                                        @elseif($item->classification === 'current')
                                            <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">CURRENT</span>
                                        @elseif($item->classification === 'future')
                                            <span class="px-1.5 py-0.5 bg-slate-50 text-slate-500 border border-slate-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">FUTURE</span>
                                        @else
                                            <span class="px-1.5 py-0.5 bg-slate-100 text-slate-400 border border-slate-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">HISTORICAL</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <img alt="Avatar" class="w-4 h-4 rounded object-cover grayscale" src="https://ui-avatars.com/api/?name={{ urlencode($item->owner->name ?? 'User') }}&background=f1f5f9&color=64748b&size=32"/>
                                            <span class="font-semibold text-slate-700">{{ $item->owner->name ?? 'Unassigned' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="font-medium text-slate-500">{{ $item->department->name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-slate-500">{{ $item->planned_start_date ? $item->planned_start_date->format('d M Y') : '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-slate-500">{{ $item->planned_end_date ? $item->planned_end_date->format('d M Y') : '-' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if(!$hasGroupedItems)
            <div class="text-center py-12 bg-white border border-slate-200 rounded-sm">
                <span class="material-symbols-outlined text-slate-300 text-4xl mb-2">assignment_late</span>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">No active work items matching filters for this date</p>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleArea(id) {
        const el = document.getElementById(id);
        const icon = document.getElementById('icon-' + id);
        if (el) {
            if (el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                if (icon) icon.style.transform = 'rotate(0deg)';
            } else {
                el.classList.add('hidden');
                if (icon) icon.style.transform = 'rotate(-90deg)';
            }
        }
    }
</script>
@endsection
