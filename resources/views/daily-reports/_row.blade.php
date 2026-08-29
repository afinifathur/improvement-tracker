@php
    $itemId = is_array($item) ? ($item['id'] ?? '') : ($item->id ?? '');
    $title = is_array($item) ? ($item['title'] ?? '') : ($item->title ?? '');
    $description = is_array($item) ? ($item['description'] ?? '') : ($item->description ?? '');
    $weeklyPlanId = is_array($item) ? ($item['weekly_plan_id'] ?? '') : ($item->weekly_plan_id ?? '');
    
    // Dates
    $start = is_array($item) ? ($item['planned_start_date'] ?? $defaultDate) : ($item->planned_start_date ? $item->planned_start_date->toDateString() : $defaultDate);
    $end = is_array($item) ? ($item['planned_end_date'] ?? $defaultDate) : ($item->planned_end_date ? $item->planned_end_date->toDateString() : $defaultDate);
    
    // Status
    $status = is_array($item) ? ($item['status'] ?? 'not_started') : ($item->status?->value ?? $item->status ?? 'not_started');
@endphp

<tr class="work-item-row hover:bg-slate-50/50 transition-colors align-top">
    @if(!empty($itemId))
        <input type="hidden" name="work_items[{{ $index }}][id]" value="{{ $itemId }}">
    @endif
    
    {{-- Kegiatan & Deskripsi stacked --}}
    <td class="py-2 px-2 space-y-1">
        <input type="text"
               name="work_items[{{ $index }}][title]"
               value="{{ $title }}"
               required
               class="work-item-title w-full bg-white border border-outline-variant/20 rounded-sm text-sm py-1 px-2 focus:ring-1 focus:ring-primary focus:outline-none"
               placeholder="Judul kegiatan (misal: Opname Rak B-2)">
        <input type="text"
               name="work_items[{{ $index }}][description]"
               value="{{ $description }}"
               class="w-full bg-slate-50 border border-outline-variant/15 rounded-sm text-xs py-0.5 px-2 focus:ring-1 focus:ring-primary focus:outline-none text-on-surface-variant placeholder:text-on-surface-variant/50"
               placeholder="Deskripsi tambahan (opsional)...">
    </td>

    {{-- Rencana Mingguan Terkait select --}}
    <td class="py-2 px-2">
        <select name="work_items[{{ $index }}][weekly_plan_id]"
                data-selected="{{ $weeklyPlanId }}"
                class="work-item-weekly-plan w-full bg-white border border-outline-variant/20 rounded-sm text-sm py-1 px-2 focus:ring-1 focus:ring-primary focus:outline-none">
            <option value="">— Tidak terkait rencana —</option>
            @foreach($weeklyPlans as $plan)
                <option value="{{ $plan->id }}" {{ $weeklyPlanId == $plan->id ? 'selected' : '' }}>{{ $plan->title }}</option>
            @endforeach
        </select>
    </td>

    {{-- Durasi (Mulai & Selesai) --}}
    <td class="py-2 px-2">
        <div class="flex items-center gap-1">
            <input type="date"
                   name="work_items[{{ $index }}][planned_start_date]"
                   value="{{ $start }}"
                   required
                   class="w-1/2 bg-white border border-outline-variant/20 rounded-sm text-xs py-1 px-1 focus:ring-1 focus:ring-primary focus:outline-none">
            <span class="text-xs text-on-surface-variant">–</span>
            <input type="date"
                   name="work_items[{{ $index }}][planned_end_date]"
                   value="{{ $end }}"
                   required
                   class="w-1/2 bg-white border border-outline-variant/20 rounded-sm text-xs py-1 px-1 focus:ring-1 focus:ring-primary focus:outline-none">
        </div>
    </td>

    {{-- Status --}}
    <td class="py-2 px-2">
        <select name="work_items[{{ $index }}][status]"
                class="work-item-status w-full bg-white border border-outline-variant/20 rounded-sm text-sm py-1 px-2 focus:ring-1 focus:ring-primary focus:outline-none font-semibold">
            <option value="not_started" {{ $status === 'not_started' ? 'selected' : '' }} class="text-slate-700">Belum Mulai</option>
            <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }} class="text-blue-700">Berjalan</option>
            <option value="blocked" {{ $status === 'blocked' ? 'selected' : '' }} class="text-amber-700">Terblokir</option>
            <option value="completed" {{ $status === 'completed' ? 'selected' : '' }} class="text-green-700">Selesai</option>
            <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }} class="text-red-700">Dibatalkan</option>
        </select>
    </td>

    {{-- Proof of Work (URL Peroni Cloud) --}}
    <td class="py-2 px-2">
        @php
            $proofOfWorkUrl = is_array($item) ? ($item['proof_of_work_url'] ?? '') : ($item->proof_of_work_url ?? '');
        @endphp
        <input type="url"
               name="work_items[{{ $index }}][proof_of_work_url]"
               value="{{ $proofOfWorkUrl }}"
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
