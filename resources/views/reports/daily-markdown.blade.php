@php
    $cell = function ($val) {
        if ($val === null || $val === '') {
            return '—';
        }
        $str = (string) $val;
        return str_replace(['|', "\r\n", "\r", "\n"], ['\|', ' ', ' ', ' '], trim($str));
    };
@endphp
# Daily Operational Snapshot — {!! $metadata['report_date'] !!}

> **Snapshot Metadata**  
> - **Report Date**: {!! $metadata['report_date'] !!} ({!! $metadata['day_name'] !!})  
> - **Generated At**: {!! $metadata['generated_at'] !!}  
> - **Snapshot Mode**: `{!! $metadata['snapshot_mode'] !!}`  
> - **Timezone**: {!! $metadata['timezone'] !!}  
> - **System Version**: {!! $metadata['system_version'] !!}  
> - **Snapshot ID**: `{!! $metadata['snapshot_id'] !!}`  

---

## 1. Executive Summary

| Metric | Count | Context / Definition |
|---|---:|---|
| **Total Active Workload** | {!! $summary['total_active'] !!} | Open workload (`not_started`, `in_progress`, `blocked`) |
| **Completed Today** | {!! $summary['completed_today'] !!} | Finished on `{!! $metadata['report_date'] !!}` |
| **New Work Items Today** | {!! $summary['new_today'] !!} | Created on `{!! $metadata['report_date'] !!}` |
| **Net Daily Delta** | {!! $summary['net_delta'] >= 0 ? '+' . $summary['net_delta'] : $summary['net_delta'] !!} | `New Work Items Today - Completed Today` |
| **Due Today** | {!! $summary['due_today'] !!} | `planned_end_date = {!! $metadata['report_date'] !!}` |
| **In Grace Period** | {!! $summary['in_grace'] !!} | Expired but within 2 working days grace |
| **Overdue (>2 Working Days)** | {!! $summary['overdue'] !!} | Exceeded deadline beyond grace period |
| **Blocked Work Items** | {!! $summary['blocked'] !!} | Status `blocked` with recorded constraint |
| **Daily Report Compliance** | {!! $summary['compliance_percent'] !!}% | Submitted obligations / Total eligible obligations |

---

## 2. Daily Report Compliance Matrix

- **Summary**: `{!! $compliance['submitted'] !!} / {!! $compliance['total'] !!} Obligations ({!! $compliance['percent'] !!}%)`
- **Missing Reporters / Pairs**: {!! $compliance['missing']->isNotEmpty() ? $compliance['missing']->implode(', ') : '*(Semua PIC/pasangan telah menyampaikan laporan)*' !!}

| Personnel / Pair | Department | Area | Status | Submission Time |
|---|---|---|---|---|
@forelse($compliance['details'] as $row)
| {!! $cell($row->name) !!} | {!! $cell($row->department_name) !!} | {!! $cell($row->area_name) !!} | {!! $cell($row->status) !!} | {!! $cell($row->submission_time) !!} |
@empty
| — | *Tidak ada data personel aktif pada tanggal ini* | — | — | — |
@endforelse

---

## 3. Personnel Activity & Daily Results

@forelse($personnelActivity as $areaAct)
### Area: {!! $areaAct->area_code !!} - {!! $areaAct->area_name !!} ({!! $areaAct->department_name !!})
#### PIC: {!! $areaAct->pic_name !!}
- **Daily Narrative (`today_result`)**:
  > {!! str_replace(["\r\n", "\r", "\n"], "\n  > ", trim($areaAct->narrative)) !!}
- **Work Items Associated with Daily Report**:
@forelse($areaAct->work_items as $wi)
  - `[#{!! $wi->id !!}]` `[{!! $wi->status !!}]` **{!! $wi->title !!}** — Planned: `{!! $wi->planned_start_date !!}` s.d. `{!! $wi->planned_end_date !!}` | Type: `{!! $wi->work_type !!}` | Evidence: {!! $wi->proof_of_work_url ? '[' . $wi->proof_of_work_url . '](' . $wi->proof_of_work_url . ')' : '—' !!}
@empty
  - *(Tidak ada item pekerjaan yang dilampirkan pada laporan harian ini)*
@endforelse

@empty
*(Tidak ada laporan harian yang disubmit pada tanggal ini)*
@endforelse

---

## 4. Work Item Status & Exception Registers

### 4.1 Overdue Register (> 2 Working Days Grace)
*(Pekerjaan aktif yang melewati batas tenggang 2 hari kerja sesuai WorkingDayService)*

| ID | Title | PIC | Area | Planned End | Lateness | Blocked Reason |
|---|---|---|---|---|---:|---|
@forelse($overdueItems as $item)
| #{!! $item->id !!} | {!! $cell($item->title) !!} | {!! $cell($item->owner?->name) !!} | {!! $cell($item->area?->name) !!} | {!! $cell($item->planned_end_date ? $item->planned_end_date->toDateString() : null) !!} | +{!! $item->days_overdue !!} hari | {!! $cell($item->blocked_reason_note ?: ($item->blocked_reason?->value ?? null)) !!} |
@empty
| — | *Tidak ada pekerjaan overdue pada tanggal ini* | — | — | — | — | — |
@endforelse

### 4.2 Grace Period Register (1–2 Days After Deadline)
*(Pekerjaan aktif dalam masa tenggang kerja)*

| ID | Title | PIC | Area | Planned End | Grace Status | Work Status |
|---|---|---|---|---|---|---|
@forelse($graceItems as $item)
| #{!! $item->id !!} | {!! $cell($item->title) !!} | {!! $cell($item->owner?->name) !!} | {!! $cell($item->area?->name) !!} | {!! $cell($item->planned_end_date ? $item->planned_end_date->toDateString() : null) !!} | {!! $cell($item->grace_label) !!} | {!! $cell($item->status instanceof \App\Enums\WorkItemStatus ? $item->status->value : $item->status) !!} |
@empty
| — | *Tidak ada pekerjaan dalam masa grace period pada tanggal ini* | — | — | — | — | — |
@endforelse

### 4.3 Completed on Date
*(Pekerjaan yang diselesaikan pada {!! $metadata['report_date'] !!})*

| ID | Title | PIC | Area | Completed Time | Proof of Work |
|---|---|---|---|---|---|
@forelse($completedTodayItems as $item)
| #{!! $item->id !!} | {!! $cell($item->title) !!} | {!! $cell($item->owner?->name) !!} | {!! $cell($item->area?->name) !!} | {!! $cell($item->completed_at ? \Carbon\Carbon::parse($item->completed_at)->setTimezone('Asia/Jakarta')->format('H:i:s') : null) !!} | {!! $item->proof_of_work_url ? '[Bukti Kerja](' . $item->proof_of_work_url . ')' : '—' !!} |
@empty
| — | *Tidak ada pekerjaan yang diselesaikan pada tanggal ini* | — | — | — | — |
@endforelse

### 4.4 Blocked Register
*(Pekerjaan aktif yang mengalami kendala/blocker)*

| ID | Title | PIC | Area | Blocked At | Blocked By Department | Reason & Note |
|---|---|---|---|---|---|---|
@forelse($blockedItems as $item)
| #{!! $item->id !!} | {!! $cell($item->title) !!} | {!! $cell($item->owner?->name) !!} | {!! $cell($item->area?->name) !!} | {!! $cell($item->blocked_at ? \Carbon\Carbon::parse($item->blocked_at)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i') : null) !!} | {!! $cell($item->blockedByDepartment?->name) !!} | {!! $cell($item->blocked_reason_note ?: ($item->blocked_reason?->value ?? null)) !!} |
@empty
| — | *Tidak ada pekerjaan yang berstatus blocked pada tanggal ini* | — | — | — | — | — |
@endforelse

---

## 5. Weekly Plans Progress

| Plan ID | Owner | Title | Category | Impact | Linked Items (Comp / Total) | Progress | Score |
|---|---|---|---|---|---:|---:|---|
@forelse($weeklyPlans as $plan)
| #{!! $plan->id !!} | {!! $cell($plan->owner_name) !!} | {!! $cell($plan->title) !!} | {!! $cell($plan->category) !!} | {!! $cell($plan->impact_level) !!} | {!! $plan->completed_linked !!} / {!! $plan->total_linked !!} | {!! $plan->progress_percent !!}% | {!! $cell($plan->score) !!} |
@empty
| — | *Tidak ada rencana mingguan untuk minggu ini* | — | — | — | — | — | — |
@endforelse

---

## 6. Schedule Changes & Extensions Recorded Today

| Work Item ID | Title | PIC | Old Planned End | New Planned End | Reason | Note |
|---|---|---|---|---|---|---|
@forelse($scheduleChanges as $change)
| #{!! $change->work_item_id !!} | {!! $cell($change->workItem?->title) !!} | {!! $cell($change->workItem?->owner?->name) !!} | {!! $cell($change->old_end_date ? $change->old_end_date->toDateString() : null) !!} | {!! $cell($change->new_end_date ? $change->new_end_date->toDateString() : null) !!} | {!! $cell($change->reason) !!} | {!! $cell($change->reason_note) !!} |
@empty
| — | *Tidak ada perubahan jadwal yang tercatat pada tanggal ini* | — | — | — | — | — |
@endforelse

---

## 7. Issues & Inter-Department Constraints

| Issue ID | Title | Area / Department | Status | First Reported | Source Report ID |
|---|---|---|---|---|---|
@forelse($issues as $issue)
| #{!! $issue->id !!} | {!! $cell($issue->title) !!} | {!! $cell(($issue->area?->name ?? '—') . ' / ' . ($issue->department?->name ?? '—')) !!} | {!! $cell($issue->status instanceof \App\Enums\IssueStatus ? $issue->status->value : $issue->status) !!} | {!! $cell($issue->first_reported_at ? \Carbon\Carbon::parse($issue->first_reported_at)->setTimezone('Asia/Jakarta')->format('Y-m-d') : null) !!} | {!! $cell($issue->source_daily_report_id ? '#' . $issue->source_daily_report_id : null) !!} |
@empty
| — | *Tidak ada isu atau kendala aktif yang tercatat* | — | — | — | — |
@endforelse

---

## 8. Department & Area Workload Summary

| Department | Area Code | Area Name | Active Items | In Progress | Blocked | Overdue | Completed Today |
|---|---|---|---:|---:|---:|---:|---:|
@forelse($workloadSummary as $ws)
| {!! $cell($ws->department_name) !!} | {!! $cell($ws->area_code) !!} | {!! $cell($ws->area_name) !!} | {!! $ws->active_count !!} | {!! $ws->in_progress_count !!} | {!! $ws->blocked_count !!} | {!! $ws->overdue_count !!} | {!! $ws->completed_today_count !!} |
@empty
| — | *Tidak ada data area* | — | — | — | — | — | — |
@endforelse

---

## 9. Data Integrity & Snapshot Notes

- **Working Day Evaluation**: Evaluated under standard workweek (Senin–Sabtu aktif, Minggu libur) via `WorkingDayService`.
@if($metadata['snapshot_mode'] === 'DAILY')
- **Snapshot Mode**: `DAILY`. Snapshot generated on {!! $metadata['generated_at'] !!} for business date {!! $metadata['report_date'] !!}. Operational snapshot recorded on the same business date.
@else
- **Snapshot Mode**: `RETROACTIVE_RECONSTRUCTION`. This snapshot is a retroactive reconstruction generated on {!! $metadata['generated_at'] !!} for business date {!! $metadata['report_date'] !!}. Historical event data is preserved where available. Mutable work-item fields such as current status or proof may reflect the current database state at reconstruction time and should not be interpreted as a complete historical event log.
@endif
