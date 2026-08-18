@extends('layouts.app')

@section('title', 'Kaizen Tracker | Hari Ini')

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
                <span>TAMPILAN HARIAN</span>
                <span>/</span>
                <span class="text-secondary">HARI INI</span>
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
                Hari Ini
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
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">SAAT INI</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['expected']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">BEBAN AKTIF</span>
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
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('work-items.today') }}" class="filter-bar flex-wrap gap-3">
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
            <a href="{{ route('work-items.today', ['date' => $date]) }}" class="text-[10px] font-bold text-red-500 hover:text-red-700 flex items-center gap-1 uppercase tracking-wider ml-auto">
                <span class="material-symbols-outlined text-[14px]">close</span> Hapus Filter
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
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $areaItems->count() }} pekerjaan</span>
                    </div>

                    <!-- Table Container -->
                    <div id="area-{{ $area->id }}" class="table-container rounded-t-none">
                        <table class="table-dense">
                            <thead>
                                <tr>
                                    <th class="w-32">STATUS</th>
                                    <th>PEKERJAAN</th>
                                    <th class="w-16 text-center">WEEKLY</th>
                                    <th class="w-32">KLASIFIKASI</th>
                                    <th class="w-40">PENANGGUNG JAWAB</th>
                                    <th class="w-40">DEPARTEMEN</th>
                                    <th class="w-24">MULAI</th>
                                    <th class="w-24">BATAS WAKTU</th>
                                    <th class="w-20">TINDAKAN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($areaItems as $item)
                                    <tr>
                                        <td>
                                            <select onchange="updateItemStatus({{ $item->id }}, this.value)" class="text-[10px] font-bold uppercase tracking-wider rounded border border-slate-200 px-1 py-0.5 outline-none focus:ring-1 focus:ring-secondary bg-white text-slate-700">
                                                <option value="not_started" {{ $item->status->value === 'not_started' ? 'selected' : '' }}>Belum Mulai</option>
                                                <option value="in_progress" {{ $item->status->value === 'in_progress' ? 'selected' : '' }}>Berjalan</option>
                                                <option value="blocked" {{ $item->status->value === 'blocked' ? 'selected' : '' }}>Terblokir</option>
                                                <option value="completed" {{ $item->status->value === 'completed' ? 'selected' : '' }}>Selesai</option>
                                                <option value="cancelled" {{ $item->status->value === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                                            </select>
                                        </td>
                                        <td>
                                            <p class="font-bold text-slate-800">{{ $item->title }}</p>
                                            @if($item->description)
                                                <p class="text-[10px] text-slate-400 truncate max-w-lg mt-0.5">{{ $item->description }}</p>
                                            @endif
                                        </td>
                                        <td class="text-center text-xs">
                                            @if($item->weekly_plan_id)
                                                <span class="text-emerald-600 font-bold" title="{{ $item->weeklyPlan->title ?? 'Terkait Rencana Mingguan' }}">☑</span>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->classification === 'overdue')
                                                <span class="px-1.5 py-0.5 bg-red-50 text-red-700 border border-red-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">TERLAMBAT</span>
                                            @elseif($item->classification === 'current')
                                                <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">SAAT INI</span>
                                            @elseif($item->classification === 'future')
                                                <span class="px-1.5 py-0.5 bg-slate-50 text-slate-500 border border-slate-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">MENDATANG</span>
                                            @else
                                                <span class="px-1.5 py-0.5 bg-slate-100 text-slate-400 border border-slate-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">RIWAYAT</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <img alt="Avatar" class="w-4 h-4 rounded object-cover grayscale" src="https://ui-avatars.com/api/?name={{ urlencode($item->owner->name ?? 'User') }}&background=f1f5f9&color=64748b&size=32"/>
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
                                        <td>
                                            <button onclick="openExtendModal({{ $item->id }}, '{{ $item->planned_end_date ? $item->planned_end_date->toDateString() : '' }}')" class="flex items-center gap-0.5 px-1.5 py-0.5 bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 rounded text-[9px] font-bold uppercase tracking-wider transition-colors">
                                                <span class="material-symbols-outlined text-[10px]">schedule</span> Perpanjang
                                            </button>
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
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wide">Area Belum Ditentukan</span>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $unassignedItems->count() }} pekerjaan</span>
                </div>

                <!-- Table Container -->
                <div id="area-unassigned" class="table-container rounded-t-none">
                    <table class="table-dense">
                        <thead>
                            <tr>
                                <th class="w-32">STATUS</th>
                                <th>PEKERJAAN</th>
                                <th class="w-16 text-center">WEEKLY</th>
                                <th class="w-32">KLASIFIKASI</th>
                                <th class="w-40">PENANGGUNG JAWAB</th>
                                <th class="w-40">DEPARTEMEN</th>
                                <th class="w-24">MULAI</th>
                                <th class="w-24">BATAS WAKTU</th>
                                <th class="w-20">TINDAKAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unassignedItems as $item)
                                <tr>
                                    <td>
                                        <select onchange="updateItemStatus({{ $item->id }}, this.value)" class="text-[10px] font-bold uppercase tracking-wider rounded border border-slate-200 px-1 py-0.5 outline-none focus:ring-1 focus:ring-secondary bg-white text-slate-700">
                                            <option value="not_started" {{ $item->status->value === 'not_started' ? 'selected' : '' }}>Belum Mulai</option>
                                            <option value="in_progress" {{ $item->status->value === 'in_progress' ? 'selected' : '' }}>Berjalan</option>
                                            <option value="blocked" {{ $item->status->value === 'blocked' ? 'selected' : '' }}>Terblokir</option>
                                            <option value="completed" {{ $item->status->value === 'completed' ? 'selected' : '' }}>Selesai</option>
                                            <option value="cancelled" {{ $item->status->value === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                                        </select>
                                    </td>
                                    <td>
                                        <p class="font-bold text-slate-800">{{ $item->title }}</p>
                                        @if($item->description)
                                            <p class="text-[10px] text-slate-400 truncate max-w-lg mt-0.5">{{ $item->description }}</p>
                                        @endif
                                    </td>
                                    <td class="text-center text-xs">
                                        @if($item->weekly_plan_id)
                                            <span class="text-emerald-600 font-bold" title="{{ $item->weeklyPlan->title ?? 'Terkait Rencana Mingguan' }}">☑</span>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->classification === 'overdue')
                                            <span class="px-1.5 py-0.5 bg-red-50 text-red-700 border border-red-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">TERLAMBAT</span>
                                        @elseif($item->classification === 'current')
                                            <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">SAAT INI</span>
                                        @elseif($item->classification === 'future')
                                            <span class="px-1.5 py-0.5 bg-slate-50 text-slate-500 border border-slate-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">MENDATANG</span>
                                        @else
                                            <span class="px-1.5 py-0.5 bg-slate-100 text-slate-400 border border-slate-200/50 rounded-[2px] text-[9px] font-extrabold uppercase tracking-wide">RIWAYAT</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <img alt="Avatar" class="w-4 h-4 rounded object-cover grayscale" src="https://ui-avatars.com/api/?name={{ urlencode($item->owner->name ?? 'User') }}&background=f1f5f9&color=64748b&size=32"/>
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
                                    <td>
                                        <button onclick="openExtendModal({{ $item->id }}, '{{ $item->planned_end_date ? $item->planned_end_date->toDateString() : '' }}')" class="flex items-center gap-0.5 px-1.5 py-0.5 bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 rounded text-[9px] font-bold uppercase tracking-wider transition-colors">
                                            <span class="material-symbols-outlined text-[10px]">schedule</span> Perpanjang
                                        </button>
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
                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Tidak ada pekerjaan aktif yang cocok dengan filter untuk tanggal ini</p>
            </div>
        @endif
    </div>
</div>

<!-- BLOCK MODAL -->
<div id="blockedModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 items-center justify-center hidden">
    <div class="bg-white rounded-sm border border-slate-200 shadow-xl max-w-sm w-full p-5 space-y-4">
        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-700 flex items-center gap-2">
            <span class="material-symbols-outlined text-base text-amber-500">warning</span>
            Laporkan Kendala Pekerjaan
        </h3>
        <div class="space-y-3">
            <div>
                <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-1">Kategori Kendala</label>
                <select id="blocked_reason" class="w-full h-8 px-2 border border-slate-200 rounded text-[11px] outline-none bg-white">
                    <option value="waiting_sparepart">Menunggu Suku Cadang (Waiting Sparepart)</option>
                    <option value="waiting_material">Menunggu Material (Waiting Material)</option>
                    <option value="waiting_decision">Menunggu Keputusan (Waiting Decision)</option>
                    <option value="other">Lainnya (Other)</option>
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-1">Departemen Penyebab</label>
                <select id="blocked_by_department_id" class="w-full h-8 px-2 border border-slate-200 rounded text-[11px] outline-none bg-white">
                    <option value="">Tidak ada/Lainnya</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-1">Keterangan Kendala</label>
                <textarea id="blocked_reason_note" rows="3" class="w-full p-2 border border-slate-200 rounded text-[11px] outline-none resize-none placeholder-slate-400" placeholder="Jelaskan kendala secara rinci..."></textarea>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 text-[10px] font-bold">
            <button onclick="hideModal('blockedModal'); window.location.reload();" class="px-3 h-8 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded uppercase tracking-wider transition-colors">Batal</button>
            <button onclick="submitBlocked()" class="px-3 h-8 bg-amber-500 hover:bg-amber-600 text-white rounded uppercase tracking-wider transition-colors">Simpan</button>
        </div>
    </div>
</div>

<!-- CANCELLED MODAL -->
<div id="cancelledModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 items-center justify-center hidden">
    <div class="bg-white rounded-sm border border-slate-200 shadow-xl max-w-sm w-full p-5 space-y-4">
        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-700 flex items-center gap-2">
            <span class="material-symbols-outlined text-base text-red-500">cancel</span>
            Batalkan Pekerjaan
        </h3>
        <div class="space-y-3">
            <div>
                <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-1">Alasan Pembatalan</label>
                <select id="cancel_reason" class="w-full h-8 px-2 border border-slate-200 rounded text-[11px] outline-none bg-white">
                    <option value="change_priority">Perubahan Prioritas (Change Priority)</option>
                    <option value="duplicate">Duplikasi Data (Duplicate)</option>
                    <option value="legacy_cleanup">Pembersihan Data Lama (Legacy Cleanup)</option>
                    <option value="carried_over">Dipindahkan ke Periode Berikutnya (Carried Over)</option>
                    <option value="other">Lainnya (Other)</option>
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-1">Keterangan Pembatalan</label>
                <textarea id="cancel_reason_note" rows="3" class="w-full p-2 border border-slate-200 rounded text-[11px] outline-none resize-none placeholder-slate-400" placeholder="Jelaskan alasan secara rinci..."></textarea>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 text-[10px] font-bold">
            <button onclick="hideModal('cancelledModal'); window.location.reload();" class="px-3 h-8 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded uppercase tracking-wider transition-colors">Batal</button>
            <button onclick="submitCancelled()" class="px-3 h-8 bg-red-500 hover:bg-red-600 text-white rounded uppercase tracking-wider transition-colors">Simpan</button>
        </div>
    </div>
</div>

<!-- EXTEND MODAL -->
<div id="extendModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 items-center justify-center hidden">
    <div class="bg-white rounded-sm border border-slate-200 shadow-xl max-w-sm w-full p-5 space-y-4">
        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-700 flex items-center gap-2">
            <span class="material-symbols-outlined text-base text-secondary">schedule</span>
            Perpanjang Pekerjaan (Reschedule)
        </h3>
        <div class="space-y-3">
            <div>
                <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-1">Tanggal Selesai Baru</label>
                <input type="date" id="new_end_date" class="w-full h-8 px-2 border border-slate-200 rounded text-[11px] outline-none"/>
            </div>
            <div>
                <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-1">Alasan Perpanjangan</label>
                <input type="text" id="extend_reason" class="w-full h-8 px-2 border border-slate-200 rounded text-[11px] outline-none placeholder-slate-400" placeholder="Contoh: Stok data belum lengkap"/>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 text-[10px] font-bold">
            <button onclick="hideModal('extendModal')" class="px-3 h-8 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded uppercase tracking-wider transition-colors">Batal</button>
            <button onclick="submitExtension()" class="px-3 h-8 bg-secondary hover:bg-secondary/90 text-white rounded uppercase tracking-wider transition-colors">Simpan</button>
        </div>
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

    let currentItemIdForModal = null;

    function updateItemStatus(itemId, status) {
        if (status === 'blocked') {
            currentItemIdForModal = itemId;
            showModal('blockedModal');
            return;
        }
        if (status === 'cancelled') {
            currentItemIdForModal = itemId;
            showModal('cancelledModal');
            return;
        }
        
        submitStatusUpdate(itemId, status);
    }

    function submitStatusUpdate(itemId, status, extraData = {}) {
        const data = {
            _token: '{{ csrf_token() }}',
            status: status,
            ...extraData
        };
        
        fetch(`/work-items/${itemId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Gagal memperbarui status.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan jaringan.');
        });
    }

    function submitBlocked() {
        const reason = document.getElementById('blocked_reason').value;
        const note = document.getElementById('blocked_reason_note').value;
        const deptId = document.getElementById('blocked_by_department_id').value;
        
        submitStatusUpdate(currentItemIdForModal, 'blocked', {
            blocked_reason: reason,
            blocked_reason_note: note,
            blocked_by_department_id: deptId
        });
        hideModal('blockedModal');
    }

    function submitCancelled() {
        const reason = document.getElementById('cancel_reason').value;
        const note = document.getElementById('cancel_reason_note').value;
        
        submitStatusUpdate(currentItemIdForModal, 'cancelled', {
            cancel_reason: reason,
            cancel_reason_note: note
        });
        hideModal('cancelledModal');
    }

    function openExtendModal(itemId, currentEndDate) {
        currentItemIdForModal = itemId;
        document.getElementById('new_end_date').value = currentEndDate;
        document.getElementById('extend_reason').value = '';
        showModal('extendModal');
    }

    function submitExtension() {
        const newEndDate = document.getElementById('new_end_date').value;
        const reason = document.getElementById('extend_reason').value;
        
        if (!newEndDate || !reason) {
            alert('Semua bidang harus diisi.');
            return;
        }

        const data = {
            _token: '{{ csrf_token() }}',
            new_end_date: newEndDate,
            reason: reason
        };
        
        fetch(`/work-items/${currentItemIdForModal}/extend`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Gagal memperpanjang pekerjaan.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan jaringan.');
        });
        hideModal('extendModal');
    }

    function showModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    // Ensure we reload if they click cancel on prompt to keep dropdown state in sync
    function hideModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }
</script>
@endsection
