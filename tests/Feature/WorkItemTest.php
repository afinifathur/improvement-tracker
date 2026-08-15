<?php

namespace Tests\Feature;

use App\Enums\BlockedReason;
use App\Enums\CancelReason;
use App\Enums\WorkItemStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemScheduleChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkItemTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkItem(array $overrides = []): WorkItem
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = $overrides['owner_id'] ?? User::factory()->create(['role' => 'spv'])->id;

        $data = array_merge([
            'title' => 'Sample work item',
            'owner_id' => $owner,
            'original_start_date' => '2026-08-14',
            'original_end_date' => '2026-08-20',
            'planned_start_date' => '2026-08-14',
            'planned_end_date' => '2026-08-20',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ], $overrides);

        return WorkItem::create($data);
    }

    public function test_work_item_belongs_to_owner(): void
    {
        $owner = User::factory()->create(['role' => 'spv']);
        $item = $this->makeWorkItem(['owner_id' => $owner->id]);

        $this->assertTrue($item->owner->is($owner));
    }

    public function test_work_item_belongs_to_department(): void
    {
        $department = Department::create(['name' => 'Maintenance', 'code' => 'MNT']);
        $item = $this->makeWorkItem(['department_id' => $department->id]);

        $this->assertTrue($item->department->is($department));
    }

    public function test_work_item_blocked_by_department(): void
    {
        $purchasing = Department::create(['name' => 'Purchasing', 'code' => 'PUR']);
        $item = $this->makeWorkItem(['blocked_by_department_id' => $purchasing->id]);

        $this->assertTrue($item->blockedByDepartment->is($purchasing));
    }

    public function test_work_item_carry_over_lineage(): void
    {
        $original = $this->makeWorkItem();
        $carried = $this->makeWorkItem(['carried_from_id' => $original->id]);

        $this->assertTrue($carried->carriedFrom->is($original));
        $this->assertTrue($original->carriedOverItems->contains($carried));
    }

    public function test_work_item_has_schedule_changes(): void
    {
        $item = $this->makeWorkItem();
        $admin = User::factory()->create(['role' => 'admin']);

        WorkItemScheduleChange::create([
            'work_item_id' => $item->id,
            'old_start_date' => '2026-08-14',
            'old_end_date' => '2026-08-20',
            'new_start_date' => '2026-08-14',
            'new_end_date' => '2026-08-25',
            'reason' => 'waiting_sparepart',
            'changed_by' => $admin->id,
            'changed_at' => now(),
        ]);

        $this->assertCount(1, $item->scheduleChanges);
        $this->assertEquals('2026-08-25', $item->scheduleChanges->first()->new_end_date->format('Y-m-d'));
    }

    public function test_work_item_status_values(): void
    {
        $values = array_map(fn ($status) => $status->value, WorkItemStatus::cases());

        $this->assertEqualsCanonicalizing(
            ['not_started', 'in_progress', 'blocked', 'completed', 'cancelled'],
            $values
        );
    }

    public function test_work_item_defaults_to_not_started(): void
    {
        $item = $this->makeWorkItem();

        $this->assertSame(WorkItemStatus::NotStarted, $item->status);
    }

    public function test_completed_at_is_nullable_until_completion(): void
    {
        $item = $this->makeWorkItem();

        $this->assertNull($item->completed_at);

        $item->status = WorkItemStatus::Completed;
        $item->completed_at = now();
        $item->save();

        $this->assertNotNull($item->fresh()->completed_at);
    }

    public function test_blocked_context_can_be_recorded(): void
    {
        $purchasing = Department::create(['name' => 'Purchasing', 'code' => 'PUR']);
        $item = $this->makeWorkItem();

        $item->status = WorkItemStatus::Blocked;
        $item->blocked_reason = BlockedReason::WaitingSparepart;
        $item->blocked_reason_note = 'Awaiting bearing from vendor';
        $item->blocked_at = now();
        $item->blocked_by_department_id = $purchasing->id;
        $item->save();

        $fresh = $item->fresh();

        $this->assertSame(WorkItemStatus::Blocked, $fresh->status);
        $this->assertSame(BlockedReason::WaitingSparepart, $fresh->blocked_reason);
        $this->assertTrue($fresh->blockedByDepartment->is($purchasing));
    }

    public function test_cancel_context_can_be_recorded(): void
    {
        $item = $this->makeWorkItem();

        $item->status = WorkItemStatus::Cancelled;
        $item->cancel_reason = CancelReason::CarriedOver;
        $item->cancel_reason_note = 'Moved to next planning period';
        $item->save();

        $fresh = $item->fresh();

        $this->assertSame(WorkItemStatus::Cancelled, $fresh->status);
        $this->assertSame(CancelReason::CarriedOver, $fresh->cancel_reason);
    }

    public function test_original_dates_are_immutable_after_creation(): void
    {
        $item = $this->makeWorkItem();

        $this->expectException(\LogicException::class);

        $item->original_end_date = '2026-08-30';
        $item->save();
    }

    public function test_start_date_must_be_on_or_before_end_date(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makeWorkItem([
            'original_start_date' => '2026-08-20',
            'original_end_date' => '2026-08-14',
        ]);
    }

    public function test_department_is_a_snapshot_not_dynamic(): void
    {
        $maintenance = Department::create(['name' => 'Maintenance', 'code' => 'MNT']);
        $production = Department::create(['name' => 'Production', 'code' => 'PRD']);
        $owner = User::factory()->create(['role' => 'spv', 'department_id' => $maintenance->id]);

        $item = $this->makeWorkItem([
            'owner_id' => $owner->id,
            'department_id' => $maintenance->id,
        ]);

        $owner->department_id = $production->id;
        $owner->save();

        $this->assertTrue($item->fresh()->department->is($maintenance));
    }
}
