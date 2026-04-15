<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('plan_templates', function (Blueprint $table) {
            $table->id();

            // --- CÁC KHÓA NGOẠI QUAN TRỌNG ---
            // FK đến bảng plans (Kế hoạch học kỳ)
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');

            // FK đến bảng classes (Lớp học - Xác định ma trận này dành cho lớp nào)
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');

            // FK đến bảng subjects (Môn học)
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');

            // --- THÔNG TIN MA TRẬN ---
            $table->tinyInteger('day_of_week')->comment('2-8: Thứ 2 đến Chủ nhật');
            $table->string('session')->comment('Sáng / Chiều');
            $table->string('period_range')->comment('Ví dụ: 1-3, 4-6');

            // Có thể thêm ghi chú nội dung bài học dự kiến (nếu cần)
            $table->text('description')->nullable();

            $table->timestamps();

            // Thêm Index để truy vấn lịch học kỳ nhanh hơn
            $table->index(['plan_id', 'class_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('plan_templates');
    }
};
