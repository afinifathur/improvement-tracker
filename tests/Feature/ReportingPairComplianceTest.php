<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\DailyReport;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingPairComplianceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    private function personnel(string $name, string $email): User
    {
        $dept = Department::create(['code' => 'D-'.uniqid(), 'name' => 'Dept '.$name]);
        $area = Area::create(['code' => 'A-'.uniqid(), 'name' => 'Area '.$name, 'department_id' => $dept->id]);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $dept->id,
        ]);

        AreaAssignment::create([
            'area_id' => $area->id,
            'user_id' => $user->id,
            'role' => 'spv',
            'started_at' => '2026-01-01',
        ]);

        return $user;
    }

    private function makeDailyReport(User $reporter, string $date): void
    {
        DailyReport::create([
            'report_date' => $date,
            'reported_by' => $reporter->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
    }

    public function test_bagus_submits_sahrul_is_not_missing(): void
    {
        $bagus = $this->personnel('BAGUS', 'bagus@peroniks.com');
        $sahrul = $this->personnel('SAHRUL', 'sahrul@peroniks.com');

        $today = now()->toDateString();
        $this->makeDailyReport($bagus, $today);

        $response = $this->actingAs($this->admin)->get('/dashboard');
        $missing = $response->viewData('missingToday');

        $this->assertNotContains('BAGUS', $missing->all());
        $this->assertNotContains('SAHRUL', $missing->all());
        $this->assertNotContains('BAGUS / SAHRUL', $missing->all());

        // Denominator (total obligations) should be 1. Numerator (submitted) should be 1.
        $days = collect($response->viewData('days'))->keyBy('dateStr');
        $this->assertSame(1, $days[$today]['total']);
        $this->assertSame(1, $days[$today]['submitted']);
        $this->assertSame(100, $days[$today]['percent']);

        // Assert presentation layer: exactly one row for BAGUS / SAHRUL, and no separate rows.
        $personnelList = collect($response->viewData('personnel'));
        $this->assertTrue($personnelList->contains(fn ($p) => $p->name === 'BAGUS / SAHRUL'));
        $this->assertFalse($personnelList->contains(fn ($p) => $p->name === 'BAGUS'));
        $this->assertFalse($personnelList->contains(fn ($p) => $p->name === 'SAHRUL'));

        $response->assertSee('BAGUS / SAHRUL');
    }

    public function test_sahrul_submits_bagus_is_not_missing(): void
    {
        $bagus = $this->personnel('BAGUS', 'bagus@peroniks.com');
        $sahrul = $this->personnel('SAHRUL', 'sahrul@peroniks.com');

        $today = now()->toDateString();
        $this->makeDailyReport($sahrul, $today);

        $response = $this->actingAs($this->admin)->get('/dashboard');
        $missing = $response->viewData('missingToday');

        $this->assertNotContains('BAGUS', $missing->all());
        $this->assertNotContains('SAHRUL', $missing->all());
        $this->assertNotContains('BAGUS / SAHRUL', $missing->all());

        // Denominator (total obligations) should be 1. Numerator (submitted) should be 1.
        $days = collect($response->viewData('days'))->keyBy('dateStr');
        $this->assertSame(1, $days[$today]['total']);
        $this->assertSame(1, $days[$today]['submitted']);
        $this->assertSame(100, $days[$today]['percent']);

        // Assert presentation layer: exactly one row for BAGUS / SAHRUL, and no separate rows.
        $personnelList = collect($response->viewData('personnel'));
        $this->assertTrue($personnelList->contains(fn ($p) => $p->name === 'BAGUS / SAHRUL'));
        $this->assertFalse($personnelList->contains(fn ($p) => $p->name === 'BAGUS'));
        $this->assertFalse($personnelList->contains(fn ($p) => $p->name === 'SAHRUL'));

        $response->assertSee('BAGUS / SAHRUL');
    }

    public function test_neither_submits_pair_is_still_missing(): void
    {
        $bagus = $this->personnel('BAGUS', 'bagus@peroniks.com');
        $sahrul = $this->personnel('SAHRUL', 'sahrul@peroniks.com');

        $today = now()->toDateString();

        $response = $this->actingAs($this->admin)->get('/dashboard');
        $missing = $response->viewData('missingToday');

        // Should contain the single combined string "BAGUS / SAHRUL"
        $this->assertContains('BAGUS / SAHRUL', $missing->all());
        $this->assertNotContains('BAGUS', $missing->all());
        $this->assertNotContains('SAHRUL', $missing->all());

        // Denominator (total obligations) should be 1. Numerator (submitted) should be 0.
        $days = collect($response->viewData('days'))->keyBy('dateStr');
        $this->assertSame(1, $days[$today]['total']);
        $this->assertSame(0, $days[$today]['submitted']);
        $this->assertSame(0, $days[$today]['percent']);

        // Assert presentation layer: exactly one row for BAGUS / SAHRUL, and no separate rows.
        $personnelList = collect($response->viewData('personnel'));
        $this->assertTrue($personnelList->contains(fn ($p) => $p->name === 'BAGUS / SAHRUL'));
        $this->assertFalse($personnelList->contains(fn ($p) => $p->name === 'BAGUS'));
        $this->assertFalse($personnelList->contains(fn ($p) => $p->name === 'SAHRUL'));

        $response->assertSee('BAGUS / SAHRUL');
    }

    public function test_normal_individual_who_does_not_submit_remains_missing(): void
    {
        $afin = $this->personnel('AFIN', 'afin@peroniks.com');
        $today = now()->toDateString();

        $response = $this->actingAs($this->admin)->get('/dashboard');
        $missing = $response->viewData('missingToday');

        $this->assertContains('AFIN', $missing->all());

        $days = collect($response->viewData('days'))->keyBy('dateStr');
        $this->assertSame(1, $days[$today]['total']);
        $this->assertSame(0, $days[$today]['submitted']);
    }

    public function test_normal_individual_who_submits_is_compliant(): void
    {
        $afin = $this->personnel('AFIN', 'afin@peroniks.com');
        $today = now()->toDateString();
        $this->makeDailyReport($afin, $today);

        $response = $this->actingAs($this->admin)->get('/dashboard');
        $missing = $response->viewData('missingToday');

        $this->assertNotContains('AFIN', $missing->all());

        $days = collect($response->viewData('days'))->keyBy('dateStr');
        $this->assertSame(1, $days[$today]['total']);
        $this->assertSame(1, $days[$today]['submitted']);
        $this->assertSame(100, $days[$today]['percent']);
    }

    public function test_existing_compliance_calculations_remain_unchanged_for_other_personnel(): void
    {
        // Setup a mixed environment: 1 pair, 1 individual
        $bagus = $this->personnel('BAGUS', 'bagus@peroniks.com');
        $sahrul = $this->personnel('SAHRUL', 'sahrul@peroniks.com');
        $afin = $this->personnel('AFIN', 'afin@peroniks.com');

        $today = now()->toDateString();

        // Scenario A: Nobody submits
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $days = collect($response->viewData('days'))->keyBy('dateStr');
        // Pair (1 obligation) + AFIN (1 obligation) = 2 obligations
        $this->assertSame(2, $days[$today]['total']);
        $this->assertSame(0, $days[$today]['submitted']);
        $this->assertSame(0, $days[$today]['percent']);
        $missing = $response->viewData('missingToday')->all();
        $this->assertContains('BAGUS / SAHRUL', $missing);
        $this->assertContains('AFIN', $missing);
        $this->assertCount(2, $missing);

        // Assert presentation layer: exactly one row for BAGUS / SAHRUL, and no separate rows.
        $personnelList = collect($response->viewData('personnel'));
        $this->assertTrue($personnelList->contains(fn ($p) => $p->name === 'BAGUS / SAHRUL'));
        $this->assertFalse($personnelList->contains(fn ($p) => $p->name === 'BAGUS'));
        $this->assertFalse($personnelList->contains(fn ($p) => $p->name === 'SAHRUL'));

        // Scenario B: Only BAGUS submits
        $this->makeDailyReport($bagus, $today);
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $days = collect($response->viewData('days'))->keyBy('dateStr');
        // Total obligations = 2, submitted obligations = 1 (pair submitted, AFIN did not)
        $this->assertSame(2, $days[$today]['total']);
        $this->assertSame(1, $days[$today]['submitted']);
        $this->assertSame(50, $days[$today]['percent']);
        $missing = $response->viewData('missingToday')->all();
        $this->assertNotContains('BAGUS / SAHRUL', $missing);
        $this->assertContains('AFIN', $missing);
        $this->assertCount(1, $missing);

        // Scenario C: Both BAGUS and AFIN submit
        $this->makeDailyReport($afin, $today);
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $days = collect($response->viewData('days'))->keyBy('dateStr');
        // Total = 2, submitted = 2
        $this->assertSame(2, $days[$today]['total']);
        $this->assertSame(2, $days[$today]['submitted']);
        $this->assertSame(100, $days[$today]['percent']);
        $missing = $response->viewData('missingToday')->all();
        $this->assertEmpty($missing);
    }
}
