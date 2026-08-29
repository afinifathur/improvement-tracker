@extends('layouts.app')

@section('title', 'Kaizen Tracker | Kelola Personel')

@section('content')
<div class="p-6 space-y-6">
    <!-- Title & Navigation Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-200 pb-4 gap-4">
        <div>
            <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                <span>OPERASI</span>
                <span>/</span>
                <span class="text-secondary">KELOLA PERSONEL</span>
            </div>
            <h2 class="text-xl font-bold tracking-tight text-slate-800 mt-1">
                Daftar Personel & Hak Akses
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Kelola identitas personel, status keaktifan, dan riwayat penugasan organisasi</p>
        </div>

        <a href="{{ route('users.create') }}" class="flex items-center gap-1.5 bg-[#0066B3] hover:bg-[#005292] text-white px-3 py-1.5 rounded text-xs font-bold uppercase tracking-wider transition-all shadow-sm self-start md:self-auto">
            <span class="material-symbols-outlined text-[16px]">person_add</span>
            <span>Tambah Personel Baru</span>
        </a>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">TOTAL TERDAFTAR</span>
            <span class="text-2xl font-bold tracking-tight text-slate-800 mt-1">{{ number_format($summary['total']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-green-500">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">PERSONEL AKTIF</span>
            <span class="text-2xl font-bold tracking-tight text-green-700 mt-1">{{ number_format($summary['active']) }}</span>
        </div>
        <div class="bg-white p-4 border border-slate-200 rounded-sm shadow-sm flex flex-col justify-between border-l-4 border-l-slate-400">
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">NONAKTIF / RESIGN</span>
            <span class="text-2xl font-bold tracking-tight text-slate-500 mt-1">{{ number_format($summary['inactive']) }}</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('users.index') }}" class="filter-bar flex-wrap gap-3">
        <!-- Text Search -->
        <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded px-2.5 py-1">
            <span class="material-symbols-outlined text-[16px] text-slate-400">search</span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..." class="bg-transparent border-none text-[11px] p-0 outline-none w-44 focus:ring-0 placeholder-slate-400"/>
        </div>

        <!-- Status Filter Tab -->
        <div class="flex items-center bg-slate-100 p-0.5 rounded border border-slate-200">
            <a href="{{ route('users.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded {{ $status === 'active' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-500 hover:text-slate-700' }}">
                Aktif ({{ $summary['active'] }})
            </a>
            <a href="{{ route('users.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded {{ $status === 'inactive' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-500 hover:text-slate-700' }}">
                Nonaktif ({{ $summary['inactive'] }})
            </a>
            <a href="{{ route('users.index', array_merge(request()->except('status'), ['status' => 'all'])) }}" class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded {{ $status === 'all' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-500 hover:text-slate-700' }}">
                Semua ({{ $summary['total'] }})
            </a>
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

        <!-- Role Filter -->
        <select name="role" onchange="this.form.submit()" class="filter-control">
            <option value="">Semua Role</option>
            @foreach($roles as $r)
                <option value="{{ $r }}" {{ request('role') === $r ? 'selected' : '' }}>
                    {{ strtoupper($r) }}
                </option>
            @endforeach
        </select>

        <!-- Reset Button -->
        @if(request()->anyFilled(['search', 'department_id', 'role']) || request('status') === 'all' || request('status') === 'inactive')
            <a href="{{ route('users.index') }}" class="text-[10px] font-bold text-red-500 hover:text-red-700 flex items-center gap-1 uppercase tracking-wider ml-auto">
                <span class="material-symbols-outlined text-[14px]">close</span> Hapus Filter
            </a>
        @endif
    </form>

    <!-- Table Container -->
    <div class="table-container">
        <table class="table-dense">
            <thead>
                <tr>
                    <th>PERSONEL</th>
                    <th class="w-32">ROLE</th>
                    <th class="w-40">DEPARTEMEN</th>
                    <th class="w-48">PENUGASAN AREA</th>
                    <th class="w-32 text-center">STATUS</th>
                    <th class="w-36 text-center">BEBAN KERJA</th>
                    <th class="w-32 text-right">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    @php
                        $activeAssignments = $user->areaAssignments->filter(fn($a) => $a->activeOn(now()));
                        $lastAssignment = $user->areaAssignments->first();
                    @endphp
                    <tr>
                        <td>
                            <div class="flex items-center gap-2.5">
                                <x-avatar class="w-7 h-7 rounded {{ $user->is_active ? 'grayscale-0' : 'grayscale opacity-60' }}" :name="$user->name" background="0058be" color="fff"/>
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-800 {{ !$user->is_active ? 'line-through text-slate-400' : '' }}">{{ $user->name }}</p>
                                    <p class="text-[10px] text-slate-400 font-mono">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded text-[9px] font-bold text-slate-600 uppercase tracking-widest">
                                {{ strtoupper($user->role) }}
                            </span>
                        </td>
                        <td>
                            <span class="font-medium text-slate-600">{{ $user->department->name ?? '—' }}</span>
                        </td>
                        <td>
                            @if($activeAssignments->isNotEmpty())
                                <div class="flex flex-wrap gap-1">
                                    @foreach($activeAssignments as $assignment)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-blue-50 border border-blue-200 text-blue-800 rounded text-[9px] font-semibold">
                                            <span>{{ $assignment->area?->name ?? 'Area' }}</span>
                                            <span class="text-[8px] text-blue-500 uppercase">({{ $assignment->role->value }})</span>
                                        </span>
                                    @endforeach
                                </div>
                            @elseif(!$user->is_active && $lastAssignment)
                                <div class="text-[10px] text-slate-400">
                                    <span>Terakhir: {{ $lastAssignment->area?->name ?? 'Area' }}</span>
                                    <span class="block text-[8px] text-slate-400">s/d {{ $lastAssignment->ended_at ? $lastAssignment->ended_at->format('d M Y') : '—' }}</span>
                                </div>
                            @else
                                <span class="text-slate-400 italic text-[10px]">Belum ditugaskan</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($user->is_active)
                                <span class="badge-status badge-completed">Aktif</span>
                            @else
                                <div class="inline-flex flex-col items-center">
                                    <span class="badge-status badge-cancelled">Nonaktif</span>
                                    @if($user->inactive_effective_date)
                                        <span class="text-[8px] text-slate-400 mt-0.5">Efektif {{ $user->inactive_effective_date->format('d M Y') }}</span>
                                    @endif
                                    @if($user->deactivation_reason)
                                        <span class="text-[8px] text-slate-400 italic">({{ $user->deactivation_reason }})</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($user->unfinished_work_items_count > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-800 rounded-full text-[10px] font-bold" title="{{ $user->unfinished_work_items_count }} pekerjaan belum selesai">
                                    <span class="material-symbols-outlined text-[12px]">pending_actions</span>
                                    <span>{{ $user->unfinished_work_items_count }} sisa</span>
                                </span>
                            @else
                                <span class="text-[10px] text-slate-400 font-semibold">0 sisa</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('users.edit', $user) }}" title="Edit data" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded text-[10px] font-bold uppercase tracking-wider transition-colors">
                                    Edit
                                </a>

                                @if($user->is_active)
                                    <button type="button" 
                                            onclick="openDeactivateModal({{ $user->id }}, '{{ addslashes($user->name) }}', {{ $user->unfinished_work_items_count }})"
                                            title="Nonaktifkan Personel" 
                                            class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded text-[10px] font-bold uppercase tracking-wider transition-colors">
                                        Nonaktifkan
                                    </button>
                                @else
                                    <form action="{{ route('users.reactivate', $user) }}" method="POST" class="inline" onsubmit="return confirm('Aktifkan kembali personel {{ addslashes($user->name) }}?');">
                                        @csrf
                                        <button type="submit" title="Aktifkan Kembali" class="px-2 py-1 bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 rounded text-[10px] font-bold uppercase tracking-wider transition-colors">
                                            Aktifkan
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-slate-400 italic">
                            Tidak ada data personel yang cocok dengan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Deactivate Confirmation Modal -->
<div id="deactivate-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
    <div class="bg-white w-full max-w-md rounded shadow-xl overflow-hidden border border-slate-200">
        <form id="deactivate-form" method="POST" action="">
            @csrf
            <div class="p-5 space-y-4">
                <div class="flex items-center gap-2 text-red-600 border-b border-slate-100 pb-3">
                    <span class="material-symbols-outlined text-2xl">person_off</span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Nonaktifkan Personel</h3>
                        <p class="text-[10px] text-slate-400">Seluruh riwayat historis tetap terjaga di database.</p>
                    </div>
                </div>

                <div id="modal-warning-box" class="hidden bg-amber-50 border border-amber-200 rounded p-2.5 text-[11px] text-amber-900 flex items-start gap-2">
                    <span class="material-symbols-outlined text-base text-amber-600 shrink-0">warning</span>
                    <div>
                        <span class="font-bold">Perhatian:</span> Personel ini memiliki <span id="modal-unfinished-count" class="font-bold">0</span> pekerjaan yang belum selesai. Pekerjaan historis tetap tersimpan atas nama personel ini.
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-1">Nama Personel</label>
                    <input type="text" id="modal-user-name" readonly class="w-full bg-slate-100 border border-slate-200 rounded px-2.5 py-1.5 text-xs text-slate-700 font-bold outline-none"/>
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-1">Tanggal Efektif Nonaktif <span class="text-red-500">*</span></label>
                    <input type="date" name="effective_date" id="modal-effective-date" required value="{{ now()->toDateString() }}" class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded px-2.5 py-1.5 text-xs text-slate-800 outline-none"/>
                    <p class="text-[9px] text-slate-400 mt-1">Tanggal hari terakhir aktif. Penugasan area aktif akan ditutup per tanggal ini.</p>
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-1">Alasan Penonaktifan</label>
                    <select name="reason" class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded px-2.5 py-1.5 text-xs text-slate-800 outline-none">
                        <option value="Resign">Resign / Mengundurkan Diri</option>
                        <option value="Mutasi">Mutasi / Pindah Divisi</option>
                        <option value="PHK">Pemutusan Hubungan Kerja (PHK)</option>
                        <option value="Kontrak Selesai">Kontrak Selesai</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-1">Catatan Tambahan (Opsional)</label>
                    <textarea name="note" rows="2" placeholder="Keterangan administratif..." class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded p-2 text-xs text-slate-800 outline-none"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-3 bg-slate-50 border-t border-slate-200">
                <button type="button" onclick="closeDeactivateModal()" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 rounded text-xs font-bold uppercase tracking-wider transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-bold uppercase tracking-wider transition-colors shadow-sm">
                    Konfirmasi Nonaktifkan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openDeactivateModal(userId, userName, unfinishedCount) {
        const form = document.getElementById('deactivate-form');
        form.action = `/users/${userId}/deactivate`;
        document.getElementById('modal-user-name').value = userName;

        const warningBox = document.getElementById('modal-warning-box');
        const countSpan = document.getElementById('modal-unfinished-count');
        if (unfinishedCount > 0) {
            countSpan.textContent = unfinishedCount;
            warningBox.classList.remove('hidden');
        } else {
            warningBox.classList.add('hidden');
        }

        document.getElementById('deactivate-modal').classList.remove('hidden');
    }

    function closeDeactivateModal() {
        document.getElementById('deactivate-modal').classList.add('hidden');
    }
</script>
@endsection
