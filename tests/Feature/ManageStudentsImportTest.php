<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Training\Models\Student;
use Modules\Training\Models\TrainingClass;
use Tests\TestCase;

class ManageStudentsImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_comma_csv_with_utf8_bom_dates_and_default_status(): void
    {
        $admin = $this->createUser(User::ROLE_ADMIN);
        $class = $this->createTrainingClass(totalStudents: 45);
        $csv = "\xEF\xBB\xBFstudent_code,name,date_of_birth,status\r\n"
            . "HV001,  Nguyen   Van A  ,15/08/2005,\r\n"
            . "\r\n"
            . "HV002,Tran Thi B,2005-11-20,graduated\r\n";

        $this->actingAs($admin)->postJson('/management/students/import', [
            'class_id' => $class->id,
            'import_file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ])->assertOk()
            ->assertJsonPath('imported_count', 2)
            ->assertJsonPath('class.id', $class->id);

        $this->assertDatabaseHas('students', [
            'class_id' => $class->id,
            'student_code' => 'HV001',
            'name' => 'Nguyen Van A',
            'date_of_birth' => '2005-08-15',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('students', [
            'class_id' => $class->id,
            'student_code' => 'HV002',
            'date_of_birth' => '2005-11-20',
            'status' => 'graduated',
        ]);
        $this->assertSame(45, $class->fresh()->total_students);
    }

    public function test_admin_can_import_semicolon_csv(): void
    {
        $admin = $this->createUser(User::ROLE_ADMIN);
        $class = $this->createTrainingClass();
        $csv = "student_code;name;date_of_birth;status\nHV010;Le Van C;;suspended\n";

        $this->actingAs($admin)->postJson('/management/students/import', [
            'class_id' => $class->id,
            'import_file' => UploadedFile::fake()->createWithContent('students.txt', $csv),
        ])->assertOk()
            ->assertJsonPath('imported_count', 1);

        $this->assertDatabaseHas('students', [
            'student_code' => 'HV010',
            'name' => 'Le Van C',
            'date_of_birth' => null,
            'status' => 'suspended',
        ]);
    }

    public function test_missing_required_header_is_rejected(): void
    {
        $admin = $this->createUser(User::ROLE_ADMIN);
        $class = $this->createTrainingClass();

        $this->actingAs($admin)->postJson('/management/students/import', [
            'class_id' => $class->id,
            'import_file' => UploadedFile::fake()->createWithContent(
                'students.csv',
                "student_code,date_of_birth\nHV001,2005-01-01\n"
            ),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('import_file')
            ->assertJsonFragment(['File CSV thiếu cột bắt buộc \'name\'.']);

        $this->assertDatabaseCount('students', 0);
    }

    public function test_class_is_required_for_import(): void
    {
        $admin = $this->createUser(User::ROLE_ADMIN);

        $this->actingAs($admin)->postJson('/management/students/import', [
            'import_file' => UploadedFile::fake()->createWithContent(
                'students.csv',
                "student_code,name\nHV001,Student One\n"
            ),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('class_id');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_duplicate_and_invalid_rows_cancel_the_entire_import(): void
    {
        $admin = $this->createUser(User::ROLE_ADMIN);
        $class = $this->createTrainingClass();
        Student::query()->create([
            'class_id' => $class->id,
            'student_code' => 'HV001',
            'name' => 'Existing Student',
            'status' => 'active',
        ]);
        $csv = "student_code,name,date_of_birth,status\n"
            . "hv001,Duplicate Database,2005-01-01,active\n"
            . "HV002,Valid Student,2005-02-02,active\n"
            . "HV002,Duplicate File,2005-03-03,active\n"
            . "HV003,Invalid Student,31/02/2005,unknown\n";

        $response = $this->actingAs($admin)->postJson('/management/students/import', [
            'class_id' => $class->id,
            'import_file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('import_file');

        $messages = implode(' ', $response->json('errors.import_file'));
        $this->assertStringContainsString('Dòng 2', $messages);
        $this->assertStringContainsString('Dòng 4', $messages);
        $this->assertStringContainsString('Dòng 5', $messages);
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseMissing('students', ['student_code' => 'HV002']);
    }

    public function test_file_type_and_size_are_validated(): void
    {
        $admin = $this->createUser(User::ROLE_ADMIN);
        $class = $this->createTrainingClass();

        $this->actingAs($admin)->postJson('/management/students/import', [
            'class_id' => $class->id,
            'import_file' => UploadedFile::fake()->create('students.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('import_file');

        $this->actingAs($admin)->postJson('/management/students/import', [
            'class_id' => $class->id,
            'import_file' => UploadedFile::fake()->create('students.csv', 5121, 'text/csv'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('import_file');
    }

    public function test_more_than_five_thousand_rows_is_rejected_without_writes(): void
    {
        $admin = $this->createUser(User::ROLE_ADMIN);
        $class = $this->createTrainingClass();
        $lines = ['student_code,name,date_of_birth,status'];

        for ($index = 1; $index <= 5001; $index++) {
            $lines[] = "HV{$index},Student {$index},,active";
        }

        $this->actingAs($admin)->postJson('/management/students/import', [
            'class_id' => $class->id,
            'import_file' => UploadedFile::fake()->createWithContent('students.csv', implode("\n", $lines)),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('import_file')
            ->assertJsonFragment(['File CSV chỉ được chứa tối đa 5.000 dòng dữ liệu.']);

        $this->assertDatabaseCount('students', 0);
    }

    public function test_non_management_user_cannot_import_or_download_template(): void
    {
        $studentUser = $this->createUser(User::ROLE_STUDENT);
        $class = $this->createTrainingClass();

        $this->actingAs($studentUser)->postJson('/management/students/import', [
            'class_id' => $class->id,
            'import_file' => UploadedFile::fake()->createWithContent(
                'students.csv',
                "student_code,name\nHV001,Student One\n"
            ),
        ])->assertForbidden();

        $this->actingAs($studentUser)
            ->get('/management/students/import-template')
            ->assertForbidden();
    }

    public function test_admin_can_download_utf8_csv_template(): void
    {
        $admin = $this->createUser(User::ROLE_ADMIN);

        $response = $this->actingAs($admin)
            ->get('/management/students/import-template')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload('student-import-template.csv');

        $this->assertStringStartsWith("\xEF\xBB\xBFstudent_code,name,date_of_birth,status", (string) $response->getContent());
    }

    public function test_management_page_renders_student_import_controls(): void
    {
        $admin = $this->createUser(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->get('/management')
            ->assertOk()
            ->assertSee('id="importBtn"', false)
            ->assertSee('id="importForm"', false)
            ->assertSee(route('management.students.import-template'), false);
    }

    private function createUser(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => User::STATUS_APPROVED,
        ]);
    }

    private function createTrainingClass(int $totalStudents = 30): TrainingClass
    {
        return TrainingClass::query()->create([
            'code' => 'CLS-' . fake()->unique()->numerify('####'),
            'name' => 'Lop ' . fake()->unique()->numerify('####'),
            'course_year' => 2026,
            'total_students' => $totalStudents,
            'status' => 'active',
        ]);
    }
}
