@extends('layouts.app')

@section('title', 'Kaizen Tracker | Rencana Mingguan Baru')

@section('content')
<!-- Main Content -->
<div class="flex-1 flex justify-center py-8 px-6 overflow-y-auto">
    <div class="w-full max-w-3xl">
        <!-- Form Header -->
        <div class="mb-6 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-inverse-surface">Rencana Mingguan Baru</h1>
                <p class="text-sm text-on-surface-variant">Tentukan sasaran untuk Minggu {{ now()->format('W') }}</p>
            </div>
            <div class="text-[11px] font-medium text-primary flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">lightbulb</span>
                Disarankan 2–3 rencana per minggu
            </div>
        </div>

        <form action="{{ route('api.weekly-plans.store') }}" method="POST" class="space-y-4">
            @csrf
            <!-- Row 1: SPV & Title -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-4 relative" id="spv-selector">
                    <label class="block text-[10px] uppercase tracking-widest text-on-surface-variant font-bold mb-1">SPV</label>
                    <input type="text" id="spv-search" placeholder="Ketik nama SPV..." autocomplete="off" class="w-full bg-surface-container-lowest border-0 border-b border-outline-variant focus:border-primary focus:ring-0 py-2 text-on-surface text-sm transition-all placeholder:text-surface-dim"/>
                    <input type="hidden" name="user_id" id="spv-user-id" value="{{ old('user_id') }}">
                    <div id="spv-dropdown" class="hidden absolute left-0 right-0 top-full z-20 mt-1 bg-white border border-outline-variant rounded-sm shadow-lg max-h-56 overflow-y-auto"></div>
                </div>
                <div class="md:col-span-8">
                    <label class="block text-[10px] uppercase tracking-widest text-on-surface-variant font-bold mb-1">Judul Rencana</label>
                    <input name="title" required type="text" class="w-full bg-surface-container-lowest border-0 border-b border-outline-variant focus:border-primary focus:ring-0 px-0 py-2 text-on-surface text-sm transition-all placeholder:text-surface-dim" placeholder="contoh: Pengurangan Cycle Time"/>
                </div>
            </div>

            <!-- Row 2: Expected Output (Highlighted) -->
            <div class="bg-primary-container/10 p-3 rounded-sm border-l-4 border-primary">
                <div class="flex items-center gap-2 mb-1">
                    <label class="block text-[10px] uppercase tracking-widest text-primary font-bold">Hasil yang Diharapkan</label>
                    <span class="text-[9px] bg-primary text-on-primary px-1 rounded-full uppercase">Wajib</span>
                </div>
                <input name="expected_output" required minlength="10" class="w-full bg-transparent border-0 border-b border-primary/20 focus:border-primary focus:ring-0 px-0 py-1 text-on-surface font-medium text-sm transition-all placeholder:text-slate-400" placeholder="contoh: penurunan 15% waktu menganggur" type="text"/>
            </div>

            <!-- Row 4: Category & Impact -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-2">
                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-on-surface-variant font-bold mb-2">Kategori</label>
                    <div class="flex flex-wrap gap-1">
                        <label class="cursor-pointer">
                            <input checked type="radio" name="category" value="improvement" class="hidden peer"/>
                            <span class="px-3 py-1.5 text-[10px] font-bold border border-outline-variant text-on-surface-variant peer-checked:bg-inverse-surface peer-checked:text-surface peer-checked:border-inverse-surface transition-all inline-block rounded-sm">Perbaikan</span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="category" value="problem" class="hidden peer"/>
                            <span class="px-3 py-1.5 text-[10px] font-bold border border-outline-variant text-on-surface-variant peer-checked:bg-inverse-surface peer-checked:text-surface peer-checked:border-inverse-surface transition-all inline-block rounded-sm">Pemecahan Masalah</span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="category" value="maintenance" class="hidden peer"/>
                            <span class="px-3 py-1.5 text-[10px] font-bold border border-outline-variant text-on-surface-variant peer-checked:bg-inverse-surface peer-checked:text-surface peer-checked:border-inverse-surface transition-all inline-block rounded-sm">Maintenance</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-on-surface-variant font-bold mb-2">Tingkat Dampak</label>
                    <div class="flex gap-1">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="impact_level" value="low" class="hidden peer"/>
                            <span class="w-full text-center px-2 py-1.5 text-[10px] font-bold border border-outline-variant text-on-surface-variant peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-700 transition-all inline-block rounded-sm">Rendah</span>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input checked type="radio" name="impact_level" value="medium" class="hidden peer"/>
                            <span class="w-full text-center px-2 py-1.5 text-[10px] font-bold border border-outline-variant text-on-surface-variant peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-700 transition-all inline-block rounded-sm">Sedang</span>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="impact_level" value="high" class="hidden peer"/>
                            <span class="w-full text-center px-2 py-1.5 text-[10px] font-bold border border-outline-variant text-on-surface-variant peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-700 transition-all inline-block rounded-sm">Tinggi</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Row 5: Week -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-on-surface-variant font-bold mb-1">Tanggal Minggu</label>
                    <input name="week_start_date" required value="{{ now()->toDateString() }}" type="date" class="w-full bg-surface-container-lowest border-0 border-b border-outline-variant focus:border-primary focus:ring-0 px-0 py-1 text-on-surface text-sm transition-all"/>
                    <p class="text-[9px] text-on-surface-variant mt-1">Pilih tanggal apa saja dalam minggu tersebut — sistem akan menyetel Senin–Minggu secara otomatis.</p>
                </div>
            </div>

            <!-- Row 6: Actions -->
            <div class="pt-6 mt-2 border-t border-slate-200/50 flex flex-col sm:flex-row gap-3">
                <button type="submit" class="flex-1 py-3 bg-gradient-to-r from-primary to-primary-dim text-on-primary font-bold uppercase tracking-[0.1em] text-xs rounded-sm hover:shadow-md transition-all active:scale-[0.98] flex items-center justify-center gap-2">
                    Kirim Rencana
                    <span class="material-symbols-outlined text-sm">send</span>
                </button>
            </div>
            <p class="text-center text-on-surface-variant text-[9px] font-medium opacity-60">
                Rencana dipantau melalui Global Kaizen System.
            </p>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const personnel = @json($personnel->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'dept' => $u->department_name])->values());
    const search = document.getElementById('spv-search');
    const userId = document.getElementById('spv-user-id');
    const dropdown = document.getElementById('spv-dropdown');
    const selector = document.getElementById('spv-selector');

    function filter(q) {
        q = q.trim().toLowerCase();
        if (!q) {
            return [];
        }
        return personnel.filter(function (p) {
            return p.name.toLowerCase().indexOf(q) !== -1;
        });
    }

    function render(items) {
        dropdown.innerHTML = '';
        if (items.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'px-3 py-2 text-xs text-on-surface-variant';
            empty.textContent = 'Tidak ada hasil';
            dropdown.appendChild(empty);
            return;
        }
        items.forEach(function (p) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'w-full text-left px-3 py-2 text-sm hover:bg-slate-50 flex items-center justify-between gap-2';
            const name = document.createElement('span');
            name.className = 'font-semibold text-on-surface';
            name.textContent = p.name;
            const dept = document.createElement('span');
            dept.className = 'text-[10px] text-on-surface-variant uppercase';
            dept.textContent = p.dept || '';
            btn.appendChild(name);
            btn.appendChild(dept);
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
                search.value = p.name;
                userId.value = p.id;
                dropdown.classList.add('hidden');
            });
            dropdown.appendChild(btn);
        });
    }

    search.addEventListener('input', function () {
        userId.value = '';
        render(filter(this.value));
        dropdown.classList.remove('hidden');
    });

    search.addEventListener('focus', function () {
        if (this.value.trim()) {
            render(filter(this.value));
            dropdown.classList.remove('hidden');
        }
    });

    document.addEventListener('click', function (e) {
        if (!selector.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
})();
</script>
@endsection

