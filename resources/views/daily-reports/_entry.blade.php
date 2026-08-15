@php
    $isEdit = ! is_null($report);
    $formAction = $isEdit ? route('daily-reports.update', $report) : route('daily-reports.store');
@endphp

<div class="p-6 max-w-4xl mx-auto w-full space-y-6">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1">
                <a href="{{ route('daily-reports.index', ['date' => $date]) }}" class="hover:text-primary">Daily Reports</a>
                <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                <span class="text-primary">{{ $isEdit ? 'Edit' : 'New' }} Report</span>
            </nav>
            <h2 class="text-2xl font-bold tracking-tight text-inverse-surface">Daily Report</h2>
        </div>
        <div class="flex gap-2 text-xs">
            <a href="{{ route('daily-reports.index', ['date' => $date]) }}" class="px-3 py-1.5 rounded-sm bg-surface-container-high text-on-surface-variant font-bold uppercase tracking-wider hover:bg-tertiary hover:text-white transition-all">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="bg-white p-3 border border-outline-variant/20 rounded-sm">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Person</span>
            <span class="text-sm font-semibold text-inverse-surface">{{ $person->name }}</span>
            <span class="block text-[10px] text-on-surface-variant uppercase">{{ $person->role }}</span>
        </div>
        <div class="bg-white p-3 border border-outline-variant/20 rounded-sm">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Department</span>
            <span class="text-sm font-semibold text-inverse-surface">{{ $person->department?->name ?? '—' }}</span>
        </div>
        <div class="bg-white p-3 border border-outline-variant/20 rounded-sm">
            <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant block mb-1">Date</span>
            <span class="text-sm font-semibold text-inverse-surface">{{ \Illuminate\Support\Carbon::parse($date)->format('l, d F Y') }}</span>
        </div>
    </div>

    <section class="space-y-3">
        <h3 class="text-[11px] font-bold uppercase tracking-widest text-on-surface flex items-center gap-2">
            <span class="material-symbols-outlined text-base">task</span> Active Work
        </h3>

        <div class="space-y-3">
            <div>
                <h4 class="text-[10px] font-bold uppercase tracking-widest text-error mb-1">Overdue ({{ count($workItems['overdue']) }})</h4>
                <div class="bg-white border border-outline-variant/20 rounded-sm">
                    @forelse($workItems['overdue'] as $item)
                        @include('daily-reports._work-item', ['item' => $item])
                    @empty
                        <div class="px-4 py-3 text-xs text-on-surface-variant italic">No overdue work.</div>
                    @endforelse
                </div>
            </div>

            <div>
                <h4 class="text-[10px] font-bold uppercase tracking-widest text-primary mb-1">Current ({{ count($workItems['current']) }})</h4>
                <div class="bg-white border border-outline-variant/20 rounded-sm">
                    @forelse($workItems['current'] as $item)
                        @include('daily-reports._work-item', ['item' => $item])
                    @empty
                        <div class="px-4 py-3 text-xs text-on-surface-variant italic">No current work.</div>
                    @endforelse
                </div>
            </div>

            <details class="bg-white border border-outline-variant/20 rounded-sm">
                <summary class="px-4 py-3 cursor-pointer text-[10px] font-bold uppercase tracking-widest text-on-surface-variant hover:text-primary">Future ({{ count($workItems['future']) }})</summary>
                <div class="border-t border-outline-variant/10">
                    @forelse($workItems['future'] as $item)
                        @include('daily-reports._work-item', ['item' => $item])
                    @empty
                        <div class="px-4 py-3 text-xs text-on-surface-variant italic">No future work.</div>
                    @endforelse
                </div>
            </details>
        </div>
    </section>

    <form action="{{ $formAction }}" method="POST" class="space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @else
            <input type="hidden" name="report_date" value="{{ $date }}">
            <input type="hidden" name="reported_by" value="{{ $person->id }}">
        @endif

        <section class="space-y-2">
            <h3 class="text-[11px] font-bold uppercase tracking-widest text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-base">notes</span> Today's Result
            </h3>
            <textarea name="today_result" rows="4" class="w-full bg-white border border-outline-variant/30 rounded-sm text-sm p-3 resize-none focus:ring-primary" placeholder="What happened today?">{{ old('today_result', $report?->today_result) }}</textarea>
        </section>

        <section class="space-y-2">
            <div class="flex items-center justify-between">
                <h3 class="text-[11px] font-bold uppercase tracking-widest text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">add_task</span> New Work Items
                </h3>
                <button type="button" onclick="addWorkItem()" class="px-3 py-1.5 rounded-sm bg-surface-container-high text-on-surface-variant text-[10px] font-bold uppercase tracking-wider hover:bg-primary hover:text-white transition-all">+ Add Work Item</button>
            </div>
            <div id="work-item-list" class="space-y-2"></div>
            <template id="work-item-template">
                <div class="work-item-row grid grid-cols-12 gap-2 bg-white border border-outline-variant/20 rounded-sm p-3">
                    <div class="col-span-12 md:col-span-5">
                        <label class="block text-[9px] font-bold uppercase tracking-widest text-on-surface-variant mb-1">Title</label>
                        <input type="text" name="work_items[][title]" required class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-sm text-sm py-1.5 px-2 focus:ring-primary" placeholder="Work item title">
                    </div>
                    <div class="col-span-12 md:col-span-5">
                        <label class="block text-[9px] font-bold uppercase tracking-widest text-on-surface-variant mb-1">Description (optional)</label>
                        <input type="text" name="work_items[][description]" class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-sm text-sm py-1.5 px-2 focus:ring-primary" placeholder="Context">
                    </div>
                    <div class="col-span-6 md:col-span-1">
                        <label class="block text-[9px] font-bold uppercase tracking-widest text-on-surface-variant mb-1">Start</label>
                        <input type="date" name="work_items[][planned_start_date]" value="{{ $defaultDate }}" required class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-sm text-sm py-1.5 px-1 focus:ring-primary">
                    </div>
                    <div class="col-span-6 md:col-span-1">
                        <label class="block text-[9px] font-bold uppercase tracking-widest text-on-surface-variant mb-1">End</label>
                        <input type="date" name="work_items[][planned_end_date]" value="{{ $defaultDate }}" required class="w-full bg-surface-container-lowest border border-outline-variant/30 rounded-sm text-sm py-1.5 px-1 focus:ring-primary">
                    </div>
                    <div class="col-span-12 flex items-center justify-end">
                        <button type="button" onclick="removeWorkItem(this)" class="text-on-surface-variant hover:text-error text-sm">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                </div>
            </template>
        </section>

        <div class="pt-4 border-t border-outline-variant/15 flex justify-end">
            <button type="submit" class="px-8 py-3 bg-primary text-white rounded-sm text-xs font-bold uppercase tracking-widest hover:brightness-110 active:scale-[0.98] transition-all">
                Save Report
            </button>
        </div>
    </form>
</div>

<script>
    function addWorkItem() {
        const template = document.getElementById('work-item-template');
        const node = template.content.cloneNode(true);
        document.getElementById('work-item-list').appendChild(node);
    }

    function removeWorkItem(button) {
        button.closest('.work-item-row').remove();
    }
</script>
