@extends('layouts.app')

@section('title', 'Kaizen Tracker | Daily Control Center')

@section('content')
<div class="p-6 max-w-6xl mx-auto w-full space-y-6">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1">
                <span>Daily</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-primary">Control Center</span>
            </nav>
            <h2 class="text-2xl font-bold tracking-tight text-inverse-surface">Daily Reports</h2>
            <p class="text-sm text-on-surface-variant">{{ \Illuminate\Support\Carbon::parse($date)->format('l, d F Y') }}</p>
        </div>
        <form method="GET" action="{{ route('daily-reports.index') }}" class="flex items-center gap-2">
            <input type="date" name="date" value="{{ $date }}" class="bg-white border border-outline-variant/30 rounded-sm text-sm py-1.5 px-3 focus:ring-1 focus:ring-primary">
            <button type="submit" class="bg-primary text-white px-4 py-1.5 rounded-sm text-xs font-bold uppercase tracking-wider hover:brightness-110">Go</button>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white p-4 border border-outline-variant/20 rounded-sm">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Expected</span>
            <span class="text-2xl font-bold text-inverse-surface">{{ $summary['expected'] }}</span>
        </div>
        <div class="bg-white p-4 border border-outline-variant/20 rounded-sm border-l-4 border-l-primary">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Processed</span>
            <span class="text-2xl font-bold text-primary">{{ $summary['processed'] }}</span>
        </div>
        <div class="bg-white p-4 border border-outline-variant/20 rounded-sm">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Remaining</span>
            <span class="text-2xl font-bold text-inverse-surface">{{ $summary['remaining'] }}</span>
        </div>
        <div class="bg-white p-4 border border-outline-variant/20 rounded-sm">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Open Work</span>
            <span class="text-2xl font-bold text-inverse-surface">{{ $summary['open'] }}</span>
        </div>
        <div class="bg-white p-4 border border-outline-variant/20 rounded-sm">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Blocked</span>
            <span class="text-2xl font-bold text-amber-600">{{ $summary['blocked'] }}</span>
        </div>
        <div class="bg-white p-4 border border-outline-variant/20 rounded-sm">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Overdue</span>
            <span class="text-2xl font-bold text-error">{{ $summary['overdue'] }}</span>
        </div>
    </div>

    @forelse($grouped as $deptName => $people)
    <section class="space-y-2">
        <h3 class="text-[11px] font-bold uppercase tracking-widest text-on-surface flex items-center gap-2">
            <span class="material-symbols-outlined text-base">domain</span> {{ $deptName }}
            <span class="text-on-surface-variant font-normal">({{ $people->count() }})</span>
        </h3>
        <div class="bg-white border border-outline-variant/20 rounded-sm divide-y divide-outline-variant/10">
            @foreach($people as $person)
            <div class="grid grid-cols-12 items-center px-4 py-3 hover:bg-slate-50 transition-colors">
                <div class="col-span-8">
                    <p class="text-sm font-semibold text-inverse-surface">{{ $person->name }}</p>
                    <p class="text-[10px] text-on-surface-variant uppercase">{{ $person->role }}</p>
                </div>
                <div class="col-span-2">
                    @if($processedIds->contains($person->id))
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-green-700">
                        <span class="material-symbols-outlined text-sm">check_circle</span> Entered
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-amber-600">
                        <span class="material-symbols-outlined text-sm">pending</span> Not entered
                    </span>
                    @endif
                </div>
                <div class="col-span-2 text-right">
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('daily-reports.create', ['person' => $person->id, 'date' => $date]) }}" class="inline-block px-3 py-1.5 rounded-sm bg-surface-container-high text-on-surface-variant text-[10px] font-bold uppercase tracking-wider hover:bg-primary hover:text-white transition-all">
                        {{ $processedIds->contains($person->id) ? 'Open' : 'Enter' }}
                    </a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @empty
    <div class="p-8 text-center text-on-surface-variant italic text-sm">No expected reporters found.</div>
    @endforelse>
</div>
@endsection
