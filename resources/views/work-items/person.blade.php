@extends('layouts.app')

@section('title', 'Kaizen Tracker | Person')

@section('content')
<div class="p-6 space-y-6">
    <!-- Title Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-200 pb-4 gap-4">
        <div>
            <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                <span>PERSONNEL VIEW</span>
                <span>/</span>
                <span class="text-secondary">PERSON</span>
            </div>
            <h2 class="text-xl font-bold tracking-tight text-slate-800 mt-1">
                {{ $selectedPerson ? $selectedPerson->name : 'Personnel Workload' }}
            </h2>
        </div>
    </div>

    @if($selectedPerson)
        <!-- Person Header Card -->
        <div class="bg-white border border-slate-200 rounded-sm p-4 flex flex-wrap items-center gap-6">
            <img alt="Avatar" class="w-12 h-12 rounded object-cover grayscale" src="https://ui-avatars.com/api/?name={{ urlencode($selectedPerson->name) }}&background=0058be&color=fff&size=96"/>
            <div class="min-w-0">
                <h3 class="text-base font-extrabold text-slate-800">{{ $selectedPerson->name }}</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $selectedPerson->role }}</p>
            </div>
            <div class="flex-1 min-w-[200px] grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 block">Current Area</span>
                    <span class="text-sm font-semibold text-slate-700">{{ $selectedPerson->areaAssignments->filter(fn($a) => $a->activeOn(now()))->map(fn($a) => $a->area?->name)->filter()->implode(', ') ?: '—' }}</span>
                </div>
                <div>
                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 block">Department</span>
                    <span class="text-sm font-semibold text-slate-700">{{ $selectedPerson->department->name ?? '—' }}</span>
                </div>
            </div>
        </div>

        <!-- Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-blue-500">
                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">ACTIVE</span>
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
            <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-green-500">
                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">COMPLETED</span>
                <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['completed']) }}</span>
            </div>
        </div>

        <!-- Tabs -->
        @php $tab = request('tab', 'all'); @endphp
        <div class="flex items-center gap-1">
            @foreach(['all' => 'All', 'active' => 'Active', 'completed' => 'Completed', 'blocked' => 'Blocked'] as $tabKey => $tabLabel)
                <a href="{{ route('work-items.person', array_merge(request()->query(), ['tab' => $tabKey])) }}" class="px-3 py-1.5 rounded-sm text-[10px] font-bold uppercase tracking-wider {{ $tab === $tabKey ? 'bg-secondary text-white' : 'bg-white border border-slate-200 text-slate-500 hover:bg-slate-50' }} transition-colors">
                    {{ $tabLabel }}
                </a>
            @endforeach
        </div>
    @endif

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('work-items.person') }}" class="filter-bar flex-wrap gap-3">
        <!-- Text Search -->
        <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded px-2.5 py-1">
            <span class="material-symbols-outlined text-[16px] text-slate-400">search</span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search work item..." class="bg-transparent border-none text-[11px] p-0 outline-none w-32 focus:ring-0 placeholder-slate-400"/>
        </div>

        <!-- Person Selector -->
        <select name="person" onchange="this.form.submit()" class="filter-control">
            <option value="">All People</option>
            @foreach($users as $usr)
                <option value="{{ $usr->id }}" {{ request('person') == $usr->id ? 'selected' : '' }}>
                    {{ $usr->name }}
                </option>
            @endforeach
        </select>

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

        @if($selectedPerson)
            <!-- Status Filter -->
            <select name="status" onchange="this.form.submit()" class="filter-control">
                <option value="">All Statuses</option>
                <option value="not_started" {{ request('status') === 'not_started' ? 'selected' : '' }}>Not Started</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        @endif

        <!-- Reset Button -->
        @if(request()->anyFilled(['search', 'person', 'department_id', 'area_id', 'status']))
            <a href="{{ route('work-items.person') }}" class="text-[10px] font-bold text-red-500 hover:text-red-700 flex items-center gap-1 uppercase tracking-wider ml-auto">
                <span class="material-symbols-outlined text-[14px]">close</span> Clear Filters
            </a>
        @endif
    </form>

    @if($selectedPerson)
        <!-- Detail WorkItem Register -->
        <div class="table-container">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th class="w-24">STATUS</th>
                        <th>WORK</th>
                        <th class="w-36">AREA</th>
                        <th class="w-36">DEPARTMENT</th>
                        <th class="w-24">START</th>
                        <th class="w-24">DUE</th>
                        <th class="w-24">UPDATED</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workItems as $item)
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
                                <span class="font-medium text-slate-500">{{ $item->area->name ?? 'Unassigned' }}</span>
                            </td>
                            <td>
                                <span class="font-medium text-slate-500">{{ $item->department->name ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="text-slate-500 text-[10px]">{{ $item->planned_start_date ? $item->planned_start_date->format('d M Y') : '-' }}</span>
                            </td>
                            <td>
                                <span class="text-slate-500 text-[10px]">{{ $item->planned_end_date ? $item->planned_end_date->format('d M Y') : '-' }}</span>
                            </td>
                            <td>
                                <span class="text-slate-500 text-[10px]">{{ $item->updated_at ? $item->updated_at->format('d M Y') : '-' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12">
                                <span class="material-symbols-outlined text-slate-300 text-4xl mb-2 block">person_off</span>
                                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">No work items for this person</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <!-- Overview Table -->
        <div class="table-container">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th>PERSON</th>
                        <th class="w-24">POSITION</th>
                        <th class="w-40">AREA</th>
                        <th class="w-40">DEPARTMENT</th>
                        <th class="w-16 text-center">OPEN</th>
                        <th class="w-16 text-center">IN PROGRESS</th>
                        <th class="w-16 text-center">BLOCKED</th>
                        <th class="w-16 text-center">OVERDUE</th>
                        <th class="w-16 text-center">COMPLETED</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($people as $person)
                        <tr class="cursor-pointer" onclick="window.location='{{ route('work-items.person', ['person' => $person->user->id]) }}'">
                            <td>
                                <div class="flex items-center gap-2">
                                    <img alt="Avatar" class="w-5 h-5 rounded object-cover grayscale" src="https://ui-avatars.com/api/?name={{ urlencode($person->user->name) }}&background=f1f5f9&color=64748b&size=32"/>
                                    <span class="font-bold text-slate-700">{{ $person->user->name }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="px-1.5 py-0.5 bg-slate-100 border border-slate-200 rounded-[2px] text-[9px] font-bold text-slate-500 uppercase tracking-wide">{{ $person->user->role }}</span>
                            </td>
                            <td>
                                <span class="font-medium text-slate-500">{{ $person->current_areas }}</span>
                            </td>
                            <td>
                                <span class="font-medium text-slate-500">{{ $person->user->department->name ?? 'N/A' }}</span>
                            </td>
                            <td class="text-center font-bold text-slate-700">{{ $person->counts['open'] }}</td>
                            <td class="text-center font-bold text-blue-600">{{ $person->counts['in_progress'] }}</td>
                            <td class="text-center font-bold text-amber-600">{{ $person->counts['blocked'] }}</td>
                            <td class="text-center font-bold text-red-600">{{ $person->counts['overdue'] }}</td>
                            <td class="text-center font-bold text-green-600">{{ $person->counts['completed'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-12">
                                <span class="material-symbols-outlined text-slate-300 text-4xl mb-2 block">group</span>
                                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">No personnel workload found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
