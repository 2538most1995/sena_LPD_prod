<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courses')) {
            Schema::create('courses', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('created_by')->nullable()->index();
                $table->string('name')->unique();
                $table->string('category', 100);
                $table->unsignedInteger('hours')->default(0);
                $table->string('owner', 150)->default('สกร.ระดับอำเภอเสนา');
                $table->text('description')->nullable();
                $table->string('attachment_path')->nullable();
                $table->string('attachment_name')->nullable();
                $table->string('word_attachment_path')->nullable();
                $table->string('word_attachment_name')->nullable();
                $table->string('pdf_attachment_path')->nullable();
                $table->string('pdf_attachment_name')->nullable();
                $table->string('approval_status', 20)->default('approved')->index();
                $table->unsignedInteger('reviewed_by')->nullable();
                $table->text('review_note')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('prefix', 30);
                $table->string('first_name', 100);
                $table->string('last_name', 100);
                $table->string('gender', 20)->default('ไม่ระบุ');
                $table->string('id_card', 20)->unique();
                $table->date('birthday')->nullable();
                $table->string('education', 100)->nullable();
                $table->string('career', 100)->nullable();
                $table->string('target_group', 100)->nullable();
                $table->decimal('annual_income', 12, 2)->default(0);
                $table->text('address')->nullable();
                $table->string('phone', 30)->nullable();
                $table->date('registered_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lecturers')) {
            Schema::create('lecturers', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('prefix', 30);
                $table->string('first_name', 100);
                $table->string('last_name', 100);
                $table->string('id_card', 20)->unique();
                $table->date('birthday')->nullable();
                $table->string('education', 100)->nullable();
                $table->string('career', 100)->nullable();
                $table->text('address')->nullable();
                $table->string('phone', 30)->nullable();
                $table->date('registered_at')->nullable();
                $table->string('expertise')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('created_by')->nullable()->index();
                $table->unsignedInteger('course_id')->index();
                $table->unsignedInteger('lecturer_id')->index();
                $table->string('title');
                $table->text('objective')->nullable();
                $table->string('format_type', 120)->nullable();
                $table->string('attribute_type', 120)->nullable();
                $table->string('activity_type', 120)->nullable();
                $table->string('place')->nullable();
                $table->text('address')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->unsignedSmallInteger('fiscal_year');
                foreach (['lecturer_cost', 'material_cost', 'board_cost', 'food_cost', 'snack_cost', 'place_cost', 'transport_cost', 'other_cost'] as $field) {
                    $table->decimal($field, 12, 2)->default(0);
                }
                $table->string('status', 30)->default('ฉบับร่าง');
                $table->string('approval_status', 20)->default('approved')->index();
                $table->unsignedInteger('reviewed_by')->nullable();
                $table->text('review_note')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('project_students')) {
            Schema::create('project_students', function (Blueprint $table): void {
                $table->unsignedInteger('project_id');
                $table->unsignedInteger('student_id');
                $table->timestamp('joined_at')->useCurrent();
                $table->primary(['project_id', 'student_id']);
            });
        }

        if (! Schema::hasTable('scores')) {
            Schema::create('scores', function (Blueprint $table): void {
                $table->unsignedInteger('project_id');
                $table->unsignedInteger('student_id');
                $table->decimal('knowledge_score', 5, 2)->default(0);
                $table->decimal('skill_score', 5, 2)->default(0);
                $table->decimal('attribute_score', 5, 2)->default(0);
                $table->timestamp('updated_at')->useCurrent();
                $table->primary(['project_id', 'student_id']);
            });
        }

        if (! Schema::hasTable('activity_photos')) {
            Schema::create('activity_photos', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('project_id')->index();
                $table->string('photo_type', 20);
                $table->string('file_path');
                $table->string('caption')->nullable();
                $table->unsignedTinyInteger('sort_order')->default(1);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('user_id')->index();
                $table->string('title', 200);
                $table->text('message')->nullable();
                $table->string('link')->nullable();
                $table->boolean('is_read')->default(false)->index();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        // These tables can belong to the legacy application. Never remove them
        // automatically during a rollback of the Laravel baseline.
    }
};
