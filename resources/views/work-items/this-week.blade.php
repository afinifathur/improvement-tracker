@extends('layouts.app')

@section('title', 'Kaizen Tracker | Minggu Ini')

@section('content')
@php
    $carbonDate = \Carbon\Carbon::parse($date);
    $prevWeekDate = $carbonDate->copy()->subWeek()->toDateString();
    $nextWeekDate = $carbonDate->copy()->addWeek()->toDateString();
    $formattedStart = strtoupper(\Carbon\Carbon::parse($weekStart)->format('d M Y'));
    $formattedEnd = strtoupper(\Carbon\Carbon::parse($weekEnd)->format('d M Y'));
@endphp
<div class="p-6 space-y-6">
    <!-- Title & Week Navigation Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-200 pb-4 gap-4">
        <div>
            <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                <span>TAMPILAN MINGGUAN</span>
                <span>/</span>
                <span class="text-secondary">MINGGU INI</span>
            </div>
            <div class="flex items-center gap-3 mt-1">
                <h2 class="text-xl font-extrabold tracking-tight text-slate-800">
                    MINGGU {{ $weekNumber }}
                </h2>
                <span class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded text-[10px] font-semibold text-slate-500">
                    {{ $formattedStart }} — {{ $formattedEnd }}
                </span>
            </div>
        </div>

        <!-- Week Controls Form -->
        <form method="GET" action="{{ route('work-items.this-week') }}" id="date-nav-form" class="flex items-center gap-1.5">
            <!-- Retain current filters during date change -->
            @foreach(request()->except(['date', 'page']) as $key => $val)
                <input type="hidden" name="{{ $key }}" value="{{ $val }}"/>
            @endforeach

            <a href="{{ route('work-items.this-week', array_merge(request()->query(), ['date' => $prevWeekDate])) }}" class="flex items-center justify-center px-2 h-7 bg-white border border-slate-200 rounded text-[11px] font-bold text-slate-600 hover:bg-slate-50 transition-colors uppercase tracking-wider gap-1">
                <span class="material-symbols-outlined text-[16px]">chevron_left</span> Minggu Lalu
            </a>
            <a href="{{ route('work-items.this-week', array_merge(request()->query(), ['date' => \Carbon\Carbon::today()->toDateString()])) }}" class="px-2.5 h-7 flex items-center justify-center bg-white border border-slate-200 rounded text-[11px] font-bold text-slate-600 hover:bg-slate-50 transition-colors uppercase tracking-wider">
                Minggu Ini
            </a>
            <a href="{{ route('work-items.this-week', array_merge(request()->query(), ['date' => $nextWeekDate])) }}" class="flex items-center justify-center px-2 h-7 bg-white border border-slate-200 rounded text-[11px] font-bold text-slate-600 hover:bg-slate-50 transition-colors uppercase tracking-wider gap-1">
                Minggu Depan <span class="material-symbols-outlined text-[16px]">chevron_right</span>
            </a>
            <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="h-7 px-2 border border-slate-200 rounded text-[11px] font-medium text-slate-600 outline-none focus:border-secondary focus:ring-0 cursor-pointer"/>
        </form>
    </div>

    <!-- Top Metrics Row -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">DIRENCANAKAN</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['planned']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">BERJALAN</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['in_progress']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-amber-500">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">TERBLOKIR</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['blocked']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-red-500">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">TERLAMBAT</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['overdue']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-green-500">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">SELESAI</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['completed']) }}</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('work-items.this-week') }}" class="filter-bar flex-wrap gap-3">
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
            <option value="">Status Aktif</option>
            <option value="not_started" {{ request('status') === 'not_started' ? 'selected' : '' }}>Belum Mulai</option>
            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>Berjalan</option>
            <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Terblokir</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
        </select>

        <!-- Reset Button -->
        @if(request()->anyFilled(['search', 'department_id', 'area_id', 'owner_id', 'status']))
            <a href="{{ route('work-items.this-week', ['date' => $date]) }}" class="text-[10px] font-bold text-red-500 hover:text-red-700 flex items-center gap-1 uppercase tracking-wider ml-auto">
                <span class="material-symbols-outlined text-[14px]">close</span> Hapus Filter
            </a>
        @endif
    </form>

    <!-- PRIMARY SECTION: RENCANA MINGGUAN (KOMITMEN) -->
    <div class="space-y-4">
        <h3 class="text-[11px] font-extrabold uppercase tracking-widest text-slate-500 border-b border-slate-200 pb-2">
            RENCANA MINGGUAN (KOMITMEN)
        </h3>

        @if($weeklyPlans->isEmpty())
            <div class="text-center py-6 bg-white border border-slate-200 rounded-sm">
                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Tidak ada rencana mingguan untuk minggu ini</p>
            </div>
        @else
            <!-- Weekly Plans Overview Table -->
            <div class="table-container bg-white border border-slate-200 rounded-sm shadow-sm overflow-x-auto">
                <table class="table-dense w-full">
                    <thead>
                        <tr>
                            <th class="w-8 text-center whitespace-nowrap">#</th>
                            <th class="w-36 whitespace-nowrap">PENANGGUNG JAWAB</th>
                            <th class="w-28 whitespace-nowrap">DEPARTEMEN</th>
                            <th class="w-24 whitespace-nowrap">JENIS</th>
                            <th class="min-w-[160px] max-w-[260px]">SASARAN / RENCANA</th>
                            <th class="min-w-[220px] max-w-[340px]">TARGET</th>
                            <th class="w-28 text-center whitespace-nowrap">PROGRESS</th>
                            <th class="w-28 text-center whitespace-nowrap">STATUS</th>
                            <th class="w-20 text-center whitespace-nowrap">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($weeklyPlans as $index => $plan)
                            @php
                                $planItems = $linkedItemsGrouped->get($plan->id) ?? collect();
                                $totalCount = $planItems->count();
                                $completedCount = $planItems->where('status.value', 'completed')->count();
                                $pct = $totalCount > 0 ? (int) round(($completedCount / $totalCount) * 100) : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="text-center font-bold text-slate-400 text-[11px] whitespace-nowrap">{{ $index + 1 }}</td>
                                <td class="whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <x-avatar class="w-4 h-4 rounded grayscale" :name="$plan->user->name ?? 'User'" background="f1f5f9" color="64748b"/>
                                        <span class="font-bold text-slate-800 text-xs">{{ $plan->user->name }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap">
                                    <span class="font-semibold text-slate-600 text-[11px]">{{ $plan->user->department->name ?? 'N/A' }}</span>
                                </td>
                                <td class="whitespace-nowrap">
                                    @if($plan->category === 'improvement')
                                        <span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200/60 rounded text-[9px] font-extrabold uppercase tracking-wide">Improvement</span>
                                    @elseif($plan->category === 'problem')
                                        <span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200/60 rounded text-[9px] font-extrabold uppercase tracking-wide">Problem</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 border border-slate-200 rounded text-[9px] font-extrabold uppercase tracking-wide">{{ ucfirst($plan->category) }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-normal break-words">
                                    <p class="font-bold text-slate-800 text-xs tracking-tight uppercase leading-snug whitespace-normal break-words">{{ $plan->title }}</p>
                                </td>
                                <td class="whitespace-normal break-words">
                                    <div class="text-slate-600 text-[11px] font-medium leading-relaxed whitespace-normal break-words">
                                        {{ $plan->expected_output ?? '-' }}
                                    </div>
                                </td>
                                <td class="text-center whitespace-nowrap">
                                    <div class="flex flex-col items-center gap-1 w-24 mx-auto">
                                        <div class="flex items-center justify-between w-full text-[10px] font-bold text-slate-700">
                                            <span class="tabular-nums text-slate-600">{{ $completedCount }} / {{ $totalCount }}</span>
                                            <span class="tabular-nums {{ $pct === 100 ? 'text-emerald-600 font-extrabold' : 'text-slate-600' }}">{{ $pct }}%</span>
                                        </div>
                                        <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-full transition-all duration-300 {{ $pct === 100 ? 'bg-emerald-600' : ($pct > 0 ? 'bg-blue-600' : 'bg-slate-300') }}" style="width: {{ $pct }}%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center whitespace-nowrap">
                                    @if($plan->status === 'planned')
                                        <span class="badge-status bg-slate-100 text-slate-600 border border-slate-200/50">Direncanakan</span>
                                    @elseif($plan->status === 'completed')
                                        <span class="badge-status badge-completed">Selesai</span>
                                    @elseif($plan->status === 'completed_no_impact')
                                        <span class="badge-status bg-emerald-50 text-emerald-600 border border-emerald-200/50">Selesai Tanpa Dampak</span>
                                    @elseif($plan->status === 'not_completed')
                                        <span class="badge-status badge-cancelled">Gagal</span>
                                    @elseif($plan->status === 'extended')
                                        <span class="badge-status badge-in-progress">Diperpanjang</span>
                                    @endif
                                </td>
                                <td class="text-center whitespace-nowrap">
                                    <button type="button" onclick="openPlanDetail({{ $plan->id }})" class="inline-flex items-center justify-center gap-1 px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-200 hover:border-slate-300 rounded text-[10px] font-bold text-slate-700 uppercase tracking-wider transition-colors shadow-sm whitespace-nowrap shrink-0">
                                        <span class="material-symbols-outlined text-[13px] text-slate-500">visibility</span> DETAIL
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Detail Modals for each Weekly Plan -->
            @foreach($weeklyPlans as $plan)
                @php
                    $planItems = $linkedItemsGrouped->get($plan->id) ?? collect();
                    $totalCount = $planItems->count();
                    $completedCount = $planItems->where('status.value', 'completed')->count();
                    $pct = $totalCount > 0 ? (int) round(($completedCount / $totalCount) * 100) : 0;
                @endphp
                <div id="plan-detail-modal-{{ $plan->id }}" class="plan-detail-modal fixed inset-0 z-[90] hidden flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 overflow-y-auto" onclick="handleBackdropClick(event, {{ $plan->id }})">
                    <div class="bg-white w-full max-w-2xl rounded-sm shadow-2xl overflow-hidden border border-slate-200 my-8 flex flex-col max-h-[90vh]" onclick="event.stopPropagation()">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                            <div class="flex items-center gap-3">
                                <x-avatar class="w-6 h-6 rounded grayscale" :name="$plan->user->name ?? 'User'" background="f1f5f9" color="64748b"/>
                                <div>
                                    <span class="text-xs font-bold text-slate-800 uppercase tracking-wide">{{ $plan->user->name }}</span>
                                    <span class="text-slate-400 text-xs font-medium">&mdash; {{ $plan->user->department->name ?? 'N/A' }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($plan->status === 'planned')
                                    <span class="badge-status bg-slate-100 text-slate-600 border border-slate-200/50">Direncanakan</span>
                                @elseif($plan->status === 'completed')
                                    <span class="badge-status badge-completed">Selesai</span>
                                @elseif($plan->status === 'completed_no_impact')
                                    <span class="badge-status bg-emerald-50 text-emerald-600 border border-emerald-200/50">Selesai Tanpa Dampak</span>
                                @elseif($plan->status === 'not_completed')
                                    <span class="badge-status badge-cancelled">Gagal</span>
                                @elseif($plan->status === 'extended')
                                    <span class="badge-status badge-in-progress">Diperpanjang</span>
                                @endif
                                <button type="button" onclick="closePlanDetail({{ $plan->id }})" class="text-slate-400 hover:text-slate-600 p-1 rounded hover:bg-slate-100 transition-colors flex items-center justify-center" title="Tutup">
                                    <span class="material-symbols-outlined text-[20px]">close</span>
                                </button>
                            </div>
                        </div>

                        <!-- Modal Body (Scrollable) -->
                        <div class="p-6 space-y-5 overflow-y-auto flex-1">
                            <!-- Title & Classification -->
                            <div>
                                <h4 class="text-base font-extrabold text-slate-800 tracking-tight uppercase">{{ $plan->title }}</h4>
                                <p class="text-xs text-slate-400 mt-1 uppercase tracking-wide font-semibold">
                                    {{ ucfirst($plan->category) }} &middot; 
                                    {{ $plan->impact_level === 'low' ? 'Rendah' : ($plan->impact_level === 'medium' ? 'Sedang' : 'Tinggi') }} &middot; 
                                    Minggu {{ \Carbon\Carbon::parse($plan->week_start_date)->isoWeek() }}
                                </p>
                                @if($plan->expected_output)
                                    <div class="mt-2.5 text-xs text-slate-600 bg-slate-50 p-3 rounded border border-slate-100 leading-relaxed whitespace-normal break-words">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Target Sasaran</span>
                                        {{ $plan->expected_output }}
                                    </div>
                                @endif
                            </div>

                            <!-- Progress Information -->
                            <div class="bg-slate-50 border border-slate-100 rounded-sm p-3.5 space-y-2">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-500 font-semibold uppercase tracking-wider text-[10px]">Progress</span>
                                    <span class="font-bold text-slate-800 tabular-nums">{{ $completedCount }} / {{ $totalCount }} pekerjaan selesai ({{ $pct }}%)</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                    <div class="h-full transition-all duration-300 {{ $pct === 100 ? 'bg-emerald-600' : ($pct > 0 ? 'bg-blue-600' : 'bg-slate-300') }}" style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>

                            <!-- Linked Daily WorkItems -->
                            <div class="space-y-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">RIWAYAT PEKERJAAN HARIAN</span>
                                @if($planItems->isEmpty())
                                    <div class="bg-slate-50 border border-dashed border-slate-200 rounded p-4 text-center">
                                        <p class="text-xs text-slate-400 italic">BELUM ADA PEKERJAAN HARIAN YANG DITAUTKAN KE RENCANA INI</p>
                                    </div>
                                @else
                                    <div class="border border-slate-200 rounded-sm divide-y divide-slate-100 bg-white">
                                        @foreach($planItems as $item)
                                            <div class="flex items-center text-xs justify-between p-2.5 px-3 hover:bg-slate-50/50 transition-colors">
                                                <div class="flex items-center gap-3">
                                                    <span class="text-slate-400 font-mono text-[11px] whitespace-nowrap">{{ $item->planned_start_date ? \Carbon\Carbon::parse($item->planned_start_date)->format('d M') : '-' }}</span>
                                                    <span class="text-slate-300">|</span>
                                                    <span class="font-bold text-slate-700">{{ $item->title }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="badge-status
                                                        @if($item->status->value === 'completed') badge-completed
                                                        @elseif($item->status->value === 'cancelled') badge-cancelled
                                                        @elseif($item->status->value === 'blocked') badge-blocked
                                                        @elseif($item->status->value === 'in_progress') badge-in-progress
                                                        @else badge-not-started
                                                        @endif text-[9px] font-bold">
                                                        {{ $item->status->value === 'not_started' ? 'Belum Mulai' : ($item->status->value === 'in_progress' ? 'Berjalan' : ($item->status->value === 'blocked' ? 'Terblokir' : ($item->status->value === 'completed' ? 'Selesai' : 'Dibatalkan'))) }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Modal Footer: Evaluation Actions (Only visible/actionable for Admin) -->
                        @if(auth()->user()->isAdmin())
                            <div class="p-4 px-6 border-t border-slate-100 bg-slate-50/60 space-y-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">EVALUASI</span>
                                
                                <div class="flex items-center gap-2 flex-wrap" id="eval-badge-container-{{ $plan->id }}">
                                    @if($plan->status !== 'planned')
                                        <button type="button" onclick="toggleEvalButtons({{ $plan->id }})" class="text-[10px] font-bold text-primary uppercase hover:underline flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs">edit</span> Ubah Evaluasi
                                        </button>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 flex-wrap {{ $plan->status !== 'planned' ? 'hidden' : '' }}" id="eval-buttons-container-{{ $plan->id }}">
                                    <button type="button" onclick="openEvaluationModal({{ $plan->id }}, 'completed')" class="px-3 py-1.5 rounded-sm bg-primary text-white text-[10px] font-bold uppercase tracking-wider hover:bg-primary/95 transition-all">Selesai</button>
                                    <button type="button" onclick="openEvaluationModal({{ $plan->id }}, 'completed_no_impact')" class="px-3 py-1.5 rounded-sm bg-slate-100 border border-slate-200 text-slate-600 text-[10px] font-bold uppercase tracking-wider hover:bg-slate-200 transition-all">Selesai Tanpa Dampak</button>
                                    <button type="button" onclick="updateStatus({{ $plan->id }}, 'not_completed')" class="px-3 py-1.5 rounded-sm bg-red-50 border border-red-200 text-red-600 text-[10px] font-bold uppercase tracking-wider hover:bg-red-100 transition-all">Gagal</button>
                                    <button type="button" onclick="openExtensionModal({{ $plan->id }}, '{{ $plan->week_end_date ? \Carbon\Carbon::parse($plan->week_end_date)->toDateString() : '' }}')" class="px-3 py-1.5 rounded-sm bg-amber-50 border border-amber-200 text-amber-600 text-[10px] font-bold uppercase tracking-wider hover:bg-amber-100 transition-all">Perpanjang</button>
                                    @if($plan->status !== 'planned')
                                        <button type="button" onclick="toggleEvalButtons({{ $plan->id }})" class="px-3 py-1.5 rounded-sm bg-slate-50 border border-slate-200 text-slate-400 text-[10px] font-bold uppercase tracking-wider hover:bg-slate-100 transition-all">Batal</button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- SECONDARY SECTION: PEKERJAAN DI LUAR RENCANA MINGGUAN -->
    <div class="space-y-4 pt-4">
        <h3 class="text-[11px] font-extrabold uppercase tracking-widest text-slate-500 border-b border-slate-200 pb-2">
            PEKERJAAN DI LUAR RENCANA MINGGUAN
        </h3>

        <div class="space-y-4">
            @php
                $hasIndependent = false;
            @endphp

            @foreach($areas as $area)
                @if($independentGrouped->has($area->id))
                    @php
                        $hasIndependent = true;
                        $areaItems = $independentGrouped->get($area->id);
                    @endphp
                    <div>
                        <!-- Area Header Toggle -->
                        <div class="flex items-center justify-between bg-slate-50 border border-slate-200 px-4 py-2 select-none cursor-pointer hover:bg-slate-100/70 transition-colors rounded-t-sm" onclick="toggleArea('area-{{ $area->id }}')">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[16px] text-slate-500 transition-transform" id="icon-area-{{ $area->id }}">expand_more</span>
                                <span class="text-xs font-bold text-slate-700 uppercase tracking-wide">{{ $area->name }}</span>
                                <span class="px-1.5 py-0.5 bg-slate-200/80 rounded text-[9px] font-bold text-slate-600">{{ $area->code }}</span>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $areaItems->count() }} pekerjaan</span>
                        </div>

                        <!-- Table Container -->
                        <div id="area-{{ $area->id }}" class="table-container rounded-t-none">
                            <table class="table-dense">
                                <thead>
                                    <tr>
                                        <th class="w-24">STATUS</th>
                                        <th>PEKERJAAN</th>
                                        <th class="w-32">KLASIFIKASI</th>
                                        <th class="w-40">PENANGGUNG JAWAB</th>
                                        <th class="w-40">DEPARTEMEN</th>
                                        <th class="w-24">MULAI</th>
                                        <th class="w-24">BATAS WAKTU</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($areaItems as $item)
                                        <tr>
                                            <td>
                                                @if($item->status->value === 'not_started')
                                                    <span class="badge-status badge-not-started">Belum Mulai</span>
                                                @elseif($item->status->value === 'in_progress')
                                                    <span class="badge-status badge-in-progress">Berjalan</span>
                                                @elseif($item->status->value === 'blocked')
                                                    <span class="badge-status badge-blocked">Terblokir</span>
                                                @elseif($item->status->value === 'completed')
                                                    <span class="badge-status badge-completed">Selesai</span>
                                                @elseif($item->status->value === 'cancelled')
                                                    <span class="badge-status badge-cancelled">Dibatalkan</span>
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
                                                    <span class="px-1.5 py-0.5 bg-red-50 text-red-700 border border-red-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">TERLAMBAT</span>
                                                @elseif($item->classification === 'current')
                                                    <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">SAAT INI</span>
                                                @elseif($item->classification === 'upcoming')
                                                    <span class="px-1.5 py-0.5 bg-slate-50 text-slate-500 border border-slate-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">MENDATANG</span>
                                                @elseif($item->classification === 'completed')
                                                    <span class="px-1.5 py-0.5 bg-green-50 text-green-700 border border-green-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">SELESAI</span>
                                                @elseif($item->classification === 'blocked')
                                                    <span class="px-1.5 py-0.5 bg-amber-50 text-amber-700 border border-amber-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">TERBLOKIR</span>
                                                @else
                                                    <span class="px-1.5 py-0.5 bg-slate-100 text-slate-400 border border-slate-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">DIBATALKAN</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <x-avatar class="w-4 h-4 rounded grayscale" :name="$item->owner->name ?? 'User'" background="f1f5f9" color="64748b"/>
                                                    <span class="font-semibold text-slate-700">{{ $item->owner->name ?? 'Belum Ditentukan' }}</span>
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
            @if($independentGrouped->has(null) || $independentGrouped->has(''))
                @php
                    $hasIndependent = true;
                    $unassignedItems = $independentGrouped->get(null) ?? $independentGrouped->get('');
                @endphp
                <div>
                    <!-- Unassigned Header Toggle -->
                    <div class="flex items-center justify-between bg-slate-50 border border-slate-200 px-4 py-2 select-none cursor-pointer hover:bg-slate-100/70 transition-colors rounded-t-sm" onclick="toggleArea('area-unassigned')">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-slate-500 transition-transform" id="icon-area-unassigned">expand_more</span>
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wide">Area Belum Ditentukan</span>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $unassignedItems->count() }} pekerjaan</span>
                    </div>

                    <!-- Table Container -->
                    <div id="area-unassigned" class="table-container rounded-t-none">
                        <table class="table-dense">
                            <thead>
                                <tr>
                                    <th class="w-24">STATUS</th>
                                    <th>PEKERJAAN</th>
                                    <th class="w-32">KLASIFIKASI</th>
                                    <th class="w-40">PENANGGUNG JAWAB</th>
                                    <th class="w-40">DEPARTEMEN</th>
                                    <th class="w-24">MULAI</th>
                                    <th class="w-24">BATAS WAKTU</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($unassignedItems as $item)
                                    <tr>
                                        <td>
                                            @if($item->status->value === 'not_started')
                                                <span class="badge-status badge-not-started">Belum Mulai</span>
                                            @elseif($item->status->value === 'in_progress')
                                                <span class="badge-status badge-in-progress">Berjalan</span>
                                            @elseif($item->status->value === 'blocked')
                                                <span class="badge-status badge-blocked">Terblokir</span>
                                            @elseif($item->status->value === 'completed')
                                                <span class="badge-status badge-completed">Selesai</span>
                                            @elseif($item->status->value === 'cancelled')
                                                <span class="badge-status badge-cancelled">Dibatalkan</span>
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
                                                <span class="px-1.5 py-0.5 bg-red-50 text-red-700 border border-red-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">TERLAMBAT</span>
                                            @elseif($item->classification === 'current')
                                                <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">SAAT INI</span>
                                            @elseif($item->classification === 'upcoming')
                                                <span class="px-1.5 py-0.5 bg-slate-50 text-slate-500 border border-slate-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">MENDATANG</span>
                                            @elseif($item->classification === 'completed')
                                                <span class="px-1.5 py-0.5 bg-green-50 text-green-700 border border-green-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">SELESAI</span>
                                            @elseif($item->classification === 'blocked')
                                                <span class="px-1.5 py-0.5 bg-amber-50 text-amber-700 border border-amber-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">TERBLOKIR</span>
                                            @else
                                                <span class="px-1.5 py-0.5 bg-slate-100 text-slate-400 border border-slate-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">DIBATALKAN</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <x-avatar class="w-4 h-4 rounded grayscale" :name="$item->owner->name ?? 'User'" background="f1f5f9" color="64748b"/>
                                                <span class="font-semibold text-slate-700">{{ $item->owner->name ?? 'Belum Ditentukan' }}</span>
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

            @if(!$hasIndependent)
                <div class="text-center py-8 bg-white border border-slate-200 rounded-sm">
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Tidak ada pekerjaan di luar rencana mingguan untuk minggu ini</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Evaluation Modal (Selesai / Selesai Tanpa Dampak) -->
<div id="evaluation-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-lg rounded-sm shadow-2xl overflow-hidden border border-slate-200">
        <form id="evaluation-form" onsubmit="event.preventDefault(); submitEvaluation();" enctype="multipart/form-data">
            <input type="hidden" name="plan_id" id="eval-plan-id">
            <input type="hidden" name="status" id="eval-status">
            <div class="p-6 space-y-4">
                <h3 class="text-base font-extrabold text-slate-800 uppercase tracking-tight" id="eval-modal-title">Evaluasi Rencana Mingguan</h3>
                
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Catatan Penutupan (Opsional)</label>
                    <textarea name="notes" id="eval-notes" class="w-full bg-white border border-slate-200 rounded text-xs p-2 h-24 resize-none focus:border-primary focus:ring-0" placeholder="Pelajaran yang dipetik..."></textarea>
                </div>
                
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Bukti Pekerjaan (Opsional)</label>
                    <input type="file" name="proofs[]" multiple class="block w-full text-xs text-slate-500 file:mr-4 file:py-1.5 file:px-3 file:rounded file:border file:border-slate-200 file:text-xs file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100"/>
                </div>
            </div>
            <div class="flex border-t border-slate-100 text-xs">
                <button type="button" onclick="closeEvaluationModal()" class="flex-1 px-4 py-3 font-bold uppercase tracking-widest text-slate-500 hover:bg-slate-50 border-r border-slate-100 transition-colors">Batal</button>
                <button type="submit" class="flex-1 bg-primary text-white px-4 py-3 font-bold uppercase tracking-widest hover:bg-primary/95 transition-colors">Simpan Evaluasi</button>
            </div>
        </form>
    </div>
</div>

<!-- Extension Modal (Perpanjang) -->
<div id="extension-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-lg rounded-sm shadow-2xl overflow-hidden border border-slate-200">
        <form id="extension-form" onsubmit="event.preventDefault(); submitExtension();">
            <input type="hidden" name="plan_id" id="ext-plan-id">
            <div class="p-6 space-y-4">
                <h3 class="text-base font-extrabold text-slate-800 uppercase tracking-tight">Perpanjang Rencana Mingguan</h3>
                
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Tanggal Selesai Baru <span class="text-red-500">*</span></label>
                    <input type="date" name="week_end_date" id="ext-week-end-date" required class="w-full bg-white border border-slate-200 rounded text-xs p-2 focus:border-primary focus:ring-0"/>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Alasan Perpanjangan <span class="text-red-500">*</span></label>
                    <textarea name="notes" id="ext-notes" required class="w-full bg-white border border-slate-200 rounded text-xs p-2 h-24 resize-none focus:border-primary focus:ring-0" placeholder="Sebutkan alasan perpanjangan rencana..."></textarea>
                </div>
            </div>
            <div class="flex border-t border-slate-100 text-xs">
                <button type="button" onclick="closeExtensionModal()" class="flex-1 px-4 py-3 font-bold uppercase tracking-widest text-slate-500 hover:bg-slate-50 border-r border-slate-100 transition-colors">Batal</button>
                <button type="submit" class="flex-1 bg-primary text-white px-4 py-3 font-bold uppercase tracking-widest hover:bg-primary/95 transition-colors">Perpanjang Rencana</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openPlanDetail(id) {
        const modal = document.getElementById('plan-detail-modal-' + id);
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function closePlanDetail(id) {
        const modal = document.getElementById('plan-detail-modal-' + id);
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    function handleBackdropClick(event, id) {
        if (event.target === event.currentTarget) {
            closePlanDetail(id);
        }
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.plan-detail-modal').forEach(function(modal) {
                modal.classList.add('hidden');
            });
            closeEvaluationModal();
            closeExtensionModal();
            document.body.style.overflow = '';
        }
    });

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

    function toggleEvalButtons(planId) {
        const buttons = document.getElementById('eval-buttons-container-' + planId);
        const badge = document.getElementById('eval-badge-container-' + planId);
        if (buttons) {
            buttons.classList.toggle('hidden');
        }
        if (badge) {
            badge.classList.toggle('hidden');
        }
    }

    function openEvaluationModal(planId, status) {
        document.getElementById('eval-plan-id').value = planId;
        document.getElementById('eval-status').value = status;
        document.getElementById('eval-notes').value = '';
        
        const titleText = status === 'completed' ? 'Evaluasi Rencana: Selesai' : 'Evaluasi Rencana: Selesai Tanpa Dampak';
        document.getElementById('eval-modal-title').textContent = titleText;
        
        document.getElementById('evaluation-modal').classList.remove('hidden');
    }

    function closeEvaluationModal() {
        document.getElementById('evaluation-modal').classList.add('hidden');
    }

    function submitEvaluation() {
        const form = document.getElementById('evaluation-form');
        const formData = new FormData(form);
        const id = formData.get('plan_id');

        fetch(`/api/weekly-plans/${id}/status`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-HTTP-Method-Override': 'PATCH'
            }
        }).then(res => res.json()).then(data => {
            location.reload();
        }).catch(err => {
            alert('Validasi gagal. Periksa input.');
        });
    }

    function openExtensionModal(planId, currentEndDate) {
        document.getElementById('ext-plan-id').value = planId;
        document.getElementById('ext-notes').value = '';
        
        // Default new end date to current end date + 7 days
        if (currentEndDate) {
            const dateObj = new Date(currentEndDate);
            dateObj.setDate(dateObj.getDate() + 7);
            document.getElementById('ext-week-end-date').value = dateObj.toISOString().split('T')[0];
        } else {
            document.getElementById('ext-week-end-date').value = '';
        }
        
        document.getElementById('extension-modal').classList.remove('hidden');
    }

    function closeExtensionModal() {
        document.getElementById('extension-modal').classList.add('hidden');
    }

    function submitExtension() {
        const form = document.getElementById('extension-form');
        const formData = new FormData(form);
        formData.append('status', 'extended');
        const id = formData.get('plan_id');

        fetch(`/api/weekly-plans/${id}/status`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-HTTP-Method-Override': 'PATCH'
            }
        }).then(res => res.json()).then(data => {
            location.reload();
        }).catch(err => {
            alert('Perpanjangan gagal. Periksa input.');
        });
    }

    function updateStatus(id, status) {
        if(!confirm('Anda yakin ingin mengubah status ini?')) return;
        
        const formData = new FormData();
        formData.append('status', status);

        fetch(`/api/weekly-plans/${id}/status`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-HTTP-Method-Override': 'PATCH'
            }
        }).then(res => res.json()).then(data => {
            location.reload();
        });
    }
</script>
@endsection
