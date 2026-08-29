@php
    $isEdit = ! is_null($report);
    $formAction = $isEdit ? route('daily-reports.update', $report) : route('daily-reports.store');
    $currentCarbon = \Carbon\Carbon::parse($date);
    $prevDate = $currentCarbon->copy()->subDay()->toDateString();
    $nextDate = $currentCarbon->copy()->addDay()->toDateString();
    $todayDate = now()->toDateString();
    $isToday = $date === $todayDate;
@endphp

<div class="p-6 max-w-6xl mx-auto w-full space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1">
                <a href="{{ route('daily-reports.index', ['date' => $date]) }}" class="hover:text-primary date-nav-link">Laporan Harian</a>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-primary">{{ $isEdit ? 'Ubah' : 'Baru' }} Laporan</span>
            </nav>
            <h2 class="text-2xl font-bold tracking-tight text-inverse-surface">Laporan Harian</h2>
        </div>

        <div class="flex flex-wrap items-center gap-2 self-start md:self-auto">
            @if($person)
                <div class="flex items-center bg-white border border-slate-200 rounded-sm shadow-xs p-0.5">
                    <a href="{{ route('daily-reports.navigate', ['person' => $person->id, 'date' => $prevDate, 'area_id' => $areaId]) }}"
                       class="date-nav-link inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-600 hover:text-primary hover:bg-slate-50 rounded transition-colors"
                       title="Hari Sebelumnya ({{ \Carbon\Carbon::parse($prevDate)->format('d M Y') }})">
                        <span class="material-symbols-outlined text-[15px]">arrow_back</span>
                        <span>Hari Sebelumnya</span>
                    </a>

                    <div class="px-3 py-1 text-[11px] font-extrabold uppercase tracking-wide text-slate-800 border-x border-slate-200 bg-slate-50/70">
                        {{ $currentCarbon->format('d M Y') }}
                    </div>

                    @if(! $isToday)
                        <a href="{{ route('daily-reports.navigate', ['person' => $person->id, 'date' => $todayDate, 'area_id' => $areaId]) }}"
                           class="date-nav-link inline-flex items-center px-2 py-1 text-[10px] font-extrabold uppercase tracking-wider text-blue-700 bg-blue-50/80 hover:bg-blue-100/80 border-r border-slate-200 transition-colors"
                           title="Lompat ke Hari Ini ({{ \Carbon\Carbon::parse($todayDate)->format('d M Y') }})">
                            Hari Ini
                        </a>
                    @endif

                    <a href="{{ route('daily-reports.navigate', ['person' => $person->id, 'date' => $nextDate, 'area_id' => $areaId]) }}"
                       class="date-nav-link inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-600 hover:text-primary hover:bg-slate-50 rounded transition-colors"
                       title="Hari Berikutnya ({{ \Carbon\Carbon::parse($nextDate)->format('d M Y') }})">
                        <span>Hari Berikutnya</span>
                        <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                    </a>
                </div>
            @endif

            <a href="{{ route('daily-reports.index', ['date' => $date]) }}"
               class="date-nav-link px-3 py-1.5 rounded-sm bg-surface-container-high text-on-surface-variant text-[10px] font-bold uppercase tracking-wider hover:bg-tertiary hover:text-white transition-all">
                Kembali
            </a>
        </div>
    </div>

    {{-- Validation error summary --}}
    @if($errors->any())
    <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 rounded-sm px-4 py-3 shadow-sm">
        <span class="material-symbols-outlined text-red-500 text-lg shrink-0">error</span>
        <div class="text-xs">
            <p class="font-bold mb-1">Terdapat kesalahan input. Periksa kembali:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Existing Report Notification Banner --}}
    <div id="existing-report-banner" class="hidden bg-blue-50 border border-blue-200 text-blue-900 rounded-sm p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
        <div class="flex items-start gap-3">
            <span class="material-symbols-outlined text-blue-600 text-xl shrink-0 mt-0.5">info</span>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-extrabold text-blue-900 uppercase tracking-wide">✓ Laporan Sudah Ada</span>
                    <span class="text-[9px] bg-blue-100 text-blue-800 font-extrabold px-1.5 py-0.5 rounded uppercase tracking-wider">Mode Lanjutan / Tambah Pekerjaan</span>
                </div>
                <p class="text-xs text-blue-800 mt-1">
                    Laporan harian untuk <strong id="banner-person-name"></strong> tanggal <strong id="banner-date"></strong> di area <strong id="banner-area-name"></strong> sudah tercatat (<span id="banner-item-count">0</span> pekerjaan). Anda dapat langsung menambahkan pekerjaan baru di bawah ini.
                </p>
            </div>
        </div>
        <a id="banner-edit-link" href="#" class="px-3 py-1.5 bg-white border border-blue-200 hover:border-blue-300 text-blue-700 hover:text-blue-900 rounded text-[10px] font-bold uppercase tracking-wider transition-colors shrink-0 text-center shadow-sm">
            Buka Halaman Edit Penuh &rarr;
        </a>
    </div>

    {{-- Main form --}}
    <form id="daily-report-form" action="{{ $formAction }}" method="POST" class="space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Context Selectors --}}
        <div class="bg-white p-4 border border-outline-variant/20 rounded-sm shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Personel select --}}
                <div>
                    <label for="reported_by" class="block text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1">Personel <span class="text-error">*</span></label>
                    @if($isEdit)
                        <input type="hidden" name="reported_by" value="{{ $person->id }}">
                        <div class="bg-slate-50 border border-outline-variant/30 rounded-sm text-sm py-2 px-3 text-on-surface-variant font-semibold">
                            {{ $person->name }} ({{ strtoupper($person->role) }})
                        </div>
                    @else
                        <input type="hidden" name="reported_by" id="select-reported-by" value="{{ old('reported_by', $person?->id) }}">
                        <div class="relative" id="personnel-search-container">
                            <div class="relative">
                                <input type="text" id="personnel-search-input" readonly placeholder="— Pilih Personel —"
                                       class="w-full bg-white border border-outline-variant/30 rounded-sm text-sm py-2 pl-3 pr-8 focus:ring-1 focus:ring-primary focus:outline-none cursor-pointer"
                                       value="">
                                <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none text-lg">
                                    arrow_drop_down
                                </span>
                            </div>
                            
                            <div id="personnel-dropdown" class="hidden absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded shadow-lg max-h-60 overflow-y-auto z-50">
                                <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                    <div class="relative">
                                        <input type="text" id="personnel-filter-input" placeholder="Cari personel..."
                                               class="w-full bg-white border border-slate-200 rounded-sm text-xs py-1.5 pl-7 pr-3 focus:ring-1 focus:ring-primary focus:outline-none">
                                        <span class="material-symbols-outlined absolute left-2 top-1/2 -translate-y-1/2 text-slate-400 text-sm">
                                            search
                                        </span>
                                    </div>
                                </div>
                                <ul class="py-1 text-sm divide-y divide-slate-50" id="personnel-options-list">
                                    @foreach($allPersonnel as $u)
                                        <li class="option-item px-3 py-2 hover:bg-slate-50 cursor-pointer flex justify-between items-center" 
                                            data-value="{{ $u->id }}" 
                                            data-label="{{ $u->name }} ({{ strtoupper($u->role) }})"
                                            data-search="{{ strtolower($u->name . ' ' . $u->role) }}">
                                            <div>
                                                <span class="font-semibold text-slate-800">{{ $u->name }}</span>
                                                <span class="text-xs text-slate-400 ml-1">— {{ strtoupper($u->role) }}</span>
                                            </div>
                                            @if($u->department_name)
                                                <span class="text-[10px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded font-bold uppercase tracking-wider">{{ $u->department_name }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Tanggal input --}}
                <div>
                    <label for="report_date" class="block text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1">Tanggal <span class="text-error">*</span></label>
                    @if($isEdit)
                        <input type="hidden" name="report_date" value="{{ $date }}">
                        <div class="bg-slate-50 border border-outline-variant/30 rounded-sm text-sm py-2 px-3 text-on-surface-variant font-semibold">
                            {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                        </div>
                    @else
                        <input type="date" id="input-report-date" name="report_date" required
                               value="{{ old('report_date', $date) }}"
                               class="w-full bg-white border border-outline-variant/30 rounded-sm text-sm py-2 px-3 focus:ring-1 focus:ring-primary focus:outline-none">
                    @endif
                </div>

                {{-- Area select --}}
                <div>
                    <label for="area_id" class="block text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1">Area <span class="text-error">*</span></label>
                    @if($isEdit)
                        <input type="hidden" name="area_id" value="{{ $areaId }}">
                        @php
                            $areaModel = $activeAreas->firstWhere('id', $areaId);
                            $areaName = $areaModel?->name ?? 'Area #' . $areaId;
                        @endphp
                        <div class="bg-slate-50 border border-outline-variant/30 rounded-sm text-sm py-2 px-3 text-on-surface-variant font-semibold">
                            {{ $areaName }}
                        </div>
                    @else
                        <input type="hidden" name="area_id" id="select-area-id" value="{{ old('area_id', $areaId) }}">
                        <div class="relative" id="area-search-container">
                            <div class="relative">
                                <input type="text" id="area-search-input" readonly placeholder="— Pilih Area —"
                                       class="w-full bg-white border border-outline-variant/30 rounded-sm text-sm py-2 pl-3 pr-8 focus:ring-1 focus:ring-primary focus:outline-none cursor-pointer"
                                       value="">
                                <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none text-lg">
                                    arrow_drop_down
                                </span>
                            </div>
                            
                            <div id="area-dropdown" class="hidden absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded shadow-lg max-h-60 overflow-y-auto z-50">
                                <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0">
                                    <div class="relative">
                                        <input type="text" id="area-filter-input" placeholder="Cari area..."
                                               class="w-full bg-white border border-slate-200 rounded-sm text-xs py-1.5 pl-7 pr-3 focus:ring-1 focus:ring-primary focus:outline-none">
                                        <span class="material-symbols-outlined absolute left-2 top-1/2 -translate-y-1/2 text-slate-400 text-sm">
                                            search
                                        </span>
                                    </div>
                                </div>
                                <ul class="py-1 text-sm divide-y divide-slate-50" id="area-options-list">
                                    <!-- Populated dynamically via JS -->
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- High density table for work items --}}
        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-[11px] font-bold uppercase tracking-widest text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">add_task</span> Pekerjaan
                </h3>
                <button type="button" id="btn-add-work-item"
                        class="px-3 py-1.5 rounded-sm bg-surface-container-high text-on-surface-variant text-[10px] font-bold uppercase tracking-wider hover:bg-primary hover:text-white transition-all">
                    + Tambah Pekerjaan
                </button>
            </div>

            <div class="bg-white border border-outline-variant/20 rounded-sm overflow-x-auto shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-outline-variant/25 bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">
                            <th class="py-2.5 px-3 min-w-[280px] w-[32%]">Kegiatan & Deskripsi</th>
                            <th class="py-2.5 px-3 min-w-[180px] w-[20%]">Rencana Mingguan</th>
                            <th class="py-2.5 px-3 min-w-[180px] w-[18%]">Tanggal (Mulai – Selesai)</th>
                            <th class="py-2.5 px-3 min-w-[110px] w-[12%]">Status</th>
                            <th class="py-2.5 px-3 min-w-[170px] w-[18%]">Proof of Work (Peroni Cloud)</th>
                            <th class="py-2.5 px-3 w-10 text-center"></th>
                        </tr>
                    </thead>
                    <tbody id="work-item-list-body" class="divide-y divide-outline-variant/10">
                        @if(old('work_items'))
                            @foreach(old('work_items') as $idx => $item)
                                @include('daily-reports._row', ['index' => $idx, 'item' => $item, 'weeklyPlans' => $weeklyPlans, 'defaultDate' => $defaultDate])
                            @endforeach
                        @elseif($isEdit && count($reportItems) > 0)
                            @foreach($reportItems as $idx => $item)
                                @include('daily-reports._row', ['index' => $idx, 'item' => $item, 'weeklyPlans' => $weeklyPlans, 'defaultDate' => $defaultDate])
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Catatan Hari Ini (opsional) --}}
        <section class="space-y-2">
            <h3 class="text-[11px] font-bold uppercase tracking-widest text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-base">notes</span> Catatan Hari Ini
                <span class="text-on-surface-variant font-normal normal-case tracking-normal text-[10px]">(opsional)</span>
            </h3>
            <textarea name="today_result" rows="2"
                      class="w-full bg-white border border-outline-variant/30 rounded-sm text-sm p-3 resize-none focus:ring-1 focus:ring-primary focus:outline-none"
                      placeholder="Ringkasan catatan apa yang terjadi hari ini...">{{ old('today_result', $report?->today_result) }}</textarea>
        </section>

        {{-- Submit --}}
        <div class="pt-4 border-t border-outline-variant/15 flex justify-end">
            <button id="btn-submit" type="submit"
                    class="px-8 py-3 bg-primary text-white rounded-sm text-xs font-bold uppercase tracking-widest hover:brightness-110 active:scale-[0.98] transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">save</span>
                <span id="btn-submit-label">Simpan Laporan</span>
            </button>
        </div>
    </form>
</div>

{{-- Template (hidden, cloned by JS) --}}
<template id="work-item-template">
    <tr class="work-item-row hover:bg-slate-50/50 transition-colors align-top">
        <input type="hidden" name="work_items[__INDEX__][id]" value="">
        
        {{-- Kegiatan & Deskripsi stacked --}}
        <td class="py-2 px-2 space-y-1">
            <input type="text"
                   name="work_items[__INDEX__][title]"
                   required
                   class="work-item-title w-full bg-white border border-outline-variant/20 rounded-sm text-sm py-1 px-2 focus:ring-1 focus:ring-primary focus:outline-none"
                   placeholder="Judul kegiatan (misal: Opname Rak B-2)">
            <input type="text"
                   name="work_items[__INDEX__][description]"
                   class="w-full bg-slate-50 border border-outline-variant/15 rounded-sm text-xs py-0.5 px-2 focus:ring-1 focus:ring-primary focus:outline-none text-on-surface-variant placeholder:text-on-surface-variant/50"
                   placeholder="Deskripsi tambahan (opsional)...">
        </td>

        {{-- Rencana Mingguan Terkait select --}}
        <td class="py-2 px-2">
            <select name="work_items[__INDEX__][weekly_plan_id]"
                    class="work-item-weekly-plan w-full bg-white border border-outline-variant/20 rounded-sm text-sm py-1 px-2 focus:ring-1 focus:ring-primary focus:outline-none">
                <option value="">— Tidak terkait rencana —</option>
            </select>
        </td>

        {{-- Durasi (Mulai & Selesai) --}}
        <td class="py-2 px-2">
            <div class="flex items-center gap-1">
                <input type="date"
                       name="work_items[__INDEX__][planned_start_date]"
                       value="__DEFAULT_DATE__"
                       required
                       class="w-1/2 bg-white border border-outline-variant/20 rounded-sm text-xs py-1 px-1 focus:ring-1 focus:ring-primary focus:outline-none">
                <span class="text-xs text-on-surface-variant">–</span>
                <input type="date"
                       name="work_items[__INDEX__][planned_end_date]"
                       value="__DEFAULT_DATE__"
                       required
                       class="w-1/2 bg-white border border-outline-variant/20 rounded-sm text-xs py-1 px-1 focus:ring-1 focus:ring-primary focus:outline-none">
            </div>
        </td>

        {{-- Status --}}
        <td class="py-2 px-2">
            <select name="work_items[__INDEX__][status]"
                    class="work-item-status w-full bg-white border border-outline-variant/20 rounded-sm text-sm py-1 px-2 focus:ring-1 focus:ring-primary focus:outline-none font-semibold">
                <option value="not_started" selected class="text-slate-700">Belum Mulai</option>
                <option value="in_progress" class="text-blue-700">Berjalan</option>
                <option value="blocked" class="text-amber-700">Terblokir</option>
                <option value="completed" class="text-green-700">Selesai</option>
                <option value="cancelled" class="text-red-700">Dibatalkan</option>
            </select>
        </td>

        {{-- Proof of Work (URL Peroni Cloud) --}}
        <td class="py-2 px-2">
            <input type="url"
                   name="work_items[__INDEX__][proof_of_work_url]"
                   class="work-item-proof-url w-full bg-white border border-outline-variant/20 rounded-sm text-xs py-1 px-2 focus:ring-1 focus:ring-primary focus:outline-none placeholder:text-slate-400 font-mono"
                   placeholder="http://10.88.8.46:1001/photos/...">
        </td>

        {{-- Remove button --}}
        <td class="py-2 px-2 text-center align-middle">
            <button type="button" class="btn-remove-item text-on-surface-variant hover:text-error transition-colors p-1"
                    title="Hapus pekerjaan ini">
                <span class="material-symbols-outlined text-lg">delete</span>
            </button>
        </td>
    </tr>
</template>

<script>
(function () {
    let workItemIndex = 0;
    let currentWeeklyPlans = @json($weeklyPlans->map(fn($p) => ['id' => $p->id, 'title' => $p->title]));
    let currentAreas = @json($activeAreas->map(fn($a) => ['id' => $a->id, 'name' => $a->name]));
    const optionsUrlTemplate = "{{ route('api.users.daily-report-options', ['user' => ':user']) }}";

    function getDefaultDate() {
        const input = document.getElementById('input-report-date');
        return input ? input.value : '{{ $defaultDate }}';
    }

    function styleStatusSelect(select) {
        if (!select) return;
        const val = select.value;
        select.classList.remove('text-slate-700', 'text-blue-700', 'text-amber-700', 'text-green-700', 'text-red-700');
        if (val === 'not_started') select.classList.add('text-slate-700');
        else if (val === 'in_progress') select.classList.add('text-blue-700');
        else if (val === 'blocked') select.classList.add('text-amber-700');
        else if (val === 'completed') select.classList.add('text-green-700');
        else if (val === 'cancelled') select.classList.add('text-red-700');
    }

    function showNonBlockingWarning(message) {
        let warnDiv = document.getElementById('ajax-warning');
        if (!warnDiv) {
            warnDiv = document.createElement('div');
            warnDiv.id = 'ajax-warning';
            warnDiv.className = 'fixed bottom-4 right-4 bg-amber-50 border border-amber-300 text-amber-900 px-4 py-3 rounded-sm shadow-md text-xs flex items-center gap-2 z-50 transition-all duration-300';
            document.body.appendChild(warnDiv);
        }
        warnDiv.innerHTML = `<span class="material-symbols-outlined text-amber-500 text-sm">warning</span> <span>${message}</span>`;
        warnDiv.style.opacity = '1';
        setTimeout(() => {
            warnDiv.style.opacity = '0';
            setTimeout(() => warnDiv.remove(), 300);
        }, 5000);
    }

    function updateAllWeeklyPlanDropdowns() {
        const selects = document.querySelectorAll('.work-item-weekly-plan');
        selects.forEach(select => {
            const currentSelected = select.value || select.getAttribute('data-selected');
            select.innerHTML = '<option value="">— Tidak terkait rencana —</option>';
            currentWeeklyPlans.forEach(plan => {
                const opt = document.createElement('option');
                opt.value = plan.id;
                opt.textContent = plan.title;
                if (currentSelected == plan.id) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });
        });
    }

    function updateAreaDropdownOptions() {
        const list = document.getElementById('area-options-list');
        if (!list) return;

        list.innerHTML = '';
        const hiddenInput = document.getElementById('select-area-id');
        const searchInput = document.getElementById('area-search-input');
        const currentValue = hiddenInput ? hiddenInput.value : '';

        let currentLabel = '';

        currentAreas.forEach(a => {
            const li = document.createElement('li');
            li.className = 'option-item px-3 py-2 hover:bg-slate-50 cursor-pointer flex justify-between items-center';
            li.setAttribute('data-value', a.id);
            li.setAttribute('data-label', a.name);
            li.setAttribute('data-search', a.name.toLowerCase());

            li.innerHTML = `<div><span class="font-semibold text-slate-800">${a.name}</span></div>`;

            if (currentValue == a.id) {
                currentLabel = a.name;
                li.classList.add('bg-slate-100');
            }

            li.addEventListener('click', function() {
                if (hiddenInput) {
                    hiddenInput.value = a.id;
                    const event = new Event('change', { bubbles: true });
                    hiddenInput.dispatchEvent(event);
                }
                if (searchInput) {
                    searchInput.value = a.name;
                }
                document.getElementById('area-dropdown').classList.add('hidden');
            });

            list.appendChild(li);
        });

        if (searchInput) {
            searchInput.value = currentLabel || '';
        }
    }

    function initPersonnelSelector() {
        const hiddenInput = document.getElementById('select-reported-by');
        const searchInput = document.getElementById('personnel-search-input');
        const dropdown = document.getElementById('personnel-dropdown');
        const filterInput = document.getElementById('personnel-filter-input');
        const optionsList = document.getElementById('personnel-options-list');

        if (!searchInput || !dropdown || !optionsList) return;

        searchInput.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.remove('hidden');
            if (filterInput) {
                filterInput.value = '';
                filterInput.focus();
            }
            const items = optionsList.querySelectorAll('li');
            items.forEach(item => item.classList.remove('hidden'));
        });

        if (filterInput) {
            filterInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const items = optionsList.querySelectorAll('li');
                items.forEach(item => {
                    const searchAttr = item.getAttribute('data-search') || '';
                    if (searchAttr.includes(query)) {
                        item.classList.remove('hidden');
                    } else {
                        item.classList.add('hidden');
                    }
                });
            });
            filterInput.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }

        const items = optionsList.querySelectorAll('li');
        items.forEach(item => {
            item.addEventListener('click', function() {
                const val = this.getAttribute('data-value');
                const label = this.getAttribute('data-label');
                if (hiddenInput) {
                    hiddenInput.value = val;
                    const event = new Event('change', { bubbles: true });
                    hiddenInput.dispatchEvent(event);
                }
                searchInput.value = label;
                dropdown.classList.add('hidden');
            });
        });

        if (hiddenInput && hiddenInput.value) {
            const matched = optionsList.querySelector(`li[data-value="${hiddenInput.value}"]`);
            if (matched) {
                searchInput.value = matched.getAttribute('data-label');
            }
        }
    }

    function initAreaSelector() {
        const searchInput = document.getElementById('area-search-input');
        const dropdown = document.getElementById('area-dropdown');
        const filterInput = document.getElementById('area-filter-input');
        const optionsList = document.getElementById('area-options-list');

        if (!searchInput || !dropdown || !optionsList) return;

        searchInput.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.remove('hidden');
            if (filterInput) {
                filterInput.value = '';
                filterInput.focus();
            }
            const items = optionsList.querySelectorAll('li');
            items.forEach(item => item.classList.remove('hidden'));
        });

        if (filterInput) {
            filterInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const items = optionsList.querySelectorAll('li');
                items.forEach(item => {
                    const searchAttr = item.getAttribute('data-search') || '';
                    if (searchAttr.includes(query)) {
                        item.classList.remove('hidden');
                    } else {
                        item.classList.add('hidden');
                    }
                });
            });
            filterInput.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    }

    // Close on click outside
    document.addEventListener('click', function(e) {
        const personnelDropdown = document.getElementById('personnel-dropdown');
        const personnelSearchContainer = document.getElementById('personnel-search-container');
        if (personnelDropdown && personnelSearchContainer && !personnelSearchContainer.contains(e.target)) {
            personnelDropdown.classList.add('hidden');
        }

        const areaDropdown = document.getElementById('area-dropdown');
        const areaSearchContainer = document.getElementById('area-search-container');
        if (areaDropdown && areaSearchContainer && !areaSearchContainer.contains(e.target)) {
            areaDropdown.classList.add('hidden');
        }
    });

    const selectPerson = document.getElementById('select-reported-by');
    const inputDate = document.getElementById('input-report-date');
    const selectArea = document.getElementById('select-area-id');
    let isAppendMode = false;

    function renderAppendWorkItems(existingItems) {
        const listBody = document.getElementById('work-item-list-body');
        listBody.innerHTML = '';
        workItemIndex = 0;

        existingItems.forEach((item) => {
            const template = document.getElementById('work-item-template');
            let html = template.innerHTML
                .replaceAll('__INDEX__', workItemIndex)
                .replaceAll('__DEFAULT_DATE__', getDefaultDate());

            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = html.trim();
            const row = wrapper.firstElementChild;

            // Set ID
            const idInput = row.querySelector('input[name*="[id]"]');
            if (idInput) idInput.value = item.id;

            // Set Title & Description
            const titleInput = row.querySelector('.work-item-title');
            if (titleInput) titleInput.value = item.title;

            const descInput = row.querySelector('input[name*="[description]"]');
            if (descInput) descInput.value = item.description || '';

            // Set Weekly Plan
            const weeklySelect = row.querySelector('.work-item-weekly-plan');
            weeklySelect.innerHTML = '<option value="">— Tidak terkait rencana —</option>';
            currentWeeklyPlans.forEach(plan => {
                const opt = document.createElement('option');
                opt.value = plan.id;
                opt.textContent = plan.title;
                if (item.weekly_plan_id == plan.id) {
                    opt.selected = true;
                }
                weeklySelect.appendChild(opt);
            });

            // Set Dates
            const startInput = row.querySelector('input[name*="[planned_start_date]"]');
            const endInput = row.querySelector('input[name*="[planned_end_date]"]');
            if (startInput && item.planned_start_date) startInput.value = item.planned_start_date;
            if (endInput && item.planned_end_date) endInput.value = item.planned_end_date;

            // Set Status
            const statusSelect = row.querySelector('.work-item-status');
            if (statusSelect && item.status) {
                statusSelect.value = item.status;
                styleStatusSelect(statusSelect);
            }

            // Set Proof of Work URL
            const proofInput = row.querySelector('.work-item-proof-url');
            if (proofInput && item.proof_of_work_url) {
                proofInput.value = item.proof_of_work_url;
            }

            // Visual badge "Tersimpan"
            const titleCell = row.querySelector('td:first-child');
            if (titleCell) {
                const badge = document.createElement('div');
                badge.className = 'flex items-center gap-1 mt-0.5';
                badge.innerHTML = '<span class="px-1.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wide bg-slate-100 text-slate-500 border border-slate-200/60 rounded">Tersimpan</span>';
                titleCell.appendChild(badge);
            }

            row.querySelector('.btn-remove-item').addEventListener('click', function () {
                row.remove();
                reindex();
            });

            if (titleInput) {
                titleInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        addWorkItem();
                        const allRows = document.querySelectorAll('#work-item-list-body .work-item-row');
                        if (allRows.length > 0) {
                            const lastRow = allRows[allRows.length - 1];
                            const lastTitle = lastRow.querySelector('.work-item-title');
                            if (lastTitle) lastTitle.focus();
                        }
                    }
                });
            }

            listBody.appendChild(row);
            workItemIndex++;
        });

        // Always append one blank new work item row below existing ones
        addWorkItem();
    }

    function fetchOptions() {
        if (!selectPerson || !inputDate) return;
        const personId = selectPerson.value;
        const dateVal = inputDate.value;

        if (!personId || !dateVal) return;

        const url = optionsUrlTemplate.replace(':user', personId) + '?date=' + dateVal;

        fetch(url)
            .then(res => {
                if (!res.ok) throw new Error('Failed to fetch options');
                return res.json();
            })
            .then(data => {
                currentAreas = data.areas;
                updateAreaDropdownOptions();

                currentWeeklyPlans = data.weekly_plans;
                updateAllWeeklyPlanDropdowns();

                const banner = document.getElementById('existing-report-banner');
                const form = document.getElementById('daily-report-form');
                const submitLabel = document.getElementById('btn-submit-label');

                if (data.existing_report && !{{ $isEdit ? 'true' : 'false' }}) {
                    isAppendMode = true;

                    // Show notification banner
                    if (banner) {
                        banner.classList.remove('hidden');
                        const personNameInput = document.getElementById('personnel-search-input');
                        const personName = personNameInput ? personNameInput.value : 'Personel';

                        document.getElementById('banner-person-name').textContent = personName;
                        document.getElementById('banner-date').textContent = data.existing_report.report_date;
                        document.getElementById('banner-area-name').textContent = data.existing_report.area_name;
                        document.getElementById('banner-item-count').textContent = data.existing_report.work_items.length;
                        document.getElementById('banner-edit-link').href = data.existing_report.edit_url;
                    }

                    // Set Area
                    if (data.existing_report.area_id) {
                        const hiddenAreaInput = document.getElementById('select-area-id');
                        const searchAreaInput = document.getElementById('area-search-input');
                        if (hiddenAreaInput && searchAreaInput) {
                            hiddenAreaInput.value = data.existing_report.area_id;
                            searchAreaInput.value = data.existing_report.area_name;
                            updateAreaDropdownOptions();
                        }
                    }

                    // Set today_result if empty
                    const todayResultTextarea = document.querySelector('textarea[name="today_result"]');
                    if (todayResultTextarea && !todayResultTextarea.value.trim() && data.existing_report.today_result) {
                        todayResultTextarea.value = data.existing_report.today_result;
                    }

                    // Update form action & method to PUT existing report
                    form.action = data.existing_report.update_url;
                    let methodInput = document.getElementById('append-method-override');
                    if (!methodInput) {
                        methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.id = 'append-method-override';
                        form.appendChild(methodInput);
                    }
                    methodInput.value = 'PUT';

                    if (submitLabel) {
                        submitLabel.textContent = 'Simpan Tambahan Pekerjaan';
                    }

                    // Render existing items + 1 new blank row
                    renderAppendWorkItems(data.existing_report.work_items);

                } else if (!{{ $isEdit ? 'true' : 'false' }}) {
                    if (banner) banner.classList.add('hidden');

                    if (isAppendMode) {
                        isAppendMode = false;
                        form.action = "{{ route('daily-reports.store') }}";
                        const methodInput = document.getElementById('append-method-override');
                        if (methodInput) methodInput.remove();

                        if (submitLabel) submitLabel.textContent = 'Simpan Laporan';

                        const listBody = document.getElementById('work-item-list-body');
                        listBody.innerHTML = '';
                        workItemIndex = 0;
                        addWorkItem();
                    }

                    if (data.has_active_assignments && data.areas.length === 1) {
                        const hiddenAreaInput = document.getElementById('select-area-id');
                        const searchAreaInput = document.getElementById('area-search-input');
                        if (hiddenAreaInput && searchAreaInput) {
                            hiddenAreaInput.value = data.areas[0].id;
                            searchAreaInput.value = data.areas[0].name;
                            updateAreaDropdownOptions();
                        }
                    }
                }
            })
            .catch(err => {
                console.error(err);
                showNonBlockingWarning('Koneksi gagal: Gagal memuat rencana mingguan terbaru.');
            });
    }

    if (selectPerson) selectPerson.addEventListener('change', fetchOptions);
    if (inputDate) inputDate.addEventListener('change', fetchOptions);

    function addWorkItem() {
        const template = document.getElementById('work-item-template');
        let html = template.innerHTML
            .replaceAll('__INDEX__', workItemIndex)
            .replaceAll('__DEFAULT_DATE__', getDefaultDate());

        const wrapper = document.createElement('tbody');
        wrapper.innerHTML = html.trim();
        const row = wrapper.firstElementChild;

        const weeklySelect = row.querySelector('.work-item-weekly-plan');
        weeklySelect.innerHTML = '<option value="">— Tidak terkait rencana —</option>';
        currentWeeklyPlans.forEach(plan => {
            const opt = document.createElement('option');
            opt.value = plan.id;
            opt.textContent = plan.title;
            weeklySelect.appendChild(opt);
        });

        row.querySelector('.btn-remove-item').addEventListener('click', function () {
            row.remove();
            reindex();
        });

        const titleInput = row.querySelector('.work-item-title');
        titleInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addWorkItem();
                const allRows = document.querySelectorAll('#work-item-list-body .work-item-row');
                if (allRows.length > 0) {
                    const lastRow = allRows[allRows.length - 1];
                    const lastTitle = lastRow.querySelector('.work-item-title');
                    if (lastTitle) lastTitle.focus();
                }
            }
        });

        styleStatusSelect(row.querySelector('.work-item-status'));

        document.getElementById('work-item-list-body').appendChild(row);
        workItemIndex++;
    }

    function reindex() {
        const rows = document.querySelectorAll('#work-item-list-body .work-item-row');
        rows.forEach(function (row, i) {
            row.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace(/work_items\[\d+\]/, 'work_items[' + i + ']');
            });
        });
        workItemIndex = rows.length;
    }

    function initExistingRows() {
        const rows = document.querySelectorAll('#work-item-list-body .work-item-row');
        rows.forEach((row, i) => {
            const statusSelect = row.querySelector('.work-item-status');
            if (statusSelect) styleStatusSelect(statusSelect);

            const removeBtn = row.querySelector('.btn-remove-item');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    row.remove();
                    reindex();
                });
            }

            const titleInput = row.querySelector('.work-item-title');
            if (titleInput) {
                titleInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        addWorkItem();
                        const allRows = document.querySelectorAll('#work-item-list-body .work-item-row');
                        if (allRows.length > 0) {
                            const lastRow = allRows[allRows.length - 1];
                            const lastTitle = lastRow.querySelector('.work-item-title');
                            if (lastTitle) lastTitle.focus();
                        }
                    }
                });
            }
        });
        workItemIndex = rows.length;
    }

    let isFormDirty = false;

    const reportForm = document.getElementById('daily-report-form');
    if (reportForm) {
        reportForm.addEventListener('input', function () {
            isFormDirty = true;
        });
        reportForm.addEventListener('change', function () {
            isFormDirty = true;
        });
    }

    document.querySelectorAll('.date-nav-link').forEach(link => {
        link.addEventListener('click', function (e) {
            if (isFormDirty) {
                if (!confirm('Perubahan belum disimpan. Lanjut tanpa menyimpan?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    });

    document.getElementById('btn-add-work-item').addEventListener('click', function() {
        isFormDirty = true;
        addWorkItem();
    });

    document.getElementById('work-item-list-body').addEventListener('change', function (e) {
        if (e.target.classList.contains('work-item-status')) {
            styleStatusSelect(e.target);
        }
    });

    document.getElementById('daily-report-form').addEventListener('submit', function () {
        isFormDirty = false;
        reindex();
        const btn = document.getElementById('btn-submit');
        const label = document.getElementById('btn-submit-label');
        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');
        label.textContent = 'Menyimpan...';
    });

    window.addEventListener('DOMContentLoaded', function () {
        initPersonnelSelector();
        initAreaSelector();
        updateAreaDropdownOptions();
        initExistingRows();
        if (document.getElementById('work-item-list-body').children.length === 0) {
            addWorkItem();
        }
        if (selectPerson && selectPerson.value && !{{ $isEdit ? 'true' : 'false' }}) {
            fetchOptions();
        }
    });
})();
</script>
