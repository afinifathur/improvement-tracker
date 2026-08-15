<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyReportAreaIdentityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function reporter(): User
    {
        return User::factory()->create(['role' => 'spv']);
    }

    private function area(string $code): Area
    {
        return Area::create(['code' => $code, 'name' => $code]);
    }

    public function test_validation_requires_area_id_for_new_records(): void
    {
        $spv = $this->reporter();

        $this->actingAs($this->admin())->post(route('daily-reports.store'), [
            'report_date' => '2026-08-14',
            'reported_by' => $spv->id,
        ])->assertSessionHasErrors('area_id');

        $this->assertDatabaseCount('daily_reports', 0);
    }

    public function test_two_reports_same_person_date_different_areas_allowed(): void
    {
        $admin = $this->admin();
        $spv = $this->reporter();
        $flange = $this->area('NT-FL');
        $fitting = $this->area('NT-PF');

        $this->actingAs($admin)->post(route('daily-reports.store'), [
            'report_date' => '2026-08-14',
            'reported_by' => $spv->id,
            'area_id' => $flange->id,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('daily-reports.store'), [
            'report_date' => '2026-08-14',
            'reported_by' => $spv->id,
            'area_id' => $fitting->id,
        ])->assertRedirect();

        $this->assertSame(2, DailyReport::count());
        $this->assertSame(2, DailyReport::where('reported_by', $spv->id)
            ->whereDate('report_date', '2026-08-14')
            ->count());
    }

    public function test_duplicate_same_person_area_date_is_rejected(): void
    {
        $admin = $this->admin();
        $spv = $this->reporter();
        $flange = $this->area('NT-FL');

        $this->actingAs($admin)->post(route('daily-reports.store'), [
            'report_date' => '2026-08-14',
            'reported_by' => $spv->id,
            'area_id' => $flange->id,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('daily-reports.store'), [
            'report_date' => '2026-08-14',
            'reported_by' => $spv->id,
            'area_id' => $flange->id,
        ])->assertSessionHasErrors('area_id');

        $this->assertSame(1, DailyReport::count());
    }
}
