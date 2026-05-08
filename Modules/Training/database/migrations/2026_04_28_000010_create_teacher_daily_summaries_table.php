<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bảng lưu phần nhận xét tổng hợp hoạt động huấn luyện trong ngày (Phần 2 của nhật ký)
     * do giảng viên điền sau khi dạy xong trong ngày.
     */
    public function up(): void
    {
        Schema::create('teacher_daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->date('log_date');

            // a) Thực hiện kế hoạch huấn luyện
            $table->text('training_plan_comment')->nullable();

            // b) Thực hiện quy chế, quy định về GDĐT
            $table->text('regulation_comment')->nullable();

            // c) Quản lý, sử dụng hội trường, vật chất, trang thiết bị đào tạo
            $table->text('facility_comment')->nullable();

            // d) Những việc cần tiếp tục xử lý
            $table->text('followup_comment')->nullable();

            $table->timestamps();

            $table->unique(['teacher_id', 'log_date'], 'teacher_daily_summaries_teacher_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_daily_summaries');
    }
};
