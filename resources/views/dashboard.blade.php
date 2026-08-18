@extends('layouts.app')

@section('title', 'Kaizen Tracker | Dasbor')

@section('content')
<div class="p-8 space-y-8">
    <div class="flex items-end justify-between border-b border-slate-100 pb-4">
        <h2 class="text-xl font-bold tracking-tight text-inverse-surface">Dasbor Kinerja</h2>
        <div class="text-right">
            <p class="text-[11px] font-bold text-on-surface-variant uppercase">W{{ now()->format('W') }}: {{ now()->startOfWeek()->format('M d') }}-{{ now()->endOfWeek()->format('d') }}</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-4 text-[11px]">
        <div class="flex items-center gap-2 px-3 py-1.5 bg-red-50 text-red-700 border border-red-100 font-bold uppercase tracking-tight">
            <span class="material-symbols-outlined text-base">report</span>
            Peringatan: {{ $incompleteCount ?? 0 }} rencana belum lengkap terdeteksi
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 text-slate-500 border border-slate-200 font-medium italic">
            <span class="material-symbols-outlined text-base">event_busy</span>
            Sinkron Terakhir: {{ now()->format('H:i:s') }}
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 border border-slate-100">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Total Rencana</span>
            <span class="text-3xl font-bold tracking-tighter text-inverse-surface">{{ number_format($totalPlans ?? 0) }}</span>
        </div>
        <div class="bg-white p-5 border border-slate-100">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Selesai</span>
            <span class="text-3xl font-bold tracking-tighter text-inverse-surface">{{ number_format($completedPlans ?? 0) }}</span>
        </div>
        <div class="bg-white p-5 border border-slate-100">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Persentase %</span>
            <span class="text-3xl font-bold tracking-tighter text-inverse-surface">{{ $completionRate ?? 0 }}%</span>
        </div>
        <div class="bg-white p-5 border border-slate-100 border-l-4 border-l-primary">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Skor Rata-rata</span>
            <span class="text-3xl font-bold tracking-tighter text-inverse-surface">{{ number_format($avgScore ?? 0, 1) }}</span>
        </div>
    </div>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-[11px] font-bold uppercase tracking-widest text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-base">fact_check</span>
                Matriks Kinerja
            </h3>
            <button class="text-[10px] font-bold text-primary hover:underline">EKSPOR DATA</button>
        </div>
        <div class="bg-white border border-slate-200">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-3 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">SPV / Dept</th>
                        <th class="px-6 py-3 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant text-center w-24">Rencana</th>
                        <th class="px-6 py-3 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant text-center w-24">Selesai</th>
                        <th class="px-6 py-3 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant text-center w-32">Status</th>
                        <th class="px-6 py-3 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant text-right w-24">Skor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($performanceData ?? [] as $data)
                    <tr class="row-interactive transition-colors">
                        <td class="px-6 py-3">
                            <p class="text-sm font-bold text-inverse-surface">{{ $data->user->name }}</p>
                            <p class="text-[10px] text-on-surface-variant uppercase">{{ $data->user->department_name }}</p>
                        </td>
                        <td class="px-6 py-3 text-center text-sm font-medium">{{ $data->total }}</td>
                        <td class="px-6 py-3 text-center text-sm font-medium">{{ $data->completed }}</td>
                        <td class="px-6 py-3 text-center">
                            <div class="flex items-center justify-center">
                                <span class="w-2 h-2 rounded-full {{ $data->rate >= 80 ? 'bg-green-500' : ($data->rate >= 50 ? 'bg-yellow-400' : 'bg-red-500') }} mr-2"></span>
                                <span class="text-[11px] font-bold {{ $data->rate >= 80 ? 'text-green-700' : ($data->rate >= 50 ? 'text-yellow-700' : 'text-red-700') }}">{{ $data->rate }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-right text-sm font-bold">{{ number_format($data->avg_score, 1) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-on-surface-variant italic text-sm">Tidak ada data kinerja untuk minggu ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
        <div class="space-y-4">
            <h3 class="text-[11px] font-bold uppercase tracking-widest text-on-surface">Sorotan Mingguan</h3>
            @if($topPerformer ?? null)
            <div class="bg-white p-4 border border-slate-100 flex items-center gap-4">
                <img alt="{{ $topPerformer->name }}" class="w-12 h-12 rounded-sm object-cover grayscale" src="https://ui-avatars.com/api/?name={{ urlencode($topPerformer->name) }}&background=005db6&color=fff"/>
                <div class="flex-1">
                    <p class="text-sm font-bold">{{ $topPerformer->name }}</p>
                    <p class="text-[10px] text-on-surface-variant uppercase tracking-tighter">{{ $topPerformer->department_name }} • Peringkat #1</p>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold text-primary">{{ number_format($topPerformer->avg_score, 1) }}</p>
                    <p class="text-[9px] font-bold uppercase">Skor</p>
                </div>
            </div>
            @else
            <div class="bg-white p-4 border border-slate-100 text-center text-[10px] uppercase font-bold text-slate-400">
                Menentukan Performer Terbaik...
            </div>
            @endif
        </div>
        <div class="flex flex-col gap-1 text-[10px] font-bold text-on-surface-variant uppercase text-right self-end pb-1">
            <span>Audit {{ now()->format('H:i:s') }}</span>
            <span class="text-primary flex items-center justify-end gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span> LIVE
            </span>
        </div>
    </div>
</div>
@endsection
