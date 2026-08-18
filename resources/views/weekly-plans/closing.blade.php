@extends('layouts.app')

@section('title', 'Kaizen Tracker | Penutupan Mingguan')

@section('content')
<section class="p-8 max-w-7xl mx-auto w-full space-y-6">
    <!-- Breadcrumbs & Heading -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1">
                <span>Tracker</span>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-primary">Penutupan Mingguan</span>
            </nav>
            <h2 class="text-2xl font-bold tracking-tight text-inverse-surface">Validasi Cepat: Minggu {{ now()->format('W') }}</h2>
        </div>
        <div class="flex gap-2">
            <button class="bg-primary text-white px-8 py-3 rounded-sm text-sm font-bold uppercase tracking-widest hover:brightness-110 shadow-lg shadow-primary/30 transition-all active:scale-95">
                Finalisasi Minggu {{ now()->format('W') }}
            </button>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="flex gap-4 bg-surface-container-low p-3 rounded-sm border border-outline-variant/20 items-center overflow-x-auto">
        <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant whitespace-nowrap">Filter:</span>
        <select class="bg-white border border-outline-variant/30 rounded-sm text-xs py-1 px-3 focus:ring-1 focus:ring-primary">
            <option>Semua SPV</option>
        </select>
        <button class="ml-auto text-[10px] font-bold uppercase tracking-widest text-on-surface-variant hover:text-primary underline">Hapus Semua</button>
    </div>

    <!-- Compact Stats Bar -->
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-surface-container-lowest p-3 rounded border border-outline-variant/10 flex justify-between items-center">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Rencana</span>
            <span class="text-xl font-bold">{{ $stats->total ?? 0 }}</span>
        </div>
        <div class="bg-surface-container-lowest p-3 rounded border border-outline-variant/10 flex justify-between items-center border-l-4 border-l-primary">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Selesai</span>
            <span class="text-xl font-bold text-primary">{{ $stats->completed ?? 0 }}</span>
        </div>
        <div class="bg-surface-container-lowest p-3 rounded border border-outline-variant/10 flex justify-between items-center border-l-4 border-l-error">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Tertunda</span>
            <span class="text-xl font-bold text-error">{{ $stats->pending ?? 0 }}</span>
        </div>
        <div class="bg-surface-container-lowest p-3 rounded border border-outline-variant/10 flex justify-between items-center border-l-4 border-l-secondary">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Diperpanjang</span>
            <span class="text-xl font-bold text-secondary">{{ $stats->extended ?? 0 }}</span>
        </div>
    </div>

    <!-- Fast Validation List Layout -->
    <div class="bg-white border border-outline-variant/20 rounded-sm divide-y divide-outline-variant/10">
        <!-- Header -->
        <div class="grid grid-cols-12 px-4 py-2 bg-surface-container-low text-[10px] font-bold uppercase tracking-widest text-on-surface-variant items-center">
            <div class="col-span-1">ID</div>
            <div class="col-span-5">Rencana Kaizen / Sasaran & Detail Eksekusi</div>
            <div class="col-span-2 text-center">SPV</div>
            <div class="col-span-4 text-center">Aksi Validasi</div>
        </div>

        @forelse($plans ?? [] as $plan)
        @php
            $hasUncompleted = false;
            $workItemsCount = $plan->workItems->count();
            foreach ($plan->workItems as $item) {
                if (!in_array($item->status->value, ['completed', 'cancelled'])) {
                    $hasUncompleted = true;
                }
            }
        @endphp
        <div class="grid grid-cols-12 px-4 py-4 items-start hover:bg-slate-50 transition-colors {{ $plan->status !== 'planned' ? 'opacity-90' : '' }}">
            <div class="col-span-1 text-xs font-mono text-on-surface-variant pt-1">#{{ $plan->id }}</div>
            <div class="col-span-5 pr-4 space-y-2">
                <div>
                    <p class="text-sm font-semibold text-inverse-surface">{{ $plan->title }}</p>
                    <p class="text-[10px] text-on-surface-variant uppercase font-medium mt-0.5">{{ $plan->category }}</p>
                </div>
                
                <!-- Daily Execution History -->
                <div class="bg-slate-50 border border-slate-200/60 rounded-sm p-3 space-y-2">
                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 block">Riwayat Eksekusi Harian ({{ $workItemsCount }} Pekerjaan)</span>
                    
                    @if($workItemsCount === 0)
                        <div class="flex items-center gap-2 p-2 bg-amber-50 border border-amber-200/50 text-amber-800 rounded-sm text-[10px]">
                            <span class="material-symbols-outlined text-[14px] text-amber-500">warning</span>
                            <span>Rencana ini belum memiliki laporan pekerjaan harian.</span>
                        </div>
                    @else
                        <div class="space-y-1">
                            @foreach($plan->workItems as $item)
                                <div class="flex items-center justify-between text-[11px] text-slate-600 border-b border-slate-100 pb-1">
                                    <span class="truncate max-w-[250px]">{{ $item->title }}</span>
                                    <span class="px-1.5 py-0.5 rounded-[2px] text-[8px] font-bold uppercase tracking-wider 
                                        @if($item->status->value === 'completed') bg-green-50 text-green-700 border border-green-200/30
                                        @elseif($item->status->value === 'cancelled') bg-slate-100 text-slate-500 border border-slate-200/30
                                        @elseif($item->status->value === 'blocked') bg-amber-50 text-amber-700 border border-amber-200/30
                                        @else bg-blue-50 text-blue-700 border border-blue-200/30
                                        @endif border">
                                        {{ $item->status->value === 'not_started' ? 'Belum Mulai' : ($item->status->value === 'in_progress' ? 'Berjalan' : ($item->status->value === 'blocked' ? 'Terblokir' : ($item->status->value === 'completed' ? 'Selesai' : 'Dibatalkan'))) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        @if($hasUncompleted)
                            <div class="flex items-center gap-2 p-2 bg-amber-50 border border-amber-200/50 text-amber-800 rounded-sm text-[10px] mt-2">
                                <span class="material-symbols-outlined text-[14px] text-amber-500">warning</span>
                                <span>Terdapat pekerjaan harian ditautkan yang belum selesai.</span>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
            <div class="col-span-2 text-center text-xs text-on-surface-variant pt-1">
                {{ $plan->user->name }}
            </div>
            <div class="col-span-4 flex justify-end items-center gap-1 pt-1">
                @if($plan->status === 'planned')
                <button onclick="openValidationModal({{ $plan->id }}, 'completed')" class="px-3 py-1.5 rounded-sm bg-primary text-white text-[10px] font-bold uppercase tracking-wider shadow-sm">Selesai</button>
                <button onclick="openValidationModal({{ $plan->id }}, 'completed_no_impact')" class="px-3 py-1.5 rounded-sm bg-surface-container-high text-on-surface-variant text-[10px] font-bold uppercase tracking-wider hover:bg-tertiary hover:text-white transition-all">Tanpa Dampak</button>
                <button onclick="updateStatus({{ $plan->id }}, 'not_completed')" class="px-3 py-1.5 rounded-sm bg-surface-container-high text-on-surface-variant text-[10px] font-bold uppercase tracking-wider hover:bg-error hover:text-white transition-all">Gagal</button>
                <button onclick="updateStatus({{ $plan->id }}, 'extended')" class="px-3 py-1.5 rounded-sm bg-surface-container-high text-on-surface-variant text-[10px] font-bold uppercase tracking-wider hover:bg-secondary hover:text-white transition-all">Perpanjang</button>
                @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200">
                    <span class="material-symbols-outlined text-xs mr-1">check_circle</span> {{ match($plan->status) { 'planned' => 'Direncanakan', 'completed' => 'Selesai', 'completed_no_impact' => 'Selesai Tanpa Dampak', 'not_completed' => 'Tidak Selesai', 'extended' => 'Diperpanjang', default => str_replace('_', ' ', $plan->status) } }}
                </span>
                <button onclick="openValidationModal({{ $plan->id }}, '{{ $plan->status }}')" class="text-[10px] font-bold text-primary uppercase ml-3 hover:underline">Ubah</button>
                @endif
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-on-surface-variant italic text-sm">Tidak ada rencana aktif untuk minggu ini.</div>
        @endforelse
    </div>
</section>

<!-- Validation Modal (Simplified for Logic) -->
<div id="validation-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-lg rounded-sm shadow-2xl overflow-hidden">
        <form id="validation-form" onsubmit="event.preventDefault(); submitValidation();" enctype="multipart/form-data">
            <input type="hidden" name="plan_id" id="modal-plan-id">
            <input type="hidden" name="status" id="modal-status">
            <div class="p-6 space-y-4">
                <h3 class="text-xl font-bold text-inverse-surface">Validasi Implementasi Rencana</h3>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-on-surface-variant mb-1">Catatan Penutupan</label>
                    <textarea name="notes" class="w-full bg-white border border-outline-variant/30 rounded-sm text-sm p-2 h-24 resize-none focus:ring-primary" placeholder="Pelajaran yang dipetik..."></textarea>
                </div>
                <div id="proof-container">
                    <div class="text-[10px] font-bold text-error uppercase mb-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">error</span> Bukti Diperlukan
                    </div>
                    <input type="file" name="proofs[]" multiple class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                </div>
            </div>
            <div class="flex border-t border-outline-variant/15">
                <button type="button" onclick="closeValidationModal()" class="flex-1 px-4 py-4 text-xs font-bold uppercase tracking-widest text-on-surface-variant hover:bg-slate-50 transition-colors border-r">Batal</button>
                <button type="submit" class="flex-1 bg-primary text-white px-4 py-4 text-xs font-bold uppercase tracking-widest hover:brightness-110">Konfirmasi & Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openValidationModal(id, status) {
        document.getElementById('modal-plan-id').value = id;
        document.getElementById('modal-status').value = status;
        document.getElementById('validation-modal').classList.remove('hidden');
    }

    function closeValidationModal() {
        document.getElementById('validation-modal').classList.add('hidden');
    }

    function submitValidation() {
        const form = document.getElementById('validation-form');
        const formData = new FormData(form);
        const id = formData.get('plan_id');

        fetch(`/api/weekly-plans/${id}/status`, {
            method: 'POST', // Use POST with _method PATCH for Laravel
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
