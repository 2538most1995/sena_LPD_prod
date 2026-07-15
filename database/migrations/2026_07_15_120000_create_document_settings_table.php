<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_settings')) {
            return;
        }

        Schema::create('document_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id')->unique();
            $table->string('learning_center_name')->nullable();
            $table->string('district_office_name')->nullable();
            $table->string('district_office_short_name')->nullable();
            $table->string('province_office_name')->nullable();
            $table->string('document_no_prefix', 80)->nullable();
            $table->string('office_address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('fax', 30)->nullable();
            $table->string('owner_unit')->nullable();
            $table->string('registrar_name', 200)->nullable();
            $table->string('registrar_position', 150)->nullable();
            $table->string('responsible_name', 200)->nullable();
            $table->string('responsible_position', 150)->nullable();
            $table->string('director_name', 200)->nullable();
            $table->string('director_position', 180)->nullable();
            $table->string('finance_officer_name', 200)->nullable();
            $table->string('finance_officer_position', 150)->nullable();
            $table->string('payer_name', 200)->nullable();
            $table->string('payer_position', 150)->nullable();
            $table->string('certifier_name', 200)->nullable();
            $table->string('certifier_position', 150)->nullable();
            $table->string('supervisor_name', 200)->nullable();
            $table->string('supervisor_position', 150)->nullable();
            $table->string('follow_up_name', 200)->nullable();
            $table->string('follow_up_position', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_settings');
    }
};
