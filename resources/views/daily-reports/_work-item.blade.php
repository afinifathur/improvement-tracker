@php
    $statusValue = $item->status instanceof \App\Enums\WorkItemStatus ? $item->status->value : (string) $item->status;
    $badge = match ($statusValue) {
        'not_started' => 'bg-slate-100 text-slate-600 border-slate-200',
        'in_progress' => 'bg-blue-100 text-blue-700 border-blue-200',
        'blocked' => 'bg-amber-100 text-amber-800 border-amber-200',
        'completed' => 'bg-green-100 text-green-700 border-green-200',
        'cancelled' => 'bg-red-100 text-red-700 border-red-200',
        default => 'bg-slate-100 text-slate-600 border-slate-200',
    };
    $blockedReason = $item->blocked_reason instanceof \App\Enums\BlockedReason ? $item->blocked_reason->value : $item->blocked_reason;
    $statusLabel = match ($statusValue) {
        'not_started' => 'Belum Mulai',
        'in_progress' => 'Berjalan',
        'blocked' => 'Terblokir',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        default => str_replace('_', ' ', $statusValue),
    };
    $blockedReasonLabel = match ($blockedReason) {
        'waiting_material' => 'Menunggu Material',
        'waiting_sparepart' => 'Menunggu Sparepart',
        'waiting_approval' => 'Menunggu Persetujuan',
        'waiting_vendor' => 'Menunggu Vendor',
        'machine_unavailable' => 'Mesin Tidak Tersedia',
        'manpower' => 'Kekurangan Tenaga',
        'external_dependency' => 'Ketergantungan Eksternal',
        'other' => 'Lainnya',
        default => str_replace('_', ' ', $blockedReason),
    };
@endphp
<div class="flex items-start justify-between gap-4 px-4 py-3 border-b border-outline-variant/10 last:border-b-0">
    <div class="min-w-0">
        <p class="text-sm font-semibold text-inverse-surface">{{ $item->title }}</p>
        @if($item->description)
        <p class="text-xs text-on-surface-variant mt-0.5">{{ $item->description }}</p>
        @endif
        @if($statusValue === 'blocked' && $blockedReason)
        <p class="text-[10px] text-amber-700 mt-0.5">Terblokir: {{ $blockedReasonLabel }}</p>
        @endif
    </div>
    <div class="shrink-0 text-right space-y-1">
        <p class="text-xs text-on-surface-variant">{{ $item->planned_start_date->format('d M') }} – {{ $item->planned_end_date->format('d M') }}</p>
        <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider border {{ $badge }}">{{ $statusLabel }}</span>
    </div>
</div>
