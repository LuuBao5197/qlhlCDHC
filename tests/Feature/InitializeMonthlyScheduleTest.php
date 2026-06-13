<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Application\InitializeMonthlySchedule\InitializeMonthlyScheduleHandler;
use Modules\Schedule\Application\InitializeMonthlySchedule\InitializeMonthlyScheduleRequest;
use Tests\TestCase;

class InitializeMonthlyScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('schedule.initialize_monthly_schedule.strict_next_month_only', true);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_current_month_can_be_initialized_in_strict_rolling_mode(): void
    {
        Carbon::setTestNow('2026-06-12 08:00:00');

        $request = $this->makeInitializeRequest(6, 2026);
        $request->validateResolved();

        $this->assertSame(6, (int) $request->validated('month'));
        $this->assertSame(2026, (int) $request->validated('year'));
    }

    public function test_month_outside_current_and_next_month_is_rejected_in_strict_rolling_mode(): void
    {
        Carbon::setTestNow('2026-06-12 08:00:00');

        $this->expectException(ValidationException::class);

        $this->makeInitializeRequest(8, 2026)->validateResolved();
    }

    public function test_initialization_is_idempotent_and_bulk_upsert_refreshes_template_fields(): void
    {
        Carbon::setTestNow('2026-06-12 08:00:00');

        $user = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
        ]);
        $timestamp = now();

        $classId = DB::table('classes')->insertGetId([
            'code' => 'C01',
            'name' => 'Lop C01',
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $subjectId = DB::table('subjects')->insertGetId([
            'code' => 'SUB01',
            'name' => 'Mon 1',
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $replacementSubjectId = DB::table('subjects')->insertGetId([
            'code' => 'SUB02',
            'name' => 'Mon 2',
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $planId = DB::table('plans')->insertGetId([
            'name' => 'Ke hoach test',
            'semester' => 1,
            'year' => 2026,
            'status' => 'draft',
            'current_step' => 'draft',
            'effective_from' => '2026-06-01',
            'effective_to' => '2026-06-30',
            'approved_version' => 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $templateId = DB::table('plan_templates')->insertGetId([
            'plan_id' => $planId,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'day_of_week' => 2,
            'days_of_week' => json_encode([2]),
            'session' => 'Sang',
            'period_range' => '1-2',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $handler = $this->app->make(InitializeMonthlyScheduleHandler::class);
        $firstRequest = $this->makeInitializeRequest(6, 2026, $user);
        $firstRequest->validateResolved();
        $firstResult = $handler->handle($firstRequest);

        $this->assertSame(1, $firstResult['processed_plans']);
        $this->assertSame(1, $firstResult['created_schedules']);
        $this->assertSame(10, $firstResult['created_slots']);
        $this->assertDatabaseCount('monthly_schedules', 1);
        $this->assertDatabaseCount('schedule_slots', 10);

        DB::table('plan_templates')->where('id', $templateId)->update([
            'subject_id' => $replacementSubjectId,
            'updated_at' => now(),
        ]);

        $secondRequest = $this->makeInitializeRequest(6, 2026, $user);
        $secondRequest->validateResolved();
        $secondResult = $handler->handle($secondRequest);

        $this->assertSame(1, $secondResult['processed_plans']);
        $this->assertSame(0, $secondResult['created_schedules']);
        $this->assertSame(0, $secondResult['created_slots']);
        $this->assertDatabaseCount('monthly_schedules', 1);
        $this->assertDatabaseCount('schedule_slots', 10);
        $this->assertSame(
            10,
            DB::table('schedule_slots')->where('subject_id', $replacementSubjectId)->count()
        );
    }

    public function test_all_eligible_plans_generate_schedule_slots(): void
    {
        Carbon::setTestNow('2026-06-12 08:00:00');

        $user = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
        ]);
        [$classId, $subjectId] = $this->seedClassAndSubject('ALL');
        $firstPlanId = $this->seedPlan('Plan 1');
        $secondPlanId = $this->seedPlan('Plan 2');
        $this->seedTemplate($firstPlanId, $classId, $subjectId);
        $this->seedTemplate($secondPlanId, $classId, $subjectId);

        $request = $this->makeInitializeRequest(6, 2026, $user);
        $request->validateResolved();
        $result = $this->app->make(InitializeMonthlyScheduleHandler::class)->handle($request);

        $this->assertSame(2, $result['processed_plans']);
        $this->assertSame(2, $result['created_schedules']);
        $this->assertSame(10, $result['created_slots']);
        $this->assertDatabaseCount('monthly_schedules', 2);
        $this->assertDatabaseCount('schedule_slots', 10);

        foreach ([$firstPlanId, $secondPlanId] as $planId) {
            $monthlyScheduleId = DB::table('monthly_schedules')
                ->where('plan_id', $planId)
                ->value('id');

            $this->assertNotNull($monthlyScheduleId);
            $this->assertSame(
                5,
                DB::table('schedule_slots')->where('monthly_schedule_id', $monthlyScheduleId)->count()
            );
        }
    }

    public function test_one_invalid_plan_rolls_back_the_entire_month_initialization(): void
    {
        Carbon::setTestNow('2026-06-12 08:00:00');

        $user = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
        ]);
        [$classId, $subjectId] = $this->seedClassAndSubject('ROLLBACK');
        $validPlanId = $this->seedPlan('Plan hop le');
        $invalidPlanId = $this->seedPlan('Plan thieu template');
        $this->seedTemplate($validPlanId, $classId, $subjectId);

        $request = $this->makeInitializeRequest(6, 2026, $user);
        $request->validateResolved();

        try {
            $this->app->make(InitializeMonthlyScheduleHandler::class)->handle($request);
            $this->fail('Expected validation failure for a plan without an applicable template.');
        } catch (ValidationException $exception) {
            $messages = implode(' ', $exception->errors()['month'] ?? []);
            $this->assertStringContainsString("Plan #{$invalidPlanId}", $messages);
        }

        $this->assertDatabaseCount('monthly_schedules', 0);
        $this->assertDatabaseCount('schedule_slots', 0);
    }

    private function seedClassAndSubject(string $suffix): array
    {
        $timestamp = now();
        $classId = DB::table('classes')->insertGetId([
            'code' => "C-{$suffix}",
            'name' => "Lop {$suffix}",
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $subjectId = DB::table('subjects')->insertGetId([
            'code' => "SUB-{$suffix}",
            'name' => "Mon {$suffix}",
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        return [$classId, $subjectId];
    }

    private function seedPlan(string $name): int
    {
        return DB::table('plans')->insertGetId([
            'name' => $name,
            'semester' => 1,
            'year' => 2026,
            'status' => 'draft',
            'current_step' => 'draft',
            'effective_from' => '2026-06-01',
            'effective_to' => '2026-06-30',
            'approved_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedTemplate(int $planId, int $classId, int $subjectId): void
    {
        DB::table('plan_templates')->insert([
            'plan_id' => $planId,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'day_of_week' => 2,
            'days_of_week' => json_encode([2]),
            'session' => 'Sang',
            'period_range' => '1-1',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeInitializeRequest(
        int $month,
        int $year,
        ?User $user = null
    ): InitializeMonthlyScheduleRequest {
        $request = InitializeMonthlyScheduleRequest::create(
            '/monthly-schedules/initialize',
            'POST',
            compact('month', 'year')
        );
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));
        $request->setUserResolver(fn (): User => $user ?? new User([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
        ]));

        return $request;
    }
}
