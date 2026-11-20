<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commitments', function (Blueprint $table) {
            $table->foreignId('source_commitment_id')
                ->nullable()
                ->constrained('commitments')
                ->nullOnDelete();
            $table->unique(['family_id', 'source_commitment_id']);
        });
    }

    public function down(): void
    {
        Schema::table('commitments', function (Blueprint $table) {
            $table->dropUnique(['family_id', 'source_commitment_id']);
            $table->dropConstrainedForeignId('source_commitment_id');
        });
    }
};
