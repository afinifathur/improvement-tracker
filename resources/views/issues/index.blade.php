@extends('layouts.app')

@section('title', 'Kaizen Tracker | Issues')

@section('content')
<div class="p-6 space-y-6">
    <!-- Title Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-200 pb-4 gap-4">
        <div>
            <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                <span>OPERATIONS</span>
                <span>/</span>
                <span class="text-secondary">ISSUES</span>
            </div>
            <h2 class="text-xl font-bold tracking-tight text-slate-800 mt-1">
                Issue Register
            </h2>
        </div>
    </div>

    <!-- Top Metrics Row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">TOTAL ISSUES</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['total']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-red-500">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">OPEN</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['open']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-blue-500">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">RESOLVED</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['resolved']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-slate-400">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">CLOSED</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['closed']) }}</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('issues.index') }}" class="filter-bar flex-wrap gap-3">
        <!-- Text Search -->
        <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded px-2.5 py-1">
            <span class="material-symbols-outlined text-[16px] text-slate-400">search</span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search issue..." class="bg-transparent border-none text-[11px] p-0 outline-none w-32 focus:ring-0 placeholder-slate-400"/>
        </div>

        <!-- Status Filter -->
        <select name="status" onchange="this.form.submit()" class="filter-control">
            <option value="">All Statuses</option>
            <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
            <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
            <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
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

        <!-- Date Range Filters -->
        <div class="flex items-center gap-1">
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">From</span>
            <input type="date" name="from" value="{{ request('from') }}" onchange="this.form.submit()" class="filter-control cursor-pointer"/>
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">To</span>
            <input type="date" name="to" value="{{ request('to') }}" onchange="this.form.submit()" class="filter-control cursor-pointer"/>
        </div>

        <!-- Reset Button -->
        @if(request()->anyFilled(['search', 'status', 'department_id', 'area_id', 'from', 'to']))
            <a href="{{ route('issues.index') }}" class="text-[10px] font-bold text-red-500 hover:text-red-700 flex items-center gap-1 uppercase tracking-wider ml-auto">
                <span class="material-symbols-outlined text-[14px]">close</span> Clear Filters
            </a>
        @endif
    </form>

    <!-- Issues Register Table -->
    <div class="table-container">
        <table class="table-dense">
            <thead>
                <tr>
                    <th class="w-24">STATUS</th>
                    <th>ISSUE</th>
                    <th class="w-36">DEPARTMENT</th>
                    <th class="w-36">AREA</th>
                    <th class="w-32">FIRST REPORTED</th>
                    <th class="w-16">AGE</th>
                    <th class="w-48">SOURCE</th>
                </tr>
            </thead>
            <tbody>
                @forelse($issues as $issue)
                    <tr>
                        <td>
                            @if($issue->status->value === 'open')
                                <span class="badge-status badge-open">Open</span>
                            @elseif($issue->status->value === 'resolved')
                                <span class="badge-status badge-resolved">Resolved</span>
                            @else
                                <span class="badge-status badge-closed">Closed</span>
                            @endif
                        </td>
                        <td>
                            <p class="font-bold text-slate-800">{{ $issue->title }}</p>
                            @if($issue->description)
                                <p class="text-[10px] text-slate-400 truncate max-w-lg mt-0.5">{{ $issue->description }}</p>
                            @endif
                        </td>
                        <td>
                            <span class="font-medium text-slate-500">{{ $issue->department->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="font-medium text-slate-500">{{ $issue->area->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="text-slate-500 text-[10px]">{{ $issue->first_reported_at ? $issue->first_reported_at->format('d M Y') : '-' }}</span>
                        </td>
                        <td>
                            @if($issue->status->value === 'open' && $issue->days_open !== null)
                                <span class="px-1.5 py-0.5 bg-red-50 text-red-700 border border-red-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">{{ $issue->days_open }}d</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td>
                            @if($issue->sourceDailyReport)
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('daily-reports.edit', $issue->sourceDailyReport) }}" class="text-secondary hover:underline text-[10px] font-semibold">
                                        Daily Report · {{ $issue->sourceDailyReport->report_date->format('d M Y') }}
                                    </a>
                                @else
                                    <span class="text-slate-500 text-[10px]">Daily Report · {{ $issue->sourceDailyReport->report_date->format('d M Y') }}</span>
                                @endif
                                @if($issue->sourceDailyReport->reporter)
                                    <span class="block text-[9px] text-slate-400">{{ $issue->sourceDailyReport->reporter->name }}</span>
                                @endif
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-12">
                            <span class="material-symbols-outlined text-slate-300 text-4xl mb-2 block">task_alt</span>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">No issues matching filters</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
