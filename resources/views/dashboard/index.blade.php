@extends('layouts.app')

@section('title', 'Kaizen Tracker | Dashboard')

@section('content')
<div class="p-6 space-y-6">
    <!-- Title Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-200 pb-4 gap-4">
        <div>
            <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                <span>MANAJEMEN</span>
                <span>/</span>
                <span class="text-secondary">DASHBOARD</span>
            </div>
            <h2 class="text-xl font-bold tracking-tight text-slate-800 mt-1">Dashboard</h2>
            <p class="text-xs text-slate-400 mt-0.5">Ringkasan pekerjaan dan kepatuhan rencana</p>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">TOTAL PEKERJAAN</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($kpis['total']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-blue-500">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">BELUM SELESAI</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($kpis['unfinished']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-red-500">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">TERLAMBAT</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($kpis['overdue']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-green-500">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">SELESAI</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($kpis['completed']) }}</span>
        </div>
    </div>

    <!-- Workload Chart -->
    <div class="bg-white border border-slate-200 rounded-sm shadow-sm p-4 space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-[11px] font-bold uppercase tracking-widest text-slate-700 flex items-center gap-2">
                <span class="material-symbols-outlined text-base text-secondary">bar_chart</span>
                PEKERJAAN BELUM SELESAI PER PERSONEL
            </h3>
            <span class="text-[10px] font-semibold text-slate-400">{{ $rows->count() }} personel</span>
        </div>

        <div class="overflow-x-auto pb-2">
            <!-- Bars -->
            <div class="flex items-end gap-2 pb-1">
                @foreach($rows as $row)
                <div class="flex-none w-[36px] flex flex-col items-center justify-end" title="{{ $row['name'] }} — {{ $row['count'] }} pekerjaan belum selesai">
                    <span class="text-[9px] font-bold text-slate-600 tabular-nums leading-none mb-1">{{ $row['count'] }}</span>
                    <div class="w-4 rounded-t-sm {{ $row['count'] > 0 ? 'bg-secondary' : 'bg-slate-200' }}"
                         style="height: {{ $row['count'] > 0 ? max(6, (int) round($row['count'] / $maxCount * 160)) : 2 }}px;"></div>
                </div>
                @endforeach
            </div>

            <!-- Baseline -->
            <div class="border-t border-slate-300"></div>

            <!-- Labels -->
            <div class="flex gap-2">
                @foreach($rows as $row)
                <div class="flex-none w-[36px] flex items-start justify-center pt-2 h-32">
                    <span class="text-[9px] font-semibold text-slate-500 whitespace-nowrap" 
                          style="writing-mode: vertical-rl; transform: rotate(180deg);"
                          title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Daily Plan Compliance Matrix -->
    <div class="bg-white border border-slate-200 rounded-sm shadow-sm p-4 space-y-3">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h3 class="text-[11px] font-bold uppercase tracking-widest text-slate-700 flex items-center gap-2">
                <span class="material-symbols-outlined text-base text-secondary">fact_check</span>
                KEPATUHAN RENCANA HARIAN
            </h3>

            <div class="flex items-center gap-2 text-[10px] font-semibold">
                <a href="{{ route('dashboard.index', ['date' => $prevWeekDate]) }}" class="flex items-center gap-0.5 px-2 h-6 bg-white border border-slate-200 rounded text-slate-600 hover:bg-slate-50 transition-colors uppercase tracking-wide">
                    <span class="material-symbols-outlined text-[14px]">chevron_left</span> Minggu Sebelumnya
                </a>
                <span class="px-2 h-6 flex items-center bg-slate-50 border border-slate-200 rounded text-slate-500 tabular-nums">
                    {{ $weekStart->translatedFormat('j M') }} — {{ $weekEnd->translatedFormat('j M Y') }}
                </span>
                <a href="{{ route('dashboard.index', ['date' => $nextWeekDate]) }}" class="flex items-center gap-0.5 px-2 h-6 bg-white border border-slate-200 rounded text-slate-600 hover:bg-slate-50 transition-colors uppercase tracking-wide">
                    Minggu Berikutnya <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th class="text-left align-bottom">PERSONEL</th>
                        @foreach($days as $day)
                        <th class="text-center align-bottom {{ $day['isToday'] ? 'bg-blue-50' : '' }}">
                            <div class="leading-tight">{{ $day['weekday'] }}</div>
                            <div class="text-[9px] font-normal normal-case leading-tight">{{ $day['dayLabel'] }}</div>
                            <div class="text-[11px] leading-tight">{{ $day['percent'] }}%</div>
                            <div class="text-[8px] font-normal normal-case text-slate-400 leading-tight">{{ $day['submitted'] }}/{{ $day['total'] }}</div>
                            @if($day['isToday'])
                            <div class="text-[8px] font-bold text-secondary mt-0.5">HARI INI</div>
                            @endif
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($personnel as $person)
                    <tr>
                        <td class="font-semibold text-slate-700">{{ $person->name }}</td>
                        @foreach($days as $day)
                        <td class="text-center {{ $day['isToday'] ? 'bg-blue-50/50' : '' }}">
                            @if(isset($submittedByDate[$day['dateStr']][$person->id]))
                            <span class="material-symbols-outlined text-[16px] text-green-600" style="font-variation-settings: 'FILL' 1;">check_circle</span>
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

        @if($personnel->isNotEmpty())
            @if($missingToday->isNotEmpty())
            <div class="flex items-start gap-2 text-[10px] font-bold uppercase tracking-wide text-red-700 bg-red-50 border border-red-200 rounded-sm px-3 py-2">
                <span class="material-symbols-outlined text-sm">warning</span>
                <span>BELUM MENGUMPULKAN HARI INI ({{ $missingToday->count() }}): <span class="font-semibold">{{ $missingToday->implode(', ') }}</span></span>
            </div>
            @else
            <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wide text-green-700 bg-green-50 border border-green-200 rounded-sm px-3 py-2">
                <span class="material-symbols-outlined text-sm">check_circle</span>
                <span>SEMUA PERSONEL SUDAH MENGUMPULKAN RENCANA HARI INI</span>
            </div>
            @endif
        @endif
    </div>
</div>
@endsection
