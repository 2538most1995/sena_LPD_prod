<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $globalUniqueIndex = collect(Schema::getIndexes('lecturers'))
            ->first(fn (array $index): bool => $index['unique']
                && $index['columns'] === ['id_card']);

        if ($globalUniqueIndex) {
            Schema::table('lecturers', function (Blueprint $table) use ($globalUniqueIndex): void {
                $table->dropUnique($globalUniqueIndex['name']);
            });
        }

        Schema::table('lecturers', function (Blueprint $table): void {
            $table->unique(['created_by', 'id_card'], 'lecturers_owner_id_card_unique');
        });
    }

    public function down(): void
    {
        Schema::table('lecturers', function (Blueprint $table): void {
            $table->dropUnique('lecturers_owner_id_card_unique');
            $table->unique('id_card', 'lecturers_id_card_unique');
        });
    }
};
