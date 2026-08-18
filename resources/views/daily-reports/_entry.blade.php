@php
    $isEdit = ! is_null($report);
    $formAction = $isEdit ? route('daily-reports.update', $report) : route('daily-reports.store');
@endphp

<div class="p-6 max-w-6xl mx-auto w-full space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1">
                <a href="{{ route('daily-reports.index', ['date' => $date]) }}" class="hover:text-primary">Laporan Harian</a>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-primary">{{ $isEdit ? 'Ubah' : 'Baru' }} Laporan</span>
            </nav>
            <h2 class="text-2xl font-bold tracking-tight text-inverse-surface">Laporan Harian</h2>
        </div>
        <a href="{{ route('daily-reports.index', ['date' => $date]) }}"
           class="px-3 py-1.5 rounded-sm bg-surface-container-high text-on-surface-variant text-[10px] font-bold uppercase tracking-wider hover:bg-tertiary hover:text-white transition-all self-start md:self-auto">
            Kembali
        </a>
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
                            <th class="py-2.5 px-3 min-w-[320px] w-[40%]">Kegiatan & Deskripsi</th>
                            <th class="py-2.5 px-3 min-w-[200px] w-[25%]">Rencana Mingguan Terkait</th>
                            <th class="py-2.5 px-3 min-w-[200px] w-[20%]">Tanggal (Mulai – Selesai)</th>
                            <th class="py-2.5 px-3 min-w-[120px] w-[15%]">Status</th>
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

                if (data.has_active_assignments && data.areas.length === 1) {
                    const hiddenAreaInput = document.getElementById('select-area-id');
                    const searchAreaInput = document.getElementById('area-search-input');
                    if (hiddenAreaInput && searchAreaInput) {
                        hiddenAreaInput.value = data.areas[0].id;
                        searchAreaInput.value = data.areas[0].name;
                        updateAreaDropdownOptions();
                    }
                }

                currentWeeklyPlans = data.weekly_plans;
                updateAllWeeklyPlanDropdowns();
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

    document.getElementById('btn-add-work-item').addEventListener('click', addWorkItem);

    document.getElementById('work-item-list-body').addEventListener('change', function (e) {
        if (e.target.classList.contains('work-item-status')) {
            styleStatusSelect(e.target);
        }
    });

    document.getElementById('daily-report-form').addEventListener('submit', function () {
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
    });
})();
</script>
