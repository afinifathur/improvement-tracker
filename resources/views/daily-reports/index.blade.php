@extends('layouts.app')

@section('title', 'Kaizen Tracker | Pusat Kendali Harian')

@section('content')
<div class="p-6 max-w-6xl mx-auto w-full space-y-6">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1">
                <span>Harian</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-primary">Pusat Kendali</span>
            </nav>
            <h2 class="text-2xl font-bold tracking-tight text-inverse-surface">Laporan Harian</h2>
            <p class="text-sm text-on-surface-variant">{{ \Illuminate\Support\Carbon::parse($date)->format('l, d F Y') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if(auth()->user()->isAdmin())
                <a href="{{ route('daily-reports.create') }}"
                   class="bg-primary text-white px-4 py-1.5 rounded-sm text-xs font-bold uppercase tracking-wider hover:brightness-110 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">add</span> + LAPORAN HARIAN
                </a>
            @endif
            <form method="GET" action="{{ route('daily-reports.index') }}" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ $date }}" class="bg-white border border-outline-variant/30 rounded-sm text-sm py-1.5 px-3 focus:ring-1 focus:ring-primary">
                <button type="submit" class="bg-primary text-white px-4 py-1.5 rounded-sm text-xs font-bold uppercase tracking-wider hover:brightness-110">Cari</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white p-4 border border-outline-variant/20 rounded-sm">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Target Laporan</span>
            <span class="text-2xl font-bold text-inverse-surface">{{ $summary['expected'] }}</span>
        </div>
        <div class="bg-white p-4 border border-outline-variant/20 rounded-sm border-l-4 border-l-primary">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Sudah Lapor</span>
            <span class="text-2xl font-bold text-primary">{{ $summary['processed'] }}</span>
        </div>
        <div class="bg-white p-4 border border-outline-variant/20 rounded-sm">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Belum Lapor</span>
            <span class="text-2xl font-bold text-inverse-surface">{{ $summary['remaining'] }}</span>
        </div>
        <div class="bg-white p-4 border border-outline-variant/20 rounded-sm">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Pekerjaan Terbuka</span>
            <span class="text-2xl font-bold text-inverse-surface">{{ $summary['open'] }}</span>
        </div>
        <div class="bg-white p-4 border border-outline-variant/20 rounded-sm">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Terblokir</span>
            <span class="text-2xl font-bold text-amber-600">{{ $summary['blocked'] }}</span>
        </div>
        <div class="bg-white p-4 border border-outline-variant/20 rounded-sm">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Terlambat</span>
            <span class="text-2xl font-bold text-error">{{ $summary['overdue'] }}</span>
        </div>
    </div>

    @forelse($grouped as $deptName => $people)
    <section class="space-y-3">
        <h3 class="text-[11px] font-extrabold uppercase tracking-widest text-slate-500 flex items-center gap-1.5 border-b border-slate-200 pb-1.5">
            <span class="material-symbols-outlined text-[16px] text-slate-400">domain</span>
            <span>{{ $deptName }}</span>
            <span class="text-slate-400 font-semibold">({{ $people->count() }})</span>
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($people as $person)
            @php
                $personAreas = $person->areaAssignments->map(fn($a) => $a->area)->filter();
            @endphp
            <div class="bg-white border border-slate-200 rounded-sm p-3.5 flex flex-col justify-between hover:border-slate-300 hover:shadow-sm transition-all">
                <!-- Top: Person Info & Status -->
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <x-avatar class="w-6 h-6 rounded grayscale shrink-0" :name="$person->name ?? 'User'" background="f1f5f9" color="64748b"/>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-800 uppercase tracking-tight truncate" title="{{ $person->name }}">{{ $person->name }}</p>
                                <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">{{ strtoupper($person->role) }}</p>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="shrink-0">
                            @if($processedIds->contains($person->id))
                                <span class="inline-flex items-center gap-1 text-[9px] font-extrabold uppercase tracking-wide text-emerald-700 bg-emerald-50 border border-emerald-200/60 px-1.5 py-0.5 rounded">
                                    <span class="material-symbols-outlined text-[11px]">check_circle</span> Sudah Isi
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-[9px] font-extrabold uppercase tracking-wide text-amber-700 bg-amber-50 border border-amber-200/60 px-1.5 py-0.5 rounded">
                                    <span class="material-symbols-outlined text-[11px]">pending</span> Belum Isi
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Area Info -->
                    <div class="mt-2 text-[10px] text-slate-500">
                        @if($personAreas->isEmpty())
                            <span class="text-amber-600 font-medium italic">— Belum ada area aktif</span>
                        @else
                            <span class="font-medium text-slate-600 uppercase tracking-wide truncate block" title="{{ $personAreas->pluck('name')->implode(', ') }}">
                                <span class="text-slate-400 font-normal">Area:</span> {{ $personAreas->pluck('name')->implode(', ') }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Bottom: Rencana Count & Action Button -->
                <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 text-[11px]">
                        <span class="text-slate-400 font-medium text-[10px] uppercase tracking-wider">Rencana</span>
                        <span class="font-bold tabular-nums text-xs {{ ($planCounts[$person->id] ?? 0) > 0 ? 'text-slate-800' : 'text-slate-400' }}">
                            {{ $planCounts[$person->id] ?? 0 }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-1">
                        @if(auth()->user()->isAdmin())
                            @if($personAreas->isEmpty())
                                <span class="text-[10px] text-slate-400 italic">Tanpa area</span>
                            @else
                                @foreach($personAreas as $area)
                                    <a href="{{ route('daily-reports.create', ['person' => $person->id, 'date' => $date, 'area_id' => $area->id]) }}"
                                       class="inline-flex items-center gap-0.5 px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-wider transition-colors shadow-sm {{ $processedIds->contains($person->id) ? 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 hover:border-slate-300' : 'bg-primary text-white hover:bg-primary/95' }}">
                                        {{ $processedIds->contains($person->id) ? 'Buka' : 'Isi' }}
                                        @if($personAreas->count() > 1)
                                            <span class="font-semibold text-[9px] opacity-80 ml-0.5">({{ $area->name }})</span>
                                        @endif
                                    </a>
                                @endforeach
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @empty
    <div class="p-8 text-center text-slate-400 italic text-sm">Tidak ada pelapor yang diharapkan.</div>
    @endforelse
</div>
@endsection
